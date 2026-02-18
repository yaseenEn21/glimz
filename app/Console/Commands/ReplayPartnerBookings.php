<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ReplayPartnerBookings extends Command
{
    protected $signature = 'partner:replay-bookings
                            {--log= : مسار ملف اللوج (افتراضي: storage/logs/laravel.log)}
                            {--date= : فلتر التاريخ مثل 2026-02-18}
                            {--time-from= : من وقت معين مثل 12:00}
                            {--time-to= : لحد وقت معين مثل 16:00}
                            {--dry-run : عرض الطلبات فقط بدون إرسال}
                            {--delay=500 : تأخير بين الطلبات بالميلي ثانية}
                            {--concurrent : إرسال الطلبات بالتوازي}
                            {--api-key= : مفتاح API للشريك}
                            {--api-url= : رابط الـ API (افتراضي من .env)}';

    protected $description = 'إعادة إرسال حجوزات الشريك من اللوج للاختبار';

    public function handle(): void
    {
        $logPath = $this->option('log') ?? storage_path('logs/laravel.log');

        if (!file_exists($logPath)) {
            $this->error("ملف اللوج غير موجود: {$logPath}");
            return;
        }

        $this->info("📂 قراءة اللوج: {$logPath}");

        // ✅ استخراج كل الطلبات من اللوج
        $requests = $this->extractRequestsFromLog($logPath);

        if (empty($requests)) {
            $this->warn('⚠️ لم يتم إيجاد أي طلبات في اللوج');
            return;
        }

        // ✅ فلترة بالوقت إذا طُلب
        $requests = $this->applyFilters($requests);

        $this->info("✅ تم إيجاد " . count($requests) . " طلب");
        $this->newLine();

        // ✅ عرض ملخص
        $this->displaySummary($requests);

        if ($this->option('dry-run')) {
            $this->warn('🔍 Dry-run mode — لم يتم إرسال أي طلب');
            return;
        }

        if (!$this->confirm('هل تريد إرسال الطلبات؟')) {
            return;
        }

        // ✅ إرسال
        $apiKey  = $this->option('api-key') ?? config('services.partner_api.key');
        $apiUrl  = $this->option('api-url') ?? config('app.url');
        $delay   = (int) $this->option('delay');

        if ($this->option('concurrent')) {
            $this->sendConcurrent($requests, $apiUrl, $apiKey);
        } else {
            $this->sendSequential($requests, $apiUrl, $apiKey, $delay);
        }
    }

    // ═══════════════════════════════════════════════════════
    //  استخراج الطلبات من اللوج
    // ═══════════════════════════════════════════════════════

    private function extractRequestsFromLog(string $path): array
    {
        $requests = [];
        $handle   = fopen($path, 'r');

        while (($line = fgets($handle)) !== false) {
            // ✅ نبحث عن سطر "Incoming request"
            if (!str_contains($line, '[PartnerBooking] Incoming request')) {
                continue;
            }

            // استخراج الـ JSON
            $jsonStart = strpos($line, '{');
            if ($jsonStart === false) continue;

            $json = substr($line, $jsonStart);
            $data = json_decode($json, true);
            if (!$data || empty($data['payload'])) continue;

            // استخراج الوقت من السطر
            preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $m);

            $requests[] = [
                'log_time'   => $m[1] ?? null,
                'partner_id' => $data['partner_id'],
                'payload'    => $data['payload'],
            ];
        }

        fclose($handle);
        return $requests;
    }

    // ═══════════════════════════════════════════════════════
    //  فلترة
    // ═══════════════════════════════════════════════════════

    private function applyFilters(array $requests): array
    {
        $date      = $this->option('date');
        $timeFrom  = $this->option('time-from');
        $timeTo    = $this->option('time-to');

        return array_filter($requests, function ($req) use ($date, $timeFrom, $timeTo) {
            $startDateTime = $req['payload']['start_date_time'] ?? '';

            // فلتر التاريخ
            if ($date && !str_starts_with($startDateTime, $date)) {
                return false;
            }

            // فلتر الوقت
            if ($timeFrom || $timeTo) {
                $time = substr($startDateTime, 11, 5); // HH:MM
                if ($timeFrom && $time < $timeFrom) return false;
                if ($timeTo   && $time > $timeTo)   return false;
            }

            return true;
        });
    }

    // ═══════════════════════════════════════════════════════
    //  عرض ملخص
    // ═══════════════════════════════════════════════════════

    private function displaySummary(array $requests): void
    {
        // توزيع الأوقات
        $times = [];
        foreach ($requests as $req) {
            $time = substr($req['payload']['start_date_time'] ?? '', 11, 5);
            $times[$time] = ($times[$time] ?? 0) + 1;
        }

        ksort($times);

        $this->table(
            ['الوقت المطلوب', 'عدد الطلبات'],
            array_map(fn($t, $c) => [$t, $c], array_keys($times), array_values($times))
        );
    }

    // ═══════════════════════════════════════════════════════
    //  إرسال متسلسل
    // ═══════════════════════════════════════════════════════

    private function sendSequential(array $requests, string $url, string $apiKey, int $delay): void
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        $bar = $this->output->createProgressBar(count($requests));
        $bar->start();

        foreach ($requests as $req) {
            $result = $this->sendRequest($url, $apiKey, $req['payload']);

            if ($result['success']) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    $req['payload']['external_booking_id'],
                    $req['payload']['customer_name'],
                    $result['error_code'],
                    $result['error'],
                ];
            }

            $bar->advance();
            usleep($delay * 1000);
        }

        $bar->finish();
        $this->newLine(2);

        $this->displayResults($results);
    }

    // ═══════════════════════════════════════════════════════
    //  إرسال بالتوازي
    // ═══════════════════════════════════════════════════════

    private function sendConcurrent(array $requests, string $url, string $apiKey): void
    {
        $this->warn('⚡ إرسال بالتوازي — هذا يختبر الـ Race Condition');

        $promises = [];
        $results  = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($requests as $req) {
            $promises[] = Http::withHeaders([
                'X-Partner-Key' => $apiKey,
                'Accept'        => 'application/json',
            ])->async()->post("{$url}/api/partners/v1/bookings", $req['payload']);
        }

        // انتظر كل الردود
        foreach ($promises as $i => $promise) {
            $response = $promise->wait();
            $body     = $response->json();

            if ($response->successful() && ($body['success'] ?? false)) {
                $results['success']++;
            } else {
                $results['failed']++;
                $results['errors'][] = [
                    $requests[$i]['payload']['external_booking_id'],
                    $requests[$i]['payload']['customer_name'],
                    $body['error_code'] ?? 'HTTP_' . $response->status(),
                    $body['error'] ?? $response->body(),
                ];
            }
        }

        $this->displayResults($results);
    }

    // ═══════════════════════════════════════════════════════
    //  إرسال طلب واحد
    // ═══════════════════════════════════════════════════════

    private function sendRequest(string $url, string $apiKey, array $payload): array
    {
        try {
            $response = Http::withHeaders([
                'X-Partner-Key' => $apiKey,
                'Accept'        => 'application/json',
            ])->post("{$url}/api/partners/v1/bookings", $payload);

            $body = $response->json();

            if ($response->successful() && ($body['success'] ?? false)) {
                return ['success' => true];
            }

            return [
                'success'    => false,
                'error_code' => $body['error_code'] ?? 'HTTP_' . $response->status(),
                'error'      => $body['error'] ?? 'Unknown error',
            ];

        } catch (\Exception $e) {
            return [
                'success'    => false,
                'error_code' => 'EXCEPTION',
                'error'      => $e->getMessage(),
            ];
        }
    }

    // ═══════════════════════════════════════════════════════
    //  عرض النتائج
    // ═══════════════════════════════════════════════════════

    private function displayResults(array $results): void
    {
        $this->info("✅ نجح: {$results['success']}");
        $this->error("❌ فشل: {$results['failed']}");

        if (!empty($results['errors'])) {
            $this->newLine();
            $this->warn('الطلبات الفاشلة:');
            $this->table(
                ['External ID', 'الاسم', 'Error Code', 'السبب'],
                $results['errors']
            );
        }
    }
}