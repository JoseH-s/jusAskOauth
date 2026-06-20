<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('titulo', 'Blog')</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
    <div class="container py-5" style="max-width: 760px;">
        @yield('content')
        <footer class="text-center text-muted mt-5 pt-4 border-top">
            <small>Publicado com Jus-Ask</small>
        </footer>
    </div>
</body>
</html>
