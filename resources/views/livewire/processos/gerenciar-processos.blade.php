<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Processos</h1>
        @unless ($mostrarForm)
            <button class="btn btn-primary" wire:click="novo">Novo processo</button>
        @endunless
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning">{{ session('warning') }}</div>
    @endif

    @if ($mostrarForm)
        <div class="card mb-4">
            <div class="card-header">{{ $processoId ? 'Editar processo' : 'Novo processo' }}</div>
            <div class="card-body">
                <form wire:submit="salvar">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Cliente</label>
                            <select class="form-select @error('clienteId') is-invalid @enderror" wire:model="clienteId">
                                <option value="">— Sem cliente —</option>
                                @foreach ($clientes as $cliente)
                                    <option value="{{ $cliente->id }}">{{ $cliente->nome }}</option>
                                @endforeach
                            </select>
                            @error('clienteId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Número do processo *</label>
                            <input type="text" class="form-control @error('numero') is-invalid @enderror" wire:model="numero">
                            @error('numero') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Última atualização</label>
                            <input type="date" class="form-control @error('ultimaAtualizacao') is-invalid @enderror" wire:model="ultimaAtualizacao">
                            @error('ultimaAtualizacao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="ativo" wire:model="ativo">
                                <label class="form-check-label" for="ativo">Processo ativo</label>
                            </div>
                        </div>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary">Salvar</button>
                        <button type="button" class="btn btn-outline-secondary" wire:click="cancelar">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Número</th>
                        <th>Cliente</th>
                        <th>Última atualização</th>
                        <th>Situação</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($processos as $processo)
                        <tr wire:key="processo-{{ $processo->id }}">
                            <td>{{ $processo->numero }}</td>
                            <td>{{ $processo->cliente?->nome ?? '—' }}</td>
                            <td>{{ $processo->ultima_atualizacao?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                @if ($processo->ativo)
                                    <span class="badge bg-success">Ativo</span>
                                @else
                                    <span class="badge bg-secondary">Encerrado</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('processos.detalhe', ['tenant' => app(\App\Services\TenantManager::class)->tenant(), 'processo' => $processo]) }}" class="btn btn-sm btn-outline-secondary">Conteúdo</a>
                                <button class="btn btn-sm btn-outline-primary" wire:click="editar({{ $processo->id }})">Editar</button>
                                <button class="btn btn-sm btn-outline-info"
                                        wire:click="sincronizar({{ $processo->id }})"
                                        wire:loading.attr="disabled"
                                        wire:target="sincronizar({{ $processo->id }})">
                                    <span wire:loading.remove wire:target="sincronizar({{ $processo->id }})">Sincronizar</span>
                                    <span wire:loading wire:target="sincronizar({{ $processo->id }})">Sincronizando...</span>
                                </button>
                                <button class="btn btn-sm btn-outline-danger"
                                        wire:click="excluir({{ $processo->id }})"
                                        wire:confirm="Remover este processo?">Excluir</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-4">Nenhum processo cadastrado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
