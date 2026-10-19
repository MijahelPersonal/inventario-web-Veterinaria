<?php
declare(strict_types=1);
namespace App\Service;
use App\Model\Appointment;
use App\Model\Catalog;
use App\Model\Finance;
use App\Model\Inventory;
use DomainException;
use PDO;

// Coordina ventas, stock y dinero con transacciones; pagar nunca marca una cita atendida.
final class FinanceService
{
    public function __construct(private PDO $db) {}
    private function key(array $input): string
    {
        $key = (string) ($input['request_key'] ?? '');
        if (!preg_match('/^[a-f0-9]{64}$/D', $key)) {
            throw new DomainException('Recarga el formulario antes de continuar.');
        }
        return $key;
    }
    private function transaction(callable $operation): mixed
    {
        $this->db->beginTransaction();
        try {
            $result = $operation();
            $this->db->commit();
            return $result;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
    // Anular conserva el documento y devuelve exactamente los lotes que salieron.
    public function voidSale(int $id, string $reason, int $user): void
    {
        if (trim($reason) === '' || mb_strlen($reason) > 255) {
            throw new DomainException('Indica un motivo válido para anular.');
        }
        $this->transaction(function () use ($id, $reason, $user): void {
            $finance = new Finance($this->db);
            $sale = $finance->sale($id, true);
            if (!$sale || $sale['status'] !== 'confirmed') {
                throw new DomainException('La venta no está confirmada.');
            }
            if (bccomp($finance->paid($id), '0', 2) !== 0) {
                throw new DomainException('Revierte los cobros antes de anular la venta.');
            }
            $inventory = new Inventory($this->db);
            foreach ($inventory->saleMovements($id) as $movement) {
                $inventory->product((int) $movement['product_id']);
                $lot = $inventory->lot((int) $movement['lot_id']);
                $quantity = bcsub('0', $movement['quantity'], 3);
                $inventory->setQuantity((int) $lot['id'], bcadd($lot['quantity'], $quantity, 3));
                $inventory->addMovement(
                    (int) $lot['id'],
                    $quantity,
                    $movement['cost'],
                    'devolucion',
                    'Anulación venta #' . $id . ': ' . $reason,
                    null,
                    (string) $id,
                    $user,
                    bin2hex(random_bytes(32)),
                );
            }
            $this->db
                ->prepare("UPDATE sales SET status='void',void_reason=? WHERE id=?")
                ->execute([$reason, $id]);
            (new AuditService($this->db))->record($user, 'void', 'sales', $id, [
                'reason' => $reason,
            ]);
        });
    }
    public function sale(array $input, int $user): int
    {
        $key = $this->key($input);
        return $this->transaction(function () use ($input, $user, $key): int {
            $model = new Finance($this->db);
            $catalog = new Catalog($this->db);
            // Un mismo usuario no puede confirmar simultáneamente dos copias del mismo formulario.
            $this->db->prepare('SELECT id FROM users WHERE id=? FOR UPDATE')->execute([$user]);
            if ($previous = $model->byRequest('sales', $key)) {
                if ((int) $previous['user_id'] !== $user) {
                    throw new DomainException('Formulario inválido.');
                }
                return (int) $previous['id'];
            }
            $appointment = empty($input['appointment_id']) ? null : (int) $input['appointment_id'];
            $client = empty($input['client_id']) ? null : (int) $input['client_id'];
            $items = [];
            if ($appointment) {
                $record = (new Appointment($this->db))->find($appointment, true);
                if (!$record || !in_array($record['status'], ['confirmada', 'atendida'], true)) {
                    throw new DomainException('Confirma la cita antes de generar el cargo.');
                }
                if ($bill = $model->appointmentBill($appointment)) {
                    return (int) $bill['id'];
                }
                $pet = $catalog->find('pets', (int) $record['pet_id']);
                $client = (int) $pet['client_id'];
                $service = $catalog->find('services', (int) $record['service_id']);
                $items[] = [
                    'product' => null,
                    'service' => (int) $service['id'],
                    'description' => $service['name'],
                    'quantity' => '1.000',
                    'price' => $record['price'],
                ];
            } else {
                if (!is_array($input['items'] ?? null) || count($input['items']) > 50) {
                    throw new DomainException('Carrito inválido.');
                }
                foreach ($input['items'] as $line) {
                    if (!is_array($line)) {
                        throw new DomainException('Línea inválida.');
                    }
                    $item = (string) ($line['item'] ?? '');
                    if ($item === '') {
                        continue;
                    }
                    if (!preg_match('/^(product|service):(\d+)$/D', $item, $matches)) {
                        throw new DomainException('Selecciona un producto o servicio válido.');
                    }
                    $quantity = InventoryService::decimal((string) ($line['quantity'] ?? ''));
                    if (bccomp($quantity, '0', 3) <= 0) {
                        throw new DomainException('Cantidad inválida.');
                    }
                    $items[] = [
                        'product' => $matches[1] === 'product' ? (int) $matches[2] : null,
                        'service' => $matches[1] === 'service' ? (int) $matches[2] : null,
                        'quantity' => $quantity,
                    ];
                }
                if (!$items) {
                    throw new DomainException('Agrega al menos un producto o servicio.');
                }
            }
            if ($client) {
                $customer = $catalog->find('clients', $client);
                if (!$customer || !$customer['active']) {
                    throw new DomainException('Cliente no disponible.');
                }
            }
            // Bloquear productos en orden estable reduce los interbloqueos entre dos cajas.
            usort($items, static fn($a, $b) => ($a['product'] ?? 0) <=> ($b['product'] ?? 0));
            $sale = $model->createSale($client, $appointment, $user, $key);
            $total = '0.00';
            $cost = '0.00';
            foreach ($items as $line) {
                $product = $line['product'];
                $service = $line['service'];
                $record = $product
                    ? (new Inventory($this->db))->product($product)
                    : $catalog->find('services', $service);
                if (!$record || (!$record['active'] && !$appointment)) {
                    throw new DomainException('Un producto o servicio ya no está disponible.');
                }
                $price = $line['price'] ?? $record['price'];
                $lineTotal = bcadd(bcmul($price, $line['quantity'], 5), '0.005', 2);
                $lineCost = $product
                    ? (new InventoryService($this->db))->takeForSale(
                        $product,
                        $line['quantity'],
                        $user,
                        $sale,
                    )
                    : '0.00';
                $model->addItem(
                    $sale,
                    $product,
                    $service,
                    $line['description'] ?? $record['name'],
                    $line['quantity'],
                    $price,
                    $lineTotal,
                    $lineCost,
                );
                $total = bcadd($total, $lineTotal, 2);
                $cost = bcadd($cost, $lineCost, 2);
            }
            if (bccomp($total, '999999999999.99', 2) > 0) {
                throw new DomainException('Total fuera de rango.');
            }
            $model->totals($sale, $total, $cost);
            (new AuditService($this->db))->record($user, 'create', 'sales', $sale);
            return $sale;
        });
    }
    public function payment(array $input, int $user): int
    {
        $key = $this->key($input);
        $amount = InventoryService::decimal((string) ($input['amount'] ?? ''), 2);
        $method = (string) ($input['method'] ?? '');
        $sale = (int) ($input['sale_id'] ?? 0);
        $notes = trim((string) ($input['notes'] ?? ''));
        if (
            bccomp($amount, '0', 2) <= 0 ||
            !in_array($method, ['efectivo', 'transferencia', 'tarjeta'], true) ||
            mb_strlen($notes) > 255
        ) {
            throw new DomainException('Importe, método o nota inválidos.');
        }
        return $this->transaction(function () use (
            $key,
            $amount,
            $method,
            $sale,
            $notes,
            $user,
        ): int {
            $model = new Finance($this->db);
            $session = $model->session($user, true);
            if (!$session) {
                throw new DomainException('Abre tu caja antes de cobrar.');
            }
            $bill = $model->sale($sale, true);
            if (!$bill || $bill['status'] !== 'confirmed') {
                throw new DomainException('La venta no está disponible para cobro.');
            }
            if ($previous = $model->byRequest('payments', $key)) {
                if ((int) $previous['user_id'] !== $user || (int) $previous['sale_id'] !== $sale) {
                    throw new DomainException('Formulario inválido.');
                }
                return (int) $previous['id'];
            }
            $balance = bcsub($bill['total'], $model->paid($sale), 2);
            if (bccomp($amount, $balance, 2) > 0) {
                throw new DomainException(
                    'El cobro supera el saldo pendiente. Registra solo el importe aplicado, sin el vuelto.',
                );
            }
            $id = $model->addPayment(
                $sale,
                (int) $session['id'],
                $amount,
                $method,
                null,
                $notes,
                $user,
                $key,
            );
            (new AuditService($this->db))->record($user, 'payment', 'payments', $id);
            return $id;
        });
    }
    public function reverse(int $payment, string $reason, int $user): void
    {
        if (trim($reason) === '' || mb_strlen($reason) > 255) {
            throw new DomainException('Indica el motivo de reversión.');
        }
        $this->transaction(function () use ($payment, $reason, $user): void {
            $model = new Finance($this->db);
            $session = $model->session($user, true);
            $original = $model->payment($payment);
            if (
                !$session ||
                !$original ||
                $original['reversal_of'] ||
                bccomp($original['amount'], '0', 2) <= 0
            ) {
                throw new DomainException('Abre caja y selecciona un cobro original.');
            }
            $model->sale((int) $original['sale_id'], true);
            $query = $this->db->prepare('SELECT id FROM payments WHERE reversal_of=?');
            $query->execute([$payment]);
            if ($query->fetch()) {
                throw new DomainException('El cobro ya fue revertido.');
            }
            $id = $model->addPayment(
                (int) $original['sale_id'],
                (int) $session['id'],
                bcsub('0', $original['amount'], 2),
                $original['method'],
                $payment,
                $reason,
                $user,
                bin2hex(random_bytes(32)),
            );
            (new AuditService($this->db))->record($user, 'reverse', 'payments', $id, [
                'original' => $payment,
            ]);
        });
    }
    public function cash(string $action, array $input, int $user): void
    {
        $amount = InventoryService::decimal((string) ($input['amount'] ?? ''), 2);
        $this->transaction(function () use ($action, $input, $user, $amount): void {
            $model = new Finance($this->db);
            $this->db->prepare('SELECT id FROM users WHERE id=? FOR UPDATE')->execute([$user]);
            $session = $model->session($user, true);
            if ($action === 'open') {
                if ($session) {
                    throw new DomainException('Ya tienes una caja abierta.');
                }
                $id = $model->open($user, $amount);
            } else {
                if (!$session) {
                    throw new DomainException('No tienes una caja abierta.');
                }
                $id = (int) $session['id'];
                if ($action === 'close') {
                    $model->close($id, $model->expected($session), $amount);
                } elseif ($action === 'entry') {
                    $key = $this->key($input);
                    $reason = trim((string) ($input['reason'] ?? ''));
                    if (
                        $reason === '' ||
                        mb_strlen($reason) > 255 ||
                        bccomp($amount, '0', 2) <= 0 ||
                        !in_array($input['direction'] ?? '', ['in', 'out'], true)
                    ) {
                        throw new DomainException('Movimiento de caja inválido.');
                    }
                    if ($model->byRequest('cash_entries', $key)) {
                        return;
                    }
                    $model->entry(
                        $id,
                        ($input['direction'] === 'out' ? '-' : '') . $amount,
                        $reason,
                        $user,
                        $key,
                    );
                } else {
                    throw new DomainException('Operación de caja inválida.');
                }
            }
            (new AuditService($this->db))->record($user, 'cash_' . $action, 'cash_sessions', $id);
        });
    }
}
