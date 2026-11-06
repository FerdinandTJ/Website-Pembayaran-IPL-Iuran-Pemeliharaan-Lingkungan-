<?php

namespace App\Services;

use Dompdf\Dompdf;
use Dompdf\Options;

class ReportPdf
{
    public function render(array $data): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('isJavascriptEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $pdf = new Dompdf($options);
        $pdf->loadHtml(view($data['report'] === 'annual' ? 'pengurus.reports.annual-pdf' : 'pengurus.reports.pdf', $data)->render());
        $pdf->setPaper('A4', 'landscape');
        $pdf->render();
        $font = $pdf->getFontMetrics()->getFont('DejaVu Sans');
        $pdf->getCanvas()->page_text(32, 571, 'EnviroPay | Laporan pengurus', $font, 8, [0.38, 0.45, 0.42]);
        $pdf->getCanvas()->page_text(675, 571, 'Halaman {PAGE_NUM} / {PAGE_COUNT}', $font, 8, [0.38, 0.45, 0.42]);

        return $pdf->output();
    }
}
