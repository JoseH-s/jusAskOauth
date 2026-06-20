<?php

namespace App\Http\Controllers;

use App\Models\OauthAuthCode;
use App\Models\OauthAccessToken;
use App\Models\OauthClient;
use App\Models\Empresa;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Implementa OAuth 2.0 Authorization Code + PKCE para o Claude Desktop.
 *
 * Fluxo:
 *   1. GET  /oauth/authorize  → exibe tela de consentimento (ou redireciona se já logado)
 *   2. POST /oauth/authorize  → usuário aprova → redireciona com ?code=...
 *   3. POST /oauth/token      → Claude Desktop troca code por access_token
 *   4. GET  /oauth/token-info → (opcional) valida token e retorna info
 */
class OauthController extends Controller
{
    // ─── 1. Authorization endpoint (GET) ──────────────────────────────────────

    public function authorize(Request $request): View|RedirectResponse
    {
        // Se o usuário não está logado, redireciona para login preservando os params
        if (! $request->user()) {
            return redirect()->guest(route('login') . '?' . http_build_query([
                'next' => $request->fullUrl(),
            ]));
        }

        [$client, $error] = $this->resolveClient($request);
        if ($error) {
            return $this->oauthError($error);
        }

        // Descobre o tenant: pode vir em ?tenant= ou ser inferido pela empresa do usuário
        $tenant = $this->resolveTenant($request);
        if (! $tenant) {
            return $this->oauthError('Nenhuma empresa associada ao usuário. Acesse o painel e selecione uma empresa.');
        }

        return view('oauth.authorize', [
            'client'        => $client,
            'tenant'        => $tenant,
            'redirect_uri'  => $request->input('redirect_uri'),
            'state'         => $request->input('state'),
            'code_challenge'        => $request->input('code_challenge'),
            'code_challenge_method' => $request->input('code_challenge_method', 'S256'),
            'scope'         => $request->input('scope', 'mcp'),
        ]);
    }

    // ─── 2. Authorization endpoint (POST — usuário aprovou) ───────────────────

    public function approveAuthorization(Request $request): RedirectResponse
    {
        if (! $request->user()) {
            abort(401);
        }

        $request->validate([
            'client_id'             => ['required', 'string'],
            'redirect_uri'          => ['required', 'url'],
            'tenant'                => ['required', 'string'],
            'state'                 => ['nullable', 'string'],
            'code_challenge'        => ['nullable', 'string'],
            'code_challenge_method' => ['nullable', 'in:S256,plain'],
            'scope'                 => ['nullable', 'string'],
            'approved'              => ['required', 'in:1,0'],
        ]);

        [$client, $error] = $this->resolveClient($request);
        if ($error) {
            return $this->oauthError($error);
        }

        // Usuário negou
        if ($request->input('approved') !== '1') {
            return $this->redirectWithError(
                $request->input('redirect_uri'),
                'access_denied',
                'O usuário negou o acesso.',
                $request->input('state')
            );
        }

        // Gera authorization code (válido por 5 minutos)
        $code = OauthAuthCode::create([
            'code'                  => Str::random(64),
            'user_id'               => $request->user()->id,
            'tenant'                => $request->input('tenant'),
            'client_id'             => $client->id,
            'redirect_uri'          => $request->input('redirect_uri'),
            'code_challenge'        => $request->input('code_challenge'),
            'code_challenge_method' => $request->input('code_challenge_method', 'S256'),
            'scope'                 => $request->input('scope', 'mcp'),
            'used'                  => false,
            'expires_at'            => now()->addMinutes(5),
        ]);

        $params = ['code' => $code->code];
        if ($state = $request->input('state')) {
            $params['state'] = $state;
        }

        return redirect($request->input('redirect_uri') . '?' . http_build_query($params));
    }

    // ─── 3. Token endpoint (POST) ─────────────────────────────────────────────

    public function token(Request $request): JsonResponse
    {
        $grantType = $request->input('grant_type');

        return match ($grantType) {
            'authorization_code' => $this->handleAuthorizationCode($request),
            default => response()->json([
                'error'             => 'unsupported_grant_type',
                'error_description' => "grant_type '{$grantType}' não suportado.",
            ], 400),
        };
    }

    // ─── 4. Token info (GET) — útil para debug ────────────────────────────────

