<?php

namespace App\Mcp\Prompts;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Prompt;
use Laravel\Mcp\Server\Prompts\Argument;

#[Description('Consulta os processos de um CNPJ e gera um gráfico a partir das agregações retornadas.')]
class GerarGraficoProcessosPrompt extends Prompt
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'cnpj' => ['required', 'string'],
        ], [
            'cnpj.required' => 'Informe o CNPJ da parte (14 dígitos) para gerar o gráfico.',
        ]);

        $cnpj = $validated['cnpj'];

        return Response::text(<<<PROMPT
        Use a tool "consultar-processos-por-cnpj" com o cnpj {$cnpj}.
        Com base no campo "agregacoes" do resultado, gere um gráfico de barras da distribuição
        de processos por tribunal e, se fizer sentido, um segundo gráfico por ano de ajuizamento.
        Produza um arquivo HTML autocontido usando Chart.js (via CDN) com os dados já preenchidos,
        e em seguida resuma em texto os principais números (total de processos e os 3 tribunais com mais casos).
        PROMPT);
    }

    /**
     * @return array<int, Argument>
     */
    public function arguments(): array
    {
        return [
            new Argument(
                name: 'cnpj',
                description: 'CNPJ da parte, com ou sem máscara (14 dígitos).',
                required: true,
            ),
        ];
    }
}
