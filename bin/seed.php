<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';
require ROOT . '/database/Seeder.php';
if (PHP_SAPI !== 'cli') {
    exit();
}
try {
    $result = Exam\Seeder::run(Exam\DB::connect());
    echo "Sample data ready: {$result['users']} accounts, {$result['exams']} exams.\nRoles: admin / instructor / student.\nPassword comes from SEED_PASSWORD. Existing accounts and examinations are not overwritten.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Seeder failed: ' . $e->getMessage() . "\n");
    exit(1);
}