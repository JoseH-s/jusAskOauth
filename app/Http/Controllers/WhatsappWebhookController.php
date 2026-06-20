<?php

namespace App\Http\Controllers;

use App\Services\AtendimentoProcessoService;
use App\Services\EvolutionWhatsappService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Recebe os webhooks da Evolution API (eventos do WhatsApp).
 *
 * Configure na Evolution para enviar o evento MESSAGES_UPSERT para:
 *   POST /{tenant}/webhooks/whatsapp
 *
 * Segurança simples por token na query string (?token=...), comparado
 * com EVOLUTION_WEBHOOK_TOKEN do .env.
 */
class WhatsappWebhookController extends Controller
{
    public function handle(
        Request $request,
        AtendimentoProcessoService $atendimento,
        EvolutionWhatsappService $whatsapp,
    ): JsonResponse {
        // Autenticação leve do webhook
        $tokenEsperado = (string) env('EVOLUTION_WEBHOOK_TOKEN', '');
        if ($tokenEsperado !== '' && (string) $request->query('token') !== $tokenEsperado) {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        $evento = (string) $request->input('event');

        // Só nos interessam mensagens recebidas
        if (! in_array($evento, ['messages.upsert', 'MESSAGES_UPSERT'], true)) {
            return response()->json(['status' => 'ignored', 'event' => $evento]);
        }

        $tenant = $request->route('tenant') ?: $request->query('tenant');
        $data = $request->input('data', []);

        // Ignora mensagens enviadas por nós mesmos
        if (($data['key']['fromMe'] ?? false) === true) {
            return response()->json(['status' => 'ignored', 'reason' => 'fromMe']);
        }

        $remoteJid = $data['key']['remoteJid'] ?? null;
        $texto     = $this->extrairTexto($data);

        // Ignora grupos e mensagens sem texto (áudio, imagem, etc.)
        if (! $remoteJid || str_contains($remoteJid, '@g.us') || $texto === null || trim($texto) === '') {
            return response()->json(['status' => 'ignored', 'reason' => 'sem texto utilizável']);
        }

        try {
            $whatsapp->enviarPresenca($remoteJid, 'composing');

            $resposta = $atendimento->responder(
                mensagem: $texto,
                tenant: is_string($tenant) ? $tenant : null,
                sessionId: $remoteJid,
            );

            $whatsapp->enviarTexto($remoteJid, $resposta);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['status' => 'error'], 200); // 200 evita reentrega em loop
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Extrai o texto da mensagem nos formatos comuns da Evolution/WhatsApp.
     */
    private function extrairTexto(array $data): ?string
    {
        $message = $data['message'] ?? [];

        return $message['conversation']
            ?? $message['extendedTextMessage']['text']
            ?? $message['imageMessage']['caption']
            ?? $message['videoMessage']['caption']
            ?? null;
    }
}
