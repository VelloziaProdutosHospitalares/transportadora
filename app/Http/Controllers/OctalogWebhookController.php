<?php

namespace App\Http\Controllers;

use App\Http\Requests\OctalogWebhookIncomingRequest;
use App\Services\OctalogInboundWebhookProcessor;
use Illuminate\Http\JsonResponse;

class OctalogWebhookController extends Controller
{
    public function __invoke(
        OctalogWebhookIncomingRequest $request,
        OctalogInboundWebhookProcessor $processor,
    ): JsonResponse {
        /** @var array<int, array<string, mixed>> $items */
        $items = $request->validated('payload');
        $stats = $processor->process($items);

        return response()->json([
            'ok' => true,
            'updated' => $stats['updated'],
            'skipped' => $stats['skipped'],
        ]);
    }
}
