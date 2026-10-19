<?php
declare(strict_types=1);
namespace App\Model;
use PDO;
final class User
{
    public function __construct(private PDO $db) {}
    public function byEmail(string $email): ?array
    {
        $query = $this->db->prepare('SELECT * FROM users WHERE email=? AND active=1');
        $query->execute([$email]); return $query->fetch() ?: null;
    }
    public function byId(int $id): ?array
    {
        $query = $this->db->prepare('SELECT id,name,email FROM users WHERE id=? AND active=1');
        $query->execute([$id]); return $query->fetch() ?: null;
    }
    public function permissions(int $id): array
    {
        $query = $this->db->prepare('SELECT DISTINCT rp.permission FROM user_roles ur JOIN role_permissions rp ON rp.role_id=ur.role_id WHERE ur.user_id=?');
        $query->execute([$id]); return $query->fetchAll(PDO::FETCH_COLUMN);
    }
}
