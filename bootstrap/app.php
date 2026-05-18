<?php

use App\Http\Middleware\CheckPasswordExpiry;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        // ✅ Register your route middleware here:
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'password.age' => CheckPasswordExpiry::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Customize the 429 Too Many Requests response
        $exceptions->throttle(function (Request $request, string $key, int $maxAttempts, int $minutes) {
            return response()->json([
                'message' => 'Too many requests. Please try again in ' . $minutes . ' minutes.',
                'retry_after_minutes' => $minutes,
            ], 429);
        });
    })->create();
