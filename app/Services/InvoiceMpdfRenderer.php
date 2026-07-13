<?php

namespace App\Services;

use Mpdf\Mpdf;

/**
 * Renders a Blade view to PDF bytes using mPDF (pure PHP, no external
 * process — runs fine on Hostinger shared hosting with proc_open disabled).
 *
 * Replaces dompdf for invoice generation specifically: dompdf was found to
 * reverse Arabic characters on the production server (e.g. "فاتورة"
 * rendering as "ةروتاف") even though the exact same template/font/code
 * rendered correctly on local dev — an environment-specific dompdf bidi/RTL
 * failure that never reproduced locally, so it couldn't be fixed by
 * further dompdf template changes. mPDF's own script-detection and
 * bidi/shaping engine handles Arabic independently of that dompdf-specific
 * code path. See InvoicePdfRenderer (kept as an emergency rollback engine,
 * selected via config('invoice.pdf_engine')) for the previous approach.
 *
 * 'dejavusans' is mPDF's own bundled font (vendor/mpdf/mpdf/ttfonts) — no
 * font files to embed/bundle ourselves, and it fully supports Arabic
 * shaping. autoScriptToLang + autoLangToFont let mPDF auto-select the
 * correct script handling per run of text, so mixed Arabic/English content
 * (an email address inside an Arabic sentence, an EGP amount, etc.) is
 * handled correctly without any manual per-fragment direction-tagging.
 */
class InvoiceMpdfRenderer
{
    public function render(string $view, array $data = []): string
    {
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 8,
            'margin_right' => 8,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'margin_header' => 0,
            'margin_footer' => 0,
            'tempDir' => config('invoice.mpdf_temp_dir'),
            'default_font' => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
        ]);

        $isRtl = (bool) ($data['isRtl'] ?? false);
        $mpdf->SetDirectionality($isRtl ? 'rtl' : 'ltr');

        $html = view($view, $data)->render();

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}
