<?php

namespace Tests\Feature;

use App\Livewire\Clientes\GerenciarClientes;
use App\Models\Cliente;
use App\Models\Empresa;
use App\Models\Membro;
use App\Models\Post;
use App\Models\Processo;
use App\Models\Site;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class MultiTenancyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Cria empresa + usuario dono + vinculo de membro.
     */
    private function criarTenant(string $tenant, ?string $cnpj, string $oab, string $email): array
    {
        $empresa = Empresa::create([
            'nome' => "Empresa {$tenant}",
            'cnpj' => $cnpj,
            'oab' => $oab,
            'tenant' => $tenant,
            'is_pessoa_fisica' => $cnpj === null,
        ]);

        $user = User::create([
            'name' => "Dono {$tenant}",
            'email' => $email,
            'password' => Hash::make('password'),
            'cpf' => '000',
            'oab' => $oab,
        ]);

        Membro::create([
            'user_id' => $user->id,
            'empresa_id' => $empresa->id,
            'tenant' => $empresa->tenant,
            'papel' => 'dono',
            'ativo' => true,
        ]);

        return [$empresa, $user];
    }

    private function ativarTenant(Empresa $empresa): void
    {
        app(TenantManager::class)->set($empresa);
    }

    public function test_registro_sem_empresa_cria_empresa_com_oab_como_tenant(): void
    {
        $this->post('/register', [
            'name' => 'Advogada Sem Empresa',
            'email' => 'semempresa@teste.com',
            'cpf' => '123.456.789-00',
            'oab' => 'MG555000',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ])->assertRedirect('/home');

        $empresa = Empresa::where('tenant', 'MG555000')->first();

        $this->assertNotNull($empresa);
        $this->assertSame('Advogada Sem Empresa', $empresa->nome);
        $this->assertTrue($empresa->is_pessoa_fisica);
        $this->assertNull($empresa->cnpj);
        $this->assertDatabaseHas('membros', ['empresa_id' => $empresa->id, 'papel' => 'dono']);
    }

    public function test_registro_com_empresa_usa_cnpj_como_tenant(): void
    {
        $this->post('/register', [
            'name' => 'Advogado Com Empresa',
            'email' => 'comempresa@teste.com',
            'cpf' => '987.654.321-00',
            'oab' => 'SP111222',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
            'possui_empresa' => '1',
            'empresa_nome' => 'Escritório Teste Ltda',
            'cnpj' => '99888777000166',
        ])->assertRedirect('/home');

        $empresa = Empresa::where('tenant', '99888777000166')->first();

        $this->assertNotNull($empresa);
        $this->assertSame('Escritório Teste Ltda', $empresa->nome);
        $this->assertFalse($empresa->is_pessoa_fisica);
        $this->assertSame('99888777000166', $empresa->cnpj);
    }

    public function test_email_e_cpf_de_cliente_sao_unicos_por_tenant(): void
    {
        [$empresaA, $userA] = $this->criarTenant('TENANT-A', '11111111000111', 'AAA1', 'a@teste.com');
        [$empresaB, $userB] = $this->criarTenant('TENANT-B', '22222222000122', 'BBB1', 'b@teste.com');

        // Cria um cliente no tenant A.
        $this->actingAs($userA);
        $this->ativarTenant($empresaA);

        Livewire::test(GerenciarClientes::class)
            ->call('novo')
            ->set('nome', 'Cliente X')
            ->set('email', 'cliente@x.com')
            ->set('cpf', '111.111.111-11')
            ->set('telefone', '11111111111')
            ->call('salvar')
            ->assertHasNoErrors();

        // Mesmo e-mail/CPF no mesmo tenant deve falhar.
        Livewire::test(GerenciarClientes::class)
            ->call('novo')
            ->set('nome', 'Cliente Y')
            ->set('email', 'cliente@x.com')
            ->set('cpf', '111.111.111-11')
            ->set('telefone', '22222222222')
            ->call('salvar')
            ->assertHasErrors(['email', 'cpf']);

        // Mesmo e-mail/CPF em OUTRO tenant deve ser permitido.
        $this->actingAs($userB);
        $this->ativarTenant($empresaB);

        Livewire::test(GerenciarClientes::class)
            ->call('novo')
            ->set('nome', 'Cliente X no B')
            ->set('email', 'cliente@x.com')
            ->set('cpf', '111.111.111-11')
            ->set('telefone', '11111111111')
            ->call('salvar')
            ->assertHasNoErrors();

        $this->assertSame(2, Cliente::withoutGlobalScopes()->where('email', 'cliente@x.com')->count());
    }

    public function test_componente_de_clientes_so_lista_dados_do_tenant_ativo(): void
    {
        [$empresaA, $userA] = $this->criarTenant('T-A', '11111111000111', 'AAA1', 'a@teste.com');
        [$empresaB, $userB] = $this->criarTenant('T-B', '22222222000122', 'BBB1', 'b@teste.com');

        $this->ativarTenant($empresaA);
        Cliente::create(['nome' => 'Só do A', 'email' => 'soa@x.com', 'cpf' => '1', 'oab' => 'o']);

        $this->ativarTenant($empresaB);
        Cliente::create(['nome' => 'Só do B', 'email' => 'sob@x.com', 'cpf' => '2', 'oab' => 'o']);

        $this->actingAs($userA);
        $this->ativarTenant($empresaA);

        Livewire::test(GerenciarClientes::class)
            ->assertSee('Só do A')
            ->assertDontSee('Só do B');
    }

    public function test_processo_de_outro_tenant_retorna_404(): void
    {
        [$empresaA, $userA] = $this->criarTenant('T-A', '11111111000111', 'AAA1', 'a@teste.com');
        [$empresaB, $userB] = $this->criarTenant('T-B', '22222222000122', 'BBB1', 'b@teste.com');

        $this->ativarTenant($empresaA);
        $cliente = Cliente::create(['nome' => 'Cli A', 'email' => 'c@a.com', 'cpf' => '1', 'oab' => 'o']);
        $processoA = Processo::create(['cliente_id' => $cliente->id, 'numero' => '123', 'encerrado' => false]);

        app(TenantManager::class)->forget();

        // Usuario do tenant B tenta acessar processo do tenant A.
        $this->actingAs($userB)
            ->get("/processos/{$processoA->id}")
            ->assertNotFound();
    }

    public function test_blog_publico_e_acessivel_sem_autenticacao(): void
    {
        [$empresaA] = $this->criarTenant('T-A', '11111111000111', 'AAA1', 'a@teste.com');

        $this->ativarTenant($empresaA);
        $site = Site::create(['titulo' => 'Blog A', 'slug' => 'blog-a', 'publicado' => true]);
        Post::create([
            'site_id' => $site->id,
            'titulo' => 'Primeiro Post',
            'slug' => 'primeiro-post',
            'conteudo' => 'Olá mundo',
            'publicado' => true,
            'publicado_em' => now(),
        ]);

        app(TenantManager::class)->forget();

        $this->get('/blog/blog-a')
            ->assertOk()
            ->assertSee('Blog A')
            ->assertSee('Primeiro Post');
    }
}
