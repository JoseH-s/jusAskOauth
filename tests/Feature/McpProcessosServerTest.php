<?php

namespace Tests\Feature;

use App\Mcp\Servers\ProcessosServer;
use App\Mcp\Tools\ConsultarProcessosPorCnpjTool;
use App\Models\TokenCnj;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class McpProcessosServerTest extends TestCase
{
    use RefreshDatabase;

    private const CNPJ = '52123916000132';
    private const TENANT = '12345678000199';

    private function fakePdpj(): array
    {
        return [
            'content' => [
                [
                    'numeroProcesso' => '0011632-42.2024.5.15.0033',
                    'siglaTribunal' => 'TJSP',
                    'tramitacoes' => [[
                        'dataHoraAjuizamento' => '2024-03-10T10:00:00',
                        'valorAcao' => 1500.50,
                        'tribunal' => ['nome' => 'Tribunal de Justiça de São Paulo', 'sigla' => 'TJSP'],
                        'classe' => [['descricao' => 'Procedimento Comum']],
                        'assunto' => [['descricao' => 'Indenização']],
                        'orgaoJulgador' => ['nome' => '1ª Vara Cível'],
                    ]],
                ],
                [
                    'numeroProcesso' => '0022222-22.2023.8.26.0100',
                    'siglaTribunal' => 'TJSP',
                    'tramitacoes' => [[
                        'dataHoraAjuizamento' => '2023-05-20T10:00:00',
                        'tribunal' => ['nome' => 'Tribunal de Justiça de São Paulo', 'sigla' => 'TJSP'],
                        'classe' => [['descricao' => 'Execução Fiscal']],
                        'assunto' => [['descricao' => 'IPTU']],
                    ]],
                ],
            ],
        ];
    }

    public function test_tool_retorna_resumo_e_agregacoes(): void
    {
        TokenCnj::create(['token' => 'token-cnj', 'tenant' => self::TENANT]);

        Http::fake([
            'portaldeservicos.pdpj.jus.br/*' => Http::response($this->fakePdpj(), 200),
        ]);

        $response = ProcessosServer::tool(ConsultarProcessosPorCnpjTool::class, [
            'cnpj' => self::CNPJ,
            'tenant' => self::TENANT,
        ]);

        $response->assertOk();
        // O conteúdo estruturado é serializado como JSON no texto da resposta.
        $response->assertSee('"total":2');
        $response->assertSee('TJSP');
        $response->assertSee('por_tribunal');
        $response->assertSee('Execução Fiscal');
    }

    public function test_tool_exige_cnpj(): void
    {
        $response = ProcessosServer::tool(ConsultarProcessosPorCnpjTool::class, [
            'tenant' => self::TENANT,
        ]);

        $response->assertHasErrors();
    }

    public function test_tool_erro_quando_cnpj_invalido(): void
    {
        TokenCnj::create(['token' => 'token-cnj', 'tenant' => self::TENANT]);

        $response = ProcessosServer::tool(ConsultarProcessosPorCnpjTool::class, [
            'cnpj' => '123',
            'tenant' => self::TENANT,
        ]);

        $response->assertHasErrors();
    }
}
