@extends('layouts.app')

@section('content')
<div class="auth-wrapper" style="align-items:flex-start;padding-top:2.5rem;padding-bottom:2.5rem;">
    <div class="auth-card card shadow-sm" style="max-width:560px;">
        <div class="auth-header card-header">
            <div class="auth-brand-icon">⚖️</div>
            <h1 class="auth-title">Criar conta</h1>
            <p class="auth-subtitle">Preencha os dados para começar a usar o Jus-Ask</p>
        </div>

        <div class="auth-body card-body">
            <form method="POST" action="{{ route('register') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-12">
                        <label for="name" class="form-label">Nome completo</label>
                        <input id="name" type="text"
                               class="form-control @error('name') is-invalid @enderror"
                               name="name" value="{{ old('name') }}"
                               required autocomplete="name" autofocus
                               placeholder="Seu nome">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="email" class="form-label">E-mail</label>
                        <input id="email" type="email"
                               class="form-control @error('email') is-invalid @enderror"
                               name="email" value="{{ old('email') }}"
                               required autocomplete="email"
                               placeholder="nome@exemplo.com">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="cpf" class="form-label">CPF</label>
                        <input id="cpf" type="text"
                               class="form-control @error('cpf') is-invalid @enderror"
                               name="cpf" value="{{ old('cpf') }}"
                               required autocomplete="off"
                               placeholder="000.000.000-00" maxlength="18">
                        @error('cpf')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-12">
                        <label for="oab" class="form-label">OAB</label>
                        <input id="oab" type="text"
                               class="form-control @error('oab') is-invalid @enderror"
                               name="oab" value="{{ old('oab') }}" required
                               placeholder="Ex: 123456/SP">
                        @error('oab')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <div class="form-text">Se não tiver empresa, sua OAB será usada como identificador do tenant.</div>
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">Senha</label>
                        <input id="password" type="password"
                               class="form-control @error('password') is-invalid @enderror"
                               name="password" required
                               autocomplete="new-password"
                               placeholder="Mínimo 8 caracteres">
                        @error('password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="col-md-6">
                        <label for="password-confirm" class="form-label">Confirmar senha</label>
                        <input id="password-confirm" type="password"
                               class="form-control"
                               name="password_confirmation"
                               required autocomplete="new-password"
                               placeholder="Repita a senha">
                    </div>
                </div>

                <hr class="my-4">

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" value="1"
                           name="possui_empresa" id="possui_empresa"
                           {{ old('possui_empresa') ? 'checked' : '' }}
                           onchange="document.getElementById('empresa-fields').style.display = this.checked ? 'block' : 'none'">
                    <label class="form-check-label fw-medium" for="possui_empresa">
                        Possuo empresa / escritório
                    </label>
                </div>

                <div id="empresa-fields"
                     style="display: {{ old('possui_empresa') ? 'block' : 'none' }};">
                    <div class="row g-3 mb-1">
                        <div class="col-12">
                            <label for="empresa_nome" class="form-label">Nome da empresa</label>
                            <input id="empresa_nome" type="text"
                                   class="form-control @error('empresa_nome') is-invalid @enderror"
                                   name="empresa_nome" value="{{ old('empresa_nome') }}"
                                   placeholder="Nome do escritório">
                            @error('empresa_nome')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-12">
                            <label for="cnpj" class="form-label">CNPJ</label>
                            <input id="cnpj" type="text"
                                   class="form-control @error('cnpj') is-invalid @enderror"
                                   name="cnpj" value="{{ old('cnpj') }}"
                                   autocomplete="off"
                                   placeholder="00.000.000/0000-00" maxlength="18">
                            @error('cnpj')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">O CNPJ será o identificador do tenant.</div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-3" style="padding:.65rem;">
                    Criar conta
                </button>
            </form>
        </div>

        <div class="auth-footer">
            Já tem uma conta?
            <a href="{{ route('login') }}" class="text-decoration-none fw-semibold" style="color:#1a56db;">
                Entrar
            </a>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    function maskCpfCnpj(raw) {
        const d = raw.replace(/\D/g, '').slice(0, 14);
        if (d.length <= 11) {
            return d
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d)/, '$1.$2')
                .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
        }
        return d
            .replace(/(\d{2})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1/$2')
            .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    }

    function maskCnpj(raw) {
        const d = raw.replace(/\D/g, '').slice(0, 14);
        return d
            .replace(/(\d{2})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1.$2')
            .replace(/(\d{3})(\d)/, '$1/$2')
            .replace(/(\d{4})(\d{1,2})$/, '$1-$2');
    }

    function applyMask(id, fn) {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', function () {
            const pos = this.selectionStart;
            const old = this.value;
            this.value = fn(old);
            const diff = this.value.length - old.length;
            this.setSelectionRange(pos + diff, pos + diff);
        });
    }

    applyMask('cpf',  maskCpfCnpj);
    applyMask('cnpj', maskCnpj);
})();
</script>
@endpush
