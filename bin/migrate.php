<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';
if (PHP_SAPI !== 'cli') {
    exit();
}
try {
    $db = Exam\DB::connect();
    Exam\Migrator::run($db);
    echo "Schema ready. Migrations applied (existing records retained).\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Migration failed: ' . $e->getMessage() . "\n");
    exit(1);
}
