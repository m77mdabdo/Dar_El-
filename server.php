<?php

$publicPath = getcwd();

$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// This file allows us to emulate Apache's "mod_rewrite" functionality from the
// built-in PHP web server. This provides a convenient way to test a Laravel
// application without having installed a "real" web server software here.
if ($uri !== '/' && file_exists($publicPath.$uri)) {
    return false;
}

$formattedDateTime = date('D M j H:i:s Y');

$requestMethod = $_SERVER['REQUEST_METHOD'];
$remoteAddress = $_SERVER['REMOTE_ADDR'].':'.$_SERVER['REMOTE_PORT'];

// Project-root override of vendor/laravel/framework's server.php (Laravel's
// own ServeCommand::serverCommand() prefers base_path('server.php') over the
// vendor copy when it exists — this is Laravel's documented, intended way to
// customize the built-in dev server's router without touching vendor code).
//
// The only change from the vendor original: this diagnostic request-log
// line is written with the error-suppression operator. When a browser
// cancels/closes a request before the built-in server finishes writing to
// stdout (e.g. a navigated-away-from page, or an aborted asset request),
// file_put_contents() fails with a "Broken pipe" E_WARNING. That warning
// fires here, before Laravel (and its exception handler) has even
// bootstrapped, so it bypasses APP_DEBUG/logging entirely and — because
// display_errors is on for the CLI SAPI — gets written straight into the
// HTTP response body. The write itself is purely cosmetic (a request-log
// line for the terminal running `php artisan serve`), so a failure here is
// safe to ignore rather than letting it leak into the page.
@file_put_contents('php://stdout', "[$formattedDateTime] $remoteAddress [$requestMethod] URI: $uri\n");

require_once $publicPath.'/index.php';
