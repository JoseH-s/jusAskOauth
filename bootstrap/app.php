<?php

use App\Http\Middleware\ResolveTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            ResolveTenant::class,
            \App\Http\Middleware\CheckSessionExpiry::class,
        ]);
        $middleware->prependToPriorityList(SubstituteBindings::class, ResolveTenant::class);

        $middleware->alias([
            // Substituição do middleware original:
            // agora aceita token MCP simples OU token OAuth 2.0
            'mcp.token' => \App\Http\Middleware\AuthenticateMcpRequest::class,
        ]);

        // APIs externas não enviam token CSRF.
        $middleware->validateCsrfTokens(except: [
            'mcp/processos',
            'mcp/jus-ask',
            'oauth/token',           // ← token endpoint do OAuth (POST sem cookie)
            'webhooks/whatsapp',
            '*/webhooks/whatsapp',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
