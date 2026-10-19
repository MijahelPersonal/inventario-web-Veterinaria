<?php
declare(strict_types=1);
namespace App\Model;
use PDO;

// Consulta la agenda y bloquea registros para que dos personas no reserven el mismo horario.
final class Appointment
{
    public function __construct(private PDO $db) {}
    public function hasBill(int $id): bool
    {
        $query = $this->db->prepare(
            "SELECT id FROM sales WHERE appointment_id=? AND status='confirmed'",
        );
        $query->execute([$id]);
        return (bool) $query->fetch();
    }
    public function pets(): array
    {
        return $this->db
            ->query(
                'SELECT p.id,CONCAT(p.name," — ",c.name) name,p.active FROM pets p JOIN clients c ON c.id=p.client_id WHERE c.active=1 ORDER BY p.name',
            )
            ->fetchAll();
    }
    public function between(string $start, string $end, ?int $vet = null): array
    {
        $sql =
            'SELECT a.*,p.name pet_name,c.name client_name,s.name service_name,v.name veterinarian_name FROM appointments a JOIN pets p ON p.id=a.pet_id JOIN clients c ON c.id=p.client_id JOIN services s ON s.id=a.service_id JOIN veterinarians v ON v.id=a.veterinarian_id WHERE a.start_at>=? AND a.start_at<?';
        $values = [$start, $end];
        if ($vet) {
            $sql .= ' AND a.veterinarian_id=?';
            $values[] = $vet;
        }
        $query = $this->db->prepare($sql . ' ORDER BY a.start_at LIMIT 1000');
        $query->execute($values);
        return $query->fetchAll();
    }
    public function find(int $id, bool $lock = false): ?array
    {
        $query = $this->db->prepare(
            'SELECT * FROM appointments WHERE id=?' . ($lock ? ' FOR UPDATE' : ''),
        );
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }
    public function veterinarian(int $id): ?array
    {
        $query = $this->db->prepare('SELECT * FROM veterinarians WHERE id=? FOR UPDATE');
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }
    public function hours(int $vet, int $weekday): ?array
    {
        $query = $this->db->prepare(
            'SELECT * FROM veterinarian_hours WHERE veterinarian_id=? AND weekday=?',
        );
        $query->execute([$vet, $weekday]);
        return $query->fetch() ?: null;
    }
    public function hasHours(int $vet): bool
    {
        $query = $this->db->prepare(
            'SELECT COUNT(*) FROM veterinarian_hours WHERE veterinarian_id=?',
        );
        $query->execute([$vet]);
        return (int) $query->fetchColumn() > 0;
    }
    public function overlaps(int $vet, string $start, string $end): bool
    {
        $query = $this->db->prepare(
            "SELECT id FROM appointments WHERE veterinarian_id=? AND status IN ('solicitada','confirmada','atendida') AND start_at<? AND end_at>? LIMIT 1 FOR UPDATE",
        );
        $query->execute([$vet, $end, $start]);
        return (bool) $query->fetchColumn();
    }
    public function create(array $data): int
    {
        $query = $this->db->prepare(
            'INSERT INTO appointments(pet_id,veterinarian_id,service_id,start_at,end_at,price,source,notes,user_id,request_key) VALUES (?,?,?,?,?,?,?,?,?,?)',
        );
        $query->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }
    public function changeStatus(int $id, string $status, string $notes): void
    {
        $this->db
            ->prepare('UPDATE appointments SET status=?,notes=? WHERE id=?')
            ->execute([$status, $notes, $id]);
    }
    public function byRequest(string $key): ?array
    {
        $query = $this->db->prepare('SELECT * FROM appointments WHERE request_key=?');
        $query->execute([$key]);
        return $query->fetch() ?: null;
    }
    public function saveHours(int $vet, int $day, string $start, string $end): void
    {
        $this->db
            ->prepare(
                'INSERT INTO veterinarian_hours(veterinarian_id,weekday,start_time,end_time) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE start_time=?,end_time=?',
            )
            ->execute([$vet, $day, $start, $end, $start, $end]);
    }
}
