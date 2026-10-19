<?php
declare(strict_types=1);

namespace Core;

use PDO;
use RuntimeException;

final class Database
{
    public static function connect(array $config): PDO
    {
        if ($config['username'] === '') {
            throw new RuntimeException('La conexión a MySQL aún no está configurada.');
        }

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $config['host'],
            $config['port'],
            $config['name'],
        );

        $connection = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        // Fechas de caja y reportes usan la misma zona horaria de la aplicación.
        $connection->exec('SET time_zone = ' . $connection->quote(date('P')));
        return $connection;
    }
}
