<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MoyasarService
{
    private string $baseUrl = 'https://api.moyasar.com/v1';

    public function __construct(private string $secretKey) {}

    public function createInvoice(array $payload): array
    {
        \Log::info('📤 Sending request to Moyasar', [
            'endpoint' => $this->baseUrl.'/invoices',
            'payload' => $payload,
        ]);

        $res = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->asJson()
            ->post($this->baseUrl.'/invoices', $payload);

        \Log::info('📥 Moyasar response received', [
            'status' => $res->status(),
            'successful' => $res->successful(),
            'body' => $res->json(),
        ]);

        if (!$res->successful()) {
            \Log::error('❌ Moyasar API error', [
                'status' => $res->status(),
                'body' => $res->body(),
            ]);
            throw new \RuntimeException('Moyasar error: '.$res->body());
        }

        return $res->json();
    }

    public function fetchPayment(string $paymentId): array
    {
        \Log::info('📤 Fetching payment from Moyasar', [
            'payment_id' => $paymentId,
        ]);

        $res = Http::withBasicAuth($this->secretKey, '')
            ->acceptJson()
            ->get($this->baseUrl.'/payments/'.$paymentId);

        if (!$res->successful()) {
            \Log::error('❌ Moyasar fetch payment error', [
                'payment_id' => $paymentId,
                'status' => $res->status(),
                'body' => $res->body(),
            ]);
            throw new \RuntimeException('Moyasar error: '.$res->body());
        }

        return $res->json();
    }
}