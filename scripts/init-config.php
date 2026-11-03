<?php
// One-time configuration setup for a NEW installation. Never overwrites existing files
// and never prints the generated keys. Usage: php scripts/init-config.php
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$configDir = dirname(__DIR__) . '/app/config/';
$config = $configDir . 'config.php';
$local = $configDir . 'local.php';

try {
    if (file_exists($config) || file_exists($local)) {
        throw new RuntimeException('app/config/config.php or app/config/local.php already exists. '
            . 'Existing installations must keep their private keys; no file was changed.');
    }
    if (!copy($configDir . 'config.example.php', $config)) {
        throw new RuntimeException('Could not create app/config/config.php.');
    }
    $values = require $configDir . 'local.example.php';
    foreach (array('COOKIE_SECRET_KEY', 'ENCRYPTION_KEY', 'HMAC_SALT', 'HASH_KEY') as $key) {
        $values[$key] = bin2hex(random_bytes(32));
    }
    $previous = umask(0077);
    $written = file_put_contents($local, "<?php\n// Private installation settings. Never commit or share this file.\nreturn " . var_export($values, true) . ";\n");
    umask($previous);
    if ($written === false) {
        unlink($config);
        throw new RuntimeException('Could not create app/config/local.php.');
    }
    echo "Created app/config/config.php and app/config/local.php (unique security keys generated).\n"
        . "Next: edit app/config/local.php and replace DB_HOST, DB_NAME, DB_USER and DB_PASS.\n";
} catch (Throwable $e) {
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}
