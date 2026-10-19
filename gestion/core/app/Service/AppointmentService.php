<?php
declare(strict_types=1);
namespace App\Service;
use App\Model\Appointment;
use App\Model\Catalog;
use DateTimeImmutable;
use DomainException;
use PDO;

// Aplica disponibilidad, duración y transiciones sin confiar en los datos del calendario.
final class AppointmentService
{
    public const STATES = [
        'solicitada' => 'Solicitada',
        'confirmada' => 'Confirmada',
        'atendida' => 'Atendida',
        'cancelada' => 'Cancelada',
        'no_asistio' => 'No asistió',
    ];
    public function __construct(private PDO $db) {}
    public function create(array $input, int $userId): int
    {
        $start = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i',
            (string) ($input['start_at'] ?? ''),
        );
        if (!$start || $start->format('Y-m-d\TH:i') !== ($input['start_at'] ?? '')) {
            throw new DomainException('Fecha y hora inválidas.');
        }
        if ($start < new DateTimeImmutable('now')) {
            throw new DomainException('Selecciona un horario futuro.');
        }
        $key = (string) ($input['request_key'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/D', $key)) {
            throw new DomainException('Identificador de formulario inválido.');
        }
        $source = (string) ($input['source'] ?? 'manual');
        if (!in_array($source, ['manual', 'whatsapp'], true)) {
            throw new DomainException('Origen inválido.');
        }
        $notes = trim((string) ($input['notes'] ?? ''));
        if (mb_strlen($notes) > 3000) {
            throw new DomainException('Las notas son demasiado largas.');
        }
        $model = new Appointment($this->db);
        $catalog = new Catalog($this->db);
        $this->db->beginTransaction();
        try {
            $vet = $model->veterinarian((int) ($input['veterinarian_id'] ?? 0));
            if (!$vet || !$vet['active']) {
                throw new DomainException('Selecciona un veterinario activo.');
            }
            $previous = $model->byRequest($key);
            if ($previous) {
                if ((int) $previous['user_id'] !== $userId) {
                    throw new DomainException('Formulario inválido.');
                }
                $this->db->commit();
                return (int) $previous['id'];
            }
            $pet = $catalog->find('pets', (int) ($input['pet_id'] ?? 0));
            $service = $catalog->find('services', (int) ($input['service_id'] ?? 0));
            $client = $pet ? $catalog->find('clients', (int) $pet['client_id']) : null;
            if (
                !$pet ||
                !$pet['active'] ||
                !$client ||
                !$client['active'] ||
                !$service ||
                !$service['active']
            ) {
                throw new DomainException('Mascota, cliente o servicio no disponible.');
            }
            if (
                $vet['specialty_id'] &&
                $service['specialty_id'] &&
                $vet['specialty_id'] !== $service['specialty_id']
            ) {
                throw new DomainException(
                    'El veterinario no corresponde a la especialidad del servicio.',
                );
            }
            $end = $start->modify('+' . (int) $service['duration_minutes'] . ' minutes');
            $hours = $model->hours((int) $vet['id'], (int) $start->format('N'));
            if (!$hours && !$model->hasHours((int) $vet['id'])) {
                $hours = ['start_time' => '08:00:00', 'end_time' => '20:00:00'];
            }
            if (
                !$hours ||
                $start->format('Y-m-d') !== $end->format('Y-m-d') ||
                $start->format('H:i:s') < $hours['start_time'] ||
                $end->format('H:i:s') > $hours['end_time']
            ) {
                throw new DomainException('La cita está fuera del horario disponible.');
            }
            if (
                $model->overlaps(
                    (int) $vet['id'],
                    $start->format('Y-m-d H:i:s'),
                    $end->format('Y-m-d H:i:s'),
                )
            ) {
                throw new DomainException('El veterinario ya tiene una cita en ese horario.');
            }
            $id = $model->create([
                $pet['id'],
                $vet['id'],
                $service['id'],
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
                $service['price'],
                $source,
                $notes,
                $userId,
                $key,
            ]);
            new AuditService($this->db)->record($userId, 'create', 'appointments', $id);
            $this->db->commit();
            return $id;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
    public function changeStatus(int $id, string $status, string $notes, int $userId): void
    {
        if (!isset(self::STATES[$status]) || mb_strlen($notes) > 3000) {
            throw new DomainException('Estado o notas inválidos.');
        }
        $this->db->beginTransaction();
        try {
            $model = new Appointment($this->db);
            $record = $model->find($id, true);
            if (!$record) {
                throw new DomainException('La cita no existe.');
            }
            $allowed = [
                'solicitada' => ['confirmada', 'cancelada'],
                'confirmada' => ['atendida', 'cancelada', 'no_asistio'],
                'atendida' => [],
                'cancelada' => [],
                'no_asistio' => [],
            ];
            if ($status === 'cancelada' && $model->hasBill($id)) {
                throw new DomainException(
                    'Primero anula el cargo vinculado a la cita desde ventas.',
                );
            }
            if (
                $record['status'] !== $status &&
                !in_array($status, $allowed[$record['status']], true)
            ) {
                throw new DomainException(
                    'Transición no permitida. Las citas cerradas conservan su historial.',
                );
            }
            if (
                in_array($status, ['atendida', 'no_asistio'], true) &&
                $record['start_at'] > date('Y-m-d H:i:s')
            ) {
                throw new DomainException('La cita todavía no ha comenzado.');
            }
            $model->changeStatus($id, $status, $notes);
            new AuditService($this->db)->record($userId, 'status', 'appointments', $id, [
                'from' => $record['status'],
                'to' => $status,
            ]);
            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
    public function saveHours(array $input, int $userId): void
    {
        $day = (int) ($input['weekday'] ?? 0);
        $vet = (int) ($input['veterinarian_id'] ?? 0);
        $start = (string) ($input['start_time'] ?? '');
        $end = (string) ($input['end_time'] ?? '');
        if (
            $day < 1 ||
            $day > 7 ||
            !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D', $start) ||
            !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D', $end) ||
            $start >= $end
        ) {
            throw new DomainException('Horario inválido.');
        }
        $this->db->beginTransaction();
        try {
            $model = new Appointment($this->db);
            if (!$model->veterinarian($vet)) {
                throw new DomainException('El veterinario no existe.');
            }
            $model->saveHours($vet, $day, $start, $end);
            new AuditService($this->db)->record($userId, 'schedule', 'veterinarians', $vet, [
                'weekday' => $day,
                'start' => $start,
                'end' => $end,
            ]);
            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
}
