<?php
declare(strict_types=1);
namespace Core;
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) return;
        session_name('struch_vet');
        session_start(['use_strict_mode' => 1, 'cookie_httponly' => true,
            'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', 'cookie_samesite' => 'Lax']);
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }
    public static function flash(string $message, string $type = 'success'): void { $_SESSION['flash'] = compact('message', 'type'); }
    public static function consumeFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
