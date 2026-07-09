<?php

namespace App\Services;

use Spatie\Browsershot\Browsershot;

/**
 * Renders a Blade view to PDF bytes via headless Chrome (Browsershot) for
 * pixel-perfect, full-modern-CSS invoices — real box-shadow, flexbox, web
 * fonts — which dompdf's pure-PHP renderer can't produce. Requires Node.js
 * + a Chrome/Chromium binary + the `puppeteer` npm package on the server
 * (this app runs on a team-controlled VPS, confirmed 2026-07-09 — not
 * shared/budget hosting, where this would be a hard dependency risk).
 *
 * The only thing GenerateAndSendInvoice changed to adopt this: swapping its
 * `Pdf::loadView(...)->output()` (dompdf) call for
 * `app(InvoicePdfRenderer::class)->render(...)`. The view name, view data,
 * invoice number/path generation, and Invoice model persistence are all
 * unchanged.
 */
class InvoicePdfRenderer
{
    public function render(string $view, array $data = []): string
    {
        $html = view($view, $data)->render();

        $browsershot = Browsershot::html($html)
            ->format('A4')
            ->margins(0, 0, 0, 0)
            ->showBackground()
            ->waitUntilNetworkIdle()
            ->noSandbox()
            ->timeout(60);

        if ($chromePath = config('services.browsershot.chrome_path')) {
            $browsershot->setChromePath($chromePath);
        }

        if ($nodeBinary = config('services.browsershot.node_binary')) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        if ($npmBinary = config('services.browsershot.npm_binary')) {
            $browsershot->setNpmBinary($npmBinary);
        }

        return $browsershot->pdf();
    }
}
