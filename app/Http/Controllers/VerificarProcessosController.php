<?php

namespace App\Http\Controllers;

use App\Services\ProcessoApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Dispara a verificação de todos os processos ativos (estilo cron).
 * Não valida tenant; é protegido por token na query (?token=).
 */
class VerificarProcessosController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $tokenEsperado = (string) env('PROCESSOS_VERIFICACAO_TOKEN', '');

        if ($tokenEsperado === '' || (string) $request->query('token') !== $tokenEsperado) {
            return response()->json(['message' => 'Token inválido.'], 401);
        }

        $atualizados = ProcessoApiService::verificarProcessosAtivos();

        return response()->json(['atualizados' => $atualizados]);
    }
}
