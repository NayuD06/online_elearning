<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureAccountIsNotLocked;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway (và các nền tảng tương tự) chạy app sau proxy HTTPS
        $middleware->trustProxies(at: '*');

        // Sign out accounts an admin locked while they were signed in, and every other session of an
        // account whose password changed (e.g. reset by its real owner).
        $middleware->web(append: [AuthenticateSession::class, EnsureAccountIsNotLocked::class]);

        // Clickjacking, MIME sniffing and HTTPS headers on every response, error pages included.
        $middleware->append(AddSecurityHeaders::class);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
