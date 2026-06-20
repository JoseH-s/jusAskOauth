<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\Processo;
use App\Models\TokenCnj;
use App\Services\ProcessoApiService;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessoApiServiceTest extends TestCase
{
    use RefreshDatabase;

    private const NUMERO = '0011632-42.2024.5.15.0033';
    private const NUMERO_LIMPO = '00116324220245150033';
    private const TENANT = '12345678000199';

    /**
     * Resposta do PDPJ no formato que o service espera. O número de movimentos
     * controla o tamanho do JSON (usado para simular "dados novos").
     */
    private function fakePdpj(int $movimentos = 1): array
    {
        $movs = [];
        for ($i = 1; $i <= $movimentos; $i++) {
            $movs[] = ['dataHora' => '2024-06-01T09:30:00', 'descricao' => "Movimento {$i}"];
        }

        return [
            'content' => [[
                'numeroProcesso' => self::NUMERO,
                'tramitacoes' => [[
                    'dataHoraAjuizamento' => '2024-03-10T10:00:00',
                    'valorAcao' => 1500.50,
                    'assunto' => [['descricao' => 'Indenização por Dano Moral']],
                    'ultimoMovimento' => ['dataHora' => '2024-06-01T09:30:00'],
                    'movimentos' => $movs,
                ]],
            ]],
        ];
    }

    /** Cria empresa + tenant ativo + token CNJ + um processo ativo. */
    private function criarProcessoAtivo(): Processo
    {
        $empresa = Empresa::create([
            'nome' => 'Escritório Teste',
            'cnpj' => self::TENANT,
            'oab' => 'SP0001',
            'tenant' => self::TENANT,
            'is_pessoa_fisica' => false,
        ]);

        app(TenantManager::class)->set($empresa);

        TokenCnj::create(['token' => 'token-cnj', 'tenant' => self::TENANT]);

        return Processo::create(['numero' => self::NUMERO, 'ativo' => true]);
    }

    public function test_consultar_api_limpa_numero_resolve_token_e_retorna_json(): void
    {
        TokenCnj::create(['token' => 'Bearer meu-token-cnj', 'tenant' => self::TENANT]);

        Http::fake([
            'portaldeservicos.pdpj.jus.br/*' => Http::response($this->fakePdpj(), 200),
        ]);

        $json = ProcessoApiService::consultarApi(processo: self::NUMERO, tenant: self::TENANT);

        $this->assertNotNull($json);
        $this->assertSame(self::NUMERO, $json['content'][0]['numeroProcesso']);

        // O número vai limpo na URL e o token (sem "Bearer ") vai no Authorization.
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'numeroProcesso=' . self::NUMERO_LIMPO)
                && $request->hasHeader('Authorization', 'Bearer meu-token-cnj');
        });
    }

    public function test_consultar_e_salvar_persiste_snapshot_sem_notificar(): void
    {
        $processo = $this->criarProcessoAtivo();

        Http::fake([
            'portaldeservicos.pdpj.jus.br/*' => Http::response($this->fakePdpj(), 200),
        ]);

        $ok = ProcessoApiService::consultarESalvar($processo);

        $this->assertTrue($ok);
        $this->assertDatabaseHas('processos_conteudos', [
            'processo_id' => $processo->id,
            'numero_processo' => self::NUMERO,
        ]);
        $this->assertSame('Indenização por Dano Moral', $processo->fresh()->assunto);
        // Cadastro nunca notifica.
        $this->assertDatabaseCount('notificacoes', 0);
    }

    public function test_sincronizar_cria_baseline_e_depois_notifica_quando_cresce(): void
    {
        $processo = $this->criarProcessoAtivo();

        // fakeSequence devolve uma resposta diferente a cada request (a 1ª menor, a 2ª maior).
        Http::fakeSequence()
            ->push($this->fakePdpj(1), 200)
            ->push($this->fakePdpj(8), 200);

        // 1ª verificação: sem snapshot anterior -> baseline, sem notificar.
        $primeira = ProcessoApiService::sincronizarComVerificacao($processo);

        $this->assertFalse($primeira['atualizado']);
        $this->assertDatabaseCount('processos_conteudos', 1);
        $this->assertDatabaseCount('notificacoes', 0);

        // 2ª verificação: payload maior -> notifica.
        $segunda = ProcessoApiService::sincronizarComVerificacao($processo);

        $this->assertTrue($segunda['atualizado']);
        $this->assertDatabaseCount('notificacoes', 1);
    }

    public function test_sincronizar_nao_notifica_sem_dados_novos(): void
    {
        $processo = $this->criarProcessoAtivo();

        Http::fakeSequence()
            ->push($this->fakePdpj(3), 200)
            ->push($this->fakePdpj(3), 200);

        ProcessoApiService::sincronizarComVerificacao($processo); // baseline

        // Mesmo payload -> sem novidade.
        $resultado = ProcessoApiService::sincronizarComVerificacao($processo);

        $this->assertFalse($resultado['atualizado']);
        $this->assertDatabaseCount('notificacoes', 0);
    }

    public function test_verificar_processos_ativos_conta_atualizados(): void
    {
        $processo = $this->criarProcessoAtivo();

        Http::fakeSequence()
            ->push($this->fakePdpj(1), 200)
            ->push($this->fakePdpj(8), 200);

        ProcessoApiService::sincronizarComVerificacao($processo); // baseline

        $atualizados = ProcessoApiService::verificarProcessosAtivos();

        $this->assertSame(1, $atualizados);
        $this->assertDatabaseCount('notificacoes', 1);
    }
}
