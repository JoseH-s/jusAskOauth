<div class="container">
    <div class="mb-4">
        <h1 class="h3 mb-0">Token CNJ (PDPJ)</h1>
        <p class="text-muted small mb-0">Cole aqui o token JWT gerado no portal PJe. O token mais recente será usado automaticamente nas consultas à API.</p>
    </div>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    {{-- Formulário de cadastro --}}
    <div class="card mb-4">
        <div class="card-body">
            <form wire:submit="salvar">
                <label class="form-label fw-semibold">Novo token</label>
                <div class="input-group">
                    <input
                        type="password"
                        class="form-control font-monospace @error('tokenInput') is-invalid @enderror {{ $erro ? 'is-invalid' : '' }}"
                        wire:model="tokenInput"
                        placeholder="eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."
                        autocomplete="off"
                        autofocus
                    >
                    <button class="btn btn-primary" type="submit" wire:loading.attr="disabled">
                        <span wire:loading.remove>Salvar</span>
                        <span wire:loading>Salvando…</span>
                    </button>
                </div>
                @if ($erro)
                    <div class="text-danger small mt-1">{{ $erro }}</div>
                @endif
                <div class="form-text">Pressione <kbd>Enter</kbd> ou clique em <strong>Salvar</strong> para cadastrar.</div>
            </form>
        </div>
    </div>

    {{-- Histórico de tokens --}}
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="fw-semibold">Histórico de tokens</span>
            <small class="text-muted">O primeiro da lista é o ativo</small>
        </div>
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Token</th>
                        <th>Cadastrado em</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tokens as $loop_token)
                        <tr wire:key="token-{{ $loop_token->id }}">
                            <td>
                                @if ($loop->first)
                                    <span class="badge bg-success me-1">Ativo</span>
                                @endif
                                <code class="text-break" style="font-size:.8rem;">
                                    {{ substr($loop_token->token, 0, 40) }}…
                                </code>
                            </td>
                            <td class="text-nowrap text-muted small">
                                {{ $loop_token->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="text-end">
                                <button
                                    class="btn btn-sm btn-outline-danger"
                                    wire:click="excluir({{ $loop_token->id }})"
                                    wire:confirm="Remover este token?"
                                >Remover</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                Nenhum token cadastrado. Cole o token acima para começar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
