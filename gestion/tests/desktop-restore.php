<?php
declare(strict_types=1);
// Roundtrip real de una fixture aislada. No acredita restaurar todo el esquema de Gestión.
putenv('STRUCH_TESTING=1');
putenv('STRUCH_DESKTOP_DATA');
putenv('STRUCH_DESKTOP_CONFIG');
$config = require dirname(__DIR__) . '/config/bootstrap.php';
if (!str_ends_with($config['database']['name'], '_test')) {
    throw new RuntimeException('Esta prueba requiere una base separada terminada en _test.');
}

// Limita SOLO la enumeración del respaldo a una tabla creada por esta prueba.
// Las transacciones, consultas, quoting y escritura SQL son los de DesktopMaintenance real.
final class RestoreFixturePdo extends PDO
{
    public string $fixture = '';
    public function query(
        string $query,
        ?int $fetchMode = null,
        mixed ...$fetchModeArgs,
    ): PDOStatement|false {
        if ($query === 'SHOW TABLE STATUS') {
            if (!preg_match('/^desktop_restore_[a-f0-9]{16}_test$/D', $this->fixture)) {
                throw new RuntimeException('No hay una fixture segura seleccionada.');
            }
            $query = 'SHOW TABLE STATUS WHERE Name=' . $this->quote($this->fixture);
        }
        return $fetchMode === null
            ? parent::query($query)
            : parent::query($query, $fetchMode, ...$fetchModeArgs);
    }
}
$d = $config['database'];
$db = new RestoreFixturePdo(
    sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $d['host'], $d['port'], $d['name']),
    $d['username'],
    $d['password'],
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
);
$database = (string) $db->query('SELECT DATABASE()')->fetchColumn();
if ($database !== $d['name'] || !str_ends_with($database, '_test')) {
    throw new RuntimeException('La conexión no corresponde a la base de pruebas.');
}
$check = static function (bool $ok, string $message): void {
    if (!$ok) {
        throw new RuntimeException($message);
    }
};
$quoteIdentifier = static function (string $name): string {
    if (!preg_match('/^desktop_restore_[a-f0-9]{16}_test(?:_source)?$/D', $name)) {
        throw new RuntimeException('Nombre de tabla de prueba no autorizado.');
    }
    return '`' . $name . '`';
};
$tablesBefore = $db
    ->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")
    ->fetchAll(PDO::FETCH_COLUMN);
