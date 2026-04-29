<?php

namespace App\Http\Controllers;

use App\DTOs\SerproNfeConsultaData;
use App\Exceptions\SerproException;
use App\Http\Requests\ConsultaSerproNfeRequest;
use App\Services\SerproNfeService;
use Illuminate\Http\JsonResponse;

class SerproNfeConsultaController extends Controller
{
    public function __invoke(ConsultaSerproNfeRequest $request, SerproNfeService $serproNfeService): JsonResponse
    {
        try {
            if (! $serproNfeService->isConfigured()) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Integração SERPRO não configurada.',
                ]);
            }

            /** @var string $chave */
            $chave = $request->validated('chave_nf');

            try {
                $payload = $serproNfeService->consultarNfePorChave($chave);
            } catch (SerproException $e) {
                return response()->json([
                    'ok' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            $dto = SerproNfeConsultaData::fromApiPayload($payload, $chave);

            return response()->json([
                'ok' => true,
                'data' => $dto->toFormPrefill(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'message' => 'Não foi possível concluir a consulta. Tente novamente em instantes.',
            ], 500);
        }
    }
}
