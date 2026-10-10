<?php
require __DIR__ . '/bootstrap.php';
$failed = false;
$check = function ($ok, $message) use (&$failed) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $message . "\n";
    if (!$ok) { $failed = true; }
};
$check(PHP_VERSION_ID >= 80000 && PHP_VERSION_ID < 80100, 'PHP 8.0.x only (running ' . PHP_VERSION . ')');
foreach (array('AuthController', 'ErrorsController', 'DashboardController', 'UserController', 'ProjectController', 'ChargeController', 'NotifyController', 'ReportController') as $controller) {
    $check(class_exists($controller), 'Controller: ' . $controller);
}
foreach (array('pdo', 'pdo_mysql', 'mbstring', 'gd', 'openssl', 'fileinfo') as $extension) {
    $check(extension_loaded($extension), 'PHP extension: ' . $extension);
}
foreach (array('app/logs', 'public/uploads/receipts', 'app/uploads', 'app/backups') as $directory) {
    $check(is_dir(BASE_DIR . '/' . $directory) && is_writable(BASE_DIR . '/' . $directory), 'Writable: ' . $directory);
}
try {
    $db = Database::openConnection();
    foreach (array('users', 'projects', 'project_expenses', 'project_payment_schedules', 'project_variations', 'development_charges', 'development_charge_payments', 'notifications', 'notification_reads') as $table) {
        $db->prepare('SELECT 1 FROM `' . $table . '` LIMIT 1');
        $db->execute();
        $check(true, 'Database table: ' . $table);
    }
    $db->prepare("SELECT COUNT(*) AS total FROM users WHERE role = 'admin'");
    $db->execute();
    $result = $db->fetchAssociative();
    $check((int)$result['total'] > 0, 'Administrator exists');
} catch (Throwable $e) {
    $check(false, 'Database: ' . $e->getMessage());
}
exit($failed ? 1 : 0);
