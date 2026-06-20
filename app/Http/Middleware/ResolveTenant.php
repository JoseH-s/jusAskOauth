<?php

namespace App\Http\Middleware;

use App\Models\Empresa;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolve a empresa (tenant) ativa e a registra no TenantManager, que
 * alimenta o global scope multi-tenant. O tenant pode vir do slug na URL
 * (ex: /{tenant}/clientes) ou da sessao (requests sem slug, como /home).
 */
class ResolveTenant
{
    public function __construct(private TenantManager $tenant)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $slug = $request->route('tenant');
        $user = $request->user();

        // Rotas publicas (sem login): ativa o tenant apenas pelo slug da URL.
        if (! $user) {
            if ($slug && $empresa = Empresa::where('tenant', $slug)->first()) {
                $this->tenant->set($empresa);
            }

            return $next($request);
        }

        if ($slug) {
            // Tenant na URL: o usuario precisa ser membro ativo dele.
            $membro = $user->membros()
                ->whereHas('empresa', fn ($q) => $q->where('tenant', $slug))
                ->where('ativo', true)
                ->first();

            abort_unless($membro, 403, 'Você não tem acesso a este tenant.');
        } else {
            // Sem slug: usa a empresa da sessao, com fallback no primeiro membro ativo.
            $empresaId = $request->session()->get('empresa_ativa_id');

            $membro = $user->membros()->where('ativo', true)
                ->when($empresaId, fn ($q) => $q->where('empresa_id', $empresaId))
                ->first()
                ?? $user->membros()->where('ativo', true)->first();
        }

        if ($membro) {
            $this->tenant->set($membro->empresa);
            $request->session()->put('empresa_ativa_id', $membro->empresa_id);
        }

        return $next($request);
    }
}
