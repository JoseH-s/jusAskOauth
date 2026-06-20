<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Jus-Ask') }}</title>

    {{-- Aplica data-theme antes do CSS para evitar flash --}}
    <script>(function(){var t=localStorage.getItem('jus-theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">

    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @livewireStyles
</head>
<body>
    @php
        $tenant = app(\App\Services\TenantManager::class);
    @endphp

    <div id="app">
        <nav class="navbar navbar-expand-md navbar-dark jus-navbar">
            <div class="container">
                <a class="navbar-brand" href="{{ url('/') }}">
                    <span class="brand-icon">⚖</span>{{ config('app.name', 'Jus-Ask') }}
                </a>

                <button class="navbar-toggler" type="button"
                        data-bs-toggle="collapse"
                        data-bs-target="#navbarMain"
                        aria-controls="navbarMain"
                        aria-expanded="false"
                        aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto">
                        @auth
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('home') ? 'active-link' : '' }}"
                                   href="{{ route('home') }}">Painel</a>
                            </li>
                            @if ($tenant->check())
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('clientes') ? 'active-link' : '' }}"
                                       href="{{ route('clientes', ['tenant' => $tenant->tenant()]) }}">Clientes</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('processos') ? 'active-link' : '' }}"
                                       href="{{ route('processos', ['tenant' => $tenant->tenant()]) }}">Processos</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('site') ? 'active-link' : '' }}"
                                       href="{{ route('site', ['tenant' => $tenant->tenant()]) }}">Meu site</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('chaves-gemini') ? 'active-link' : '' }}"
                                       href="{{ route('chaves-gemini', ['tenant' => $tenant->tenant()]) }}">Chaves IA</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link {{ request()->routeIs('mcp.*') ? 'active-link' : '' }}"
                                       href="{{ route('mcp.index', ['tenant' => $tenant->tenant()]) }}">MCP</a>
                                </li>
                            @endif
                            @can('super-admin')
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button"
                                       data-bs-toggle="dropdown" aria-expanded="false">Admin</a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('admin.empresas') }}">Empresas</a></li>
                                        <li><a class="dropdown-item" href="{{ route('admin.token-cnj') }}">Token CNJ</a></li>
                                    </ul>
                                </li>
                            @endcan
                        @endauth
                    </ul>

                    <ul class="navbar-nav ms-auto align-items-center gap-1">
                        <li class="nav-item">
                            <button id="btn-theme-toggle" title="Alternar tema claro/escuro">🌙</button>
                        </li>

                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}">Entrar</a>
                                </li>
                            @endif
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}">Criar conta</a>
                                </li>
                            @endif
                        @else
                            @if ($tenant->check())
                                @php $notifCount = \App\Models\Notificacao::where('lida', false)->count(); @endphp
                                <li class="nav-item">
                                    <a class="nav-link position-relative px-2"
                                       href="{{ route('notificacoes', ['tenant' => $tenant->tenant()]) }}"
                                       title="Notificações">
                                        🔔
                                        @if ($notifCount > 0)
                                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                                  style="font-size:.55rem;padding:.25em .45em;">
                                                {{ $notifCount > 99 ? '99+' : $notifCount }}
                                            </span>
                                        @endif
                                    </a>
                                </li>
                            @endif

                            @if (auth()->user()->empresas->isNotEmpty())
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button"
                                       data-bs-toggle="dropdown" aria-expanded="false">
                                        <span style="font-size:.73rem;opacity:.65;margin-right:.15rem;">empresa</span>
                                        <strong>{{ $tenant->empresa()?->nome ?? '—' }}</strong>
                                    </a>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @foreach (auth()->user()->empresas as $empresa)
                                            <li>
                                                <form action="{{ route('empresa.trocar') }}" method="POST">
                                                    @csrf
                                                    <input type="hidden" name="empresa_id" value="{{ $empresa->id }}">
                                                    <button type="submit"
                                                            class="dropdown-item {{ $tenant->id() === $empresa->id ? 'active' : '' }}">
                                                        {{ $empresa->nome }}
                                                        <small class="text-muted d-block" style="font-size:.74rem;">
                                                            {{ $empresa->tenant }}
                                                        </small>
                                                    </button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif

                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" role="button"
                                   data-bs-toggle="dropdown" aria-expanded="false">
                                    {{ Auth::user()->name }}
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item text-danger" href="{{ route('logout') }}"
                                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                        Sair
                                    </a>
                                    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                        @csrf
                                    </form>
                                </div>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            <div class="container">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
                @if (session('erro'))
                    <div class="alert alert-danger alert-dismissible fade show">
                        {{ session('erro') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif
            </div>
            @yield('content')
        </main>
    </div>

    @livewireScripts
    @stack('scripts')
</body>
</html>
