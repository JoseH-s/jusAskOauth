<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0">Chaves Gemini</h1>
            <p class="text-muted mb-0 small">Gerencie suas chaves de API do Google Gemini para uso nas integrações com IA.</p>
        </div>
        @unless ($mostrarForm)
            <button class="btn btn-primary" wire:click="novo">Nova chave</button>
        @endunless
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    @if ($mostrarForm)
        <div class="card mb-4">
            <div class="card-header">{{ $chaveId ? 'Editar chave' : 'Nova chave' }}</div>
            <div class="card-body">
                <form wire:submit="salvar">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Apelido *</label>
                            <input type="text"
                                   class="form-control @error('apelido') is-invalid @enderror"
                                   wire:model="apelido"
                                   placeholder="Ex: Principal, Projeto X...">
                            @error('apelido') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Chave de API *</label>
                            <input type="password"
                                   class="form-control @error('chave') is-invalid @enderror"
                                   wire:model="chave"
                                   placeholder="AIzaSy...">
                            @error('chave') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <small class="text-muted">A chave é armazenada e exibida de forma mascarada na listagem.</small>
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
                        <th>Apelido</th>
                        <th>Chave</th>
                        <th>Clientes vinculados</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($chaves as $chave)
                        <tr wire:key="chave-{{ $chave->id }}">
                            <td>{{ $chave->apelido }}</td>
                            <td><code>{{ $chave->chaveMascarada() }}</code></td>
                            <td>{{ $chave->clientes->count() }}</td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary"
                                        wire:click="editar({{ $chave->id }})">Editar</button>
                                <button class="btn btn-sm btn-outline-danger"
                                        wire:click="excluir({{ $chave->id }})"
                                        wire:confirm="Remover esta chave? Clientes vinculados serão desvinculados.">Excluir</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                Nenhuma chave cadastrada. Clique em <strong>Nova chave</strong> para começar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
