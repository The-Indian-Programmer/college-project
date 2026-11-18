<?php
declare(strict_types=1);
namespace Exam;
final class Config
{
    private static array $values = [];
    public static function load(string $file): void
    {
        if (!is_file($file)) {
            return;
        }
        foreach (file($file, FILE_IGNORE_NEW_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$key, $value] = explode('=', $line, 2);
            self::$values[trim($key)] = trim(trim($value), "\"'");
        }
    }
    public static function get(string $key, string $default = ''): string
    {
        $env = getenv($key);
        return $env !== false ? $env : self::$values[$key] ?? $default;
    }
    public static function bool(string $key, bool $default = false): bool
    {
        return filter_var(self::get($key, $default ? 'true' : 'false'), FILTER_VALIDATE_BOOLEAN);
    }
}
