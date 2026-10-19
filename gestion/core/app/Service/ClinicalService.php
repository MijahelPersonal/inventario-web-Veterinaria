<?php
declare(strict_types=1);
namespace App\Service;
use App\Model\Appointment;
use App\Model\ClinicalRecord;
use DateTimeImmutable;
use DomainException;
use PDO;

// Guarda notas y reprograma citas con validación de estado, duración y disponibilidad.
final class ClinicalService
{
    public function __construct(private PDO $db) {}
    public function notes(int $id, array $input, int $user): void
    {
        $data = [];
        foreach (['symptoms', 'diagnosis', 'treatment'] as $field) {
            $value = trim((string) ($input[$field] ?? ''));
            if (mb_strlen($value) > 5000) {
                throw new DomainException('Cada nota admite hasta 5000 caracteres.');
            }
            $data[] = $value;
        }
        $this->db->beginTransaction();
        try {
            $record = (new Appointment($this->db))->find($id, true);
            if (!$record || in_array($record['status'], ['cancelada', 'no_asistio'], true)) {
                throw new DomainException('No se pueden registrar notas de atención en esta cita.');
            }
            (new ClinicalRecord($this->db))->saveNotes($id, ...$data);
            (new AuditService($this->db))->record($user, 'clinical_notes', 'appointments', $id);
            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
    public function reschedule(int $id, array $input, int $user): void
    {
        $start = DateTimeImmutable::createFromFormat(
            '!Y-m-d\TH:i',
            (string) ($input['start_at'] ?? ''),
        );
        if (
            !$start ||
            $start->format('Y-m-d\TH:i') !== ($input['start_at'] ?? '') ||
            $start < new DateTimeImmutable()
        ) {
            throw new DomainException('Selecciona una fecha y hora futura válidas.');
        }
        $vet = (int) ($input['veterinarian_id'] ?? 0);
        $model = new Appointment($this->db);
        $this->db->beginTransaction();
        try {
            $old = $model->find($id);
            if (!$old) {
                throw new DomainException('La cita no existe.');
            }
            $ids = array_unique([(int) $old['veterinarian_id'], $vet]);
            sort($ids);
            $newVet = null;
            foreach ($ids as $vetId) {
                $locked = $model->veterinarian($vetId);
                if ($vetId === $vet) {
                    $newVet = $locked;
                }
            }
            $record = $model->find($id, true);
            if (
                !$newVet ||
                !$newVet['active'] ||
                $old['veterinarian_id'] !== $record['veterinarian_id']
            ) {
                throw new DomainException(
                    'La cita cambió o el veterinario no está disponible. Recarga.',
                );
            }
            if (
                !in_array($record['status'], ['solicitada', 'confirmada'], true) ||
                $model->hasBill($id)
            ) {
                throw new DomainException('Solo se reprograman citas abiertas sin cargo generado.');
            }
            $duration =
                (new DateTimeImmutable($record['end_at']))->getTimestamp() -
                (new DateTimeImmutable($record['start_at']))->getTimestamp();
            $end = $start->modify('+' . $duration . ' seconds');
            $service = (new \App\Model\Catalog($this->db))->find(
                'services',
                (int) $record['service_id'],
            );
            if (
                $service['specialty_id'] &&
                $newVet['specialty_id'] &&
                $service['specialty_id'] !== $newVet['specialty_id']
            ) {
                throw new DomainException('Especialidad incompatible.');
            }
            $hours = $model->hours($vet, (int) $start->format('N'));
            if (!$hours && !$model->hasHours($vet)) {
                $hours = ['start_time' => '08:00:00', 'end_time' => '20:00:00'];
            }
            if (
                !$hours ||
                $start->format('Y-m-d') !== $end->format('Y-m-d') ||
                $start->format('H:i:s') < $hours['start_time'] ||
                $end->format('H:i:s') > $hours['end_time'] ||
                $model->overlaps(
                    $vet,
                    $start->format('Y-m-d H:i:s'),
                    $end->format('Y-m-d H:i:s'),
                    $id,
                )
            ) {
                throw new DomainException(
                    'Horario fuera de disponibilidad o en conflicto con otra cita.',
                );
            }
            (new ClinicalRecord($this->db))->reschedule(
                $id,
                $vet,
                $start->format('Y-m-d H:i:s'),
                $end->format('Y-m-d H:i:s'),
            );
            (new AuditService($this->db))->record($user, 'reschedule', 'appointments', $id, [
                'from' => $record['start_at'],
                'to' => $start->format('Y-m-d H:i:s'),
            ]);
            $this->db->commit();
        } catch (\Throwable $error) {
            $this->db->rollBack();
            throw $error;
        }
    }
}
