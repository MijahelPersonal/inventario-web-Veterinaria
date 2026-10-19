<?php
declare(strict_types=1);
namespace App\Model;
use PDO;

// Relaciona mascotas y veterinarios con sus citas sin borrar atenciones anteriores.
final class ClinicalRecord
{
    public function __construct(private PDO $db) {}
    public function appointments(string $kind, int $id): array
    {
        $column = $kind === 'mascotas' ? 'pet_id' : 'veterinarian_id';
        $query = $this->db->prepare(
            'SELECT a.*,p.name pet_name,c.name client_name,s.name service_name,v.name veterinarian_name FROM appointments a JOIN pets p ON p.id=a.pet_id JOIN clients c ON c.id=p.client_id JOIN services s ON s.id=a.service_id JOIN veterinarians v ON v.id=a.veterinarian_id WHERE a.' .
                $column .
                '=? ORDER BY a.start_at DESC LIMIT 100',
        );
        $query->execute([$id]);
        return $query->fetchAll();
    }
    public function appointment(int $id): ?array
    {
        $query = $this->db->prepare(
            'SELECT a.*,p.name pet_name,p.species,p.breed,p.weight,p.allergies,c.name client_name,c.phone client_phone,v.name veterinarian_name,v.license_number,s.name service_name FROM appointments a JOIN pets p ON p.id=a.pet_id JOIN clients c ON c.id=p.client_id JOIN veterinarians v ON v.id=a.veterinarian_id JOIN services s ON s.id=a.service_id WHERE a.id=?',
        );
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }
    public function saveNotes(int $id, string $symptoms, string $diagnosis, string $treatment): void
    {
        $this->db
            ->prepare('UPDATE appointments SET symptoms=?,diagnosis=?,treatment=? WHERE id=?')
            ->execute([$symptoms, $diagnosis, $treatment, $id]);
    }
    public function reschedule(int $id, int $vet, string $start, string $end): void
    {
        $this->db
            ->prepare('UPDATE appointments SET veterinarian_id=?,start_at=?,end_at=? WHERE id=?')
            ->execute([$vet, $start, $end, $id]);
    }
}
