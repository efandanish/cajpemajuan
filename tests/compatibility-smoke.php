<?php
require dirname(__DIR__) . '/scripts/bootstrap.php';

function verify($condition, $message)
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo 'PASS ' . $message . "\n";
}

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
foreach (array(
    '/index.php' => '/',
    '/public/index.php' => '/',
    '/renamed-app/public/index.php' => '/renamed-app/',
    '/public-office/public/index.php' => '/public-office/'
) as $script => $expected) {
    $_SERVER['SCRIPT_NAME'] = $script;
    $request = new Request();
    verify($request->getBaseUrl() === $expected, 'URL root for ' . $script);
}

$_SERVER['HTTP_HOST'] = 'localhost:8080';
$_SERVER['SCRIPT_NAME'] = '/index.php';
verify((new Request())->root() === 'http://localhost:8080/', 'URL root preserves development server port');
$_SERVER['HTTP_HOST'] = 'localhost';

$method = new ReflectionMethod('Database', 'getPDOType');
$method->setAccessible(true);
foreach (array(
    array(0, PDO::PARAM_INT), array(42, PDO::PARAM_INT),
    array(false, PDO::PARAM_BOOL), array(true, PDO::PARAM_BOOL),
    array(null, PDO::PARAM_NULL), array('', PDO::PARAM_STR),
    array('0', PDO::PARAM_STR), array('abc', PDO::PARAM_STR),
    array(12.5, PDO::PARAM_STR)
) as $case) {
    verify($method->invoke(null, $case[0]) === $case[1], 'PDO type for ' . var_export($case[0], true));
}

foreach (array(400, 401, 403, 404, 500) as $code) {
    $response = (new ErrorsController())->error($code);
    verify($response instanceof Response, 'Error response ' . $code);
}
$mailer = new \PHPMailer\PHPMailer\PHPMailer();
verify($mailer->addAddress('test@example.com'), 'Mail dependency loads and accepts recipient');
$captcha = new \Gregwar\Captcha\CaptchaBuilder();
$captcha->build();
verify(strlen($captcha->get()) > 0, 'CAPTCHA renders with GD');
echo "Compatibility smoke checks passed.\n";
