<?php
declare(strict_types=1);
namespace Exam;
use PDO;
final class DB
{
    public static function connect(?string $database = null): PDO
    {
        $name = $database ?? Config::get('DB_DATABASE', 'online_examination_system');
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $name)) {
            throw new \RuntimeException('Invalid database name.');
        }
        $pdo = new PDO(
            'mysql:host=' .
                Config::get('DB_HOST', '127.0.0.1') .
                ';port=' .
                Config::get('DB_PORT', '3306') .
                ';dbname=' .
                $name .
                ';charset=utf8mb4',
            Config::get('DB_USERNAME', 'exam_app'),
            Config::get('DB_PASSWORD'),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
        $pdo->exec("SET time_zone = '+00:00'");
        $pdo->exec(
            "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'",
        );
        $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED');
        return $pdo;
    }
    public static function all(PDO $db, string $sql, array $args = []): array
    {
        $q = $db->prepare($sql);
        $q->execute($args);
        return $q->fetchAll();
    }
    public static function one(PDO $db, string $sql, array $args = []): ?array
    {
        $q = $db->prepare($sql);
        $q->execute($args);
        return $q->fetch() ?: null;
    }
    public static function run(PDO $db, string $sql, array $args = []): int
    {
        $q = $db->prepare($sql);
        $q->execute($args);
        return $q->rowCount();
    }
    public static function transaction(PDO $db, callable $work): mixed
    {
        $db->beginTransaction();
        try {
            $result = $work();
            $db->commit();
            return $result;
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}
