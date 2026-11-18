<?php
declare(strict_types=1);
namespace Exam;
use PDO;
final class Migrator
{
    public static function run(PDO $db): void
    {
        $db->exec(file_get_contents(ROOT . '/database/schema.sql'));
        $column = DB::one(
            $db,
            "SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='exams' AND COLUMN_NAME='assigned_to'",
        );
        if (!$column) {
            $db->exec(
                'ALTER TABLE exams ADD assigned_to INT NULL AFTER created_by, ADD CONSTRAINT fk_exam_instructor FOREIGN KEY (assigned_to) REFERENCES users(user_id) ON DELETE RESTRICT',
            );
        }
        DB::run(
            $db,
            "INSERT IGNORE INTO schema_migrations(version) VALUES('002_responsible_instructor')",
        );
    }
}
