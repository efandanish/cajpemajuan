<?php
require __DIR__ . '/bootstrap.php';
$failed = false;
$check = function ($ok, $message) use (&$failed) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $message . "\n";
    if (!$ok) { $failed = true; }
};
$check(PHP_VERSION_ID >= 70400 && PHP_VERSION_ID < 80300, 'PHP 7.4 through 8.2 (running ' . PHP_VERSION . ')');
if (in_array('--target-php825', $argv, true)) {
    $check(PHP_VERSION === '8.2.5', 'Exact upgrade target PHP 8.2.5 (running ' . PHP_VERSION . ')');
}
foreach (array('AuthController', 'ErrorsController', 'DashboardController', 'UserController', 'ProjectController', 'ChargeController', 'NotifyController', 'NotificationController', 'ModuleController', 'ReportController', 'AuditController') as $controller) {
    $check(class_exists($controller), 'Controller: ' . $controller);
}
foreach (array('pdo', 'pdo_mysql', 'mbstring', 'gd', 'openssl', 'fileinfo') as $extension) {
    $check(extension_loaded($extension), 'PHP extension: ' . $extension);
}
foreach (array('app/logs', 'public/uploads/receipts', 'app/uploads') as $directory) {
    $check(is_dir(BASE_DIR . '/' . $directory) && is_writable(BASE_DIR . '/' . $directory), 'Writable: ' . $directory);
}

// Configuration: values are checked, never printed.
foreach (array('COOKIE_SECRET_KEY', 'ENCRYPTION_KEY', 'HMAC_SALT', 'HASH_KEY') as $key) {
    $value = Config::get($key);
    $check(is_string($value) && strlen($value) >= 32, 'Config: ' . $key . ' is set (32+ characters; run scripts/init-config.php on a new installation)');
}
foreach (array('DB_USER', 'DB_PASS') as $key) {
    $check(strpos((string) Config::get($key), 'CHANGE_ME') === false, 'Config: ' . $key . ' placeholder replaced in app/config/local.php');
}

/*
 * Essential database structure (database/caj_pemajuan_clean.sql, including migrations 001-005).
 * Read-only: information_schema metadata only, no rows are written.
 */
$tables = array(
    'users' => array('id', 'name', 'email', 'password', 'role', 'department', 'created_by', 'updated_by', 'created_at', 'updated_at'),
    'projects' => array('id', 'project_id', 'department', 'project_name', 'description', 'approval_status', 'meeting_count', 'procurement_type',
        'sst_value', 'meeting_approval_value', 'payment_method', 'start_date', 'end_date', 'project_status', 'created_by', 'updated_by', 'created_at', 'updated_at'),
    'project_payment_schedules' => array('id', 'project_id', 'claim_date', 'amount', 'type', 'status', 'created_by', 'updated_by', 'created_at'),
    'project_expenses' => array('id', 'project_id', 'schedule_id', 'amount', 'payment_date', 'description', 'created_by', 'updated_by', 'created_at'),
    'project_variations' => array('id', 'project_id', 'description', 'variation_type', 'amount', 'variation_date', 'created_by', 'updated_by', 'created_at'),
    'development_charges' => array('id', 'developer_name', 'project_name', 'osc_id', 'lot_number', 'mukim', 'file_number', 'charge_amount',
        'osc_decision', 'payment_plan', 'record_year', 'created_by', 'updated_by', 'created_at', 'updated_at'),
    'development_charge_payments' => array('id', 'development_charge_id', 'installment_no', 'due_date', 'payment_date', 'amount', 'status',
        'receipt_file', 'receipt_number', 'created_by', 'updated_by', 'created_at', 'updated_at'),
    'notifications' => array('id', 'category', 'action', 'title', 'message', 'entity_type', 'entity_id', 'audience', 'department', 'reminder_key', 'created_at'),
    'notification_reads' => array('id', 'notification_id', 'user_id', 'read_at'),
    'audit_logs' => array('id', 'action', 'entity_type', 'entity_id', 'actor_id', 'actor_name', 'actor_email', 'details', 'created_at'),
);
// Column => array(DATA_TYPE, nullable, extra) for definitions the application depends on.
$definitions = array(
    'development_charges.record_year' => array('smallint', true, 'unsigned'),
    'development_charge_payments.receipt_number' => array('varchar', true, 100),
    'users.department' => array('varchar', true, 150),
    'notifications.audience' => array('varchar', false, 20),
    'notifications.reminder_key' => array('varchar', true, 150),
    'audit_logs.details' => array('text', true, null),
);
// Index name => array(table, columns, unique).
$indexes = array(
    'email' => array('users', 'email', true),
    'project_id' => array('projects', 'project_id', true),
    'unique_project_reminder' => array('notifications', 'reminder_key', true),
    'unique_notification_user' => array('notification_reads', 'notification_id,user_id', true),
    'notification_entity_action' => array('notifications', 'entity_type,entity_id,action', false),
    'idx_charge_record_year' => array('development_charges', 'record_year', false),
    'audit_entity' => array('audit_logs', 'entity_type,entity_id', false),
    'audit_actor' => array('audit_logs', 'actor_id', false),
    'audit_created_at' => array('audit_logs', 'created_at', false),
);
// Foreign key column => array(table, referenced table.column, ON DELETE rule). Deletion integrity relies on these.
$foreignKeys = array(
    array('development_charge_payments', 'development_charge_id', 'development_charges.id', 'CASCADE'),
    array('project_payment_schedules', 'project_id', 'projects.id', 'CASCADE'),
    array('project_expenses', 'project_id', 'projects.id', 'CASCADE'),
    array('project_expenses', 'schedule_id', 'project_payment_schedules.id', 'SET NULL'),
    array('project_variations', 'project_id', 'projects.id', 'CASCADE'),
    array('notification_reads', 'notification_id', 'notifications.id', 'CASCADE'),
    array('notification_reads', 'user_id', 'users.id', 'CASCADE'),
);

