<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\Membro;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RegisterController extends Controller implements HasMiddleware
{
    use RegistersUsers;

    protected $redirectTo = '/home';

    public static function middleware(): array
    {
        return [new Middleware('guest')];
    }

    /**
     * Validacao do cadastro. Quando o usuario declara possuir empresa,
     * nome e CNPJ da empresa passam a ser obrigatorios.
     */
    protected function validator(array $data)
    {
        $validator = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'cpf' => ['required', 'string', 'max:20'],
            'oab' => ['required', 'string', 'max:30'],
            'possui_empresa' => ['nullable', 'boolean'],
            'empresa_nome' => ['nullable', 'required_if:possui_empresa,1', 'string', 'max:255'],
            'cnpj' => [
                'nullable',
                'required_if:possui_empresa,1',
                'string',
                'max:20',
                Rule::unique('empresas', 'cnpj'),
            ],
        ]);

        // Sem empresa, a OAB vira o tenant: nao pode colidir com outro tenant.
        $validator->after(function ($validator) use ($data) {
            if (! $this->possuiEmpresa($data) && ! empty($data['oab'])) {
                if (Empresa::where('tenant', $data['oab'])->exists()) {
                    $validator->errors()->add('oab', 'Esta OAB já está cadastrada como identificador de outro tenant.');
                }
            }
        });

        return $validator;
    }

    /**
     * Cria o usuario, a empresa (tenant) e o vinculo de membro.
     */
    protected function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'cpf' => $data['cpf'],
                'oab' => $data['oab'],
            ]);

            if ($this->possuiEmpresa($data)) {
                $empresa = Empresa::create([
                    'nome' => $data['empresa_nome'],
                    'cnpj' => $data['cnpj'],
                    'oab' => $data['oab'],
                    'tenant' => $data['cnpj'],
                    'is_pessoa_fisica' => false,
                ]);
            } else {
                // Sem empresa: cria uma com o nome do proprio usuario
                // e usa a OAB dele como tenant.
                $empresa = Empresa::create([
                    'nome' => $data['name'],
                    'cnpj' => null,
                    'oab' => $data['oab'],
                    'tenant' => $data['oab'],
                    'is_pessoa_fisica' => true,
                ]);
            }

            Membro::create([
                'user_id' => $user->id,
                'empresa_id' => $empresa->id,
                'tenant' => $empresa->tenant,
                'papel' => 'dono',
                'ativo' => true,
            ]);

            return $user;
        });
    }

    /**
     * Apos o cadastro, deixa a empresa recem-criada como tenant ativo.
     */
    protected function registered(Request $request, $user)
    {
        $membro = $user->membros()->first();

        if ($membro) {
            $request->session()->put('empresa_ativa_id', $membro->empresa_id);
        }

        $request->session()->put('login_at', now()->toIso8601String());
    }

    protected function possuiEmpresa(array $data): bool
    {
        return filter_var($data['possui_empresa'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }
}
