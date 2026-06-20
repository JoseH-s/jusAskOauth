<?php

namespace App\Http\Controllers;

use App\Models\McpToken;
use App\Services\McpProcessoService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class McpController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = (string) $request->route('tenant');

        // Um token MCP por empresa (tenant).
        $token = $request->user()
            ->mcpTokens()
            ->where('tenant', $tenant)
            ->latest()
            ->first();

        return view('mcp.index', [
            'token' => $token,
            'plainToken' => session('mcp_plain_token'),
            'tenant' => $tenant,
            'mcpUrl' => url('/mcp/jus-ask'),
        ]);
    }

    public function regenerateToken(Request $request): RedirectResponse
    {
        $tenant = (string) $request->route('tenant');
        $plainToken = 'mcp_' . Str::random(64);

        // Substitui apenas o token desta empresa, preservando os de outras.
        $request->user()->mcpTokens()->where('tenant', $tenant)->delete();

        $request->user()->mcpTokens()->create([
            'tenant' => $tenant,
            'token_hash' => hash('sha256', $plainToken),
            'token_preview' => substr($plainToken, 0, 10) . '...' . substr($plainToken, -6),
        ]);

        return redirect()
            ->route('mcp.index', ['tenant' => $tenant])
            ->with('status', 'Token MCP gerado. Copie agora, ele nao sera exibido novamente.')
            ->with('mcp_plain_token', $plainToken);
    }

    public function consultarProcessos(Request $request, McpProcessoService $service): JsonResponse
    {
        $mcpToken = $this->resolverTokenMcp($request);

        if (! $mcpToken) {
            return response()->json([
                'message' => 'Token MCP ausente ou invalido.',
            ], 401);
        }

        $cnpj = (string) $request->input('cnpj', $request->input('cpfCnpjParte', $request->query('cnpj', '')));

        try {
            // Consulta com o token CNJ da empresa dona deste token MCP.
            $resultado = $service->consultarPorCnpj($cnpj, $mcpToken->tenant);

            $mcpToken->update(['last_used_at' => now()]);

            return response()->json($resultado);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (RequestException $e) {
            return response()->json([
                'message' => 'Falha ao consultar a API PDPJ.',
                'status' => $e->response->status(),
                'body' => $e->response->json() ?? $e->response->body(),
            ], $e->response->status());
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Nao foi possivel consultar processos neste momento.',
            ], 500);
        }
    }

    /**
     * Resolve o token MCP enviado via header Authorization: Bearer ou ?token=.
     */
    private function resolverTokenMcp(Request $request): ?McpToken
    {
        $plainToken = $request->bearerToken() ?: (string) $request->query('token', '');

        if ($plainToken === '') {
            return null;
        }

        return McpToken::where('token_hash', hash('sha256', $plainToken))
            ->latest()
            ->first();
    }
}