try {
    $db = Database::openConnection();
    $query = function ($sql, array $params = array()) use ($db) {
        $db->prepare($sql);
        foreach ($params as $name => $value) { $db->bindValue($name, $value); }
        $db->execute();
        return $db->fetchAllAssociative();
    };

    $present = array();
    foreach ($query('SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()') as $row) {
        $present[$row['TABLE_NAME']] = $row['ENGINE'];
    }
    $columns = array();
    foreach ($query('SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, IS_NULLABLE, CHARACTER_MAXIMUM_LENGTH
        FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE()') as $row) {
        $columns[$row['TABLE_NAME'] . '.' . $row['COLUMN_NAME']] = $row;
    }

    foreach ($tables as $table => $required) {
        if (!isset($present[$table])) {
            $check(false, 'Database table: ' . $table . ' (missing; see README.md or database/migrations/README.md)');
            continue;
        }
        $check(strcasecmp($present[$table], 'InnoDB') === 0, 'Database table: ' . $table . ' (InnoDB)');
        $missing = array();
        foreach ($required as $column) {
            if (!isset($columns[$table . '.' . $column])) { $missing[] = $column; }
        }
        $check(!$missing, 'Columns: ' . $table . ($missing ? ' (missing: ' . implode(', ', $missing) . ')' : ' (' . count($required) . ' required)'));
    }

    foreach ($definitions as $name => $expected) {
        list($type, $nullable, $extra) = $expected;
        $row = isset($columns[$name]) ? $columns[$name] : null;
        $ok = $row && $row['DATA_TYPE'] === $type && ($row['IS_NULLABLE'] === 'YES') === $nullable
            && ($extra === null
                || (is_int($extra) ? (int) $row['CHARACTER_MAXIMUM_LENGTH'] === $extra : stripos($row['COLUMN_TYPE'], $extra) !== false));
        $check($ok, 'Definition: ' . $name . ' (' . strtoupper($type) . (is_int($extra) ? '(' . $extra . ')' : ($extra ? ' ' . strtoupper($extra) : ''))
            . ($nullable ? ' NULL' : ' NOT NULL') . ')');
    }

    $found = array();
    foreach ($query('SELECT TABLE_NAME, INDEX_NAME, NON_UNIQUE, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS cols
        FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() GROUP BY TABLE_NAME, INDEX_NAME, NON_UNIQUE') as $row) {
        $found[$row['TABLE_NAME'] . '.' . $row['INDEX_NAME']] = $row;
    }
    foreach ($indexes as $name => $expected) {
        list($table, $cols, $unique) = $expected;
        $row = isset($found[$table . '.' . $name]) ? $found[$table . '.' . $name] : null;
        $check($row && $row['cols'] === $cols && ((int) $row['NON_UNIQUE'] === 0) === $unique,
            'Index: ' . $table . '.' . $name . ($unique ? ' UNIQUE' : '') . ' (' . $cols . ')');
    }

    $found = array();
    foreach ($query('SELECT k.TABLE_NAME, k.COLUMN_NAME, k.REFERENCED_TABLE_NAME, k.REFERENCED_COLUMN_NAME, r.DELETE_RULE
        FROM information_schema.KEY_COLUMN_USAGE k
        JOIN information_schema.REFERENTIAL_CONSTRAINTS r
            ON r.CONSTRAINT_SCHEMA = k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME = k.CONSTRAINT_NAME
        WHERE k.TABLE_SCHEMA = DATABASE()') as $row) {
        $found[$row['TABLE_NAME'] . '.' . $row['COLUMN_NAME']] = $row['REFERENCED_TABLE_NAME'] . '.' . $row['REFERENCED_COLUMN_NAME'] . ' ' . $row['DELETE_RULE'];
    }
    foreach ($foreignKeys as $fk) {
        list($table, $column, $reference, $rule) = $fk;
        $key = $table . '.' . $column;
        $check(isset($found[$key]) && $found[$key] === $reference . ' ' . $rule,
            'Foreign key: ' . $key . ' -> ' . $reference . ' ON DELETE ' . $rule);
    }

    $constraint = $query('SELECT CHECK_CLAUSE FROM information_schema.CHECK_CONSTRAINTS
        WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = :table AND CONSTRAINT_NAME = :constraint',
        array(':table' => 'development_charges', ':constraint' => 'chk_charge_record_year'));
    // Ignore MariaDB formatting of the known migration expression; never write test rows.
    $clause = $constraint ? preg_replace('/[\s`()]+/', '', strtolower($constraint[0]['CHECK_CLAUSE'])) : '';
    $check($clause === 'record_yearisnullorrecord_yearbetween1000and9999', 'Check constraint: chk_charge_record_year (NULL or 1000-9999)');

    if (isset($present['users'])) {
        $admins = $query("SELECT COUNT(*) AS total, SUM(department IS NULL OR TRIM(department) = '') AS global_admins FROM users WHERE role = 'admin'");
        $check((int) $admins[0]['total'] > 0, 'Administrator exists (php scripts/create-admin.php)');
        // Caj Pemajuan notifications and the full Audit Log are visible to global administrators only.
        $check((int) $admins[0]['global_admins'] > 0, 'Global administrator exists (admin without Jabatan)');
    }
} catch (Throwable $e) {
    $check(false, 'Database: ' . $e->getMessage());
}
exit($failed ? 1 : 0);
