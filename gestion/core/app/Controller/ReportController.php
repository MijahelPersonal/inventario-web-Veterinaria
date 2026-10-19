<?php
declare(strict_types=1);
namespace App\Controller;
use App\Model\Catalog;
use App\Service\AppointmentService;
use App\Service\DocumentService;
use App\Service\ReportService;
use Core\Controller;

// Exporta exactamente el periodo filtrado sin duplicar la lógica de los reportes.
final class ReportController extends Controller
{
    public function index(array $parameters = []): void
    {
        $data = new ReportService($this->context->db())->generate($_GET);
        $catalog = new Catalog($this->context->db());
        $this->context->view->render(
            'reports/index.html.twig',
            $data + [
                'types' => ReportService::TYPES,
                'states' => AppointmentService::STATES,
                'filters' => $_GET,
                'veterinarians' => $catalog->choices('veterinarians'),
                'pets' => $catalog->choices('pets'),
                'query' => http_build_query($_GET),
            ],
        );
    }
    public function csv(array $parameters = []): void
    {
        $data = new ReportService($this->context->db())->generate($_GET);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte-' . $data['type'] . '.csv"');
        $output = fopen('php://output', 'w');
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, array_values($data['headers']), ',', '"', '');
        foreach ($data['rows'] as $row) {
            fputcsv(
                $output,
                array_map(
                    static fn($key) => ReportService::csvCell($row[$key] ?? ''),
                    array_keys($data['headers']),
                ),
                ',',
                '"',
                '',
            );
        }
        fclose($output);
    }
    public function pdf(array $parameters = []): void
    {
        $data = new ReportService($this->context->db())->generate($_GET);
        if (count($data['rows']) > 500) {
            throw new \DomainException(
                'El PDF admite hasta 500 registros. Reduce el periodo o utiliza CSV.',
            );
        }
        new DocumentService()->pdf(
            $this->context->view->html('documents/report.html.twig', $data),
            'reporte-' . $data['type'] . '.pdf',
        );
    }
}
