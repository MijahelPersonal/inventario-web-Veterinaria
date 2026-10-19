<?php
declare(strict_types=1);
namespace App\Model;
use PDO;

// Reúne indicadores sin multiplicar ventas al unirlas con pagos o productos.
final class Report
{
    public function __construct(private PDO $db) {}
    public function dashboard(bool $financial): array
    {
        $data = [
            'pets' => (int) $this->db
                ->query('SELECT COUNT(*) FROM pets WHERE active=1')
                ->fetchColumn(),
            'veterinarians' => (int) $this->db
                ->query('SELECT COUNT(*) FROM veterinarians WHERE active=1')
                ->fetchColumn(),
            'today' => (int) $this->db
                ->query(
                    'SELECT COUNT(*) FROM appointments WHERE start_at>=CURRENT_DATE AND start_at<CURRENT_DATE+INTERVAL 1 DAY',
                )
                ->fetchColumn(),
            'species' => $this->db
                ->query(
                    'SELECT species label,COUNT(*) value FROM pets WHERE active=1 GROUP BY species ORDER BY value DESC LIMIT 12',
                )
                ->fetchAll(),
            'states' => $this->db
                ->query('SELECT status label,COUNT(*) value FROM appointments GROUP BY status')
                ->fetchAll(),
            'upcoming' => $this->db
                ->query(
                    "SELECT a.id,a.start_at,a.status,p.name pet_name,v.name veterinarian_name,s.name service_name FROM appointments a JOIN pets p ON p.id=a.pet_id JOIN veterinarians v ON v.id=a.veterinarian_id JOIN services s ON s.id=a.service_id WHERE a.status IN ('solicitada','confirmada') AND a.end_at>=NOW() ORDER BY a.start_at LIMIT 8",
                )
                ->fetchAll(),
            'expiring' => $this->db
                ->query(
                    'SELECT p.name,l.lot_code,l.expires_at,l.quantity FROM stock_lots l JOIN products p ON p.id=l.product_id WHERE l.quantity>0 AND l.expires_at BETWEEN CURRENT_DATE AND CURRENT_DATE+INTERVAL 30 DAY ORDER BY l.expires_at LIMIT 10',
                )
                ->fetchAll(),
            'low_stock' => array_slice(
                array_values(
                    array_filter(
                        new Inventory($this->db)->overview(),
                        static fn($p) => $p['active'] &&
                            bccomp($p['available'], $p['min_stock'], 3) <= 0,
                    ),
                ),
                0,
                10,
            ),
        ];
        if ($financial) {
            $data['sales_today'] = $this->db
                ->query(
                    "SELECT COALESCE(SUM(total),0) FROM sales WHERE status='confirmed' AND created_at>=CURRENT_DATE AND created_at<CURRENT_DATE+INTERVAL 1 DAY",
                )
                ->fetchColumn();
            $data['payments_today'] = $this->db
                ->query(
                    'SELECT COALESCE(SUM(amount),0) FROM payments WHERE created_at>=CURRENT_DATE AND created_at<CURRENT_DATE+INTERVAL 1 DAY',
                )
                ->fetchColumn();
            $data['cash_today'] = $this->db
                ->query(
                    "SELECT COALESCE(SUM(amount),0) FROM payments WHERE method='efectivo' AND created_at>=CURRENT_DATE AND created_at<CURRENT_DATE+INTERVAL 1 DAY",
                )
                ->fetchColumn();
            $data['cash_entries_today'] = $this->db
                ->query(
                    'SELECT COALESCE(SUM(amount),0) FROM cash_entries WHERE created_at>=CURRENT_DATE AND created_at<CURRENT_DATE+INTERVAL 1 DAY',
                )
                ->fetchColumn();
            $data['product_margin'] = $this->db
                ->query(
                    "SELECT COALESCE(SUM(i.line_total-i.cost_total),0) FROM sale_items i JOIN sales s ON s.id=i.sale_id WHERE i.product_id IS NOT NULL AND s.status='confirmed' AND s.created_at>=CURRENT_DATE AND s.created_at<CURRENT_DATE+INTERVAL 1 DAY",
                )
                ->fetchColumn();
        }
        return $data;
    }
    public function rows(
        string $type,
        string $start,
        string $end,
        int $vet = 0,
        int $pet = 0,
        string $state = '',
    ): array {
        $parameters = [$start, $end];
        switch ($type) {
            case 'citas':
                $sql =
                    "SELECT a.id,a.start_at,p.name pet,c.name client,v.name veterinarian,s.name service,a.status,a.price,COALESCE(b.total,0) bill_total,COALESCE((SELECT SUM(pm.amount) FROM payments pm WHERE pm.sale_id=b.id),0) paid FROM appointments a JOIN pets p ON p.id=a.pet_id JOIN clients c ON c.id=p.client_id JOIN veterinarians v ON v.id=a.veterinarian_id JOIN services s ON s.id=a.service_id LEFT JOIN sales b ON b.appointment_id=a.id AND b.status='confirmed' WHERE a.start_at>=? AND a.start_at<?";
                if ($vet) {
                    $sql .= ' AND a.veterinarian_id=?';
                    $parameters[] = $vet;
                }
                if ($pet) {
                    $sql .= ' AND a.pet_id=?';
                    $parameters[] = $pet;
                }
                if ($state !== '') {
                    $sql .= ' AND a.status=?';
                    $parameters[] = $state;
                }
                $sql .= ' ORDER BY a.start_at';
                break;
            case 'ventas':
                $sql =
                    'SELECT s.id,s.created_at,COALESCE(c.name,"Consumidor final") client,s.status,s.total,COALESCE((SELECT SUM(p.amount) FROM payments p WHERE p.sale_id=s.id),0) paid FROM sales s LEFT JOIN clients c ON c.id=s.client_id WHERE s.created_at>=? AND s.created_at<? ORDER BY s.id';
                break;
            case 'cobros':
                $sql =
                    'SELECT p.id,p.created_at,p.sale_id,u.username,p.method,p.amount,p.reversal_of FROM payments p JOIN users u ON u.id=p.user_id WHERE p.created_at>=? AND p.created_at<? ORDER BY p.id';
                break;
            case 'caja':
                $sql =
                    'SELECT s.id,u.username,s.opened_at,s.closed_at,s.opening_amount,s.expected_amount,s.counted_amount,s.difference FROM cash_sessions s JOIN users u ON u.id=s.user_id WHERE s.opened_at>=? AND s.opened_at<? ORDER BY s.id';
                break;
            case 'movimientos':
                $sql =
                    'SELECT m.id,m.created_at,p.code,p.name,l.lot_code,m.kind,m.quantity,m.cost,m.reason,u.username FROM stock_movements m JOIN stock_lots l ON l.id=m.lot_id JOIN products p ON p.id=l.product_id JOIN users u ON u.id=m.user_id WHERE m.created_at>=? AND m.created_at<? ORDER BY m.id';
                break;
            default:
                throw new \LogicException('Reporte no permitido.');
        }
        $query = $this->db->prepare($sql . ' LIMIT 5001');
        $query->execute($parameters);
        return $query->fetchAll();
    }
}
