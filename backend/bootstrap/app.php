<?php

use App\Http\Middleware\ClerkAuthenticate;
use App\Http\Middleware\RequireAdminRole;
use App\Http\Middleware\VerifySingPayWebhookSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            // Guard Clerk natif — vérifie le JWT à chaque requête protégée
            'clerk.auth' => ClerkAuthenticate::class,
            // Vérification HMAC du webhook SingPay (obligatoire sur la route)
            'singpay.signed' => VerifySingPayWebhookSignature::class,
            // Guard rôle admin — bloque les citoyens sur les routes admin
            'admin.role' => RequireAdminRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
