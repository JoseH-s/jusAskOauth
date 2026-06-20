@extends('layouts.app')

@section('titulo', 'MCP - Jus-Ask')

@section('content')
    @php($tokenExemplo = $plainToken ?: 'SEU_TOKEN')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="mb-4">
                    <p class="text-muted mb-1">Integração MCP</p>
                    <h1 class="h3 mb-2">Conectar o Claude aos seus processos</h1>
                    <p class="text-muted mb-0">
                        Servidor MCP que permite ao Claude consultar os processos da empresa por CNPJ
                        (base do PDPJ/CNJ) e gerar análises e gráficos. A conexão é autenticada pelo
                        token gerado abaixo.
                    </p>
                </div>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                {{-- Passo 1 — Token --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h5 mb-3">1. Gere o token de acesso</h2>

                        @if ($plainToken)
                            <p class="text-muted mb-2">
                                Copie agora — este token <strong>não será exibido novamente</strong>.
                                Ele já está preenchido nos comandos abaixo.
                            </p>
                            <pre class="bg-light border rounded p-3 mb-3"><code>{{ $plainToken }}</code></pre>
                        @elseif ($token)
                            <p class="text-muted mb-2">
                                Token ativo: <code>{{ $token->token_preview }}</code>
                                @if ($token->last_used_at)
                                    — último uso em {{ $token->last_used_at->format('d/m/Y H:i') }}
                                @endif
                            </p>
                            <p class="text-muted mb-3 small">
                                Por segurança, o token completo não é exibido de novo. Se você não o
                                guardou, gere um novo (o anterior deixa de funcionar).
                            </p>
                        @else
                            <p class="text-muted mb-3">Nenhum token gerado ainda. Gere um para começar.</p>
                        @endif

                        <form method="POST" action="{{ route('mcp.token.regenerate', ['tenant' => $tenant]) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary">
                                {{ $token ? 'Gerar novo token' : 'Gerar token' }}
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Passo 2 — Claude Code (CLI) --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h5 mb-3">2. Adicione o servidor no Claude Code</h2>
                        <p class="text-muted mb-2">
                            No terminal, rode o comando abaixo (um único comando):
                        </p>
                        <pre class="bg-light border rounded p-3 mb-2"><code>claude mcp add --transport http jus-ask {{ $mcpUrl }} \
  --header "Authorization: Bearer {{ $tokenExemplo }}"</code></pre>
                        <p class="text-muted mb-0 small">
                            Confira a conexão com <code>claude mcp list</code>. Para remover:
                            <code>claude mcp remove jus-ask</code>.
                        </p>
                    </div>
                </div>

                {{-- Passo 2 (alternativa) — Config manual / Claude Desktop --}}
                <div class="card mb-4">
                    <div class="card-body">
                        <h2 class="h5 mb-3">2b. Ou configure manualmente (Claude Desktop / arquivo)</h2>
                        <p class="text-muted mb-2">
                            Adicione este bloco ao arquivo de configuração MCP
                            (<code>.mcp.json</code> no projeto, ou o <code>claude_desktop_config.json</code>
                            do Claude Desktop):
                        </p>
                        <pre class="bg-light border rounded p-3 mb-0"><code>{
  "mcpServers": {
    "jus-ask": {
      "type": "http",
      "url": "{{ $mcpUrl }}",
      "headers": {
        "Authorization": "Bearer {{ $tokenExemplo }}"
      }
    }
  }
}</code></pre>
                    </div>
                </div>

                {{-- Passo 3 — Uso --}}
                <div class="card">
                    <div class="card-body">
                        <h2 class="h5 mb-3">3. Use no chat do Claude</h2>
                        <p class="text-muted mb-2">
                            Depois de conectar, basta pedir em linguagem natural. Exemplos:
                        </p>
                        <pre class="bg-light border rounded p-3 mb-3"><code>Consulte os processos do CNPJ 52.123.916/0001-32.

Gere um gráfico dos processos do CNPJ 52123916000132 por tribunal e por ano.</code></pre>
                        <p class="text-muted mb-0 small">
                            O Claude usa a ferramenta <code>consultar-processos-por-cnpj</code>, que
                            retorna um resumo por processo e contagens por tribunal, ano e classe —
                            prontas para virar gráficos.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
