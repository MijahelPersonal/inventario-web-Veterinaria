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

$local = __DIR__ . '/local.php';
return is_file($local) ? array_replace_recursive($config, require $local) : $config;
