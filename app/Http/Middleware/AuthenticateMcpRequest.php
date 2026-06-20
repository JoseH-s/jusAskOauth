<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use App\Models\McpToken;
use App\Models\OauthAccessToken;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aceita as duas formas de autenticação do servidor MCP:
 *
 *  1. Token MCP simples (gerado em /{tenant}/mcp) → Claude Code / qualquer cliente HTTP
 *  2. Bearer token OAuth 2.0 (gerado pelo fluxo /oauth/authorize) → Claude Desktop
 *
 * Registrado como 'mcp.token' no bootstrap/app.php substituindo o original.
 */
class AuthenticateMcpRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken()
            ?: (string) $request->query('token', '');

        if ($plainToken === '') {
            return response()->json(['message' => 'Token de autenticação ausente.'], 401);
        }

        $hash = hash('sha256', $plainToken);

        // 1. Token MCP simples
        $mcpToken = McpToken::where('token_hash', $hash)->latest()->first();
        if ($mcpToken) {
            $mcpToken->update(['last_used_at' => now()]);
            $this->ativarTenant($mcpToken->tenant);
            return $next($request);
        }

        // 2. Token OAuth 2.0
        $oauthToken = OauthAccessToken::where('token', $hash)->first();
        if ($oauthToken && $oauthToken->isValid()) {
            $this->ativarTenant($oauthToken->tenant);
            auth()->setUser($oauthToken->user);
            return $next($request);
        }

        return response()->json(['message' => 'Token inválido ou expirado.'], 401);
    }

    private function ativarTenant(string $tenant): void
    {
        $empresa = Empresa::where('tenant', $tenant)->first();
        if ($empresa) {
            app(TenantManager::class)->set($empresa);
        }
    }
}
