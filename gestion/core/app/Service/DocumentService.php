<?php
declare(strict_types=1);
namespace App\Service;
use Dompdf\Dompdf;
use Dompdf\Options;

// Genera PDF con HTML escapado de Twig y sin descargar recursos de direcciones externas.
final class DocumentService
{
    public function bytes(string $html): string
    {
        $options = new Options([
            'isRemoteEnabled' => false,
            'isPhpEnabled' => false,
            'defaultFont' => 'DejaVu Sans',
        ]);
        $options->setChroot(dirname(__DIR__, 3) . '/public');
        $options->setTempDir(\Core\Paths::storage('cache'));
        $options->setFontCache(\Core\Paths::storage('cache'));
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4');
        $pdf->render();
        return $pdf->output();
    }
    public function pdf(string $html, string $filename): void
    {
        $bytes = $this->bytes($html);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $bytes;
    }
}
