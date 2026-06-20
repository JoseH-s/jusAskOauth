<?php

namespace App\Mcp\Servers;

use App\Mcp\Prompts\GerarGraficoProcessosPrompt;
use App\Mcp\Tools\ConsultarProcessosPorCnpjTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Jus-Ask Processos')]
#[Version('1.0.0')]
#[Instructions('Servidor para consultar processos judiciais de uma parte (pessoa jurídica) por CNPJ na base do PDPJ/CNJ. Os resultados vêm resumidos por processo e com agregações (por tribunal, ano e classe) prontas para análise e geração de gráficos.')]
class ProcessosServer extends Server
{
    protected array $tools = [
        ConsultarProcessosPorCnpjTool::class,
    ];

    protected array $resources = [
        //
    ];

    protected array $prompts = [
        GerarGraficoProcessosPrompt::class,
    ];
}
