<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$config = require dirname(__DIR__) . '/config/bootstrap.php';
$db = Core\Database::connect($config['database']);
$db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (name VARCHAR(190) PRIMARY KEY,applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
foreach (glob(dirname(__DIR__) . '/database/migrations/*.sql') as $file) {
    $name = basename($file);
    $check = $db->prepare('SELECT name FROM schema_migrations WHERE name=?'); $check->execute([$name]);
    if ($check->fetch()) continue;
    // DDL en MySQL hace commit implícito: detenerse para inspección si algo falla.
    try {
        $db->exec(file_get_contents($file));
        $db->prepare('INSERT INTO schema_migrations(name) VALUES (?)')->execute([$name]);
        echo "Aplicada: $name\n";
    } catch (Throwable $error) { fwrite(STDERR,"Falló $name. Revisar esquema antes de reintentar.\n" . $error->getMessage()); exit(1); }
}
