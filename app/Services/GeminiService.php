<?php

namespace App\Services;

use App\Models\ChaveGemini;
use App\Models\Cliente;
use App\Models\Processo;
use App\Models\ProcessoConteudo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class GeminiService
{

    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-lite';

    // ─────────────────────────────────────────────────────────────────────
    // System Prompt
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Monta o system prompt com os dados dos processos do cliente.
     * Usa withoutGlobalScopes() porque TenantManager pode não estar ativo.
     */
    static function buildSystemPrompt(int $empresaId, int $clienteId): string
    {
        $cliente = Cliente::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->findOrFail($clienteId);

        $processos = Processo::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('cliente_id', $clienteId)
            ->get();

        if ($processos->isEmpty()) {
            return <<<PROMPT
Você é um assistente jurídico virtual. O cliente {$cliente->nome} não possui processos cadastrados no sistema.
Informe educadamente que nenhum processo foi encontrado e oriente-o a entrar em contato com o escritório.
Recuse qualquer pergunta sobre outros assuntos.
PROMPT;
        }

        $processosText = $processos
            ->map(fn (Processo $p) => self::buildProcessoText($p, $empresaId))
            ->implode("\n\n---\n\n");

        return <<<PROMPT
Você é um assistente jurídico virtual. Você tem acesso exclusivamente às informações dos processos jurídicos do cliente abaixo.

CLIENTE: {$cliente->nome}

PROCESSOS DO CLIENTE:
{$processosText}

REGRAS ESTRITAS QUE VOCÊ DEVE SEGUIR:
1. Responda APENAS perguntas relacionadas aos processos listados acima.
2. Se o usuário perguntar sobre qualquer outro assunto (clima, receitas, política, outros casos, etc.), recuse educadamente e redirecione ao tema dos processos.
3. Nunca invente informações. Use apenas o que está documentado acima.
4. Se não souber a resposta com base nas informações disponíveis, diga isso claramente.
5. Seja conciso, claro e profissional.
6. Você está conversando com o próprio cliente {$cliente->nome}.
7. Nunca revele dados de outros clientes ou processos de outras pessoas.
8. Se houver informações indisponíveis, incompletas ou ocultas sobre qualquer processo, informe ao usuário com clareza o que não está disponível — nunca omita essas limitações.
9. Sempre que mencionar termos técnicos jurídicos (como 'liminar', 'tutela antecipada', 'habeas corpus', 'trânsito em julgado', 'agravo', 'apelação', 'mandado de segurança', 'citação', 'intimação', entre outros), explique-os de forma simples e acessível para um cliente leigo, usando parênteses ou um parágrafo explicativo na mesma resposta.
PROMPT;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Chat
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Envia uma mensagem ao Gemini usando a chave vinculada ao cliente
     * (ou à empresa como fallback). Lança exceção em caso de falha.
     *
     * @param  int    $empresaId
     * @param  int    $clienteId   ID do cliente para buscar a chave vinculada
     * @param  string $systemPrompt
     * @param  array  $history     [['role' => 'user'|'model', 'text' => string], ...]
     * @param  string $userMessage
     * @return string
     *
     * @throws \RuntimeException|\Throwable
     */
    static function chat(
        int    $empresaId,
        int    $clienteId,
        string $systemPrompt,
        array  $history,
        string $userMessage,
    ): string {
        $apiKey = self::resolverChave($empresaId, $clienteId);

        $contents = array_map(
            fn ($turn) => [
                'role'  => $turn['role'],
                'parts' => [['text' => $turn['text']]],
            ],
            $history,
        );

        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        $url = self::API_BASE.':generateContent?key='.$apiKey;

        try {
            $response = Http::timeout(30)->post($url, [
                'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents'           => $contents,
                'generationConfig'   => ['thinkingConfig' => ['thinkingBudget' => 0]],
            ]);

            if ($response->failed()) {
                logger()->error('GeminiService: falha na API', [
                    'empresa_id' => $empresaId,
                    'cliente_id' => $clienteId,
                    'status'     => $response->status(),
                    'body'       => $response->body(),
                ]);
                throw new \RuntimeException('Erro na API Gemini: ' . $response->status());
            }

            $text = $response->json('candidates.0.content.parts.0.text');

            if (! is_string($text) || $text === '') {
                throw new \RuntimeException('Resposta vazia recebida da API Gemini.');
            }

            return $text;
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            logger()->error('GeminiService: exceção inesperada', [
                'empresa_id' => $empresaId,
                'cliente_id' => $clienteId,
                'erro'       => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Resposta "livre": recebe a chave já resolvida e um system prompt montado
     * externamente. Usado no atendimento via WhatsApp, onde a consulta é ad-hoc
     * por número de processo (sem cliente cadastrado).
     *
     * @param array $history [['role' => 'user'|'model', 'text' => string], ...]
     *
     * @throws \RuntimeException|\Throwable
     */
    static function responder(
        $apiKey = NULL,
        $systemPrompt = NULL,
        $userMessage =NULL,
        $history = [],
    ){
        $contents = array_map(
            fn ($turn) => [
                'role'  => $turn['role'],
                'parts' => [['text' => $turn['text']]],
            ],
            $history,
        );

        $contents[] = ['role' => 'user', 'parts' => [['text' => $userMessage]]];

        $url = self::API_BASE.':generateContent?key='.$apiKey;

        $response = Http::timeout(30)->post($url, [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents'           => $contents,
            'generationConfig'   => ['thinkingConfig' => ['thinkingBudget' => 0]],
        ]);

        if ($response->failed()) {
            logger()->error('GeminiService: falha na API (responder)', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException('Erro na API Gemini: ' . $response->status());
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! is_string($text) || $text === '') {
            throw new \RuntimeException('Resposta vazia recebida da API Gemini.');
        }

        return $text;
    }

    // ─────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────

    /**
     * Resolve a chave da API: primeiro tenta a chave vinculada ao cliente,
     * depois qualquer chave da empresa.
     */
    private static function resolverChave(int $empresaId, int $clienteId): string
    {
        // 1. Chave específica do cliente
        if ($clienteId > 0) {
            $cliente = Cliente::withoutGlobalScopes()
                ->where('empresa_id', $empresaId)
                ->select(['chave_gemini_id'])
                ->find($clienteId);

            if ($cliente?->chave_gemini_id) {
                $chave = ChaveGemini::withoutGlobalScopes()
                    ->where('empresa_id', $empresaId)
                    ->where('id', $cliente->chave_gemini_id)
                    ->value('chave');
                    // dd($chave);
                if ($chave) {
                    
                    return $chave;
                }
            }
        }

        // 2. Fallback: qualquer chave da empresa
        $chave = ChaveGemini::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->value('chave');

        if ($chave) {
            return $chave;
        }

        // 3. Fallback: variável de ambiente
        $chave = env('GEMINI_API_KEY');

        if ($chave) {
            return $chave;
        }

        throw new \RuntimeException(
            'Nenhuma chave Gemini configurada para este escritório. ' .
            'Acesse o menu "Chaves Gemini" para adicionar uma chave.'
        );
    }

    private static function buildProcessoText(Processo $p, int $empresaId): string
    {
        $text  = "PROCESSO: {$p->numero}\n";
        $text .= 'Situação: ' . ($p->ativo ? 'Em andamento' : 'Encerrado') . "\n";

        $snapshot = ProcessoConteudo::withoutGlobalScopes()
            ->where('empresa_id', $empresaId)
            ->where('processo_id', $p->id)
            ->latest()
            ->first();

        if (! $snapshot || empty($snapshot->conteudo_json)) {
            $text .= "Detalhes: informações detalhadas não disponíveis (sincronização pendente).\n";
            return $text;
        }

        $raw  = is_string($snapshot->conteudo_json)
            ? json_decode($snapshot->conteudo_json, true)
            : (array) $snapshot->conteudo_json;

        $content = $raw['content'][0] ?? null;
        $tram    = $content['tramitacoes'][0] ?? null;

        if (! $tram) {
            $text .= "Detalhes: dados de tramitação não disponíveis.\n";
            return $text;
        }

        // Tribunal
        if (! empty($tram['tribunal']['nome'])) {
            $sigla = ! empty($tram['tribunal']['sigla']) ? " ({$tram['tribunal']['sigla']})" : '';
            $text .= "Tribunal: {$tram['tribunal']['nome']}{$sigla}\n";
        }
        if (! empty($tram['tribunal']['segmento'])) {
            $text .= 'Segmento: ' . str_replace('_', ' ', $tram['tribunal']['segmento']) . "\n";
        }

        // Classe processual
        if (! empty($tram['classe'][0]['descricao'])) {
            $text .= "Classe: {$tram['classe'][0]['descricao']}\n";
        }

        // Assunto(s) com hierarquia completa
        if (! empty($tram['assunto'])) {
            $text .= "Assunto(s):\n";
            foreach ($tram['assunto'] as $a) {
                $hierarquia = ! empty($a['hierarquia']) ? " | {$a['hierarquia']}" : '';
                $text .= "  - {$a['descricao']}{$hierarquia}\n";
            }
        }

        // Dados financeiros e temporais
        if (! empty($tram['valorAcao'])) {
            $text .= 'Valor da ação: R$ ' . number_format((float) $tram['valorAcao'], 2, ',', '.') . "\n";
        }
        if (! empty($tram['dataHoraAjuizamento'])) {
            $text .= 'Data de ajuizamento: ' . Carbon::parse($tram['dataHoraAjuizamento'])->format('d/m/Y H:i') . "\n";
        }
        if (! empty($tram['dataHoraUltimaDistribuicao'])) {
            $text .= 'Última distribuição: ' . Carbon::parse($tram['dataHoraUltimaDistribuicao'])->format('d/m/Y H:i') . "\n";
        }

        // Liminar e órgão julgador
        if (isset($tram['liminar'])) {
            $text .= 'Liminar: ' . ($tram['liminar'] ? 'Sim' : 'Não') . "\n";
        }
        if (! empty($tram['orgaoJulgador']['nome'])) {
            $text .= "Órgão julgador: {$tram['orgaoJulgador']['nome']}\n";
        }

        // Último movimento (resumo rápido)
        if (! empty($tram['ultimoMovimento']['dataHora'])) {
            $dataUm = Carbon::parse($tram['ultimoMovimento']['dataHora'])->format('d/m/Y H:i');
            $text  .= "Último movimento: [{$dataUm}] {$tram['ultimoMovimento']['descricao']}\n";
        }

        // Partes do processo
        if (! empty($tram['partes'])) {
            $text .= "\nPARTES DO PROCESSO:\n";
            foreach ($tram['partes'] as $parte) {
                $polo      = $parte['polo']      ?? '?';
                $tipoParte = $parte['tipoParte'] ?? '?';
                $nome      = $parte['nome']      ?? '?';
                $text .= "  [{$polo} / {$tipoParte}] {$nome}\n";

                foreach ($parte['representantes'] ?? [] as $rep) {
                    $tipoRep = $rep['tipoRepresentacao'] ?? 'Representante';
                    $nomeRep = $rep['nome'] ?? '?';
                    $oab     = '';
                    if (! empty($rep['oab'][0])) {
                        $oab = " — OAB/{$rep['oab'][0]['uf']} {$rep['oab'][0]['numero']}";
                    }
                    $text .= "    → {$tipoRep}: {$nomeRep}{$oab}\n";
                }
            }
        }

        // Todos os movimentos
        if (! empty($tram['movimentos'])) {
            $text .= "\nMOVIMENTOS DO PROCESSO:\n";
            foreach ($tram['movimentos'] as $mov) {
                $seq       = $mov['sequencia'] ?? '?';
                $data      = ! empty($mov['dataHora'])
                    ? Carbon::parse($mov['dataHora'])->format('d/m/Y H:i')
                    : '?';
                $descricao = $mov['descricao'] ?? ($mov['tipo']['nome'] ?? 'sem descrição');
                $extra     = '';

                if (! empty($mov['magistrado']['nome'])) {
                    $extra .= " — Magistrado: {$mov['magistrado']['nome']}";
                } elseif (! empty($mov['usuario']['nome'])) {
                    $extra .= " — Usuário: {$mov['usuario']['nome']}";
                }

                $text .= "  [{$seq}] {$data} — {$descricao}{$extra}\n";
            }
        }

        return $text;
    }
}