$originalMode = (string) $db->query('SELECT @@session.sql_mode')->fetchColumn();
$directory = sys_get_temp_dir() . '/struchvet-restore-' . bin2hex(random_bytes(8));
mkdir($directory, 0700);
putenv('STRUCH_DESKTOP_DATA=' . $directory);
Core\Paths::prepare();
ini_set('error_log', Core\Paths::storage('logs/php.log'));
$ownershipMarker = 'struchvet-restore-' . bin2hex(random_bytes(16));
$owned = [];
try {
    foreach ([false, true] as $noBackslashes) {
        $name = 'desktop_restore_' . bin2hex(random_bytes(8)) . '_test';
        $source = $name . '_source';
        $check(
            !in_array($name, $tablesBefore, true) && !in_array($source, $tablesBefore, true),
            'Colisión inesperada de tablas de prueba.',
        );
        $owned = [$name, $source];
        $targetSql = $quoteIdentifier($name);
        $sourceSql = $quoteIdentifier($source);
        $db->fixture = $name;
        $modes = array_values(
            array_filter(
                explode(',', $originalMode),
                static fn(string $mode): bool => $mode !== 'NO_BACKSLASH_ESCAPES',
            ),
        );
        if ($noBackslashes) {
            $modes[] = 'NO_BACKSLASH_ESCAPES';
        }
        $db->exec('SET SESSION sql_mode=' . $db->quote(implode(',', $modes)));
        $db->exec("SET time_zone='-05:00'");
        $db->exec(
            'CREATE TABLE ' .
                $targetSql .
                ' (id INT NOT NULL PRIMARY KEY,value_text LONGTEXT NULL,value_decimal DECIMAL(14,3) NOT NULL,value_binary LONGBLOB NULL,value_json JSON NULL,created_at TIMESTAMP NULL DEFAULT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT=' .
                $db->quote($ownershipMarker),
        );
        $rows = [
            [
                1,
                "Niña 雪 🐾 ' comillas dobles \" y barra \\\nsegunda línea\r\0\x1a; SELECT 'solo texto'; --",
                '12345.670',
                "\0\xff\xfe\x1a\\'\"\r\n",
                json_encode(
                    ['animal' => 'gatita ñ', 'quote' => "'\\\"", 'null' => null],
                    JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE,
                ),
                '2026-10-30 09:15:00',
            ],
            [2, null, '0.001', null, null, null],
            [3, '', '-17.125', '', '{}', '2020-02-29 23:59:59'],
        ];
        $insert = $db->prepare('INSERT INTO ' . $targetSql . ' VALUES (?,?,?,?,?,?)');
        foreach ($rows as $row) {
            $insert->execute($row);
        }
        $read = static fn(string $table): array => $db
            ->query('SELECT * FROM ' . $table . ' ORDER BY id')
            ->fetchAll();
        $expected = $read($targetSql);
        $service = new App\Service\DesktopMaintenance($db, dirname(__DIR__));
        $backup = $service->backup();
        $sql = file_get_contents($backup);
        $check(
            substr_count($sql, 'CREATE TABLE `') === 1,
            'El respaldo de fixture incluyó tablas ajenas.',
        );
        $check(!glob($directory . '/backups/*.partial'), 'Se publicó un respaldo parcial.');
        // Renombrar solo la fixture propia deja su nombre libre. Se restaura el SQL intacto, sin parser ni reemplazos.
        $db->exec('RENAME TABLE ' . $targetSql . ' TO ' . $sourceSql);
        $db->exec($sql);
        $actual = $read($targetSql);
        $check(
            $actual === $expected,
            'La restauración alteró valores, tipos, NULL, precisión decimal o bytes.',
        );
        $check(
            hash('sha256', serialize($actual)) === hash('sha256', serialize($read($sourceSql))),
            'El hash de las filas restauradas no coincide.',
        );
        $targetSchema = $db->query('SHOW CREATE TABLE ' . $targetSql)->fetch(PDO::FETCH_NUM)[1];
        $sourceSchema = $db->query('SHOW CREATE TABLE ' . $sourceSql)->fetch(PDO::FETCH_NUM)[1];
        $check(
            $targetSchema ===
                str_replace(
                    'CREATE TABLE ' . $sourceSql,
                    'CREATE TABLE ' . $targetSql,
                    $sourceSchema,
                ),
            'El esquema restaurado no coincide con la fixture.',
        );
        foreach ([$name, $source] as $table) {
            $metadata = $db->query('SHOW TABLE STATUS WHERE Name=' . $db->quote($table))->fetch();
            $check(
                ($metadata['Comment'] ?? '') === $ownershipMarker,
                'Se detuvo la limpieza: tabla sin marcador de propiedad.',
            );
        }
        $db->exec('DROP TABLE ' . $targetSql . ',' . $sourceSql);
        $owned = [];
        echo 'OK: backup→restore real de fixture InnoDB, SQL ' .
            ($noBackslashes ? 'NO_BACKSLASH_ESCAPES' : 'normal') .
            ": esquema, filas/hash, Unicode, comillas, barras, binarios, NULL, decimales y timestamps preservados.\n";
    }
} finally {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    foreach ($owned as $name) {
        // El guard exige nombres generados por esta ejecución y la conexión continúa siendo *_test.
        if ((string) $db->query('SELECT DATABASE()')->fetchColumn() !== $database) {
            throw new RuntimeException('Se detuvo la limpieza porque cambió la base de datos.');
        }
        $metadata = $db->query('SHOW TABLE STATUS WHERE Name=' . $db->quote($name))->fetch();
        if ($metadata) {
            $check(
                ($metadata['Comment'] ?? '') === $ownershipMarker,
                'No se borrará una tabla ajena a esta ejecución.',
            );
            $db->exec('DROP TABLE ' . $quoteIdentifier($name));
        }
    }
    $db->exec('SET SESSION sql_mode=' . $db->quote($originalMode));
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($files as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
    rmdir($directory);
    putenv('STRUCH_DESKTOP_DATA');
}
$tablesAfter = $db
    ->query("SHOW FULL TABLES WHERE Table_type='BASE TABLE'")
    ->fetchAll(PDO::FETCH_COLUMN);
sort($tablesBefore);
sort($tablesAfter);
$check(
    $tablesAfter === $tablesBefore,
    'Cambió el conjunto de tablas que existía antes de la prueba.',
);
echo "OK: solo se crearon, renombraron y borraron fixtures propias; tablas originales conservadas. Restauración completa de Gestión y sus claves foráneas sigue pendiente.\n";
