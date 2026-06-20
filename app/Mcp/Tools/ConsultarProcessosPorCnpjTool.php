<?php

namespace App\Mcp\Tools;

use App\Services\McpProcessoService;
use App\Services\TenantManager;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Http\Client\RequestException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[Name('consultar-processos-por-cnpj')]
#[Description('Consulta os processos de uma parte (pessoa jurídica) por CNPJ na base do PDPJ/CNJ. Retorna um resumo por processo (tribunal, classe, assunto, valor, ano) e agregações prontas para gráficos (por tribunal, por ano e por classe).')]
#[IsReadOnly]
#[IsIdempotent]
class ConsultarProcessosPorCnpjTool extends Tool
{
    public function handle(Request $request, McpProcessoService $service): Response|ResponseFactory
    {
        $validated = $request->validate([
            'cnpj' => ['required', 'string'],
            'tenant' => ['nullable', 'string'],
        ], [
            'cnpj.required' => 'Informe o CNPJ da parte (14 dígitos), ex.: 52123916000132.',
        ]);

        // tenant: argumento > resolvido pelo middleware (transporte web) > null (service usa fallback).
        $tenant = $validated['tenant']
            ?? app(TenantManager::class)->tenant();

        try {
            $resultado = $service->consultarPorCnpj($validated['cnpj'], $tenant);
        } catch (\InvalidArgumentException $e) {
            return Response::error($e->getMessage());
        } catch (RequestException $e) {
            return Response::error('Falha ao consultar a API do PDPJ (HTTP ' . $e->response->status() . ').');
        } catch (\Throwable $e) {
            return Response::error('Não foi possível consultar os processos: ' . $e->getMessage());
        }

        return Response::structured($this->resumir(
            $resultado['cnpj'],
            $resultado['data']['content'] ?? [],
        ));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'cnpj' => $schema->string()
                ->description('CNPJ da parte, com ou sem máscara (14 dígitos).')
                ->required(),

            'tenant' => $schema->string()
                ->description('Tenant da empresa cujo token CNJ deve ser usado. Opcional; quando omitido, usa o token padrão.'),
        ];
    }

    /**
     * Transforma a lista crua do PDPJ em resumo por processo + agregações para gráficos.
     *
     * @param array<int, array<string, mixed>> $content
     * @return array<string, mixed>
     */
    private function resumir(string $cnpj, array $content): array
    {
        $processos = [];
        $porTribunal = [];
        $porAno = [];
        $porClasse = [];

        foreach ($content as $item) {
            $tram = $item['tramitacoes'][0] ?? [];

            $tribunal = $tram['tribunal']['nome'] ?? ($item['siglaTribunal'] ?? 'Não informado');
            $sigla = $tram['tribunal']['sigla'] ?? ($item['siglaTribunal'] ?? null);
            $classe = $tram['classe'][0]['descricao'] ?? 'Não informada';
            $assunto = $tram['assunto'][0]['descricao'] ?? null;
            $ano = isset($tram['dataHoraAjuizamento'])
                ? substr((string) $tram['dataHoraAjuizamento'], 0, 4)
                : 'Não informado';

            $processos[] = [
                'numero' => $item['numeroProcesso'] ?? null,
                'tribunal' => $tribunal,
                'sigla' => $sigla,
                'classe' => $classe,
                'assunto' => $assunto,
                'valor_acao' => $tram['valorAcao'] ?? null,
                'ano_ajuizamento' => $ano,
                'orgao_julgador' => $tram['orgaoJulgador']['nome'] ?? null,
            ];

            $porTribunal[$sigla ?? $tribunal] = ($porTribunal[$sigla ?? $tribunal] ?? 0) + 1;
            $porAno[$ano] = ($porAno[$ano] ?? 0) + 1;
            $porClasse[$classe] = ($porClasse[$classe] ?? 0) + 1;
        }

        ksort($porAno);
        arsort($porTribunal);
        arsort($porClasse);

        return [
            'cnpj' => $cnpj,
            'total' => count($processos),
            'processos' => $processos,
            'agregacoes' => [
                'por_tribunal' => $porTribunal,
                'por_ano' => $porAno,
                'por_classe' => $porClasse,
            ],
        ];
    }
}
