<?php
declare(strict_types=1);
namespace App\Model;
use PDO;

// Consulta ventas, cobros y caja; los saldos se calculan desde sus movimientos.
final class Finance
{
    public function __construct(private PDO $db) {}
    public function sales(): array
    {
        return $this->db
            ->query(
                'SELECT s.*,c.name client_name,COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.sale_id=s.id),0) paid FROM sales s LEFT JOIN clients c ON c.id=s.client_id ORDER BY s.id DESC LIMIT 100',
            )
            ->fetchAll();
    }
    public function pendingAppointments(): array
    {
        return $this->db
            ->query(
                "SELECT a.id,a.start_at,a.price,p.name pet_name,s.name service_name FROM appointments a JOIN pets p ON p.id=a.pet_id JOIN services s ON s.id=a.service_id LEFT JOIN sales b ON b.appointment_id=a.id WHERE b.id IS NULL AND a.status IN ('confirmada','atendida') ORDER BY a.start_at DESC LIMIT 100",
            )
            ->fetchAll();
    }
    public function entries(int $session): array
    {
        $query = $this->db->prepare(
            'SELECT * FROM cash_entries WHERE session_id=? ORDER BY id DESC',
        );
        $query->execute([$session]);
        return $query->fetchAll();
    }
    public function sale(int $id, bool $lock = false): ?array
    {
        $query = $this->db->prepare(
            'SELECT * FROM sales WHERE id=?' . ($lock ? ' FOR UPDATE' : ''),
        );
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }
    public function paid(int $sale): string
    {
        $query = $this->db->prepare('SELECT COALESCE(SUM(amount),0) FROM payments WHERE sale_id=?');
        $query->execute([$sale]);
        return (string) $query->fetchColumn();
    }
    public function byRequest(string $table, string $key): ?array
    {
        if (!in_array($table, ['sales', 'payments', 'cash_entries'], true)) {
            throw new \LogicException('Tabla inválida.');
        }
        $query = $this->db->prepare("SELECT * FROM $table WHERE request_key=?");
        $query->execute([$key]);
        return $query->fetch() ?: null;
    }
    public function appointmentBill(int $id): ?array
    {
        $query = $this->db->prepare('SELECT * FROM sales WHERE appointment_id=?');
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }
    public function createSale(?int $client, ?int $appointment, int $user, string $key): int
    {
        $this->db
            ->prepare(
                'INSERT INTO sales(client_id,appointment_id,user_id,request_key) VALUES (?,?,?,?)',
            )
            ->execute([$client, $appointment, $user, $key]);
        return (int) $this->db->lastInsertId();
    }
    public function addItem(
        int $sale,
        ?int $product,
        ?int $service,
        string $description,
        string $quantity,
        string $price,
        string $total,
        string $cost,
    ): void {
        $this->db
            ->prepare(
                'INSERT INTO sale_items(sale_id,product_id,service_id,description,quantity,unit_price,line_total,cost_total) VALUES (?,?,?,?,?,?,?,?)',
            )
            ->execute([$sale, $product, $service, $description, $quantity, $price, $total, $cost]);
    }
    public function totals(int $id, string $total, string $cost): void
    {
        $this->db
            ->prepare('UPDATE sales SET total=?,cost_total=? WHERE id=?')
            ->execute([$total, $cost, $id]);
    }
    public function items(int $sale): array
    {
        $query = $this->db->prepare('SELECT * FROM sale_items WHERE sale_id=? ORDER BY id');
        $query->execute([$sale]);
        return $query->fetchAll();
    }
    public function payments(?int $sale = null): array
    {
        $query = $this->db->prepare(
            'SELECT p.*,u.username,EXISTS(SELECT 1 FROM payments r WHERE r.reversal_of=p.id) reversed FROM payments p JOIN users u ON u.id=p.user_id' .
                ($sale ? ' WHERE p.sale_id=?' : '') .
                ' ORDER BY p.id DESC LIMIT 100',
        );
        $query->execute($sale ? [$sale] : []);
        return $query->fetchAll();
    }
    public function payment(int $id): ?array
    {
        $query = $this->db->prepare('SELECT * FROM payments WHERE id=?');
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }
    public function session(int $user, bool $lock = false): ?array
    {
        $query = $this->db->prepare(
            'SELECT * FROM cash_sessions WHERE user_id=? AND closed_at IS NULL' .
                ($lock ? ' FOR UPDATE' : ''),
        );
        $query->execute([$user]);
        return $query->fetch() ?: null;
    }
    public function open(int $user, string $amount): int
    {
        $this->db
            ->prepare('INSERT INTO cash_sessions(user_id,opening_amount) VALUES (?,?)')
            ->execute([$user, $amount]);
        return (int) $this->db->lastInsertId();
    }
    public function expected(array $session): string
    {
        $query = $this->db->prepare(
            "SELECT COALESCE(SUM(amount),0) FROM payments WHERE session_id=? AND method='efectivo'",
        );
        $query->execute([$session['id']]);
        $paid = (string) $query->fetchColumn();
        $query = $this->db->prepare(
            'SELECT COALESCE(SUM(amount),0) FROM cash_entries WHERE session_id=?',
        );
        $query->execute([$session['id']]);
        return bcadd(
            bcadd($session['opening_amount'], $paid, 2),
            (string) $query->fetchColumn(),
            2,
        );
    }
    public function close(int $id, string $expected, string $counted): void
    {
        $this->db
            ->prepare(
                'UPDATE cash_sessions SET closed_at=CURRENT_TIMESTAMP,expected_amount=?,counted_amount=?,difference=? WHERE id=?',
            )
            ->execute([$expected, $counted, bcsub($counted, $expected, 2), $id]);
    }
    public function sessions(int $user): array
    {
        $query = $this->db->prepare(
            'SELECT * FROM cash_sessions WHERE user_id=? ORDER BY id DESC LIMIT 30',
        );
        $query->execute([$user]);
        return $query->fetchAll();
    }
    public function entry(
        int $session,
        string $amount,
        string $reason,
        int $user,
        string $key,
    ): void {
        $this->db
            ->prepare(
                'INSERT INTO cash_entries(session_id,amount,reason,user_id,request_key) VALUES (?,?,?,?,?)',
            )
            ->execute([$session, $amount, $reason, $user, $key]);
    }
    public function addPayment(
        int $sale,
        int $session,
        string $amount,
        string $method,
        ?int $reversal,
        string $notes,
        int $user,
        string $key,
    ): int {
        $this->db
            ->prepare(
                'INSERT INTO payments(sale_id,session_id,amount,method,reversal_of,notes,user_id,request_key) VALUES (?,?,?,?,?,?,?,?)',
            )
            ->execute([$sale, $session, $amount, $method, $reversal, $notes, $user, $key]);
        return (int) $this->db->lastInsertId();
    }
}
