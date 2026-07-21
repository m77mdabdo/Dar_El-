<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\EnsureAdminPermission;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Hostinger (like most shared hosting) terminates HTTPS at a proxy
        // in front of PHP-FPM, so the request Laravel actually receives is
        // plain HTTP with an X-Forwarded-Proto header. Without this,
        // Request::secure()/url()/route() all report/generate http:// even
        // on an https:// site — exactly the class of bug that can send an
        // OAuth callback or any absolute redirect out on the wrong scheme.
        // The proxy's IP isn't fixed/published by the host, so '*' (trust
        // whichever immediate proxy forwarded the request) is Laravel's
        // own documented setting for this hosting situation.
        $middleware->trustProxies(
            at: '*',
            headers: SymfonyRequest::HEADER_X_FORWARDED_FOR
                | SymfonyRequest::HEADER_X_FORWARDED_HOST
                | SymfonyRequest::HEADER_X_FORWARDED_PORT
                | SymfonyRequest::HEADER_X_FORWARDED_PROTO
                | SymfonyRequest::HEADER_X_FORWARDED_AWS_ELB,
        );

        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'admin.permission' => EnsureAdminPermission::class,
            'super_admin' => SuperAdminMiddleware::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
