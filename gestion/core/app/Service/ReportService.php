<?php
declare(strict_types=1);
namespace App\Service;
use App\Model\Report;
use DateTimeImmutable;
use DomainException;
use PDO;

// Valida periodos y genera la misma información para HTML, CSV y PDF.
final class ReportService
{
    public const TYPES = [
        'citas' => 'Citas y atenciones',
        'ventas' => 'Ventas',
        'cobros' => 'Cobros y reversiones',
        'caja' => 'Cierres de caja',
        'movimientos' => 'Movimientos de inventario',
    ];
    public function __construct(private PDO $db) {}
    public function generate(array $filters): array
    {
        $type = (string) ($filters['type'] ?? 'citas');
        $start = (string) ($filters['start'] ?? date('Y-m-01'));
        $end = (string) ($filters['end'] ?? date('Y-m-d'));
        $from = DateTimeImmutable::createFromFormat('!Y-m-d', $start);
        $to = DateTimeImmutable::createFromFormat('!Y-m-d', $end);
        if (
            !isset(self::TYPES[$type]) ||
            !$from ||
            !$to ||
            $from->format('Y-m-d') !== $start ||
            $to->format('Y-m-d') !== $end ||
            $to < $from ||
            $from->diff($to)->days > 366
        ) {
            throw new DomainException('Selecciona un periodo válido de hasta 366 días.');
        }
        $state = (string) ($filters['status'] ?? '');
        if ($state !== '' && !isset(AppointmentService::STATES[$state])) {
            throw new DomainException('Estado inválido.');
        }
        $headers = match ($type) {
            'citas' => [
                'id' => 'Cita',
                'start_at' => 'Fecha',
                'pet' => 'Mascota',
                'client' => 'Responsable',
                'veterinarian' => 'Veterinario',
                'service' => 'Servicio',
                'status' => 'Estado',
                'price' => 'Precio',
            ],
            'ventas' => [
                'id' => 'Venta',
                'created_at' => 'Fecha',
                'client' => 'Cliente',
                'status' => 'Estado',
                'total' => 'Total',
                'paid' => 'Cobrado neto',
            ],
            'cobros' => [
                'id' => 'Cobro',
                'created_at' => 'Fecha',
                'sale_id' => 'Venta',
                'username' => 'Usuario',
                'method' => 'Método',
                'amount' => 'Importe',
                'reversal_of' => 'Revierte cobro',
            ],
            'caja' => [
                'id' => 'Caja',
                'username' => 'Usuario',
                'opened_at' => 'Apertura',
                'closed_at' => 'Cierre',
                'opening_amount' => 'Inicial',
                'expected_amount' => 'Esperado',
                'counted_amount' => 'Contado',
                'difference' => 'Diferencia',
            ],
            'movimientos' => [
                'id' => 'Movimiento',
                'created_at' => 'Fecha',
                'code' => 'Código',
                'name' => 'Producto',
                'lot_code' => 'Lote',
                'kind' => 'Tipo',
                'quantity' => 'Cantidad',
                'cost' => 'Costo unitario',
                'reason' => 'Motivo',
                'username' => 'Usuario',
            ],
        };
        $rows = (new Report($this->db))->rows(
            $type,
            $start,
            $to->modify('+1 day')->format('Y-m-d'),
            (int) ($filters['vet'] ?? 0),
            (int) ($filters['pet'] ?? 0),
            $state,
        );
        if (count($rows) > 5000) {
            throw new DomainException('El reporte supera 5000 registros. Reduce el periodo.');
        }
        $metrics = [
            'count' => count($rows),
            'attended' => 0,
            'collected' => '0.00',
            'outstanding' => '0.00',
        ];
        if ($type === 'citas') {
            foreach ($rows as $row) {
                if ($row['status'] === 'atendida') {
                    $metrics['attended']++;
                }
                $metrics['collected'] = bcadd($metrics['collected'], $row['paid'], 2);
                $balance = bcsub($row['bill_total'], $row['paid'], 2);
                if (bccomp($balance, '0', 2) > 0) {
                    $metrics['outstanding'] = bcadd($metrics['outstanding'], $balance, 2);
                }
            }
        }
        return compact('type', 'start', 'end', 'headers', 'rows', 'metrics') + [
            'title' => self::TYPES[$type],
        ];
    }
    // Evita que Excel interprete nombres o notas como fórmulas al abrir un CSV.
    public static function csvCell(mixed $value): string
    {
        $text = (string) ($value ?? '');
        return preg_match('/^[\s]*[=+@-]/u', $text) && !preg_match('/^-\d+(\.\d+)?$/D', $text)
            ? "'" . $text
            : $text;
    }
}
