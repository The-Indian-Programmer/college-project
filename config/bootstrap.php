<?php
declare(strict_types=1);
define('ROOT', dirname(__DIR__));
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'Exam\\')) {
        $file = ROOT . '/src/' . substr($class, 5) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});
$envFile = getenv('ENV_FILE') ?: ROOT . '/.env';
// PHP's web server changes its working directory to public/ for requests.
if (!str_starts_with($envFile, '/') && !preg_match('/^[A-Za-z]:[\\\\\/]/', $envFile)) {
    $envFile = ROOT . '/' . $envFile;
}
Exam\Config::load($envFile);
date_default_timezone_set('UTC');
