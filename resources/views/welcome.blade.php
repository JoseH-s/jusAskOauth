<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Jus-Ask') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div class="container py-5 text-center" style="max-width: 640px;">
        <h1 class="display-5 fw-bold">{{ config('app.name', 'Jus-Ask') }}</h1>
        <p class="lead text-muted">
            Gestão jurídica multi-tenant: clientes, processos e um micro-blog para o seu escritório.
        </p>
        <div class="mt-4">
            @auth
                <a href="{{ route('home') }}" class="btn btn-primary btn-lg">Ir para o painel</a>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary btn-lg">Entrar</a>
                <a href="{{ route('register') }}" class="btn btn-outline-primary btn-lg">Criar conta</a>
            @endauth
        </div>
    </div>
</body>
</html>
