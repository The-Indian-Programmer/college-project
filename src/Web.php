<?php
declare(strict_types=1);
namespace Exam;
final class Web
{
    public static function boot(): void
    {
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('examina_session');
        session_set_cookie_params([
            'httponly' => true,
            'secure' => Config::bool('SESSION_SECURE'),
            'samesite' => 'Lax',
            'path' => '/',
        ]);
        session_start();
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('X-Frame-Options: DENY');
        header(
            "Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; connect-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'",
        );
        header('Cache-Control: no-store');
    }
    public static function e(mixed $v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    public static function url(string $path, array $params = []): string
    {
        return 'index.php?' . http_build_query(['r' => $path] + $params);
    }
    public static function csrf(): string
    {
        return '<input type="hidden" name="_csrf" value="' . self::e($_SESSION['csrf']) . '">';
    }
    public static function verifyCsrf(mixed $value): void
    {
        if (!is_string($value) || !hash_equals($_SESSION['csrf'] ?? '', $value)) {
            throw new DomainError(
                'Your session token has expired. Refresh the page and try again.',
                419,
            );
        }
    }
    public static function redirect(string $route, array $params = []): never
    {
        header('Location: ' . self::url($route, $params));
        exit();
    }
    public static function flash(string $message): void
    {
        $_SESSION['flash'] = $message;
    }
    public static function view(string $template, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require ROOT . '/views/' . $template . '.php';
        $content = ob_get_clean();
        require ROOT . '/views/layout.php';
    }
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_THROW_ON_ERROR);
        exit();
    }
    public static function date(?string $value, string $format = 'd M Y, h:i A'): string
    {
        if (!$value) {
            return '—';
        }
        return new \DateTimeImmutable($value, new \DateTimeZone('UTC'))
            ->setTimezone(new \DateTimeZone(Config::get('APP_TIMEZONE', 'Asia/Kolkata')))
            ->format($format);
    }
    public static function selected(mixed $a, mixed $b): string
    {
        return (string) $a === (string) $b ? ' selected' : '';
    }
}