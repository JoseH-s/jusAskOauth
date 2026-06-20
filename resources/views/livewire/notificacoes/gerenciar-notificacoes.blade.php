<div class="container" wire:poll.30s>
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0">Notificações</h1>
            @if ($totalNaoLidas > 0)
                <small class="text-muted">{{ $totalNaoLidas }} não lida(s)</small>
            @endif
        </div>
        <div class="d-flex gap-2">
            <div class="form-check form-switch mb-0 d-flex align-items-center gap-2">
                <input class="form-check-input" type="checkbox" id="apenasNaoLidas"
                       wire:model.live="apenasNaoLidas">
                <label class="form-check-label" for="apenasNaoLidas">Apenas não lidas</label>
            </div>
            @if ($totalNaoLidas > 0)
                <button class="btn btn-sm btn-outline-secondary" wire:click="marcarTodasLidas"
                        wire:confirm="Marcar todas as notificações como lidas?">
                    Marcar todas como lidas
                </button>
            @endif
        </div>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        @if ($notificacoes->isEmpty())
            <div class="card-body text-center text-muted py-5">
                Nenhuma notificação encontrada.
            </div>
        @else
            <ul class="list-group list-group-flush">
                @foreach ($notificacoes as $notif)
                    <li class="list-group-item {{ $notif->lida ? '' : 'list-group-item-warning' }} py-3"
                        style="cursor:pointer;"
                        wire:click="abrirModal({{ $notif->id }})"
                        wire:key="notif-{{ $notif->id }}">
                        <div class="d-flex align-items-start justify-content-between gap-3">
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $notif->titulo }}</div>
                                <p class="mb-1 text-muted" style="font-size:.875rem;">{{ $notif->mensagem }}</p>
                                <div style="font-size:.78rem;" class="text-muted">
                                    {{ $notif->created_at->format('d/m/Y \à\s H:i') }}
                                    @if ($notif->processo)
                                        &nbsp;·&nbsp;
                                        <a href="{{ route('processos.detalhe', ['tenant' => app(\App\Services\TenantManager::class)->tenant(), 'processo' => $notif->processo]) }}"
                                           class="text-decoration-none">
                                            Ver processo
                                        </a>
                                    @endif
                                </div>
                            </div>
                            @unless ($notif->lida)
                                <button class="btn btn-sm btn-outline-secondary flex-shrink-0"
                                        wire:click.stop="marcarLida({{ $notif->id }})">
                                    Marcar como lida
                                </button>
                            @endunless
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="card-footer">
                {{ $notificacoes->links() }}
            </div>
        @endif
    </div>

    @if ($notificacaoAberta)
        <div class="modal-backdrop fade show" style="z-index: 1990;"></div>
        <div class="modal fade show d-block"
             tabindex="-1"
             role="dialog"
             style="z-index: 2000;"
             wire:click.self="fecharModal">
            <div class="modal-dialog modal-dialog-centered" style="z-index: 2001;">
                <div class="modal-content shadow-lg">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ $notificacaoAberta->titulo }}</h5>
                        <button type="button" class="btn-close" wire:click="fecharModal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-3">{{ $notificacaoAberta->mensagem }}</p>
                        <p class="text-muted mb-0" style="font-size:.85rem;">
                            Criada em {{ $notificacaoAberta->created_at->format('d/m/Y H:i') }}
                        </p>
                    </div>
                    <div class="modal-footer">
                        @if ($notificacaoAberta->processo)
                            <a class="btn btn-primary"
                               href="{{ route('processos.detalhe', ['tenant' => app(\App\Services\TenantManager::class)->tenant(), 'processo' => $notificacaoAberta->processo]) }}">
                                Ver processo
                            </a>
                        @endif
                        <button type="button" class="btn btn-outline-secondary" wire:click="fecharModal">
                            Fechar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
