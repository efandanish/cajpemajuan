<?php

class Notification extends Model
{
    // Membership and role come from the database, never request parameters or cached departments.
    private function visibility($userId)
    {
        $user = (new User())->getById((int) $userId);
        if (!$user || !in_array($user['role'], ['admin', 'user'], true)) { return '1 = 0'; }
        // Global admins read every notification row; nothing is duplicated for them.
        if (ProjectAccessPolicy::isGlobalAdmin($user)) { return '(1 = 1)'; }
        $department = isset($user['department']) ? $user['department'] : null;
        $business = "'" . implode("', '", array_merge(AuditPresenter::PROJECT_TYPES, AuditPresenter::CHARGE_TYPES, ['user', 'notification', 'notification_read'])) . "'";
        // 'global'/'legacy' rows reach everyone only when they are genuine system announcements.
        // Business rows stored that way (e.g. older charge reminders) must not leak through it.
        $rule = "(n.audience IN ('legacy', 'global') AND n.category = 'system' AND (n.entity_type IS NULL OR n.entity_type NOT IN (" . $business . ')))';
        if (Departments::valid($department)) {
            // Department admins also see admin events scoped to their Jabatan (user management).
            // Caj Pemajuan has no confirmed Jabatan: its rows ('global_admin', or older rows tagged
            // with the actor's Jabatan) are excluded for everyone except global admins.
            $audiences = $user['role'] === 'admin' ? "'department', 'department_strict', 'admin'" : "'department', 'department_strict'";
            $charges = "'" . implode("', '", AuditPresenter::CHARGE_TYPES) . "'";
            // Only canonical server-defined values are interpolated here.
            $rule .= " OR (n.audience IN (" . $audiences . ") AND n.department = '" . $department . "'"
                . ' AND (n.entity_type IS NULL OR n.entity_type NOT IN (' . $charges . ')))';
            // Older project rows stored as 'global'/'legacy' stay visible to the project's own Jabatan.
            $rule .= " OR (n.audience IN ('legacy', 'global') AND " . $this->projectDepartmentSql() . " = '" . $department . "')";
        }
        return '(' . $rule . ')';
    }

