<?php

namespace App\Services;

use App\Models\TokenCnj;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class McpProcessoService
{

    static function consultarPorCnpj($cnpj = NULL, $tenant = NULL)
    {
        $cnpjLimpo = preg_replace('/\D+/', '', $cnpj) ?? '';

        $token = TokenCnj::query()
            ->where('tenant', $tenant)
            ->latest()
            ->value('token');

        if (empty($cnpjLimpo) || strlen($cnpjLimpo) !== 14) {
            throw new \InvalidArgumentException('Informe um CNPJ valido com 14 digitos.');
        }

        if (empty($token)) {
            throw new \RuntimeException('Nenhum token CNJ esta cadastrado para consultar o PDPJ.');
        }

        $token = trim(str_replace('Bearer ', '', (string) $token));

        $rota = 'https://portaldeservicos.pdpj.jus.br/api/v2/processos?cpfCnpjParte='.$cnpjLimpo;

        // User-Agent + withoutVerifying evitam o 403 do WAF do PDPJ (mesmo padrão do ProcessoApiService).
        $response = Http::timeout(20)
            ->withToken($token)
            ->withHeaders(['User-Agent' => 'curl/8.19.0'])
            ->withoutVerifying()
            ->get($rota);

        if (! $response->successful()) {
            throw new RequestException($response);
        }

        return [
            'cnpj' => $cnpjLimpo,
            'tenant' => $tenant,
            'data' => $response->json(),
        ];
    }
}
