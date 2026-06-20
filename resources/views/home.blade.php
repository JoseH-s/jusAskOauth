@extends('layouts.app')

@section('content')
<div class="container">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h4 mb-0 fw-bold" style="font-family:'Playfair Display',serif;">Painel</h1>
    </div>

    @if (! $empresa)
        <div class="alert alert-warning">
            Nenhuma empresa (tenant) ativa. Use o menu superior para selecionar uma empresa.
        </div>
    @else
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body py-4 px-4">
                        <div class="stat-number">{{ $totalClientes }}</div>
                        <div class="stat-label">Clientes</div>
                        <div class="stat-icon">👤</div>
                        <a href="{{ route('clientes', ['tenant' => $empresa->tenant]) }}" class="stretched-link"></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body py-4 px-4">
                        <div class="stat-number">{{ $totalProcessos }}</div>
                        <div class="stat-label">Processos</div>
                        <div class="stat-icon">⚖️</div>
                        <a href="{{ route('processos', ['tenant' => $empresa->tenant]) }}" class="stretched-link"></a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="card-body py-4 px-4">
                        <div class="stat-number">{{ $processosAbertos }}</div>
                        <div class="stat-label">Em aberto</div>
                        <div class="stat-icon">📂</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body d-flex flex-wrap align-items-center gap-3">
                <div>
                    <div class="fw-semibold" style="font-size:.95rem;">{{ $empresa->nome }}</div>
                    <div class="text-muted" style="font-size:.8rem;">
                        tenant: <code>{{ $empresa->tenant }}</code>
                        &nbsp;·&nbsp;
                        @if ($empresa->is_pessoa_fisica)
                            <span class="badge bg-secondary">pessoa física</span>
                        @else
                            <span class="badge bg-secondary">empresa</span>
                        @endif
                    </div>
                </div>
                <div class="ms-auto d-flex gap-2 flex-wrap">
                    <a href="{{ route('clientes', ['tenant' => $empresa->tenant]) }}"
                       class="btn btn-outline-primary btn-sm">Clientes</a>
                    <a href="{{ route('processos', ['tenant' => $empresa->tenant]) }}"
                       class="btn btn-outline-primary btn-sm">Processos</a>
                    <a href="{{ route('chaves-gemini', ['tenant' => $empresa->tenant]) }}"
                       class="btn btn-outline-secondary btn-sm">Chaves IA</a>
                </div>
            </div>
        </div>
    @endif

    @if (! is_null($totalEmpresas))
        <div class="card mt-4">
            <div class="card-header">Administração</div>
            <div class="card-body d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <span class="text-muted" style="font-size:.88rem;">Total de empresas (tenants) no sistema:</span>
                    <span class="fw-bold ms-1" style="font-size:1.1rem;font-family:'Playfair Display',serif;">{{ $totalEmpresas }}</span>
                </div>
                <a href="{{ route('admin.empresas') }}" class="btn btn-sm btn-outline-primary">
                    Ver todas as empresas
                </a>
            </div>
        </div>
    @endif

</div>
@endsection
