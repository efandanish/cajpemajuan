<?php

/**
 * Administrator Audit Log (read-only).
 * Access is enforced here, not by navigation: admins only. A global admin (no Jabatan) sees every
 * record; a department admin sees only records resolved to their own Jabatan. Role and Jabatan come
 * from the database row loaded by requireLogin(), never from request parameters.
 */
class AuditController extends ModuleController
{
    const PER_PAGE = 25;

    public function initialize()
    {
        $this->loadComponents([]);
        $this->loadModel('Notification');
        $this->requireLogin();
        $this->requirePostCsrf();
        $this->requireYearContext();
        $this->requireAdmin();
    }

    public function index()
    {
        $global = ProjectAccessPolicy::isGlobalAdmin($this->authenticatedUser);
        $scope = $global ? null : trim((string) $this->authenticatedUser['department']);
        $filters = $this->filters($global);

        $auditLog = new AuditLog();
        $total = $auditLog->countSearch($filters, $scope);
        $pages = max(1, (int) ceil($total / self::PER_PAGE));
        $page = isset($_GET['page']) && is_string($_GET['page']) && ctype_digit($_GET['page']) ? (int) $_GET['page'] : 1;
        $page = min(max(1, $page), $pages);

        $this->renderModule('audit/index.php', [
            'audits' => $auditLog->search($filters, $scope, self::PER_PAGE, ($page - 1) * self::PER_PAGE),
            'filters' => $filters,
            'isGlobalAdmin' => $global,
            'scopeDepartment' => $scope,
            'total' => $total,
            'page' => $page,
            'pages' => $pages
        ]);
    }

    private function filters($global)
    {
        $text = function ($key) {
            return isset($_GET[$key]) && is_string($_GET[$key]) ? trim($_GET[$key]) : '';
        };
        $filters = ['q' => mb_substr($text('q'), 0, 100, 'UTF-8'), 'department' => '', 'action' => '', 'type' => '', 'from' => '', 'to' => ''];
        // Department admins: any submitted department is ignored; their scope is fixed server-side.
        if ($global && ($text('department') === 'unassigned' || Departments::valid($text('department')))) {
            $filters['department'] = $text('department');
        }
        if (in_array($text('action'), ['CREATE', 'UPDATE', 'DELETE'], true)) {
            $filters['action'] = $text('action');
        }
        if (isset(AuditLog::RECORD_TYPES[$text('type')])) {
            $filters['type'] = $text('type');
        }
        foreach (['from', 'to'] as $key) {
            if (ChargeValidation::date($text($key))) { $filters[$key] = $text($key); }
        }
        return $filters;
    }
}
