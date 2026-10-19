<?php
declare(strict_types=1);
namespace App\Model;
use PDO;

// Entrega tarjetas de venta con disponibilidad vigente, sin duplicar productos por sus lotes.
final class Store
{
    public function __construct(private PDO $db) {}
    public function products(string $search, ?int $category, int $page): array
    {
        $where = 'p.active=1';
        $values = [];
        if ($search !== '') {
            $where .= ' AND (p.name LIKE ? OR p.code LIKE ? OR p.barcode LIKE ? OR b.name LIKE ?)';
            $values = array_fill(0, 4, '%' . $search . '%');
        }
        if ($category) {
            $where .= ' AND p.category_id=?';
            $values[] = $category;
        }
        $joins =
            ' FROM products p LEFT JOIN brands b ON b.id=p.brand_id LEFT JOIN product_categories c ON c.id=p.category_id';
        $query = $this->db->prepare('SELECT COUNT(*)' . $joins . ' WHERE ' . $where);
        $query->execute($values);
        $total = (int) $query->fetchColumn();
        $offset = ($page - 1) * 24;
        $sql =
            'SELECT p.*,b.name brand_name,c.name category_name,COALESCE((SELECT SUM(l.quantity-l.reserved_quantity) FROM stock_lots l WHERE l.product_id=p.id AND (l.expires_at IS NULL OR l.expires_at>=CURRENT_DATE)),0) available' .
            $joins .
            ' WHERE ' .
            $where .
            ' ORDER BY p.name LIMIT 24 OFFSET ' .
            $offset;
        $query = $this->db->prepare($sql);
        $query->execute($values);
        return ['products' => $query->fetchAll(), 'total' => $total];
    }
    public function image(int $id): ?string
    {
        $query = $this->db->prepare('SELECT image_path FROM products WHERE id=?');
        $query->execute([$id]);
        return $query->fetchColumn() ?: null;
    }
    public function setImage(int $id, string $filename): void
    {
        $this->db
            ->prepare('UPDATE products SET image_path=? WHERE id=?')
            ->execute([$filename, $id]);
    }
}
