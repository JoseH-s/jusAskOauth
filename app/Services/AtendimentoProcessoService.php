<?php

namespace App\Services;

use App\Models\ChaveGemini;
use App\Models\Empresa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Orquestra o atendimento via WhatsApp:
 * 1. Extrai o numero do processo da mensagem do cliente.
 * 2. Consulta o processo na API do CNJ (PDPJ).
 * 3. Guarda o contexto por contato para perguntas seguintes.
 * 4. Gera a resposta com o Gemini.
 */
class AtendimentoProcessoService
{
    private const API_URL = 'https://portaldeservicos.pdpj.jus.br/api/v2/processos';
    private const SESSION_TTL_HOURS = 7;
    private const MAX_HISTORY_TURNS = 10;

    public function __construct(private GeminiService $gemini)
    {
    }

    public function responder(string $mensagem, ?string $tenant = null, ?string $sessionId = null): string
    {
        $numero = $this->extrairNumeroProcesso($mensagem);
        $cacheKey = $this->cacheKey($tenant, $sessionId);
        $state = $cacheKey ? Cache::get($cacheKey, []) : [];

        if ($numero === null && empty($state['contexto'])) {
            return 'Ola! Para consultar o andamento, envie o *numero do processo* '
                . '(formato CNJ, ex.: 0011632-42.2024.5.15.0033). '
                . 'Depois disso, pode perguntar sobre esse processo sem repetir o numero.';
        }

        if ($numero !== null) {
            $json = $this->consultarPdpj($numero, $tenant);

            if ($json === null) {
                return "Nao consegui localizar o processo *{$numero}* na base do CNJ no momento. "
                    . 'Confira o numero e tente novamente em alguns instantes.';
            }

            $state = [
                'numero' => $numero,
                'contexto' => $this->formatarProcesso($json),
                'history' => [],
            ];
        }

        $systemPrompt = $this->montarSystemPrompt($state['numero'], $state['contexto']);
        $history = $state['history'] ?? [];

        try {
            $resposta = $this->gemini->responder(
                $this->resolverChaveGemini($tenant),
                $systemPrompt,
                $mensagem,
                $history,
            );

            if ($cacheKey) {
                $history[] = ['role' => 'user', 'text' => $mensagem];
                $history[] = ['role' => 'model', 'text' => $resposta];
                $state['history'] = array_slice($history, -self::MAX_HISTORY_TURNS);

                Cache::put($cacheKey, $state, now()->addHours(self::SESSION_TTL_HOURS));
            }

            return $resposta;
        } catch (\Throwable $e) {
            logger()->error('AtendimentoProcessoService: falha ao gerar resposta', [
                'numero' => $state['numero'] ?? $numero,
                'tenant' => $tenant,
                'erro' => $e->getMessage(),
            ]);

            return 'Localizei o processo, mas tive um problema ao gerar a resposta agora. '
                . 'Tente novamente em instantes.';
        }
    }

    /**
     * Detecta numero de processo no padrao CNJ dentro do texto.
     * Aceita com ou sem mascara (NNNNNNN-DD.AAAA.J.TR.OOOO ou 20 digitos).
     */
    public function extrairNumeroProcesso(string $texto): ?string
    {
        if (preg_match('/\d{7}-?\d{2}\.?\d{4}\.?\d\.?\d{2}\.?\d{4}/', $texto, $m)) {
            return $m[0];
        }

        $somenteDigitos = preg_replace('/\D+/', '', $texto);

        if (strlen($somenteDigitos) >= 20) {
            return substr($somenteDigitos, 0, 20);
        }

        return null;
    }

