<div id="chat-wrapper" wire:poll.300s="verificarSessao">

    {{-- Modal de identificação --}}
    @if (! $identificado)
        <div style="position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:1050;
                    display:flex;align-items:center;justify-content:center;padding:16px;">
            <div style="background:#fff;border-radius:12px;width:100%;max-width:400px;
                        box-shadow:0 8px 32px rgba(0,0,0,.2);overflow:hidden;">
                <div style="background:#075e54;padding:24px 20px;text-align:center;">
                    <div style="width:64px;height:64px;background:#25d366;border-radius:50%;
                                display:inline-flex;align-items:center;justify-content:center;
                                font-size:1.8rem;margin-bottom:10px;">⚖️</div>
                    <h5 style="color:#fff;margin:0 0 4px;font-size:1.1rem;font-weight:700;">Assistente Jurídico</h5>
                    <p style="color:rgba(255,255,255,.8);margin:0;font-size:.85rem;">{{ $empresaNome }}</p>
                </div>

                <div style="padding:24px 20px;">
                    <p style="color:#555;font-size:.88rem;text-align:center;margin-bottom:20px;">
                        Para acessar o chat, informe o número de telefone cadastrado no escritório.
                    </p>

                    <form wire:submit="identificar">
                        <div style="margin-bottom:12px;">
                            <label style="display:block;font-size:.85rem;font-weight:600;color:#333;margin-bottom:6px;">
                                Seu telefone
                            </label>
                            <input
                                type="tel"
                                wire:model="telefoneInput"
                                placeholder="(00) 00000-0000"
                                autofocus
                                style="width:100%;border:1px solid {{ $erroTelefone ? '#dc3545' : '#ddd' }};
                                       border-radius:8px;padding:12px 14px;font-size:1rem;
                                       text-align:center;outline:none;font-family:inherit;
                                       transition:border .2s;"
                            >
                            @if ($erroTelefone)
                                <p style="color:#dc3545;font-size:.82rem;margin:6px 0 0;text-align:center;">
                                    {{ $erroTelefone }}
                                </p>
                            @endif
                        </div>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            wire:target="identificar"
                            style="width:100%;background:#075e54;color:#fff;border:none;border-radius:8px;
                                   padding:12px;font-size:1rem;font-weight:600;cursor:pointer;
                                   font-family:inherit;transition:background .2s;"
                        >
                            <span wire:loading.remove wire:target="identificar">Entrar no chat</span>
                            <span wire:loading wire:target="identificar">Preparando o assistente...</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Cabeçalho --}}
    <div id="chat-header">
        <div class="avatar">⚖️</div>
        <div>
            <h1>Assistente Jurídico</h1>
            <p>
                {{ $empresaNome }}
                @if ($identificado)
                    &nbsp;·&nbsp; {{ $clienteNome }}
                @else
                    &nbsp;·&nbsp; <span style="opacity:.7;">identificação pendente</span>
                @endif
            </p>
        </div>
    </div>

    {{-- Área de mensagens --}}
    <div id="chat-messages">
        @foreach ($messages as $msg)
            @if (! ($msg['hidden'] ?? false))
                <div class="bubble-row {{ $msg['role'] }}">
                    <div class="bubble {{ $msg['role'] }}">
                        @if ($msg['role'] === 'model')
                            <div class="md">{!! $msg['html'] !!}</div>
                        @else
                            {{ $msg['text'] }}
                        @endif
                        <div class="bubble-time">{{ $msg['hora'] }}</div>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Alerta de erro da API --}}
    @if ($erroApi)
        <div style="margin:0 12px 8px;padding:10px 14px;background:#fff3cd;border:1px solid #ffc107;
                    border-radius:8px;font-size:.82rem;color:#664d03;display:flex;align-items:flex-start;gap:8px;">
            <span style="font-size:1rem;flex-shrink:0;">⚠️</span>
            <div>
                <strong>Erro ao processar sua mensagem:</strong><br>
                {{ $erroApi }}
            </div>
            <button wire:click="$set('erroApi','')"
                    style="margin-left:auto;background:none;border:none;cursor:pointer;color:#664d03;
                           font-size:1.1rem;line-height:1;flex-shrink:0;"
                    title="Fechar">×</button>
        </div>
    @endif

    {{-- Indicador "Assistente está digitando" — aparece durante o envio (lado do cliente) --}}
    <div id="typing-indicator" wire:loading.flex wire:target="enviar">
        <div class="ti-avatar">⚖️</div>
        <div class="ti-bubble">
            <div class="typing-dots">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
            <span class="ti-label">Assistente está digitando…</span>
        </div>
    </div>

    {{-- Barra de input --}}
    <div id="chat-input-bar">
        <textarea
            id="chat-textarea"
            wire:model="mensagemInput"
            placeholder="{{ $identificado ? 'Digite uma mensagem...' : 'Identifique-se para iniciar o chat' }}"
            rows="1"
            @disabled(!$identificado || $processando)
        ></textarea>
        <button
            type="button"
            wire:click="enviar"
            wire:loading.attr="disabled"
            wire:target="enviar"
            @disabled(!$identificado || $processando)
            title="Enviar"
        >
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 16 16">
                <path d="M15.964.686a.5.5 0 0 0-.65-.65L.767 5.855H.766l-.452.18a.5.5 0 0 0-.082.887l.41.26.001.002 4.995 3.178 3.178 4.995.002.002.26.41a.5.5 0 0 0 .886-.083zm-1.833 1.89L6.637 10.07l-.215-.338L.767 5.855 14.131 2.576z"/>
            </svg>
        </button>
    </div>

</div>
