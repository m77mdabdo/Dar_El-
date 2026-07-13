<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Invoice PDF Engine
    |--------------------------------------------------------------------------
    |
    | InvoicePdfService is the only thing that reads this value — no
    | controller/job/mailable should ever instantiate a PDF renderer
    | directly. 'mpdf' is the default: it renders Arabic RTL text correctly
    | on Hostinger shared hosting (dompdf was found to reverse Arabic
    | characters there — a production-environment-specific failure that
    | never reproduced locally), and needs no proc_open/shell/Node.
    |
    | 'dompdf' is kept only as an emergency rollback path (e.g.
    | INVOICE_PDF_ENGINE=dompdf in .env) while mpdf is still being verified
    | in production — not exposed to end users, purely an ops escape hatch.
    |
    */

    'pdf_engine' => env('INVOICE_PDF_ENGINE', 'mpdf'),

    /*
    |--------------------------------------------------------------------------
    | mPDF temp directory
    |--------------------------------------------------------------------------
    |
    | mPDF writes working files here during rendering. Must be writable by
    | the PHP process; never inside a publicly served directory.
    |
    */

    'mpdf_temp_dir' => storage_path('app/mpdf-temp'),

];