    private function consultarPdpj(string $numero, ?string $tenant): ?array
    {
        $token = ProcessoApiService::resolverToken($tenant);

        if ($token === '') {
            logger()->warning('AtendimentoProcessoService: nenhum token CNJ cadastrado.', [
                'tenant' => $tenant,
            ]);

            return null;
        }

        $numeroLimpo = preg_replace('/\D+/', '', $numero);

        try {
            $response = Http::timeout(20)
                ->withToken($token)
                ->withHeaders(['User-Agent' => 'Jus-Ask-WhatsApp'])
                ->withoutVerifying()
                ->get(self::API_URL, ['numeroProcesso' => $numeroLimpo]);

            if (! $response->successful()) {
                logger()->warning('AtendimentoProcessoService: resposta nao-2xx do PDPJ', [
                    'numero' => $numero,
                    'tenant' => $tenant,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $json = $response->json();

            if (empty($json['content'][0]['tramitacoes'][0])) {
                return null;
            }

            return $json;
        } catch (\Throwable $e) {
            logger()->error('AtendimentoProcessoService: excecao ao consultar PDPJ', [
                'numero' => $numero,
                'tenant' => $tenant,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function formatarProcesso(array $json): string
    {
        $content = $json['content'][0];
        $tram = $content['tramitacoes'][0];

        $text = 'NUMERO: ' . ($content['numeroProcesso'] ?? '?') . "\n";

        if (! empty($tram['tribunal']['nome'])) {
            $sigla = ! empty($tram['tribunal']['sigla']) ? " ({$tram['tribunal']['sigla']})" : '';
            $text .= "Tribunal: {$tram['tribunal']['nome']}{$sigla}\n";
        }
        if (! empty($tram['classe'][0]['descricao'])) {
            $text .= "Classe: {$tram['classe'][0]['descricao']}\n";
        }
        if (! empty($tram['assunto'])) {
            $text .= "Assuntos:\n";
            foreach ($tram['assunto'] as $assunto) {
                $text .= '  - ' . ($assunto['descricao'] ?? '?') . "\n";
            }
        }
        if (! empty($tram['valorAcao'])) {
            $text .= 'Valor da acao: R$ ' . number_format((float) $tram['valorAcao'], 2, ',', '.') . "\n";
        }
        if (! empty($tram['dataHoraAjuizamento'])) {
            $text .= 'Ajuizamento: ' . Carbon::parse($tram['dataHoraAjuizamento'])->format('d/m/Y') . "\n";
        }
        if (! empty($tram['orgaoJulgador']['nome'])) {
            $text .= "Orgao julgador: {$tram['orgaoJulgador']['nome']}\n";
        }

        if (! empty($tram['partes'])) {
            $text .= "\nPARTES:\n";
            foreach ($tram['partes'] as $parte) {
                $polo = $parte['polo'] ?? '?';
                $nome = $parte['nome'] ?? '?';
                $tipo = $parte['tipoParte'] ?? '?';
                $text .= "  [{$polo} / {$tipo}] {$nome}\n";
            }
        }

        if (! empty($tram['movimentos'])) {
            $text .= "\nMOVIMENTOS (mais recentes primeiro):\n";
            $movimentos = array_slice($tram['movimentos'], -30);

            foreach (array_reverse($movimentos) as $movimento) {
                $data = ! empty($movimento['dataHora'])
                    ? Carbon::parse($movimento['dataHora'])->format('d/m/Y')
                    : '?';
                $descricao = $movimento['descricao'] ?? ($movimento['tipo']['nome'] ?? 'sem descricao');
                $text .= "  [{$data}] {$descricao}\n";
            }
        }

        return $text;
    }

    private function montarSystemPrompt(string $numero, string $contexto): string
    {
        return <<<PROMPT
Voce e um assistente juridico virtual que atende clientes pelo WhatsApp.
Voce tem acesso exclusivamente as informacoes do processo abaixo, consultadas na base oficial do CNJ.

DADOS DO PROCESSO {$numero}:
{$contexto}

REGRAS:
1. Responda apenas com base nos dados do processo acima. Nunca invente informacoes.
2. Se a informacao pedida nao estiver nos dados, diga claramente que nao consta.
3. Seja conciso e use linguagem simples; o cliente e leigo.
4. Ao citar termos juridicos como liminar, tutela, transito em julgado ou agravo, explique em poucas palavras.
5. Formate para WhatsApp: textos curtos, use *negrito* com asteriscos quando ajudar.
6. Nao responda perguntas fora do tema deste processo.
PROMPT;
    }

    private function resolverChaveGemini(?string $tenant): string
    {
        $empresaId = null;

        if ($tenant !== null && trim($tenant) !== '') {
            $empresaId = Empresa::where('tenant', $tenant)->value('id');
        }

        $query = ChaveGemini::withoutGlobalScopes();

        if ($empresaId !== null) {
            $query->where('empresa_id', $empresaId);
        }

        $chave = $query->value('chave') ?? env('GEMINI_API_KEY');

        if (! $chave) {
            throw new \RuntimeException('Nenhuma chave Gemini configurada.');
        }

        return $chave;
    }

    private function cacheKey(?string $tenant, ?string $sessionId): ?string
    {
        if ($sessionId === null || trim($sessionId) === '') {
            return null;
        }

        return 'whatsapp_atendimento:' . sha1(($tenant ?? 'global') . '|' . $sessionId);
    }
}
