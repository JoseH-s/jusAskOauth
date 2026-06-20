<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Cliente HTTP para a Evolution API (WhatsApp não-oficial).
 *
 * Configuração via .env do Laravel:
 *   EVOLUTION_API_URL=http://localhost:8080
 *   EVOLUTION_API_KEY=<AUTHENTICATION_API_KEY do docker>
 *   EVOLUTION_INSTANCE=<nome da instância criada no manager>
 */
class EvolutionWhatsappService
{
    private string $baseUrl;
    private string $apiKey;
    private string $instance;

    public function __construct()
    {
        $this->baseUrl  = rtrim((string) env('EVOLUTION_API_URL', 'http://localhost:8080'), '/');
        $this->apiKey   = (string) env('EVOLUTION_API_KEY', '');
        $this->instance = (string) env('EVOLUTION_INSTANCE', '');
    }

    /**
     * Envia uma mensagem de texto.
     *
     * @param string $number Número/JID do destinatário (ex.: 5511999999999 ou 5511999999999@s.whatsapp.net)
     */
    public function enviarTexto(string $number, string $text): bool
    {
        $response = Http::timeout(20)
            ->withHeaders(['apikey' => $this->apiKey])
            ->post("{$this->baseUrl}/message/sendText/{$this->instance}", [
                'number' => $this->normalizarNumero($number),
                'text'   => $text,
            ]);

        if (! $response->successful()) {
            logger()->warning('EvolutionWhatsappService: falha ao enviar mensagem', [
                'number' => $number,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Marca o chat como "digitando" para dar feedback ao cliente enquanto o Gemini responde.
     */
    public function enviarPresenca(string $number, string $presence = 'composing', int $delayMs = 1200): void
    {
        try {
            Http::timeout(10)
                ->withHeaders(['apikey' => $this->apiKey])
                ->post("{$this->baseUrl}/chat/sendPresence/{$this->instance}", [
                    'number'   => $this->normalizarNumero($number),
                    'presence' => $presence,
                    'delay'    => $delayMs,
                ]);
        } catch (\Throwable $e) {
            // presença é best-effort; não interrompe o fluxo
        }
    }

    /**
     * Remove o sufixo @s.whatsapp.net e deixa apenas dígitos.
     */
    private function normalizarNumero(string $number): string
    {
        return preg_replace('/\D+/', '', explode('@', $number)[0]) ?? $number;
    }
}
