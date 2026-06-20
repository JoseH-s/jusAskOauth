<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\ConteudoProcesso;
use App\Models\Empresa;
use App\Models\Membro;
use App\Models\Post;
use App\Models\Processo;
use App\Models\Site;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = app(TenantManager::class);

        // ----- Usuario padrao do sistema: super-admin global -----
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@jusask.test',
            'password' => Hash::make('password'),
            'cpf' => '000.000.000-00',
            'oab' => null,
            'is_super_admin' => true,
        ]);

        // ----- Tenant 1: escritorio identificado por CNPJ -----
        $empresa1 = Empresa::create([
            'nome' => 'Escritório Modelo Advocacia',
            'cnpj' => '12345678000199',
            'oab' => 'SP123456',
            'tenant' => '12345678000199',
            'is_pessoa_fisica' => false,
        ]);

        $marina = User::create([
            'name' => 'Dra. Marina Advogada',
            'email' => 'marina@jusask.test',
            'password' => Hash::make('password'),
            'cpf' => '111.111.111-11',
            'oab' => 'SP123456',
        ]);

        Membro::create([
            'user_id' => $marina->id,
            'empresa_id' => $empresa1->id,
            'tenant' => $empresa1->tenant,
            'papel' => 'dono',
            'ativo' => true,
        ]);

        // ----- Tenant 2: advogado sem empresa, identificado pela OAB -----
        $empresa2 = Empresa::create([
            'nome' => 'João Pessoa Física',
            'cnpj' => null,
            'oab' => 'RJ987654',
            'tenant' => 'RJ987654',
            'is_pessoa_fisica' => true,
        ]);

        $joao = User::create([
            'name' => 'João Pessoa Física',
            'email' => 'joao@jusask.test',
            'password' => Hash::make('password'),
            'cpf' => '222.222.222-22',
            'oab' => 'RJ987654',
        ]);

        Membro::create([
            'user_id' => $joao->id,
            'empresa_id' => $empresa2->id,
            'tenant' => $empresa2->tenant,
            'papel' => 'dono',
            'ativo' => true,
        ]);

        // Marina tambem e membro do tenant 2 (um User em varias empresas).
        Membro::create([
            'user_id' => $marina->id,
            'empresa_id' => $empresa2->id,
            'tenant' => $empresa2->tenant,
            'papel' => 'advogado',
            'ativo' => true,
        ]);

        // ----- Dados de exemplo de cada tenant -----
        $tenant->set($empresa1);
        $this->popularTenant('Tenant 1');

        $tenant->set($empresa2);
        $this->popularTenant('Tenant 2');

        $tenant->forget();
    }

    /**
     * Cria clientes, processos, conteudos, site e posts para o tenant ativo.
     * O trait BelongsToTenant preenche empresa_id e tenant automaticamente.
     */
    protected function popularTenant(string $rotulo): void
    {
        // Cliente com e-mail/CPF iguais nos dois tenants: demonstra a
        // unicidade por tenant (o mesmo dado convive em tenants diferentes).
        $cliente = Cliente::create([
            'nome' => 'Carlos Cliente',
            'email' => 'carlos@exemplo.com',
            'cpf' => '333.333.333-33',
            'oab' => 'SP000001',
            'endereco' => 'Rua das Acácias',
            'numero' => '100',
            'bairro' => 'Centro',
            'cidade' => 'São Paulo',
            'pais' => 'Brasil',
            'cep' => '01000-000',
        ]);

        $clienteB = Cliente::create([
            'nome' => 'Ana Beltrano',
            'email' => 'ana@exemplo.com',
            'cpf' => '444.444.444-44',
            'oab' => 'SP000002',
        ]);

        $processo = Processo::create([
            'cliente_id' => $cliente->id,
            'numero' => '0001234-56.2026.8.26.0100',
            'ultima_atualizacao' => now()->subDays(3),
            'encerrado' => false,
        ]);

        Processo::create([
            'cliente_id' => $clienteB->id,
            'numero' => '0007654-32.2025.8.26.0100',
            'ultima_atualizacao' => now()->subDays(40),
            'encerrado' => true,
        ]);

        ConteudoProcesso::create([
            'processo_id' => $processo->id,
            'numero_processo' => $processo->numero,
            'conteudo' => "Petição inicial protocolada. Aguardando distribuição. [{$rotulo}]",
        ]);

        ConteudoProcesso::create([
            'processo_id' => $processo->id,
            'numero_processo' => $processo->numero,
            'conteudo' => 'Audiência de conciliação designada.',
        ]);

        $site = Site::create([
            'titulo' => "Blog do {$rotulo}",
            'slug' => 'blog-' . strtolower(str_replace(' ', '-', $rotulo)),
            'descricao' => 'Artigos e novidades jurídicas do escritório.',
            'publicado' => true,
        ]);

        Post::create([
            'site_id' => $site->id,
            'titulo' => 'Bem-vindo ao nosso blog',
            'slug' => 'bem-vindo',
            'conteudo' => 'Este é o primeiro post do micro-blog do escritório.',
            'publicado' => true,
            'publicado_em' => now()->subDays(5),
        ]);
    }
}
