<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Token CNJ</title>
</head>
<body>
    

    <form method="POST" action="{{ route('postTokenCnj', ['tenant' => $tenant]) }}">
        @csrf
        <div>
            <label for="token">Token CNJ</label>
            <input
                id="token"
                type="text"
                name="token"
                value=""
                placeholder="Token CNJ"
                autocomplete="off"
            >
        </div>
        <div>
            Tenant: {{ $tenant }}
        </div>

        

        <button type="submit">Salvar Token</button>
    </form>
    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
</body>
</html>
