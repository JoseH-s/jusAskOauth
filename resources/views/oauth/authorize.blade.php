@extends('layouts.app')

@section('titulo', 'Autorizar acesso — ' . $client->name)

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-6 col-lg-5">

            {{-- Cabeçalho --}}
            <div class="text-center mb-4">
                <div style="font-size: 2.5rem;">⚖️</div>
                <h1 class="h4 mt-2">Autorizar acesso</h1>
                <p class="text-muted mb-0">
                    <strong>{{ $client->name }}</strong> quer acessar sua conta Jus-Ask
                </p>
            </div>

            {{-- Card de permissões --}}
            <div class="card mb-4">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase mb-3" style="letter-spacing:.05em;">
                        Permissões solicitadas
                    </h2>
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex align-items-start mb-2">
                            <span class="me-2 text-success">✓</span>
                            <span>Consultar processos judiciais da empresa
                                <strong>{{ $tenant }}</strong> por CNPJ
                            </span>
                        </li>
                        <li class="d-flex align-items-start mb-2">
                            <span class="me-2 text-success">✓</span>
                            <span>Gerar análises e gráficos a partir dos processos</span>
                        </li>
                        <li class="d-flex align-items-start">
                            <span class="me-2 text-danger">✗</span>
                            <span class="text-muted">Não pode modificar dados nem acessar outras empresas</span>
                        </li>
                    </ul>
                </div>
            </div>

            {{-- Info do usuário logado --}}
            <p class="text-muted text-center small mb-4">
                Você está logado como <strong>{{ auth()->user()->email }}</strong>.
                Não é você? <a href="{{ route('logout') }}"
                    onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    Sair
                </a>
            </p>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>

            {{-- Formulário de aprovação --}}
            <form method="POST" action="{{ route('oauth.authorize.approve') }}">
                @csrf
                <input type="hidden" name="client_id"             value="{{ $client->client_id }}">
                <input type="hidden" name="redirect_uri"          value="{{ $redirect_uri }}">
                <input type="hidden" name="tenant"                value="{{ $tenant }}">
                <input type="hidden" name="state"                 value="{{ $state }}">
                <input type="hidden" name="code_challenge"        value="{{ $code_challenge }}">
                <input type="hidden" name="code_challenge_method" value="{{ $code_challenge_method }}">
                <input type="hidden" name="scope"                 value="{{ $scope }}">

                <div class="d-grid gap-2">
                    <button type="submit" name="approved" value="1" class="btn btn-primary btn-lg">
                        Autorizar {{ $client->name }}
                    </button>
                    <button type="submit" name="approved" value="0" class="btn btn-outline-secondary">
                        Negar
                    </button>
                </div>
            </form>

            <p class="text-muted text-center small mt-4">
                Ao autorizar, o {{ $client->name }} receberá um token de acesso válido por 1 hora.
                Você pode revogar o acesso a qualquer momento em
                <strong>{{ $tenant }}/mcp</strong>.
            </p>

        </div>
    </div>
</div>
@endsection
