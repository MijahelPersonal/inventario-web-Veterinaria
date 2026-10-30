<?php
declare(strict_types=1);

$config = [
    'name' => 'STRUCH VET',
    'environment' => 'development',
    'timezone' => 'America/Lima',
    'database' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'struch_vet',
        'username' => '',
        'password' => '',
    ],
];

$desktop = (string) getenv('STRUCH_DESKTOP_CONFIG');
if (getenv('STRUCH_DESKTOP_DATA')) {
    if ($desktop === '') { throw new RuntimeException('Falta la configuración de StruchVet Desktop.'); }
    if (!is_file($desktop)) {
        throw new RuntimeException('Configura la conexión a la base de datos desde StruchVet Desktop.');
    }
    $private = json_decode(file_get_contents($desktop), true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($private) || !is_array($private['database'] ?? null)) {
        throw new RuntimeException('La configuración de escritorio no es válida.');
    }
    $config['database'] = array_replace($config['database'], array_intersect_key($private['database'], array_flip(['host', 'port', 'name', 'username', 'password', 'ssl_ca'])));
    foreach (['host', 'name', 'username', 'password'] as $key) {
        if (!is_string($config['database'][$key])) {
            throw new RuntimeException('La configuración de la base de datos no es válida.');
        }
    }
    if (!preg_match('/^[a-zA-Z0-9.:-]+$/D', $config['database']['host']) || !preg_match('/^[a-zA-Z0-9_]{1,64}$/D', $config['database']['name']) || !is_numeric($config['database']['port']) || (int) $config['database']['port'] < 1 || (int) $config['database']['port'] > 65535) {
        throw new RuntimeException('Revisa el nombre y el puerto de la base de datos.');
    }
    $config['environment'] = 'production';
    return $config;
}
$local = __DIR__ . '/local.php';
return is_file($local) ? array_replace_recursive($config, require $local) : $config;
