<?php
declare(strict_types=1);
namespace Core;
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        // Las pruebas del navegador usan otra cookie para no cerrar la sesión de trabajo.
        session_name(getenv('STRUCH_TESTING') === '1' ? 'struch_vet_test' : 'struch_vet');
        session_start([
            'use_strict_mode' => 1,
            'cookie_httponly' => true,
            'cookie_secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'cookie_samesite' => 'Lax',
        ]);
        $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    }
    public static function flash(string $message, string $type = 'success'): void
    {
        $_SESSION['flash'] = compact('message', 'type');
    }
    public static function consumeFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }
}
