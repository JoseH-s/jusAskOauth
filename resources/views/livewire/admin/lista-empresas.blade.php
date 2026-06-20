<div class="container">
    <h1 class="h3 mb-3">Empresas (tenants)</h1>
    <p class="text-muted">Visão global de super-admin: todas as empresas cadastradas no sistema.</p>

    <div class="mb-3">
        <input type="text" class="form-control" placeholder="Buscar por nome, tenant, CNPJ ou OAB..." wire:model.live.debounce.300ms="busca">
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Tenant</th>
                        <th>Tipo</th>
                        <th>CNPJ</th>
                        <th>OAB</th>
                        <th>Membros</th>
                        <th>Clientes</th>
                        <th>Processos</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($empresas as $empresa)
                        <tr wire:key="empresa-{{ $empresa->id }}">
                            <td>{{ $empresa->nome }}</td>
                            <td><code>{{ $empresa->tenant }}</code></td>
                            <td>
                                @if ($empresa->is_pessoa_fisica)
                                    <span class="badge bg-info text-dark">Pessoa física</span>
                                @else
                                    <span class="badge bg-primary">Empresa</span>
                                @endif
                            </td>
                            <td>{{ $empresa->cnpj ?? '—' }}</td>
                            <td>{{ $empresa->oab ?? '—' }}</td>
                            <td>{{ $empresa->membros_count }}</td>
                            <td>{{ $empresa->clientes_count }}</td>
                            <td>{{ $empresa->processos_count }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">Nenhuma empresa encontrada.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
