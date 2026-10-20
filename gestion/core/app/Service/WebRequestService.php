<?php
declare(strict_types=1);
namespace App\Service;
use DomainException;
use PDO;

// Valida solicitudes públicas sin confirmar turnos ni descontar o reservar inventario.
final class WebRequestService
{
    public function __construct(private PDO $db) {}
    public function submit(array $input, string $clientHash): string
    {
        $kind = (string) ($input['kind'] ?? '');
        $key = (string) ($input['request_key'] ?? '');
        if (
            !in_array($kind, ['appointment', 'order', 'contact'], true) ||
            !preg_match('/^[a-f0-9]{64}$/D', $key) ||
            !preg_match('/^[a-f0-9]{64}$/D', $clientHash)
        ) {
            throw new DomainException('Solicitud inválida.');
        }
        $text = static function (string $field, int $max, bool $required = false) use (
            $input,
        ): string {
            if (!is_scalar($input[$field] ?? '')) {
                throw new DomainException('Dato inválido.');
            }
            $value = trim((string) ($input[$field] ?? ''));
            if (($required && $value === '') || mb_strlen($value) > $max) {
                throw new DomainException('Revisa el campo ' . $field . '.');
            }
            return $value;
        };
        $name = $text('name', 120, true);
        $phone = $text('phone', 40, true);
        $email = $text('email', 190);
        if (
            !preg_match('/^[+()\d\s.-]{6,40}$/D', $phone) ||
            ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) ||
            ($input['privacy'] ?? false) !== true
        ) {
            throw new DomainException('Revisa tu contacto y acepta el tratamiento de tus datos.');
        }
        $payload = ['message' => $text('message', 1500)];
        if ($kind === 'appointment') {
            $date = $text('date', 10, true);
            $time = $text('time', 5, true);
            $start = \DateTimeImmutable::createFromFormat('!Y-m-d H:i', $date . ' ' . $time);
            if (
                !$start ||
                $start->format('Y-m-d H:i') !== $date . ' ' . $time ||
                $start <= new \DateTimeImmutable() ||
                $start > new \DateTimeImmutable('+1 year')
            ) {
                throw new DomainException(
                    'Elige una fecha y hora futuras, dentro del próximo año.',
                );
            }
            $payload += [
                'pet_name' => $text('pet_name', 120, true),
                'species' => $text('species', 60, true),
                'service' => $text('service', 160, true),
                'date' => $date,
                'time' => $time,
            ];
        }
        if ($kind === 'order') {
            $items = $input['items'] ?? [];
            if (!is_array($items) || !$items || count($items) > 50) {
                throw new DomainException('El carrito está vacío o tiene demasiados productos.');
            }
            $payload['items'] = [];
            $seen = [];
            $total = '0.00';
            foreach ($items as $item) {
                if (!is_array($item)) {
                    throw new DomainException('Producto inválido.');
                }
                $id = (int) ($item['id'] ?? 0);
                if (isset($seen[$id])) {
                    throw new DomainException('No repitas un producto en la misma solicitud.');
                }
                $seen[$id] = true;
                $qty = InventoryService::decimal((string) ($item['quantity'] ?? ''), 3);
                if (bccomp($qty, '0', 3) <= 0) {
                    throw new DomainException('Cantidad inválida.');
                }
                $query = $this->db->prepare(
                    'SELECT p.id,p.name,p.price,COALESCE(SUM(CASE WHEN l.expires_at IS NULL OR l.expires_at>=CURRENT_DATE THEN l.quantity-l.reserved_quantity ELSE 0 END),0) available FROM products p LEFT JOIN stock_lots l ON l.product_id=p.id WHERE p.id=? AND p.active=1 AND p.publish_online=1 GROUP BY p.id',
                );
                $query->execute([$id]);
                $product = $query->fetch();
                if (!$product || bccomp($qty, (string) $product['available'], 3) > 0) {
                    throw new DomainException(
                        'Un producto ya no tiene disponibilidad suficiente. Actualiza tu carrito.',
                    );
                }
                $line = bcmul($qty, $product['price'], 2);
                $total = bcadd($total, $line, 2);
                $payload['items'][] = [
                    'id' => $id,
                    'name' => $product['name'],
                    'quantity' => $qty,
                    'price' => $product['price'],
                    'total' => $line,
                ];
            }
            $payload['total'] = $total;
        }
        $this->db->beginTransaction();
        try {
            $this->db
                ->prepare(
                    'INSERT IGNORE INTO web_submission_limits(bucket,window_start) VALUES (?,UTC_TIMESTAMP())',
                )
                ->execute([$clientHash]);
            $query = $this->db->prepare(
                'SELECT *,TIMESTAMPDIFF(SECOND,window_start,UTC_TIMESTAMP()) age FROM web_submission_limits WHERE bucket=? FOR UPDATE',
            );
            $query->execute([$clientHash]);
            $limit = $query->fetch();
            $query = $this->db->prepare(
                'SELECT reference,client_hash FROM web_requests WHERE request_key=?',
            );
            $query->execute([$key]);
            $previous = $query->fetch();
            if ($previous) {
                if (!hash_equals($previous['client_hash'], $clientHash)) {
                    throw new DomainException('Formulario inválido.');
                }
                $this->db->commit();
                return $previous['reference'];
            }
            if ((int) $limit['age'] >= 900) {
                $this->db
                    ->prepare(
                        'UPDATE web_submission_limits SET attempts=0,window_start=UTC_TIMESTAMP() WHERE bucket=?',
                    )
                    ->execute([$clientHash]);
            } elseif ((int) $limit['attempts'] >= 5) {
                throw new DomainException(
                    'Has enviado varias solicitudes. Inténtalo de nuevo en 15 minutos.',
                );
            }
            $reference = strtoupper(bin2hex(random_bytes(6)));
            $this->db
                ->prepare(
                    'INSERT INTO web_requests(reference,kind,name,phone,email,payload,request_key,client_hash) VALUES (?,?,?,?,?,?,?,?)',
                )
                ->execute([
                    $reference,
                    $kind,
                    $name,
                    $phone,
                    $email ?: null,
                    json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                    $key,
                    $clientHash,
                ]);
            $this->db
                ->prepare('UPDATE web_submission_limits SET attempts=attempts+1 WHERE bucket=?')
                ->execute([$clientHash]);
            $this->db->commit();
            return $reference;
        } catch (\Throwable $error) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $error;
        }
    }
}
