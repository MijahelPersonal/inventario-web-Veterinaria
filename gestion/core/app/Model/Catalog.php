<?php
declare(strict_types=1);
namespace App\Model;
use PDO;
// Centraliza consultas PDO para los catálogos definidos por el servidor.
final class Catalog
{
    public function __construct(private PDO $db) {}
    public function search(array $definition, string $search, int $page): array
    {
        $table = $definition['table'];
        $where = 'name LIKE ?';
        $parameters = ['%' . $search . '%'];
        if ($table === 'products') {
            $where = '(name LIKE ? OR code LIKE ? OR barcode LIKE ?)';
            $parameters = array_fill(0, 3, '%' . $search . '%');
        }
        $query = $this->db->prepare("SELECT COUNT(*) FROM $table WHERE $where");
        $query->execute($parameters);
        $total = (int) $query->fetchColumn();
        $offset = ($page - 1) * 25;
        $query = $this->db->prepare(
            "SELECT * FROM $table WHERE $where ORDER BY id DESC LIMIT 25 OFFSET $offset",
        );
        $query->execute($parameters);
        return ['rows' => $query->fetchAll(), 'total' => $total];
    }
    public function find(string $table, int $id): ?array
    {
        $query = $this->db->prepare("SELECT * FROM $table WHERE id=?");
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }
    public function choices(string $table): array
    {
        return $this->db->query("SELECT id,name,active FROM $table ORDER BY name")->fetchAll();
    }
    public function save(string $table, array $data, ?int $id): int
    {
        $fields = array_keys($data);
        if ($id) {
            $sets = implode(',', array_map(static fn($field) => "$field=?", $fields));
            $this->db
                ->prepare("UPDATE $table SET $sets WHERE id=?")
                ->execute([...array_values($data), $id]);
            return $id;
        }
        $columns = implode(',', $fields);
        $holders = implode(',', array_fill(0, count($fields), '?'));
        $this->db
            ->prepare("INSERT INTO $table ($columns) VALUES ($holders)")
            ->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }
}
