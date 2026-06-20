<div class="container">
    {{-- Cabeçalho da página --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Clientes</h1>
        <button class="btn btn-primary" wire:click="novo">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-person-plus-fill me-1 mb-1" viewBox="0 0 16 16">
                <path d="M1 14s-1 0-1-1 1-4 6-4 6 3 6 4-1 1-1 1zm5-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6"/>
                <path fill-rule="evenodd" d="M13.5 5a.5.5 0 0 1 .5.5V7h1.5a.5.5 0 0 1 0 1H14v1.5a.5.5 0 0 1-1 0V8h-1.5a.5.5 0 0 1 0-1H13V5.5a.5.5 0 0 1 .5-.5"/>
            </svg>
            Novo cliente
        </button>
    </div>

    @if (session('status'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('status') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- ═══════════════════════════════════════════════════
         MODAL
    ════════════════════════════════════════════════════ --}}
    <div id="clienteModal"
         class="modal fade"
         tabindex="-1"
         wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">

                {{-- ── HEADER ─────────────────────────────────────── --}}
                <div class="modal-header border-bottom-0 pb-0">
                    <div class="w-100">
                        <div class="d-flex justify-content-between align-items-start">
                            <h5 class="modal-title fw-bold">
                                @if ($criandoNovo)
                                    Novo cliente
                                @else
                                    {{ $nome ?: 'Editar cliente' }}
                                @endif
                            </h5>
                            <button type="button" class="btn-close" wire:click="cancelar"></button>
                        </div>

                        {{-- Stepper (somente criação) --}}
                        @if ($criandoNovo)
                            <div class="d-flex align-items-center mt-3 mb-1 px-1">
                                @php
                                    $passos = [
                                        'dados'     => ['num' => 1, 'label' => 'Dados'],
                                        'processos' => ['num' => 2, 'label' => 'Processos'],
                                        'chave'     => ['num' => 3, 'label' => 'Chave IA'],
                                    ];
                                    $ordemAbas = array_keys($passos);
                                    $indiceAtual = array_search($abaAtiva, $ordemAbas);
                                @endphp
                                @foreach ($passos as $key => $passo)
                                    @php
                                        $indice    = array_search($key, $ordemAbas);
                                        $concluido = $indice < $indiceAtual;
                                        $ativo     = $key === $abaAtiva;
                                        $futuro    = $indice > $indiceAtual;
                                    @endphp

                                    {{-- Círculo do passo --}}
                                    <div class="d-flex flex-column align-items-center" style="min-width:56px">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold"
                                             style="width:36px;height:36px;font-size:.85rem;
                                                    background:{{ $concluido ? '#198754' : ($ativo ? '#0d6efd' : '#dee2e6') }};
                                                    color:{{ ($concluido || $ativo) ? '#fff' : '#6c757d' }};">
                                            @if ($concluido)
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16"><path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0z"/></svg>
                                            @else
                                                {{ $passo['num'] }}
                                            @endif
                                        </div>
                                        <small class="mt-1 fw-semibold"
                                               style="font-size:.7rem;color:{{ $ativo ? '#0d6efd' : ($concluido ? '#198754' : '#adb5bd') }}">
                                            {{ $passo['label'] }}
                                        </small>
                                    </div>

                                    {{-- Linha conectora --}}
                                    @if (! $loop->last)
                                        <div class="flex-grow-1 mx-1" style="height:2px;margin-bottom:20px;
                                             background:{{ $concluido ? '#198754' : '#dee2e6' }};"></div>
                                    @endif
                                @endforeach
                            </div>

                        {{-- Nav-tabs (somente edição) --}}
                        @else
                            <ul class="nav nav-tabs mt-2 border-bottom-0">
                                <li class="nav-item">
                                    <button wire:click="$set('abaAtiva','dados')"
                                            class="nav-link {{ $abaAtiva === 'dados' ? 'active' : '' }}">
                                        Dados
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button wire:click="$set('abaAtiva','processos')"
                                            class="nav-link {{ $abaAtiva === 'processos' ? 'active' : '' }}">
                                        Processos
                                        @if ($processos->isNotEmpty())
                                            <span class="badge bg-secondary ms-1">
                                                {{ $processos->count() }}
                                            </span>
                                        @endif
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button wire:click="$set('abaAtiva','chave')"
                                            class="nav-link {{ $abaAtiva === 'chave' ? 'active' : '' }}">
                                        Chave IA
                                    </button>
                                </li>
                            </ul>
                        @endif
                    </div>
                </div>

                {{-- ── BODY ──────────────────────────────────────── --}}
                <div class="modal-body pt-2">

                    {{-- ── ABA DADOS ─────────────────────────────── --}}
                    @if ($abaAtiva === 'dados')
                        <form wire:submit="salvar" id="formDados">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Nome <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('nome') is-invalid @enderror"
                                           wire:model="nome"
                                           placeholder="Nome completo">
                                    @error('nome') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">Telefone <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('telefone') is-invalid @enderror"
                                           wire:model="telefone"
                                           placeholder="(00) 00000-0000">
                                    @error('telefone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">E-mail <span class="text-danger">*</span></label>
                                    <input type="email"
                                           class="form-control @error('email') is-invalid @enderror"
                                           wire:model="email"
                                           placeholder="email@exemplo.com">
                                    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold">CPF <span class="text-danger">*</span></label>
                                    <input type="text"
                                           class="form-control @error('cpf') is-invalid @enderror"
                                           wire:model="cpf"
                                           placeholder="000.000.000-00">
                                    @error('cpf') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="col-12"><hr class="my-1"><small class="text-muted">Endereço (opcional)</small></div>

                                <div class="col-md-6">
                                    <label class="form-label">Logradouro</label>
                                    <input type="text" class="form-control" wire:model="endereco" placeholder="Rua, Av…">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Número</label>
                                    <input type="text" class="form-control" wire:model="numero">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Bairro</label>
                                    <input type="text" class="form-control" wire:model="bairro">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Cidade</label>
                                    <input type="text" class="form-control" wire:model="cidade">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">UF</label>
                                    <input type="text" class="form-control text-uppercase" wire:model="estado" maxlength="2" placeholder="SP">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">CEP</label>
                                    <input type="text" class="form-control" wire:model="cep" placeholder="00000-000">
                                </div>
                            </div>
                        </form>
                    @endif

                    {{-- ── ABA PROCESSOS ─────────────────────────── --}}
                    @if ($abaAtiva === 'processos')
                        @if (! $clienteId)
                            <div class="alert alert-warning mb-0">
                                Salve os dados do cliente antes de adicionar processos.
                            </div>
                        @else
                            {{-- Botões de ação --}}
                            @unless ($mostrarFormProcesso || $modoVincular)
                                <div class="d-flex gap-2 mb-3">
                                    <button class="btn btn-sm btn-primary" wire:click="novoProcesso">
                                        + Novo processo
                                    </button>
                                    @if ($processosDisponiveis->isNotEmpty())
                                        <button class="btn btn-sm btn-outline-secondary" wire:click="ativarVincular">
                                            Vincular processo existente
                                        </button>
                                    @endif
                                </div>
                            @endunless

                            {{-- Formulário novo / editar processo --}}
                            @if ($mostrarFormProcesso)
                                <div class="card mb-3 border-primary">
                                    <div class="card-header bg-primary bg-opacity-10 text-primary fw-semibold">
                                        {{ $processoId ? 'Editar processo' : 'Novo processo' }}
                                    </div>
                                    <div class="card-body">
                                        <form wire:submit="salvarProcesso">
                                            <div class="row g-3">
                                                <div class="col-md-8">
                                                    <label class="form-label fw-semibold">Número do processo <span class="text-danger">*</span></label>
                                                    <input type="text"
                                                           class="form-control @error('processoNumero') is-invalid @enderror"
                                                           wire:model="processoNumero"
                                                           placeholder="0000000-00.0000.0.00.0000">
                                                    @error('processoNumero') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-semibold">Última atualização</label>
                                                    <input type="date"
                                                           class="form-control @error('processoUltimaAtualizacao') is-invalid @enderror"
                                                           wire:model="processoUltimaAtualizacao">
                                                    @error('processoUltimaAtualizacao') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                                </div>
                                                <div class="col-12">
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" id="processoAtivo" wire:model="processoAtivo">
                                                        <label class="form-check-label" for="processoAtivo">Processo ativo</label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="mt-3 d-flex gap-2">
                                                <button type="submit" class="btn btn-sm btn-primary">Salvar</button>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" wire:click="cancelarProcesso">Cancelar</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            @endif

                            {{-- Tabela de vinculação --}}
                            @if ($modoVincular)
                                <div class="card mb-3 border-secondary">
                                    <div class="card-header bg-secondary bg-opacity-10 fw-semibold d-flex justify-content-between align-items-center">
                                        <span>Vincular processo existente</span>
                                        <button type="button" class="btn-close btn-sm" wire:click="cancelarProcesso"></button>
                                    </div>
                                    <div class="card-body p-0">
                                        @if ($processosDisponiveis->isEmpty())
                                            <p class="text-muted text-center py-4 mb-0">Nenhum processo disponível para vinculação.</p>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-hover mb-0 align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Número</th>
                                                            <th>Assunto</th>
                                                            <th class="text-end">Ação</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($processosDisponiveis as $pd)
                                                            <tr wire:key="disp-{{ $pd->id }}">
                                                                <td class="font-monospace small">{{ $pd->numero }}</td>
                                                                <td class="text-muted small">{{ $pd->assunto ?? '—' }}</td>
                                                                <td class="text-end">
                                                                    <button class="btn btn-sm btn-success"
                                                                            wire:click="vincularProcesso({{ $pd->id }})"
                                                                            wire:confirm="Vincular este processo ao cliente?">
                                                                        Vincular
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            {{-- Processos vinculados --}}
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Número</th>
                                            <th>Última atualização</th>
                                            <th>Situação</th>
                                            <th class="text-end">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($processos as $processo)
                                            <tr wire:key="processo-{{ $processo->id }}">
                                                <td class="font-monospace small">{{ $processo->numero }}</td>
                                                <td>{{ $processo->ultima_atualizacao?->format('d/m/Y') ?? '—' }}</td>
                                                <td>
                                                    @if ($processo->ativo)
                                                        <span class="badge bg-success">Ativo</span>
                                                    @else
                                                        <span class="badge bg-secondary">Encerrado</span>
                                                    @endif
                                                </td>
                                                <td class="text-end">
                                                    <a href="{{ route('processos.detalhe', ['tenant' => app(\App\Services\TenantManager::class)->tenant(), 'processo' => $processo]) }}"
                                                       class="btn btn-sm btn-outline-secondary">Conteúdo</a>
                                                    <button class="btn btn-sm btn-outline-primary"
                                                            wire:click="editarProcesso({{ $processo->id }})">Editar</button>
                                                    <button class="btn btn-sm btn-outline-info"
                                                            wire:click="sincronizarProcesso({{ $processo->id }})"
                                                            wire:loading.attr="disabled"
                                                            wire:target="sincronizarProcesso({{ $processo->id }})">
                                                        <span wire:loading.remove wire:target="sincronizarProcesso({{ $processo->id }})">Sincronizar</span>
                                                        <span wire:loading wire:target="sincronizarProcesso({{ $processo->id }})">Sincronizando…</span>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger"
                                                            wire:click="excluirProcesso({{ $processo->id }})"
                                                            wire:confirm="Remover este processo?">Excluir</button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="4" class="text-center text-muted py-4">
                                                    Nenhum processo vinculado. Clique em <strong>+ Novo processo</strong> para começar.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif

                    {{-- ── ABA CHAVE IA ──────────────────────────── --}}
                    @if ($abaAtiva === 'chave')
                        @if (! $clienteId)
                            <div class="alert alert-warning mb-0">
                                Salve os dados do cliente antes de configurar a chave de IA.
                            </div>
                        @else
                            <p class="text-muted mb-3">
                                Selecione qual chave do Gemini será usada para atender este cliente.
                                Isso permite controlar o consumo de IA individualmente.
                            </p>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label fw-semibold">Chave Gemini</label>
                                    <select class="form-select @error('chaveGeminiId') is-invalid @enderror"
                                            wire:model="chaveGeminiId">
                                        <option value="">— Sem chave —</option>
                                        @foreach ($chavesGemini as $chave)
                                            <option value="{{ $chave->id }}">{{ $chave->apelido }}</option>
                                        @endforeach
                                    </select>
                                    @error('chaveGeminiId') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-4">
                                    <a href="{{ route('chaves-gemini', ['tenant' => app(\App\Services\TenantManager::class)->tenant()]) }}"
                                       class="btn btn-outline-secondary w-100"
                                       target="_blank">
                                        Gerenciar chaves
                                    </a>
                                </div>
                            </div>
                            @if ($chavesGemini->isEmpty())
                                <div class="alert alert-info mt-3 mb-0">
                                    Nenhuma chave cadastrada ainda.
                                    <a href="{{ route('chaves-gemini', ['tenant' => app(\App\Services\TenantManager::class)->tenant()]) }}" target="_blank">Cadastre uma agora</a>.
                                </div>
                            @endif
                        @endif
                    @endif

                </div>{{-- /modal-body --}}

                {{-- ── FOOTER ────────────────────────────────────── --}}
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" wire:click="cancelar">
                        Fechar
                    </button>

                    {{-- Botão principal contextual --}}
                    @if ($abaAtiva === 'dados')
                        <button type="submit" form="formDados" class="btn btn-primary">
                            @if ($criandoNovo && ! $clienteId)
                                Salvar e continuar
                            @else
                                Salvar
                            @endif
                        </button>
                        @if ($clienteId)
                            <button type="button" class="btn btn-primary" wire:click="$set('abaAtiva','processos')">
                                Próximo →
                            </button>
                        @endif

                    @elseif ($abaAtiva === 'processos')
                        @if ($criandoNovo && $clienteId)
                            <button type="button" class="btn btn-primary" wire:click="$set('abaAtiva','chave')">
                                Próximo →
                            </button>
                        @endif

                    @elseif ($abaAtiva === 'chave')
                        <button type="button" class="btn btn-success" wire:click="salvarChaveGemini">
                            @if ($criandoNovo)
                                Concluir
                            @else
                                Salvar chave
                            @endif
                        </button>
                    @endif
                </div>

            </div>{{-- /modal-content --}}
        </div>
    </div>{{-- /modal --}}

    {{-- ═══════════════════════════════════════════════════
         BUSCA + TABELA DE CLIENTES
    ════════════════════════════════════════════════════ --}}
    <div class="mb-3">
        <input type="text"
               class="form-control"
               placeholder="Buscar por nome, e-mail ou CPF…"
               wire:model.live.debounce.300ms="busca">
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Nome</th>
                        <th>Telefone</th>
                        <th>E-mail</th>
                        <th>CPF</th>
                        <th>Processos</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($clientes as $cliente)
                        <tr wire:key="cliente-{{ $cliente->id }}">
                            <td>{{ $cliente->nome }}</td>
                            <td>{{ $cliente->telefone ?? '—' }}</td>
                            <td>{{ $cliente->email }}</td>
                            <td>{{ $cliente->cpf }}</td>
                            <td>
                                <span class="badge bg-secondary">{{ $cliente->processos()->count() }}</span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary"
                                        wire:click="editar({{ $cliente->id }})">Editar</button>
                                <button class="btn btn-sm btn-outline-danger"
                                        wire:click="excluir({{ $cliente->id }})"
                                        wire:confirm="Remover este cliente e seus processos?">Excluir</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Nenhum cliente cadastrado.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════
         JS — controlo do modal via Livewire events
    ════════════════════════════════════════════════════ --}}
    @script
    <script>
        (function () {
            const el    = document.getElementById('clienteModal');
            const modal = new bootstrap.Modal(el, { backdrop: 'static', keyboard: false });

            $wire.on('abrirModal', () => modal.show());
            $wire.on('fecharModal', () => modal.hide());

            // Quando o modal fechar por qualquer meio, reseta o componente
            el.addEventListener('hidden.bs.modal', () => $wire.cancelar());
        })();
    </script>
    @endscript
</div>
