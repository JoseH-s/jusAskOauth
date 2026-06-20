<?php

namespace App\Services;

use App\Models\Notificacao;
use App\Models\Processo;
use App\Models\ProcessoConteudo;
use App\Models\TokenCnj;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class ProcessoApiService
{
    private const API_URL = 'https://portaldeservicos.pdpj.jus.br/api/v2/processos?numeroProcesso=';

    static function limparNumero(string $numero): string
    {
        return preg_replace('/[.\\/\-]/', '', $numero);
    }

    /**
     * Resolve o token CNJ da empresa (tenant), com fallback no .env.
     */
    static function resolverToken(?string $tenant = null): string
    {
        $raw = TokenCnj::query()->where('tenant', $tenant)->latest()->value('token')
            ?? env('TOKEN_API_PROCESSO', '');

        return trim(str_replace('Bearer ', '', (string) $raw));
    }

    /**
     * Consulta o PDPJ pelo número do processo. Retorna o JSON ou null.
     */
    static function consultarApi(?string $processo = null, ?string $tenant = null): ?array
    {
        if (empty($processo)) {
            throw new \RuntimeException('Número do processo é obrigatório.');
        }

        $token = self::resolverToken($tenant);

        if ($token === '') {
            throw new \RuntimeException('Nenhum token CNJ esta cadastrado para consultar o PDPJ.');
        }

        $numeroLimpo = self::limparNumero($processo);

        $response = Http::timeout(15)
            ->withToken($token)
            ->withHeaders(['User-Agent' => 'curl/8.19.0'])
            ->withoutVerifying()
            ->get(self::API_URL . $numeroLimpo);

        if (! $response->successful()) {
            logger()->warning('ProcessoApiService: resposta não-2xx', [
                'numero' => $processo,
                'tenant' => $tenant,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return null;
        }

        $json    = $response->json();
        $content = $json['content'][0] ?? null;
        $tram    = $content['tramitacoes'][0] ?? null;

        if (! $content || ! $tram) {
            logger()->warning('ProcessoApiService: estrutura inesperada no JSON', [
                'numero' => $processo,
                'tenant' => $tenant,
            ]);

            return null;
        }

        return $json;
    }

    /**
     * Consulta e grava um snapshot (uso no cadastro). Retorna true se sincronizou.
     */
    static function consultarESalvar(Processo $processo): bool
    {
        $json = self::consultarApi($processo->numero, $processo->tenant);

        if ($json === null) {
            return false;
        }

        self::persistirSnapshot($processo, $json);

        return true;
    }

    /**
     * Consulta e só grava/notifica quando a API traz mais dados que o último snapshot.
     *
     * @return array{atualizado: bool, tamanho_anterior: int, tamanho_novo: int}
     */
    static function sincronizarComVerificacao(Processo $processo): array
    {
        $json = self::consultarApi($processo->numero, $processo->tenant);

        if ($json === null) {
            return ['atualizado' => false, 'tamanho_anterior' => 0, 'tamanho_novo' => 0];
        }

        $tamanhoNovo = strlen(json_encode($json));

        $ultimo = ProcessoConteudo::withoutGlobalScopes()
            ->where('processo_id', $processo->id)
            ->latest()
            ->first();

        // Primeira verificação: grava a linha de base sem notificar.
        if ($ultimo === null) {
            self::persistirSnapshot($processo, $json);

            return ['atualizado' => false, 'tamanho_anterior' => 0, 'tamanho_novo' => $tamanhoNovo];
        }

        $tamanhoAtual = strlen(json_encode($ultimo->conteudo_json));

        // Sem dados novos: nada a fazer.
        if ($tamanhoNovo <= $tamanhoAtual) {
            return ['atualizado' => false, 'tamanho_anterior' => $tamanhoAtual, 'tamanho_novo' => $tamanhoNovo];
        }

        self::persistirSnapshot($processo, $json);
        self::criarNotificacao($processo, $tamanhoAtual, $tamanhoNovo);

        return ['atualizado' => true, 'tamanho_anterior' => $tamanhoAtual, 'tamanho_novo' => $tamanhoNovo];
    }

    /**
     * Verifica todos os processos ativos (de todas as empresas) e notifica os que
     * tiverem novidade. Cada processo resolve o token pelo seu próprio tenant.
     *
     * @return int Quantidade de processos que geraram notificação.
     */
    static function verificarProcessosAtivos(): int
    {
        $processos = Processo::withoutGlobalScopes()
            ->where('ativo', true)
            ->get();

        $atualizados = 0;

        foreach ($processos as $processo) {
            try {
                if (self::sincronizarComVerificacao($processo)['atualizado']) {
                    $atualizados++;
                }
            } catch (\Throwable $e) {
                logger()->error('ProcessoApiService: falha ao verificar processo', [
                    'processo_id' => $processo->id,
                    'numero'      => $processo->numero,
                    'tenant'      => $processo->tenant,
                    'erro'        => $e->getMessage(),
                ]);
            }
        }

        return $atualizados;
    }

    /**
     * Espelha campos no processo e grava um novo snapshot em processos_conteudos.
     */
    static function persistirSnapshot(Processo $processo, array $json): void
    {
        $content = $json['content'][0];
        $tram    = $content['tramitacoes'][0];

        $dataAjuizamento        = isset($tram['dataHoraAjuizamento']) ? Carbon::parse($tram['dataHoraAjuizamento']) : null;
        $dataUltimaDistribuicao = isset($tram['dataHoraUltimaDistribuicao']) ? Carbon::parse($tram['dataHoraUltimaDistribuicao']) : null;
        $dataUltimoMovimento    = isset($tram['ultimoMovimento']['dataHora']) ? Carbon::parse($tram['ultimoMovimento']['dataHora']) : null;
        $valorAcao              = $tram['valorAcao'] ?? null;
        $assunto                = $tram['assunto'][0]['descricao'] ?? null;

        $processo->update([
            'data_hora_ajuizamento'         => $dataAjuizamento,
            'valor_acao'                    => $valorAcao,
            'data_hora_ultima_distribuicao' => $dataUltimaDistribuicao,
            'assunto'                       => $assunto,
            'ultima_atualizacao'            => $dataUltimoMovimento,
        ]);

        ProcessoConteudo::create([
            'processo_id'                   => $processo->id,
            'empresa_id'                    => $processo->empresa_id,
            'tenant'                        => $processo->tenant,
            'numero_processo'               => $content['numeroProcesso'] ?? null,
            'data_hora_ajuizamento'         => $dataAjuizamento,
            'valor_acao'                    => $valorAcao,
            'data_hora_ultima_distribuicao' => $dataUltimaDistribuicao,
            'assunto'                       => $assunto,
            'conteudo_json'                 => $json,
        ]);
    }

    static function criarNotificacao(Processo $processo, int $tamanhoAnterior, int $tamanhoNovo): void
    {
        Notificacao::create([
            'processo_id' => $processo->id,
            'empresa_id'  => $processo->empresa_id,
            'tenant'      => $processo->tenant,
            'titulo'      => "Processo {$processo->numero} atualizado",
            'mensagem'    => "O processo {$processo->numero} teve atualização na API PDPJ "
                . "({$tamanhoAnterior} → {$tamanhoNovo} caracteres).",
        ]);
    }
}
