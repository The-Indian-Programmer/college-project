<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';
Exam\Web::boot();
$app = null;
try {
    $app = new Exam\App(Exam\DB::connect());
    $app->run();
} catch (Throwable $error) {
    if ($app) {
        $app->error($error);
    } else {
        error_log((string) $error);
        http_response_code(503);
        Exam\Web::view('error', [
            'title' => 'Application unavailable',
            'user' => null,
            'route' => '/login',
            'status' => 503,
            'message' =>
                'The application cannot connect to its database. Follow the database setup steps in README.md, then refresh this page.',
        ]);
    }
}