    public function tokenInfo(Request $request): JsonResponse
    {
        $plainToken = $request->bearerToken();
        if (! $plainToken) {
            return response()->json(['error' => 'Token ausente.'], 401);
        }

        $token = OauthAccessToken::where('token', hash('sha256', $plainToken))->first();
        if (! $token || ! $token->isValid()) {
            return response()->json(['error' => 'Token inválido ou expirado.'], 401);
        }

        return response()->json([
            'user_id'    => $token->user_id,
            'tenant'     => $token->tenant,
            'scope'      => $token->scope,
            'expires_at' => $token->expires_at->toIso8601String(),
        ]);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function handleAuthorizationCode(Request $request): JsonResponse
    {
        $request->validate([
            'code'          => ['required', 'string'],
            'client_id'     => ['required', 'string'],
            'redirect_uri'  => ['required', 'string'],
            'code_verifier' => ['required', 'string'], // PKCE obrigatório
        ]);

        $client = OauthClient::where('client_id', $request->input('client_id'))->first();
        if (! $client) {
            return response()->json(['error' => 'invalid_client', 'error_description' => 'Client não encontrado.'], 401);
        }

        $authCode = OauthAuthCode::where('code', $request->input('code'))
            ->where('used', false)
            ->with('client')
            ->first();

        if (! $authCode) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Código inválido ou já utilizado.'], 400);
        }

        if ($authCode->isExpired()) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Código expirado.'], 400);
        }

        if ($authCode->client->client_id !== $request->input('client_id')) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'Client não corresponde ao código.'], 400);
        }

        if ($authCode->redirect_uri !== $request->input('redirect_uri')) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'redirect_uri não corresponde.'], 400);
        }

        // Verifica PKCE
        if (! $this->verifyPkce(
            $request->input('code_verifier'),
            $authCode->code_challenge,
            $authCode->code_challenge_method ?? 'S256'
        )) {
            return response()->json(['error' => 'invalid_grant', 'error_description' => 'PKCE inválido.'], 400);
        }

        // Marca código como usado (one-time use)
        $authCode->update(['used' => true]);

        // Gera access token (válido por 1 hora)
        $plainToken  = Str::random(80);
        $tokenHashed = hash('sha256', $plainToken);

        OauthAccessToken::create([
            'token'      => $tokenHashed,
            'user_id'    => $authCode->user_id,
            'tenant'     => $authCode->tenant,
            'client_id'  => $client->id,
            'scope'      => $authCode->scope,
            'expires_at' => now()->addHour(),
        ]);

        return response()->json([
            'access_token' => $plainToken,
            'token_type'   => 'Bearer',
            'expires_in'   => 3600,
            'scope'        => $authCode->scope,
        ]);
    }

    private function verifyPkce(string $verifier, ?string $challenge, string $method): bool
    {
        if (! $challenge) {
            return true; // sem PKCE configurado, aceita
        }

        if ($method === 'S256') {
            $computed = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');
            return hash_equals($challenge, $computed);
        }

        // plain
        return hash_equals($challenge, $verifier);
    }

    /** @return array{OauthClient|null, string|null} */
    private function resolveClient(Request $request): array
    {
        $clientId = $request->input('client_id');
        if (! $clientId) {
            return [null, 'client_id obrigatório.'];
        }

        $client = OauthClient::where('client_id', $clientId)->first();
        if (! $client) {
            return [null, "Client '{$clientId}' não encontrado."];
        }

        $redirectUri = $request->input('redirect_uri');
        if ($redirectUri && ! $client->allowsRedirectUri($redirectUri)) {
            return [null, 'redirect_uri não autorizado para este client.'];
        }

        return [$client, null];
    }

    private function resolveTenant(Request $request): ?string
    {
        // Prioridade 1: tenant explícito na URL
        if ($tenant = $request->input('tenant')) {
            return $tenant;
        }

        // Prioridade 2: primeira empresa ativa do usuário
        $user = $request->user();
        if ($user) {
            $membro = $user->membros()->where('ativo', true)->first();
            return $membro?->tenant;
        }

        return null;
    }

    private function oauthError(string $message): RedirectResponse
    {
        // Sem redirect_uri seguro para redirecionar, mostra erro na própria app
        abort(400, $message);
    }

    private function redirectWithError(string $uri, string $error, string $description, ?string $state): RedirectResponse
    {
        $params = ['error' => $error, 'error_description' => $description];
        if ($state) {
            $params['state'] = $state;
        }

        return redirect($uri . '?' . http_build_query($params));
    }
}
