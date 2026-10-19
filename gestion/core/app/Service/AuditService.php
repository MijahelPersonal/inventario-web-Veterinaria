<?php
declare(strict_types=1);
namespace App\Service;
use PDO;
final class AuditService
{
    public function __construct(private PDO $db) {}
    public function record(
        ?int $userId,
        string $action,
        string $entity,
        ?int $entityId = null,
        array $details = [],
    ): void {
        $query = $this->db->prepare(
            'INSERT INTO audit_events (user_id,action,entity,entity_id,details) VALUES (?,?,?,?,?)',
        );
        $query->execute([
            $userId,
            $action,
            $entity,
            $entityId,
            json_encode($details, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ]);
    }
}
