<?php

/** Insert-only audit trail for authenticated create/update/delete operations. */
class AuditLog extends Model
{
    public static function actorId()
    {
        if (session_status() === PHP_SESSION_NONE) { @session_start(); }
        return isset($_SESSION['user_id']) && is_scalar($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0
            ? (int) $_SESSION['user_id'] : null;
    }

    public static function record($action, $entityType, $entityId = null, array $details = [])
    {
        $action = strtoupper((string) $action);
        if (!in_array($action, ['CREATE', 'UPDATE', 'DELETE'], true)) {
            throw new InvalidArgumentException('Jenis audit tidak sah.');
        }
        $actorId = self::actorId();
        $actorName = null;
        $actorEmail = null;
        if ($actorId !== null) {
            $user = (new User())->getById($actorId);
            if ($user) {
                $actorName = $user['name'];
                $actorEmail = $user['email'];
            }
        }
        $database = Database::openConnection();
        $database->prepare('INSERT INTO audit_logs
            (action, entity_type, entity_id, actor_id, actor_name, actor_email, details)
            VALUES (:action, :entity_type, :entity_id, :actor_id, :actor_name, :actor_email, :details)');
        $database->bindValue(':action', $action);
        $database->bindValue(':entity_type', $entityType);
        $database->bindValue(':entity_id', $entityId === null ? null : (int) $entityId);
        $database->bindValue(':actor_id', $actorId);
        $database->bindValue(':actor_name', $actorName);
        $database->bindValue(':actor_email', $actorEmail);
        $database->bindValue(':details', $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null);
        if (!$database->execute()) {
            throw new RuntimeException('Audit log insertion failed.');
        }
        return (int) $database->lastInsertId();
    }

    public function getById($id)
    {
        $database = Database::openConnection();
        $database->prepare('SELECT * FROM audit_logs WHERE id = :id LIMIT 1');
        $database->bindValue(':id', (int) $id);
        $database->execute();
        return $database->fetchAssociative();
    }

    /** Jabatan of a project row, read server-side when an audit is written. */
    public static function projectDepartment($projectId)
    {
        $database = Database::openConnection();
        $database->prepare('SELECT department FROM projects WHERE id = :id LIMIT 1');
        $database->bindValue(':id', (int) $projectId);
        $database->execute();
        $project = $database->fetchAssociative();
        return $project && Departments::valid($project['department']) ? $project['department'] : null;
    }

    public static function chargeReference($chargeId)
    {
        $database = Database::openConnection();
        $database->prepare('SELECT osc_id, project_name FROM development_charges WHERE id = :id LIMIT 1');
        $database->bindValue(':id', (int) $chargeId);
        $database->execute();
        $charge = $database->fetchAssociative();
        if (!$charge) { return null; }
        return $charge['osc_id'] !== null && $charge['osc_id'] !== '' ? $charge['osc_id'] : $charge['project_name'];
    }

    const RECORD_TYPES = [
        'project' => "x.entity_type = 'project'",
        'project_payment_schedule' => "x.entity_type = 'project_payment_schedule'",
        'project_expense' => "x.entity_type = 'project_expense'",
        'project_variation' => "x.entity_type = 'project_variation'",
        'development_charge' => "x.entity_type = 'development_charge'",
        'development_charge_payment' => "x.entity_type = 'development_charge_payment'",
        'receipt' => 'x.receipt_changed = 1',
        'user' => "x.entity_type = 'user'",
        'notification' => "x.entity_type IN ('notification', 'notification_read')"
    ];

    /**
     * Filtered audit history for the Audit Log page.
     * $scopeDepartment null = global admin (every row); otherwise only rows whose resolved Jabatan
     * equals it, which excludes unassigned Caj Pemajuan, notification and Jabatan-less rows.
     */
    public function search(array $filters, $scopeDepartment, $limit, $offset)
    {
        list($where, $params) = $this->searchConditions($filters, $scopeDepartment);
        $database = Database::openConnection();
        $database->prepare('SELECT * FROM (' . $this->resolvedQuery() . ') x WHERE ' . $where
            . ' ORDER BY x.id DESC LIMIT ' . max(1, (int) $limit) . ' OFFSET ' . max(0, (int) $offset));
        foreach ($params as $name => $value) { $database->bindValue($name, $value); }
        $database->execute();
        return $database->fetchAllAssociative();
    }

    public function countSearch(array $filters, $scopeDepartment)
    {
        list($where, $params) = $this->searchConditions($filters, $scopeDepartment);
        $database = Database::openConnection();
        $database->prepare('SELECT COUNT(*) AS total FROM (' . $this->resolvedQuery() . ') x WHERE ' . $where);
        foreach ($params as $name => $value) { $database->bindValue($name, $value); }
        $database->execute();
        return (int) $database->fetchAssociative()['total'];
    }

    /**
     * Resolves each row's Jabatan: the value stored at write time, else (older rows) the project or
     * user snapshot, else the live project row. Caj Pemajuan rows stay NULL (Belum Ditentukan).
     */
    private function resolvedQuery()
    {
        $json = function ($path) { return "NULLIF(JSON_UNQUOTE(JSON_EXTRACT(a.details, '" . $path . "')), 'null')"; };
        $childProject = 'COALESCE(' . $json('$.after.project_id') . ', ' . $json('$.before.project_id') . ')';
        $department = 'COALESCE(' . $json('$.department') . ","
            . " CASE WHEN a.entity_type IN ('project', 'user') THEN COALESCE(" . $json('$.after.department') . ', ' . $json('$.before.department') . ') END,'
            . " CASE WHEN a.entity_type = 'project' THEN (SELECT p.department FROM projects p WHERE p.id = a.entity_id) END,"
            . " CASE WHEN a.entity_type IN ('project_payment_schedule', 'project_expense', 'project_variation')"
            . ' THEN (SELECT p.department FROM projects p WHERE p.id = ' . $childProject . ') END)';
        $receipt = function ($field) use ($json) {
            return 'COALESCE(' . $json('$.before.' . $field) . ", '') <> COALESCE(" . $json('$.after.' . $field) . ", '')";
        };
        $receiptChanged = "CASE WHEN a.entity_type = 'development_charge_payment' AND ("
            . $receipt('receipt_file') . ' OR ' . $receipt('receipt_number') . ') THEN 1 ELSE 0 END';
        // Project transfers (also older rows whose before/after Jabatan differ) belong to both Jabatan.
        $previous = "CASE WHEN a.entity_type = 'project' AND a.action = 'UPDATE' THEN COALESCE("
            . $json('$.previous_department') . ', ' . $json('$.before.department') . ') END';
        return 'SELECT a.*, CASE WHEN a.entity_type IN (\'development_charge\', \'development_charge_payment\') THEN NULL ELSE '
            . $department . ' END AS resolved_department, ' . $previous . ' AS previous_department, '
            . $receiptChanged . ' AS receipt_changed FROM audit_logs a';
    }

    private function searchConditions(array $filters, $scopeDepartment)
    {
        $where = ['1 = 1'];
        $params = [];
        if ($scopeDepartment !== null) {
            $where[] = '(x.resolved_department = :scope_department OR x.previous_department = :scope_previous)';
            $params[':scope_department'] = (string) $scopeDepartment;
            $params[':scope_previous'] = (string) $scopeDepartment;
        }
        if (!empty($filters['q'])) {
            $where[] = '(x.actor_name LIKE :q_name OR x.actor_email LIKE :q_email)';
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $params[':q_name'] = $like;
            $params[':q_email'] = $like;
        }
        if (!empty($filters['department'])) {
            if ($filters['department'] === 'unassigned') {
                $where[] = 'x.resolved_department IS NULL';
            } else {
                $where[] = '(x.resolved_department = :department OR x.previous_department = :department_previous)';
                $params[':department'] = $filters['department'];
                $params[':department_previous'] = $filters['department'];
            }
        }
        if (!empty($filters['action'])) {
            $where[] = 'x.action = :action';
            $params[':action'] = $filters['action'];
        }
        if (!empty($filters['type']) && isset(self::RECORD_TYPES[$filters['type']])) {
            $where[] = self::RECORD_TYPES[$filters['type']];
        }
        if (!empty($filters['from'])) {
            $where[] = 'x.created_at >= :date_from';
            $params[':date_from'] = $filters['from'] . ' 00:00:00';
        }
        if (!empty($filters['to'])) {
            $where[] = 'x.created_at < DATE_ADD(:date_to, INTERVAL 1 DAY)';
            $params[':date_to'] = $filters['to'];
        }
        return [implode(' AND ', $where), $params];
    }
}
