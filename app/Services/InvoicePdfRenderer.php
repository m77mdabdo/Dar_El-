<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\FontMetrics;

/**
 * Renders a Blade view to PDF bytes using dompdf (pure PHP, no external
 * process). This app is deployed on Hostinger shared hosting, where
 * exec()/proc_open()/shell_exec() are disabled — Browsershot (headless
 * Chrome) cannot run there at all, in the checkout request or in a queued
 * job, since a queue worker on the same box has the same disabled
 * functions. dompdf has no such requirement.
 *
 * Previously used Browsershot for pixel-perfect flexbox rendering on a
 * team-controlled VPS with Node/Chrome — see git history if that
 * environment ever comes back into play. dompdf has limited/unreliable
 * flexbox support, particularly combined with RTL, so
 * `invoices/pdf.blade.php` was converted to table-based layout instead.
 *
 * Cairo font registration is self-healing: dompdf's font cache
 * (storage/fonts/installed-fonts.json) stores an *absolute path* to each
 * registered TTF, which breaks the moment the app is deployed somewhere
 * with a different absolute path than wherever the cache was generated —
 * this bit us once already (a stale cache from a local dev machine).
 * Registering fresh from the portable, git-tracked source files in
 * resources/fonts/cairo/ on every render avoids that entirely;
 * FontMetrics::registerFont() is cheap and idempotent (no-ops if the
 * target path is already correctly registered).
 */
class InvoicePdfRenderer
{
    protected const CAIRO_FONT_FAMILY = 'Cairo';

    public function render(string $view, array $data = []): string
    {
        $pdf = Pdf::setOptions([
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'defaultFont' => self::CAIRO_FONT_FAMILY,
        ]);

        $this->registerCairoFont($pdf->getDomPDF()->getFontMetrics());

        $html = view($view, $data)->render();

        return $pdf->loadHTML($html)->setPaper('a4')->output();
    }

    protected function registerCairoFont(FontMetrics $fontMetrics): void
    {
        $weights = [
            'normal' => resource_path('fonts/cairo/Cairo-Regular.ttf'),
            'bold' => resource_path('fonts/cairo/Cairo-Bold.ttf'),
        ];

        foreach ($weights as $weight => $ttfPath) {
            if (! is_file($ttfPath)) {
                continue;
            }

            $fontMetrics->registerFont(
                ['family' => self::CAIRO_FONT_FAMILY, 'weight' => $weight, 'style' => 'normal'],
                $ttfPath
            );
        }
    }
}
