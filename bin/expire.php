<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';
if (PHP_SAPI !== 'cli') {
    exit();
}
echo new Exam\AttemptService(Exam\DB::connect())->expire() . " expired attempts finalized.\n";