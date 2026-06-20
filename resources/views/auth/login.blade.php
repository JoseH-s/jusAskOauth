@extends('layouts.app')

@section('content')
<div class="auth-wrapper">
    <div class="auth-card card shadow-sm">
        <div class="auth-header card-header">
            <div class="auth-brand-icon">⚖️</div>
            <h1 class="auth-title">Jus-Ask</h1>
            <p class="auth-subtitle">Entre com sua conta para continuar</p>
        </div>

        <div class="auth-body card-body">
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input id="email"
                           type="email"
                           name="email"
                           class="form-control @error('email') is-invalid @enderror"
                           value="{{ old('email') }}"
                           placeholder="nome@exemplo.com"
                           required
                           autocomplete="email"
                           autofocus>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Senha</label>
                    <input id="password"
                           type="password"
                           name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           placeholder="••••••••"
                           required
                           autocomplete="current-password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex align-items-center justify-content-between mb-4">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember"
                               {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label text-muted" for="remember" style="font-size:.84rem;">
                            Lembrar-me
                        </label>
                    </div>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}"
                           class="text-decoration-none"
                           style="font-size:.84rem;color:#1a56db;">
                            Esqueceu a senha?
                        </a>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary w-100" style="padding:.65rem;">
                    Entrar
                </button>
            </form>
        </div>

        @if (Route::has('register'))
            <div class="auth-footer">
                Não tem uma conta?
                <a href="{{ route('register') }}" class="text-decoration-none fw-semibold" style="color:#1a56db;">
                    Criar conta
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
