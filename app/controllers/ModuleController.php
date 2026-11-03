<?php

abstract class ModuleController extends Controller
{
    protected $authenticatedUser = null;

    protected function requireLogin()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->loadModel('User');
        $currentUser = !empty($_SESSION['user_id']) && is_scalar($_SESSION['user_id'])
            ? $this->user->getById($_SESSION['user_id']) : false;
        if (!$currentUser || !in_array($currentUser['role'], ['admin', 'user'], true)) {
            $_SESSION = [];
            session_destroy();
            header('Location: ' . PUBLIC_ROOT . 'auth');
            exit;
        }
        $_SESSION['user_role'] = $currentUser['role'];
        $_SESSION['user_name'] = $currentUser['name'];
        $_SESSION['user_email'] = $currentUser['email'];
        $this->authenticatedUser = $currentUser;
    }

    protected function requireAdmin()
    {
        if (empty($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
            header('Location: ' . ReportingYear::url('dashboard?error=unauthorized'));
            exit;
        }
    }

    protected function requirePostCsrf()
    {
        // Reuse MiniPHP's token validator only: dynamic forms cannot use its fixed
        // field-count validation, and Referer is not a substitute for a token.
        if ($this->request->isPost() && !(new SecurityComponent($this))->CsrfToken()) {
            http_response_code(403);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<p>Permintaan ditolak: token keselamatan tidak sah atau tiada. Muat semula borang dan cuba lagi.</p>';
            exit;
        }
    }

    protected function requireYearContext()
    {
        try {
            ReportingYear::capture($_GET, $this->request->isPost() ? $_POST : []);
        } catch (InvalidArgumentException $error) {
            http_response_code(400);
            header('Content-Type: text/html; charset=UTF-8');
            echo htmlspecialchars($error->getMessage(), ENT_QUOTES, 'UTF-8');
            exit;
        }
    }

    protected function atomic(callable $operation)
    {
        $database = Database::openConnection();
        $database->beginTransaction();
        try {
            $result = $operation();
            $database->commit();
            return $result;
        } catch (Throwable $error) {
            $database->rollBack();
            throw $error;
        }
    }

    /**
     * Rows a single-row UPDATE is expected to report. MariaDB counts changed rows, not matched
     * rows, so writing values identical to the locked snapshot legitimately reports 0.
     */
    protected function expectedChangedRows(array $current, array $target)
    {
        foreach ($target as $column => $value) {
            $old = array_key_exists($column, $current) ? $current[$column] : null;
            if (($old === null) !== ($value === null) || (string) $old !== (string) $value) { return 1; }
        }
        return 0;
    }

    protected function audit($action, $entityType, $entityId = null, array $details = [])
    {
        $details = $this->auditContext($action, $entityType, $details);
        $auditId = AuditLog::record($action, $entityType, $entityId, $details);
        if (in_array($entityType, [
            'project', 'project_payment_schedule', 'project_expense', 'project_variation',
            'development_charge', 'development_charge_payment', 'user'
        ], true)) {
            $this->notifyAuditEvent($auditId);
        }
        return $auditId;
    }

    /**
     * Adds the affected record's Jabatan (never the actor's) so targeting and the Audit Log
     * survive deletion. Callers may pass 'department' themselves, e.g. for cascaded children
     * whose project row is already gone. Caj Pemajuan has no confirmed Jabatan, so none is added.
     */
    private function auditContext($action, $entityType, array $details)
    {
        $snapshot = AuditPresenter::snapshot($action, $details);
        if (!array_key_exists('department', $details)) {
            if ($entityType === 'project' || $entityType === 'user') {
                $department = isset($snapshot['department']) ? $snapshot['department'] : null;
                $details['department'] = Departments::valid($department) ? $department : null;
            } elseif (in_array($entityType, AuditPresenter::PROJECT_TYPES, true)) {
                $details['department'] = isset($snapshot['project_id']) ? AuditLog::projectDepartment($snapshot['project_id']) : null;
            }
        }
        // Project moved between Jabatan: keep the previous one so both are notified and can audit it.
        if ($entityType === 'project' && strtolower($action) === 'update' && !array_key_exists('previous_department', $details)) {
            $previous = isset($details['before']['department']) ? $details['before']['department'] : null;
            if (Departments::valid($previous) && $previous !== $details['department']) {
                $details['previous_department'] = $previous;
            }
        }
        if ($entityType === 'development_charge_payment' && !isset($details['charge_reference']) && isset($snapshot['development_charge_id'])) {
            $details['charge_reference'] = AuditLog::chargeReference($snapshot['development_charge_id']);
        }
        return $details;
    }

    private function notifyAuditEvent($auditId)
    {
        $audit = (new AuditLog())->getById($auditId);
        if (!$audit || empty($audit['actor_id'])) { return; }

        $details = AuditPresenter::details($audit['details']);
        $entityType = $audit['entity_type'];
        $entityId = (int) $audit['entity_id'];
        $receipt = AuditPresenter::receiptEvent($entityType, $details);
        $message = AuditPresenter::description($audit, $details)
            . ' pada ' . date('d/m/Y H:i', strtotime($audit['created_at'])) . '.';

        $this->loadModel('Notification');
        $data = [
            'category' => $this->auditNotificationCategory($entityType),
            'action' => strtolower($audit['action']) . '_audit',
            'title' => 'Aktiviti ' . ($receipt !== null ? 'Resit Bayaran' : $this->auditNotificationTitle($entityType)),
            'message' => mb_substr($message, 0, 255, 'UTF-8'),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'reminder_key' => 'audit:' . $auditId
        ];

        $department = isset($details['department']) && Departments::valid($details['department']) ? $details['department'] : null;
        if ($entityType === 'user') {
            // Admins of the target user's Jabatan; users without one are visible to global admins only.
            $data['audience'] = 'admin';
            $data['department'] = $department;
        } elseif ($department !== null && in_array($entityType, AuditPresenter::PROJECT_TYPES, true)) {
            $data['audience'] = 'department_strict';
            $data['department'] = $department;
        } else {
            // Unresolved Jabatan (all Caj Pemajuan events): global admins only, never broadcast.
            $data['audience'] = 'global_admin';
        }

        if (!$this->notification->create($data)) {
            throw new RuntimeException('Audit notification failed.');
        }

        // Department transfer: the previous Jabatan gets its own row under a distinct, unique key.
        $previous = isset($details['previous_department']) && Departments::valid($details['previous_department']) ? $details['previous_department'] : null;
        if ($entityType === 'project' && $previous !== null && $previous !== $department) {
            $data['audience'] = 'department_strict';
            $data['department'] = $previous;
            $data['reminder_key'] = 'audit:' . $auditId . ':previous';
            if (!$this->notification->create($data)) {
                throw new RuntimeException('Audit notification failed.');
            }
        }
    }

    private function auditNotificationCategory($entityType)
    {
        if (strpos($entityType, 'project') === 0) { return 'project'; }
        if (strpos($entityType, 'development_charge') === 0) { return 'charge'; }
        return 'system';
    }

    private function auditNotificationTitle($entityType)
    {
        if (strpos($entityType, 'project') === 0) { return 'Projek'; }
        if (strpos($entityType, 'development_charge') === 0) { return 'Caj Pemajuan'; }
        return 'Pengguna';
    }

    protected function currentUserDepartment()
    {
        return $this->authenticatedUser && Departments::valid($this->authenticatedUser['department'])
            ? $this->authenticatedUser['department'] : null;
    }

    protected function notifyCurrentDepartment(array $data)
    {
        $department = $this->currentUserDepartment();
        if ($department === null) { return false; }
        // Strict creator-department scope: even admins without that department do not receive it.
        $data['audience'] = 'department_strict';
        $data['department'] = $department;
        return $this->notification->create($data);
    }

    protected function renderModule($view, array $data = [])
    {
        $this->loadModel('Notification');
        $data['unreadNotificationCount'] = $this->notification->getUnreadCount($_SESSION['user_id']);
        if (!isset($data['notifications'])) {
            $data['notifications'] = $this->notification->getRecentForUser($_SESSION['user_id']);
        }
        $this->view->renderWithLayouts(
            Config::get('VIEWS_PATH') . 'layout/cajpemajuan/',
            Config::get('VIEWS_PATH') . $view,
            $data
        );
    }
}
