<?php

use App\Http\Controllers\BlogController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\McpController;
use App\Http\Controllers\OauthController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\VerificarProcessosController;
use App\Http\Controllers\WhatsappWebhookController;
use App\Livewire\Admin\ListaEmpresas;
use App\Livewire\Chat\ChatPublico;
use App\Livewire\ChavesGemini\GerenciarChavesGemini;
use App\Livewire\Clientes\GerenciarClientes;
use App\Livewire\Notificacoes\GerenciarNotificacoes;
use App\Livewire\Processos\DetalheProcesso;
use App\Livewire\Processos\GerenciarProcessos;
use App\Livewire\Site\GerenciarSite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Raiz e autenticação
|--------------------------------------------------------------------------
*/
Route::view('/', 'welcome');
Auth::routes(['verify' => false]);

/*
|--------------------------------------------------------------------------
| APIs sem tenant (autenticação própria por token, não por sessão)
|--------------------------------------------------------------------------
*/

// Endpoint MCP: consulta de processos por CNPJ. Não pertence a nenhum tenant;
// é autenticado pelo token MCP gerado na tela /{tenant}/mcp.
Route::post('/mcp/processos', [McpController::class, 'consultarProcessos'])
    ->name('mcp.processos');

// Webhook da Evolution API (WhatsApp). Autenticado por token na query (?token=).
// Preferencial com tenant no path; o fallback sem tenant aceita ?tenant=.
Route::post('/{tenant}/webhooks/whatsapp', [WhatsappWebhookController::class, 'handle'])
    ->name('webhooks.whatsapp.tenant');
Route::post('/webhooks/whatsapp', [WhatsappWebhookController::class, 'handle'])
    ->name('webhooks.whatsapp');

// Verificação de todos os processos ativos (estilo cron). Token na query (?token=).
Route::get('/processos/verificar', VerificarProcessosController::class)
    ->name('processos.verificar');

/*
|--------------------------------------------------------------------------
| OAuth 2.0 — Authorization Code + PKCE  (Claude Desktop)
|--------------------------------------------------------------------------
*/
Route::get('/oauth/authorize', [OauthController::class, 'authorize'])
    ->name('oauth.authorize');

Route::post('/oauth/authorize', [OauthController::class, 'approveAuthorization'])
    ->name('oauth.authorize.approve');

Route::post('/oauth/token', [OauthController::class, 'token'])
    ->name('oauth.token')
    ->withoutMiddleware([\App\Http\Middleware\CheckSessionExpiry::class]);

Route::get('/oauth/token-info', [OauthController::class, 'tokenInfo'])
    ->name('oauth.token-info');

/*
|--------------------------------------------------------------------------
| Rotas públicas
|--------------------------------------------------------------------------
*/

// Chat público do cliente (com tenant, sem login).
Route::get('/{tenant}/chat', ChatPublico::class)->name('chat.publico');

// Micro-blog público (sem tenant).
Route::get('/blog/{site:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/blog/{site:slug}/{post:slug}', [BlogController::class, 'post'])->name('blog.post');

// Cadastro de token CNJ via link (com tenant).
Route::get('/{tenant}/token-cnj/{data}', [TokenController::class, 'telaCnj'])->name('getTokenCnj');
Route::post('/{tenant}/token-cnj', [TokenController::class, 'store'])->name('postTokenCnj');

/*
|--------------------------------------------------------------------------
| Rotas autenticadas (painel)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::post('/empresa/trocar', [EmpresaController::class, 'trocar'])->name('empresa.trocar');

    Route::get('/admin/empresas', ListaEmpresas::class)
        ->middleware('can:super-admin')
        ->name('admin.empresas');

    // Painel da empresa (com tenant).
    Route::prefix('{tenant}')->group(function () {
        Route::get('/clientes', GerenciarClientes::class)->name('clientes');
        Route::get('/processos', GerenciarProcessos::class)->name('processos');
        Route::get('/processos/{processo}', DetalheProcesso::class)->name('processos.detalhe');
        Route::get('/site', GerenciarSite::class)->name('site');
        Route::get('/chaves-gemini', GerenciarChavesGemini::class)->name('chaves-gemini');
        Route::get('/notificacoes', GerenciarNotificacoes::class)->name('notificacoes');

        // Gestão do token MCP (a consulta em si é a rota global mcp.processos).
        Route::get('/mcp', [McpController::class, 'index'])->name('mcp.index');
        Route::post('/mcp/token', [McpController::class, 'regenerateToken'])->name('mcp.token.regenerate');
    });
});