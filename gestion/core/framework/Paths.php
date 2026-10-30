<?php
declare(strict_types=1);
namespace Core;

// El código instalado permanece de solo lectura; los datos viven en el perfil del usuario.
final class Paths
{
    public static function desktop(): bool
    {
        return getenv('STRUCH_DESKTOP_DATA') !== false && getenv('STRUCH_DESKTOP_DATA') !== '';
    }
    public static function storage(string $relative = ''): string
    {
        if (
            $relative !== '' &&
            (!preg_match('/^[a-zA-Z0-9_.\/-]+$/D', $relative) ||
                in_array('..', explode('/', $relative), true))
        ) {
            throw new \InvalidArgumentException('Ruta de almacenamiento inválida.');
        }
        $data = (string) getenv('STRUCH_DESKTOP_DATA');
        if (self::desktop()) {
            if (
                !str_starts_with($data, '/') &&
                !preg_match('/^[A-Za-z]:[\\\\\/]/', $data) &&
                !str_starts_with($data, '\\\\')
            ) {
                throw new \RuntimeException(
                    'La carpeta de datos del escritorio debe ser absoluta.',
                );
            }
            $base = rtrim($data, '/\\') . '/storage';
        } else {
            $base = dirname(__DIR__, 2) . '/storage';
        }
        return $base . ($relative !== '' ? '/' . $relative : '');
    }
    public static function prepare(): void
    {
        foreach (['cache', 'logs', 'uploads/products', 'private', 'sessions'] as $directory) {
            $path = self::storage($directory);
            if (!is_dir($path) && !mkdir($path, 0700, true) && !is_dir($path)) {
                throw new \RuntimeException(
                    'No se pudo preparar la carpeta de datos del escritorio.',
                );
            }
        }
    }
}