    /** Current Jabatan of the project a notification refers to; NULL for other or deleted records. */
    private function projectDepartmentSql()
    {
        return "(CASE n.entity_type
            WHEN 'project' THEN (SELECT p.department FROM projects p WHERE p.id = n.entity_id)
            WHEN 'project_payment_schedule' THEN (SELECT p.department FROM project_payment_schedules s JOIN projects p ON p.id = s.project_id WHERE s.id = n.entity_id)
            WHEN 'project_expense' THEN (SELECT p.department FROM project_expenses e JOIN projects p ON p.id = e.project_id WHERE e.id = n.entity_id)
            WHEN 'project_variation' THEN (SELECT p.department FROM project_variations v JOIN projects p ON p.id = v.project_id WHERE v.id = n.entity_id)
            END)";
    }

    public function getById($id, $userId = null)
    {
        if ($userId === null) { $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0; }
        $database = Database::openConnection();
        $database->prepare('SELECT n.* FROM notifications n WHERE n.id = :id AND ' . $this->visibility($userId) . ' LIMIT 1');
        $database->bindValue(':id', (int) $id);
        $database->execute();
        return $database->fetchAssociative();
    }

    public function create($data)
    {
        $audience = 'global';
        $department = null;
        $type = isset($data['entity_type']) ? $data['entity_type'] : null;
        $entityId = isset($data['entity_id']) ? (int) $data['entity_id'] : null;
        $explicitDepartment = isset($data['audience']) && in_array($data['audience'], ['department', 'department_strict'], true);
        $adminOnly = isset($data['audience']) && in_array($data['audience'], ['admin', 'global_admin'], true);
        if ($explicitDepartment) {
            $department = isset($data['department']) && Departments::valid($data['department']) ? $data['department'] : null;
            if ($department === null) { return false; }
            $audience = $data['audience'];
        } elseif ($adminOnly) {
            // 'admin' may be scoped to a Jabatan; 'global_admin' (no Jabatan resolved) never is.
            $audience = $data['audience'];
            $department = $audience === 'admin' && isset($data['department']) && Departments::valid($data['department'])
                ? $data['department'] : null;
        } elseif (in_array($type, AuditPresenter::CHARGE_TYPES, true)) {
            // Never default a Caj Pemajuan row to 'global': its Jabatan is unconfirmed.
            $audience = 'global_admin';
        } elseif (in_array($type, ['project', 'project_payment_schedule'], true) || $data['category'] === 'project') {
            $database = Database::openConnection();
            $query = $type === 'project_payment_schedule'
                ? 'SELECT p.department FROM projects p JOIN project_payment_schedules s ON s.project_id = p.id WHERE s.id = :id'
                : 'SELECT department FROM projects WHERE id = :id';
            $database->prepare($query);
            $database->bindValue(':id', $entityId);
            $database->execute();
            $project = $database->fetchAssociative();
            $department = $project && Departments::valid($project['department']) ? $project['department'] : null;
            $audience = $department === null ? 'admin' : 'department';
        }
        $reminderKey = isset($data['reminder_key']) ? $data['reminder_key'] : null;
        if ($reminderKey === null && in_array($data['action'], ['created', 'user_created'], true) && $type !== null && $entityId !== null) {
            $reminderKey = 'created:' . $type . ':' . $entityId;
        }
        if ($reminderKey === null && in_array($data['action'], ['project_overdue', 'project_payment_overdue', 'project_payment_due_today', 'project_payment_due_soon'], true)) {
            $reminderKey = $data['action'] . ':' . $type . ':' . $entityId;
        }
        $database = Database::openConnection();
        $startedTransaction = false;
        if (!$database->inTransaction()) {
            $database->beginTransaction();
            $startedTransaction = true;
        }
        try {
        $database->prepare('INSERT INTO notifications
            (category, action, title, message, entity_type, entity_id, audience, department, reminder_key)
            VALUES (:category, :action, :title, :message, :entity_type, :entity_id, :audience, :department, :reminder_key)
            ON DUPLICATE KEY UPDATE id = id');
        foreach (['category', 'action', 'title', 'message'] as $field) {
            $value = $data[$field];
            // Existing notification storage has a 255-character message limit.
            if ($field === 'message' && $audience !== 'global') { $value = mb_substr($value, 0, 255, 'UTF-8'); }
            $database->bindValue(':' . $field, $value);
        }
        foreach (['entity_type' => $type, 'entity_id' => $entityId, 'audience' => $audience,
            'department' => $department, 'reminder_key' => $reminderKey] as $field => $value) {
            $database->bindValue(':' . $field, $value);
        }
        $executed = $database->execute();
        if ($executed && $database->countRows() === 1) {
            AuditLog::record('create', 'notification', $database->lastInsertId(), [
                'after' => ['category' => $data['category'], 'action' => $data['action'], 'title' => $data['title'], 'message' => $data['message'], 'entity_type' => $type, 'entity_id' => $entityId, 'audience' => $audience, 'department' => $department]
            ]);
        }
        if ($startedTransaction) { $database->commit(); }
        return $executed;
        } catch (Throwable $error) {
            if ($startedTransaction && $database->inTransaction()) { $database->rollBack(); }
            throw $error;
        }
    }

    public function getUnreadCount($userId)
    {
        $database = Database::openConnection();
        $database->prepare('SELECT COUNT(*) AS total FROM notifications n
            LEFT JOIN notification_reads nr ON nr.notification_id = n.id AND nr.user_id = :user_id
            WHERE nr.id IS NULL AND ' . $this->visibility($userId));
        $database->bindValue(':user_id', (int) $userId);
        $database->execute();
        return (int) $database->fetchAssociative()['total'];
    }

    public function markAsRead($notificationId, $userId)
    {
        if (!$this->getById($notificationId, $userId)) { return false; }
        $database = Database::openConnection();
        $startedTransaction = false;
        if (!$database->inTransaction()) { $database->beginTransaction(); $startedTransaction = true; }
        try {
        $database->prepare('INSERT IGNORE INTO notification_reads (notification_id, user_id)
            SELECT n.id, :user_id FROM notifications n WHERE n.id = :id AND ' . $this->visibility($userId));
        $database->bindValue(':user_id', (int) $userId);
        $database->bindValue(':id', (int) $notificationId);
        $result = $database->execute();
        if ($result && $database->countRows() === 1) {
            AuditLog::record('create', 'notification_read', $database->lastInsertId(), [
                'after' => ['notification_id' => (int) $notificationId, 'user_id' => (int) $userId]
            ]);
        }
        if ($startedTransaction) { $database->commit(); }
        return $result;
        } catch (Throwable $error) {
            if ($startedTransaction && $database->inTransaction()) { $database->rollBack(); }
            throw $error;
        }
    }

    public function getAllForAudit()
    {
        $database = Database::openConnection();
        $database->prepare('SELECT * FROM notifications ORDER BY id ASC');
        $database->execute();
        return $database->fetchAllAssociative();
    }

    public function markAllAsRead($userId)
    {
        $database = Database::openConnection();
        $startedTransaction = false;
        if (!$database->inTransaction()) { $database->beginTransaction(); $startedTransaction = true; }
        try {
        $database->prepare('SELECT n.id FROM notifications n
            LEFT JOIN notification_reads nr ON nr.notification_id = n.id AND nr.user_id = :read_user_id
            WHERE nr.id IS NULL AND ' . $this->visibility($userId));
        $database->bindValue(':read_user_id', (int) $userId);
        $database->execute();
        $pending = $database->fetchAllAssociative();
        $database->prepare('INSERT IGNORE INTO notification_reads (notification_id, user_id)
            SELECT n.id, :user_id FROM notifications n WHERE ' . $this->visibility($userId));
        $database->bindValue(':user_id', (int) $userId);
        $result = $database->execute();
        foreach ($result ? $pending : [] as $notification) {
            $database->prepare('SELECT id FROM notification_reads WHERE notification_id = :notification_id AND user_id = :user_id LIMIT 1');
            $database->bindValue(':notification_id', (int) $notification['id']);
            $database->bindValue(':user_id', (int) $userId);
            $database->execute();
            $read = $database->fetchAssociative();
            if ($read) {
                AuditLog::record('create', 'notification_read', $read['id'], [
                    'after' => ['notification_id' => (int) $notification['id'], 'user_id' => (int) $userId]
                ]);
            }
        }
        if ($startedTransaction) { $database->commit(); }
        return $result;
        } catch (Throwable $error) {
            if ($startedTransaction && $database->inTransaction()) { $database->rollBack(); }
            throw $error;
        }
    }

    private function listForUser($userId, $recent)
    {
        $database = Database::openConnection();
        $database->prepare('SELECT n.*, CASE WHEN nr.id IS NULL THEN 0 ELSE 1 END AS is_read
            FROM notifications n LEFT JOIN notification_reads nr
            ON nr.notification_id = n.id AND nr.user_id = :user_id
            WHERE ' . $this->visibility($userId) . ' ORDER BY n.created_at DESC, n.id DESC' . ($recent ? ' LIMIT 10' : ''));
        $database->bindValue(':user_id', (int) $userId);
        $database->execute();
        return $database->fetchAllAssociative();
    }

    public function getAllForUser($userId) { return $this->listForUser($userId, false); }
    public function getRecentForUser($userId) { return $this->listForUser($userId, true); }

    /**
     * Global admin clear. Locks and captures every notification and its read receipts,
     * deletes exactly those IDs and verifies both counts. Notifications created after the
     * capture are not deleted. Must run inside the caller's transaction; the caller audits
     * the returned snapshots after this succeeds.
     */
    public function clearAll()
    {
        $database = Database::openConnection();
        if (!$database->inTransaction()) {
            throw new LogicException('Notification clear requires an active transaction.');
        }

        $database->prepare('SELECT * FROM notifications ORDER BY id ASC FOR UPDATE');
        $database->execute();
        $notifications = $database->fetchAllAssociative();
        $notificationIds = array_map('intval', array_column($notifications, 'id'));

        $reads = $this->selectReadsByColumn('notification_id', $notificationIds, true);
        $readIds = array_map('intval', array_column($reads, 'id'));

        if ($this->deleteByIds('notification_reads', $readIds) !== count($readIds)) {
            throw new RuntimeException('Notification read delete count mismatch.');
        }
        if ($this->deleteByIds('notifications', $notificationIds) !== count($notificationIds)) {
            throw new RuntimeException('Notification delete count mismatch.');
        }

        return ['notifications' => $notifications, 'reads' => $reads];
    }

    // Read receipts are removed by cascade when a user is deleted; these let the caller lock, delete and audit them explicitly.
    public function getReadsForUser($userId, $lock = false)
    {
        return $this->selectReadsByColumn('user_id', [(int) $userId], $lock);
    }

    public function deleteReadsByIds(array $ids)
    {
        return $this->deleteByIds('notification_reads', array_map('intval', $ids));
    }

    private function selectReadsByColumn($column, array $values, $lock)
    {
        $rows = [];
        foreach (array_chunk($values, 500) as $chunk) {
            $database = Database::openConnection();
            $placeholders = $this->bindPlaceholders($chunk);
            $database->prepare('SELECT * FROM notification_reads WHERE ' . $column . ' IN (' . implode(', ', array_keys($placeholders)) . ')
                ORDER BY id ASC' . ($lock ? ' FOR UPDATE' : ''));
            foreach ($placeholders as $name => $value) { $database->bindValue($name, $value); }
            $database->execute();
            $rows = array_merge($rows, $database->fetchAllAssociative());
        }
        return $rows;
    }

    private function deleteByIds($table, array $ids)
    {
        $deleted = 0;
        foreach (array_chunk($ids, 500) as $chunk) {
            $database = Database::openConnection();
            $placeholders = $this->bindPlaceholders($chunk);
            $database->prepare('DELETE FROM ' . $table . ' WHERE id IN (' . implode(', ', array_keys($placeholders)) . ')');
            foreach ($placeholders as $name => $value) { $database->bindValue($name, $value); }
            if ($database->execute()) {
                $deleted += (int) $database->countRows();
            }
        }
        return $deleted;
    }

    private function bindPlaceholders(array $values)
    {
        $placeholders = [];
        foreach (array_values($values) as $index => $value) {
            $placeholders[':v' . $index] = (int) $value;
        }
        return $placeholders;
    }
    public function existsForEntity(
        $action,
        $entityType,
        $entityId
    ) {
        $database = Database::openConnection();

        $database->prepare("
        SELECT id
        FROM notifications

        WHERE action = :action
        AND entity_type = :entity_type
        AND entity_id = :entity_id

        LIMIT 1
    ");

        $database->bindValue(
            ':action',
            $action
        );

        $database->bindValue(
            ':entity_type',
            $entityType
        );

        $database->bindValue(
            ':entity_id',
            $entityId
        );

        $database->execute();

        $result =
            $database->fetchAssociative();

        return $result ? true : false;
    }
    public function generateChargePaymentReminders(
        $developmentChargeModel
    ) {
        $payments =
            $developmentChargeModel
                ->getPendingPaymentsWithDueDate();


        if (empty($payments)) {
            return;
        }


        $today =
            new DateTime(
                date('Y-m-d')
            );


        foreach ($payments as $payment) {

            if (empty($payment['due_date'])) {
                continue;
            }


            $dueDate =
                new DateTime(
                    $payment['due_date']
                );


            $difference =
                (int) $today
                    ->diff($dueDate)
                    ->format('%r%a');


            /*
            |--------------------------------------------------------------------------
            | OVERDUE
            |--------------------------------------------------------------------------
            */

            if ($difference < 0) {

                $action =
                    'charge_payment_overdue';


                if (
                    !$this->existsForEntity(
                        $action,
                        'development_charge_payment',
                        $payment['id']
                    )
                ) {

                    $this->create([
                        'category' =>
                            'payment',

                        'action' =>
                            $action,

                        'title' =>
                            'Bayaran Caj Tertunggak',

                        'message' =>
                            $payment['osc_id'] .
                            ' - Ansuran #' .
                            $payment['installment_no'] .
                            ' bernilai RM ' .
                            number_format(
                                $payment['amount'],
                                2
                            ) .
                            ' telah melepasi tarikh akhir ' .
                            date(
                                'd/m/Y',
                                strtotime(
                                    $payment['due_date']
                                )
                            ) .
                            '.',

                        'entity_type' =>
                            'development_charge_payment',

                        'entity_id' =>
                            $payment['id'],

                        // No confirmed Jabatan for Caj Pemajuan yet: global admins only.
                        'audience' => 'global_admin'
                    ]);

                }

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | DUE TODAY
            |--------------------------------------------------------------------------
            */

            if ($difference === 0) {

                $action =
                    'charge_payment_due_today';


                if (
                    !$this->existsForEntity(
                        $action,
                        'development_charge_payment',
                        $payment['id']
                    )
                ) {

                    $this->create([
                        'category' =>
                            'payment',

                        'action' =>
                            $action,

                        'title' =>
                            'Bayaran Caj Perlu Dibayar Hari Ini',

                        'message' =>
                            $payment['osc_id'] .
                            ' - Ansuran #' .
                            $payment['installment_no'] .
                            ' bernilai RM ' .
                            number_format(
                                $payment['amount'],
                                2
                            ) .
                            ' perlu dibayar hari ini.',

                        'entity_type' =>
                            'development_charge_payment',

                        'entity_id' =>
                            $payment['id'],

                        // No confirmed Jabatan for Caj Pemajuan yet: global admins only.
                        'audience' => 'global_admin'
                    ]);

                }

                continue;
            }


            /*
            |--------------------------------------------------------------------------
            | WITHIN 7 DAYS
            |--------------------------------------------------------------------------
            */

            if (
                $difference > 0 &&
                $difference <= 7
            ) {

                $action =
                    'charge_payment_due_soon';


                if (
                    !$this->existsForEntity(
                        $action,
                        'development_charge_payment',
                        $payment['id']
                    )
                ) {

                    $this->create([
                        'category' =>
                            'payment',

                        'action' =>
                            $action,

                        'title' =>
                            'Bayaran Caj Akan Tiba',

                        'message' =>
                            $payment['osc_id'] .
                            ' - Ansuran #' .
                            $payment['installment_no'] .
                            ' bernilai RM ' .
                            number_format(
                                $payment['amount'],
                                2
                            ) .
                            ' perlu dibayar pada ' .
                            date(
                                'd/m/Y',
                                strtotime(
                                    $payment['due_date']
                                )
                            ) .
                            '.',

                        'entity_type' =>
                            'development_charge_payment',

                        'entity_id' =>
                            $payment['id'],

                        // No confirmed Jabatan for Caj Pemajuan yet: global admins only.
                        'audience' => 'global_admin'
                    ]);

                }

            }

        }
    }

    private function validReminderDate($value)
    {
        if (!is_string($value)) { return false; }
        $date = DateTime::createFromFormat('!Y-m-d', $value);
        return $date && $date->format('Y-m-d') === $value ? $date : false;
    }

    public function generateProjectOverdueReminders($projectModel)
    {
        foreach ($projectModel->getOverdueProjects() as $project) {
            if (!$this->validReminderDate($project['end_date']) || $project['end_date'] >= date('Y-m-d')) { continue; }
            if ($this->existsForEntity('project_overdue', 'project', $project['id'])) { continue; }
            $this->create([
                'category' => 'project', 'action' => 'project_overdue', 'title' => 'Projek Melebihi Tempoh',
                'message' => 'Projek ' . $project['project_name'] . ' di bawah Jabatan ' . $project['department']
                    . ' telah melebihi tarikh tamat ' . date('d/m/Y', strtotime($project['end_date'])) . '.',
                'entity_type' => 'project', 'entity_id' => $project['id']
            ]);
        }
    }

    public function generateProjectPaymentReminders($projectModel)
    {
        $today = new DateTime(date('Y-m-d'));
        foreach ($projectModel->getPendingSchedulesWithDueDate() as $schedule) {
            $dueDate = $this->validReminderDate($schedule['claim_date']);
            if (!$dueDate) { continue; }
            $difference = (int) $today->diff($dueDate)->format('%r%a');
            if ($difference > 7) { continue; }
            if ($difference < 0) {
                $action = 'project_payment_overdue';
                $title = 'Tuntutan Projek Tertunggak';
                $ending = ' telah melepasi tarikh ' . $dueDate->format('d/m/Y') . '.';
            } elseif ($difference === 0) {
                $action = 'project_payment_due_today';
                $title = 'Tuntutan Projek Perlu Dibayar Hari Ini';
                $ending = ' perlu dibayar hari ini.';
            } else {
                $action = 'project_payment_due_soon';
                $title = 'Tuntutan Projek Akan Tiba';
                $ending = ' dijadualkan pada ' . $dueDate->format('d/m/Y') . '.';
            }
            if ($this->existsForEntity($action, 'project_payment_schedule', $schedule['id'])) { continue; }
            $this->create(['category' => 'payment', 'action' => $action, 'title' => $title,
                'message' => $schedule['project_code'] . ' - Tuntutan bernilai RM ' . number_format($schedule['amount'], 2) . $ending,
                'entity_type' => 'project_payment_schedule', 'entity_id' => $schedule['id']]);
        }
    }
}
