<?php
declare(strict_types=1);
namespace App\Service;
use App\Model\Catalog;
use App\Model\Inventory;
use DomainException;
use PDO;

// Confirma entradas y salidas de forma atómica, con cantidades decimales y auditoría.
final class InventoryService
{
    public function __construct(private PDO $db) {}
    public static function decimal(string $value, int $scale = 3): string
    {
        if (!preg_match('/^\d{1,9}(\.\d{1,' . $scale . '})?$/D', $value)) {
            throw new DomainException('Cantidad o importe inválido.');
        }
        return bcadd($value, '0', $scale);
    }
    public function movement(array $input, int $user): int
    {
        $quantity = self::decimal((string) ($input['quantity'] ?? ''));
        if (bccomp($quantity, '0', 3) <= 0) {
            throw new DomainException('La cantidad debe ser mayor que cero.');
        }
        $reason = trim((string) ($input['reason'] ?? ''));
        $key = (string) ($input['request_key'] ?? '');
        if ($reason === '' || mb_strlen($reason) > 255 || !preg_match('/^[a-f0-9]{64}$/D', $key)) {
            throw new DomainException('Completa un motivo válido.');
        }
        $kind = (string) ($input['kind'] ?? '');
        $incoming = in_array($kind, ['compra', 'inicial'], true);
        if (!$incoming && !in_array($kind, ['ajuste', 'consumo', 'perdida', 'vencimiento'], true)) {
            throw new DomainException('Tipo de movimiento inválido.');
        }
        $model = new Inventory($this->db);
        $productId = (int) ($input['product_id'] ?? 0);
        $this->db->beginTransaction();
        try {
            $product = $model->product($productId);
            if (!$product) {
                throw new DomainException('El producto no existe.');
            }
            $previous = $model->byRequest($key);
            if ($previous) {
                if ((int) $previous['user_id'] !== $user) {
                    throw new DomainException('Formulario inválido.');
                }
                $this->db->commit();
                return (int) $previous['id'];
            }
            $supplier = null;
            $reference = null;
            if ($incoming) {
                if (!$product['active']) {
                    throw new DomainException('Activa el producto antes de recibirlo.');
                }
                $cost = self::decimal((string) ($input['cost'] ?? ''), 2);
                $code = trim((string) ($input['lot_code'] ?? ''));
                if ($code === '' || mb_strlen($code) > 100) {
                    throw new DomainException('Indica el lote (usa SIN-LOTE cuando corresponda).');
                }
                $expiry = trim((string) ($input['expires_at'] ?? '')) ?: null;
                if ($expiry) {
                    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $expiry);
                    if (!$date || $date->format('Y-m-d') !== $expiry || $expiry < date('Y-m-d')) {
                        throw new DomainException('No se puede recibir un lote vencido.');
                    }
                }
                $supplier = empty($input['supplier_id']) ? null : (int) $input['supplier_id'];
                $vendor = $supplier ? (new Catalog($this->db))->find('suppliers', $supplier) : null;
                if ($supplier && (!$vendor || !$vendor['active'])) {
                    throw new DomainException('Selecciona un proveedor activo para la compra.');
                }
                $reference = trim((string) ($input['reference'] ?? '')) ?: null;
                if ($reference && mb_strlen($reference) > 100) {
                    throw new DomainException('Referencia demasiado larga.');
                }
                $lot = $model->createLot($productId, $code, $expiry, $cost, $quantity);
            } else {
                $lot = (int) ($input['lot_id'] ?? 0);
                $record = $model->lot($lot);
                if (!$record || (int) $record['product_id'] !== $productId) {
                    throw new DomainException('El lote no pertenece al producto.');
                }
                if (
                    $record['expires_at'] &&
                    $record['expires_at'] < date('Y-m-d') &&
                    !in_array($kind, ['vencimiento', 'perdida'], true)
                ) {
                    throw new DomainException(
                        'Un lote vencido solo puede registrarse como pérdida.',
                    );
                }
                if (
                    bccomp(
                        bcsub($record['quantity'], $record['reserved_quantity'], 3),
                        $quantity,
                        3,
                    ) < 0
                ) {
                    throw new DomainException(
                        'No hay existencias disponibles suficientes en el lote.',
                    );
                }
                $cost = $record['cost'];
                $model->setQuantity($lot, bcsub($record['quantity'], $quantity, 3));
                $quantity = bcsub('0', $quantity, 3);
            }
            $id = $model->addMovement(
                $lot,
                $quantity,
                $cost,
                $kind,
                $reason,
                $supplier,
                $reference,
                $user,
                $key,
            );
            (new AuditService($this->db))->record($user, 'movement', 'stock_movements', $id);
            $this->db->commit();
            return $id;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
    // Se invoca dentro de la transacción de venta, con el producto ya bloqueado.
    public function takeForSale(int $product, string $quantity, int $user, int $sale): string
    {
        if (!$this->db->inTransaction()) {
            throw new \LogicException('Se requiere una transacción.');
        }
        $model = new Inventory($this->db);
        $pending = $quantity;
        $cost = '0.00';
        foreach ($model->sellableLots($product) as $lot) {
            if (bccomp($pending, '0', 3) <= 0) {
                break;
            }
            $available = bcsub($lot['quantity'], $lot['reserved_quantity'], 3);
            $take = bccomp($available, $pending, 3) < 0 ? $available : $pending;
            $model->setQuantity((int) $lot['id'], bcsub($lot['quantity'], $take, 3));
            $model->addMovement(
                (int) $lot['id'],
                bcsub('0', $take, 3),
                $lot['cost'],
                'venta',
                'Venta #' . $sale,
                null,
                (string) $sale,
                $user,
                bin2hex(random_bytes(32)),
            );
            $cost = bcadd($cost, bcmul($take, $lot['cost'], 2), 2);
            $pending = bcsub($pending, $take, 3);
        }
        if (bccomp($pending, '0', 3) > 0) {
            throw new DomainException('Stock insuficiente. La venta no fue confirmada.');
        }
        return $cost;
    }
}
