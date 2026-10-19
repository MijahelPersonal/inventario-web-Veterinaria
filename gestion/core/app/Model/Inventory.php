<?php
declare(strict_types=1);
namespace App\Model;
use PDO;

// Mantiene lotes y movimientos; las cantidades nunca se editan desde el catálogo.
final class Inventory
{
    public function __construct(private PDO $db) {}
    public function overview(): array
    {
        return $this->db
            ->query(
                'SELECT p.id,p.code,p.name,p.unit,p.min_stock,p.active,COALESCE(SUM(l.quantity),0) quantity,COALESCE(SUM(CASE WHEN l.expires_at IS NULL OR l.expires_at>=CURRENT_DATE THEN l.quantity-l.reserved_quantity ELSE 0 END),0) available FROM products p LEFT JOIN stock_lots l ON l.product_id=p.id GROUP BY p.id ORDER BY p.name',
            )
            ->fetchAll();
    }
    public function lots(): array
    {
        return $this->db
            ->query(
                'SELECT l.*,p.name,p.unit FROM stock_lots l JOIN products p ON p.id=l.product_id WHERE l.quantity>0 ORDER BY l.expires_at IS NULL,l.expires_at,l.id LIMIT 1000',
            )
            ->fetchAll();
    }
    public function movements(): array
    {
        return $this->db
            ->query(
                'SELECT m.*,p.name,l.lot_code,u.name user_name FROM stock_movements m JOIN stock_lots l ON l.id=m.lot_id JOIN products p ON p.id=l.product_id JOIN users u ON u.id=m.user_id ORDER BY m.id DESC LIMIT 100',
            )
            ->fetchAll();
    }
    public function product(int $id): ?array
    {
        $query = $this->db->prepare('SELECT * FROM products WHERE id=? FOR UPDATE');
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }
    public function lot(int $id): ?array
    {
        $query = $this->db->prepare('SELECT * FROM stock_lots WHERE id=? FOR UPDATE');
        $query->execute([$id]);
        return $query->fetch() ?: null;
    }
    public function byRequest(string $key): ?array
    {
        $query = $this->db->prepare('SELECT * FROM stock_movements WHERE request_key=?');
        $query->execute([$key]);
        return $query->fetch() ?: null;
    }
    public function createLot(
        int $product,
        string $code,
        ?string $expiry,
        string $cost,
        string $quantity,
    ): int {
        $this->db
            ->prepare(
                'INSERT INTO stock_lots(product_id,lot_code,expires_at,cost,quantity) VALUES (?,?,?,?,?)',
            )
            ->execute([$product, $code, $expiry, $cost, $quantity]);
        return (int) $this->db->lastInsertId();
    }
    public function setQuantity(int $lot, string $quantity): void
    {
        $this->db
            ->prepare('UPDATE stock_lots SET quantity=? WHERE id=?')
            ->execute([$quantity, $lot]);
    }
    public function addMovement(
        int $lot,
        string $quantity,
        string $cost,
        string $kind,
        string $reason,
        ?int $supplier,
        ?string $reference,
        int $user,
        string $key,
    ): int {
        $this->db
            ->prepare(
                'INSERT INTO stock_movements(lot_id,quantity,cost,kind,reason,supplier_id,reference,user_id,request_key) VALUES (?,?,?,?,?,?,?,?,?)',
            )
            ->execute([$lot, $quantity, $cost, $kind, $reason, $supplier, $reference, $user, $key]);
        return (int) $this->db->lastInsertId();
    }
    public function sellableLots(int $product): array
    {
        $query = $this->db->prepare(
            'SELECT * FROM stock_lots WHERE product_id=? AND quantity>reserved_quantity AND (expires_at IS NULL OR expires_at>=CURRENT_DATE) ORDER BY expires_at IS NULL,expires_at,id FOR UPDATE',
        );
        $query->execute([$product]);
        return $query->fetchAll();
    }
}
