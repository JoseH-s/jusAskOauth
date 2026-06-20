<div class="container">
    <div class="mb-3">
        <a href="{{ route('processos', ['tenant' => app(\App\Services\TenantManager::class)->tenant()]) }}" class="text-decoration-none">&larr; Voltar aos processos</a>
    </div>

    <h1 class="h3">Processo {{ $processo->numero }}</h1>
    <p class="text-muted">
        Cliente: <strong>{{ $processo->cliente?->nome }}</strong>
        @if ($processo->ativo)
            <span class="badge bg-success">Ativo</span>
        @else
            <span class="badge bg-secondary">Encerrado</span>
        @endif
    </p>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card mb-4">
        <div class="card-header">{{ $conteudoId ? 'Editar conteúdo' : 'Novo conteúdo' }}</div>
        <div class="card-body">
            <form wire:submit="salvar">
                <div class="mb-3">
                    <label class="form-label">Número do processo *</label>
                    <input type="text" class="form-control @error('numeroProcesso') is-invalid @enderror" wire:model="numeroProcesso">
                    @error('numeroProcesso') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="mb-3">
                    <label class="form-label">Conteúdo *</label>
                    <textarea class="form-control @error('conteudo') is-invalid @enderror" rows="5" wire:model="conteudo"></textarea>
                    @error('conteudo') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <button type="submit" class="btn btn-primary">Salvar</button>
                @if ($conteudoId)
                    <button type="button" class="btn btn-outline-secondary" wire:click="resetForm">Cancelar edição</button>
                @endif
            </form>
        </div>
    </div>

    {{-- Dados sincronizados da API PDPJ --}}
    @if ($apiSnapshots->isNotEmpty())
        @php $snap = $apiSnapshots->first(); @endphp
        <div class="card mb-4 border-info">
            <div class="card-header bg-info bg-opacity-10 d-flex justify-content-between align-items-center">
                <span class="fw-semibold">Dados da API — PDPJ</span>
                <small class="text-muted">Última sincronização: {{ \Carbon\Carbon::parse($snap['sincronizado_em'])->format('d/m/Y H:i') }}</small>
            </div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <small class="text-muted d-block">Número do processo</small>
                        <strong>{{ $snap['numero_processo'] ?? '—' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Tribunal</small>
                        <strong>{{ $snap['tribunal_nome'] ?? $snap['tribunal_sigla'] ?? '—' }}</strong>
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Segmento</small>
                        {{ $snap['segmento'] ? str_replace('_', ' ', $snap['segmento']) : '—' }}
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Data de ajuizamento</small>
                        {{ $snap['data_ajuizamento'] ? \Carbon\Carbon::parse($snap['data_ajuizamento'])->format('d/m/Y H:i') : '—' }}
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Última distribuição</small>
                        {{ $snap['data_ultima_distribuicao'] ? \Carbon\Carbon::parse($snap['data_ultima_distribuicao'])->format('d/m/Y H:i') : '—' }}
                    </div>
                    <div class="col-md-4">
                        <small class="text-muted d-block">Valor da ação</small>
                        {{ $snap['valor_acao'] !== null ? 'R$ ' . number_format($snap['valor_acao'], 2, ',', '.') : '—' }}
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Classe processual</small>
                        {{ $snap['classe'] ?? '—' }}
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Assunto</small>
                        {{ $snap['assunto'] ?? '—' }}
                        @if ($snap['assunto_hierarquia'])
                            <div><small class="text-muted">{{ $snap['assunto_hierarquia'] }}</small></div>
                        @endif
                    </div>
                </div>

                @if (!empty($snap['movimentos']))
                    <h6 class="mt-2 mb-2">Movimentos ({{ count($snap['movimentos']) }})</h6>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-sm table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:50px">#</th>
                                    <th style="width:140px">Data/Hora</th>
                                    <th>Descrição</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($snap['movimentos'] as $mov)
                                    <tr>
                                        <td>{{ $mov['sequencia'] ?? '—' }}</td>
                                        <td>{{ isset($mov['dataHora']) ? \Carbon\Carbon::parse($mov['dataHora'])->format('d/m/Y H:i') : '—' }}</td>
                                        <td>{{ $mov['descricao'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @else
        <div class="alert alert-secondary mb-4">Nenhum dado sincronizado com a API. Use o botão <strong>Sincronizar</strong> na lista de processos.</div>
    @endif

    <h2 class="h5">Conteúdos registrados</h2>
    @forelse ($conteudos as $registro)
        <div class="card mb-2" wire:key="conteudo-{{ $registro->id }}">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <strong>Processo nº {{ $registro->numero_processo }}</strong>
                    <small class="text-muted">{{ $registro->created_at?->format('d/m/Y H:i') }}</small>
                </div>
                <p class="mb-2" style="white-space: pre-wrap;">{{ $registro->conteudo }}</p>
                <button class="btn btn-sm btn-outline-primary" wire:click="editar({{ $registro->id }})">Editar</button>
                <button class="btn btn-sm btn-outline-danger"
                        wire:click="excluir({{ $registro->id }})"
                        wire:confirm="Remover este conteúdo?">Excluir</button>
            </div>
        </div>
    @empty
        <p class="text-muted">Nenhum conteúdo registrado para este processo.</p>
    @endforelse

    {{-- ── Contatos de Notificação ─────────────────────────────────── --}}
    <hr class="my-4">
    <h2 class="h5 mb-3">Contatos para notificação</h2>
    <p class="text-muted" style="font-size:.875rem;">
        Cadastre e-mails e telefones que serão notificados automaticamente quando este processo for atualizado na API PDPJ.
    </p>

    <div class="card mb-3">
        <div class="card-body">
            <form wire:submit="adicionarContato" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label">Tipo</label>
                    <select class="form-select form-select-sm" wire:model="contatoTipo" style="min-width:110px;">
                        <option value="email">E-mail</option>
                        <option value="telefone">Telefone</option>
                    </select>
                </div>
                <div class="col">
                    <label class="form-label">Valor</label>
                    <input type="text" class="form-control form-control-sm @error('contatoValor') is-invalid @enderror"
                           wire:model="contatoValor"
                           placeholder="{{ $contatoTipo === 'email' ? 'contato@email.com' : '(00) 00000-0000' }}">
                    @error('contatoValor') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-sm btn-primary">Adicionar</button>
                </div>
            </form>
        </div>
    </div>

    @if ($contatos->isNotEmpty())
        <div class="card mb-4">
            <ul class="list-group list-group-flush">
                @foreach ($contatos as $contato)
                    <li class="list-group-item d-flex align-items-center justify-content-between py-2" wire:key="contato-{{ $contato->id }}">
                        <span>
                            <span class="badge {{ $contato->tipo === 'email' ? 'bg-primary' : 'bg-success' }} me-2">
                                {{ $contato->tipo === 'email' ? 'E-mail' : 'Tel' }}
                            </span>
                            {{ $contato->valor }}
                        </span>
                        <button class="btn btn-sm btn-outline-danger"
                                wire:click="removerContato({{ $contato->id }})"
                                wire:confirm="Remover este contato?">✕</button>
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        <p class="text-muted mb-4">Nenhum contato cadastrado.</p>
    @endif

    {{-- ── Notificações deste processo ─────────────────────────────── --}}
    @if ($notificacoes->isNotEmpty())
        <hr class="my-4">
        <h2 class="h5 mb-3">Histórico de atualizações detectadas</h2>
        <div class="card">
            <ul class="list-group list-group-flush">
                @foreach ($notificacoes as $notif)
                    <li class="list-group-item {{ $notif->lida ? '' : 'list-group-item-warning' }} py-2"
                        wire:key="notif-{{ $notif->id }}">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong>{{ $notif->titulo }}</strong>
                                <p class="mb-0 text-muted" style="font-size:.83rem;">{{ $notif->mensagem }}</p>
                                <small class="text-muted">{{ $notif->created_at->format('d/m/Y H:i') }}</small>
                            </div>
                            @unless ($notif->lida)
                                <button class="btn btn-sm btn-outline-secondary ms-3 flex-shrink-0"
                                        wire:click="marcarNotificacaoLida({{ $notif->id }})">
                                    Marcar como lida
                                </button>
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
