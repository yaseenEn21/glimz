<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsService
{
    protected string $token;
    protected string $baseUrl;

    public function __construct()
    {
        $this->token = (string) config('services.taqnyat.token');
        $this->baseUrl = rtrim((string) config('services.taqnyat.base_url', 'https://api.taqnyat.sa'), '/');
    }

    /**
     * Send SMS via Taqnyat
     *
     * @param string      $to      International format WITHOUT + or 00 (we normalize)
     * @param string      $message SMS body
     * @param string      $sender  Pre-defined sender name in your Taqnyat account
     * @param int         $type    kept for backward compatibility (not used by Taqnyat)
     * @param string|null $schedule ISO datetime e.g. 2026-01-19T14:26
     */
    public function send(
        string $to,
        string $message,
        string $sender = 'Glimz',
        int $type = 0,
        ?string $schedule = null
    ): array {
        if ($this->token === '') {
            return [
                'success' => false,
                'message' => 'Missing TAQNYAT token',
                'code' => null,
                'raw' => null,
                'http_status' => null,
                'request' => null,
            ];
        }

        $to = $this->normalizeMobile($to);

        $url = $this->baseUrl . '/v1/messages';

        $payload = [
            'recipients' => [$to],       // array as per docs :contentReference[oaicite:1]{index=1}
            'body' => $message,
            'sender' => $sender,
        ];

        if ($schedule) {
            $payload['scheduledDatetime'] = $schedule; // optional :contentReference[oaicite:2]{index=2}
        }

        Log::info('TaqnyatSMS: sending', [
            'url' => $url,
            'to' => $to,
            'sender' => $sender,
            'len' => mb_strlen($message),
            'scheduled' => $schedule,
        ]);

        try {
            $response = Http::timeout(20)
                ->acceptJson()
                ->withToken($this->token) // Authorization: Bearer <token> :contentReference[oaicite:3]{index=3}
                ->post($url, $payload);
        } catch (Throwable $e) {
            Log::error('TaqnyatSMS: HTTP exception', [
                'to' => $to,
                'url' => $url,
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'فشل الاتصال بخدمة الرسائل',
                'code' => null,
                'raw' => null,
                'http_status' => null,
                'request' => $payload,
            ];
        }

        $json = $response->json();
        $raw = $response->body();

        // توثيقهم يرجّع statusCode و messageId… الخ عند النجاح :contentReference[oaicite:4]{index=4}
        $code = is_array($json) ? (string) ($json['statusCode'] ?? $response->status()) : (string) $response->status();
        $msg = is_array($json) ? (string) ($json['message'] ?? '') : '';

        return [
            'success' => $response->successful(),
            'message' => $msg !== '' ? $msg : ($response->successful() ? 'تم إرسال الرسالة' : 'فشل إرسال الرسالة'),
            'code' => $code,
            'raw' => $raw,
            'http_status' => $response->status(),
            'request' => $payload,
            'response' => $json,
        ];
    }

    /**
     * Normalize to international format without + or 00
     * Example: +9665xxxx -> 9665xxxx , 009665xxxx -> 9665xxxx :contentReference[oaicite:5]{index=5}
     */
    private function normalizeMobile(string $to): string
    {
        $to = trim($to);
        $to = str_replace([' ', '-', '(', ')'], '', $to);

        // +9665xxxx
        if (str_starts_with($to, '+')) {
            $to = substr($to, 1);
        }

        // 009665xxxx
        if (str_starts_with($to, '00')) {
            $to = substr($to, 2);
        }

        // 05xxxxxxxx -> 9665xxxxxxxx
        if (str_starts_with($to, '05')) {
            $to = '966' . substr($to, 1);
        }

        return $to;
    }
}