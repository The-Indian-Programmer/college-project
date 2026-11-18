<?php
declare(strict_types=1);
namespace Exam;
final class Rules
{
    public static function text(mixed $value, string $label, int $max, int $min = 1): string
    {
        if (!is_string($value)) {
            throw new DomainError("$label is required.");
        }
        $value = trim($value);
        if (mb_strlen($value) < $min || mb_strlen($value) > $max) {
            throw new DomainError("$label must contain $min to $max characters.");
        }
        return $value;
    }
    public static function id(mixed $value): int
    {
        if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw new DomainError('A valid record identifier is required.');
        }
        return (int) $value;
    }
    public static function password(mixed $value): string
    {
        if (!is_string($value) || strlen($value) < 10 || strlen($value) > 72) {
            throw new DomainError('Use a password of 10 to 72 bytes.');
        }
        return $value;
    }
    public static function cents(mixed $value): int
    {
        $value = (string) $value;
        if (!preg_match('/^\d{1,4}(?:\.\d{1,2})?$/D', $value)) {
            throw new DomainError(
                'Marks must be a positive number with at most two decimal places (maximum 9999.99).',
            );
        }
        $parts = explode('.', $value);
        $cents = (int) $parts[0] * 100 + (int) str_pad($parts[1] ?? '', 2, '0');
        if ($cents <= 0) {
            throw new DomainError('Marks must be greater than zero.');
        }
        return $cents;
    }
    public static function money(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
    public static function date(string $value): string
    {
        $zone = new \DateTimeZone(Config::get('APP_TIMEZONE', 'Asia/Kolkata'));
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d\TH:i', $value, $zone);
        if (!$date || $date->format('Y-m-d\TH:i') !== $value) {
            throw new DomainError('Enter a valid examination date and time.');
        }
        return $date->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
    public static function question(array $input): array
    {
        $text = self::text($input['question_text'] ?? null, 'Question', 10000);
        $type = $input['question_type'] ?? '';
        if (!in_array($type, ['MCQ', 'TF'], true)) {
            throw new DomainError('Choose multiple choice or true/false.');
        }
        $marks = self::money(self::cents($input['marks'] ?? ''));
        $options = $input['options'] ?? [];
        if (!is_array($options) || count($options) < 2 || count($options) > 6) {
            throw new DomainError('Provide 2 to 6 answer options.');
        }
        $options = array_values(array_map(fn($v) => self::text($v, 'Option', 500), $options));
        if (count(array_unique($options)) !== count($options)) {
            throw new DomainError('Answer options must be distinct.');
        }
        if ($type === 'TF' && $options !== ['True', 'False']) {
            throw new DomainError(
                'True/false questions require exactly True and False, in that order.',
            );
        }
        $correct = filter_var($input['correct'] ?? null, FILTER_VALIDATE_INT);
        if ($correct === false || $correct < 0 || $correct >= count($options)) {
            throw new DomainError('Choose exactly one correct answer.');
        }
        return compact('text', 'type', 'marks', 'options', 'correct');
    }
    public static function role(array $user, array $roles): void
    {
        if (($user['status'] ?? '') !== 'active' || !in_array($user['role'] ?? '', $roles, true)) {
            throw new DomainError('You do not have permission for this action.', 403);
        }
    }
    public static function deadline(string $start, int $minutes, string $close): string
    {
        return min(gmdate('Y-m-d H:i:s', strtotime($start . ' UTC') + $minutes * 60), $close);
    }
}