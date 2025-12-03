<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentWebhookRequest;
use App\Services\WebhookService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    use ApiResponse;
    protected $webhookService;
    public function __construct(WebhookService $webhookService)
    {
        $this->webhookService = $webhookService;
    }
    public function handle(PaymentWebhookRequest $request)
    {
        try {
            $data = $request->validated();
            $result = $this->webhookService->processWebhook($data);

            return $this->successResponse($result);
        } catch (\Throwable $th) {
            Log::error('Webhook processing failed', [
                'data' => $request->validated(),
                'error' => $th->getMessage(),
            ]);
            return $this->errorResponse($th->getMessage(), $th->getCode());
        }



        // Duplicates webhook

        // Out-of-order webhooks

        // Concurrent webhook

        // Idempotency

    }
}
