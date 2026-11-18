<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';
$failed = false;
foreach (['pdo_mysql', 'mbstring', 'session', 'openssl'] as $ext) {
    $ok = extension_loaded($ext);
    echo ($ok ? 'OK  ' : 'FAIL') . " extension $ext\n";
    $failed = $failed || !$ok;
}
foreach (['storage/mail', 'storage/logs'] as $dir) {
    $ok = is_writable(ROOT . '/' . $dir);
    echo ($ok ? 'OK  ' : 'FAIL') . " writable $dir\n";
    $failed = $failed || !$ok;
}
try {
    $db = Exam\DB::connect();
    echo 'OK  database ' . $db->query('SELECT VERSION()')->fetchColumn() . "\n";
    $v = Exam\DB::one($db, 'SELECT version FROM schema_migrations ORDER BY version DESC LIMIT 1');
    echo 'OK  schema ' . ($v['version'] ?? 'missing') . "\n";
} catch (Throwable $e) {
    echo 'FAIL database: ' . $e->getMessage() . "\n";
    $failed = true;
}
exit($failed ? 1 : 0);
