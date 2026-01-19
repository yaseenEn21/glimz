<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    public function __construct(
        private ?string $projectId = null
    ) {
        // نستخدم project_id من config
        $this->projectId = $this->projectId ?: config('services.fcm.project_id');
    }

    /* ============== واجهة عامة ============== */

    /**
     * إرسال إشعار لمستخدم واحد باستخدام template
     */
    public function sendToUserUsingTemplate(
        User $user,
        string $templateKey,
        array $templateData = [],   
        array $extraData = [],      // type, post_id, announcement_id...
        ?string $overrideTitle = null,
        ?string $overrideBody = null,
        string $locale = 'ar',
        ?int $createdBy = null,
    ): ?Notification {
        $template = NotificationTemplate::where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return null; // أو ارجع exception إذا تحب
        }

        // النص حسب اللغة
        $title = $overrideTitle ?: $template->getTitleForLocale($locale);
        $body = $overrideBody ?: $template->getBodyForLocale($locale);

        // استبدال الـ placeholders في النص
        $title = $this->replacePlaceholders($title, $templateData);
        $body = $this->replacePlaceholders($body, $templateData);

        // حفظ في جدول notifications
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'data' => $extraData ?: null,
            'is_read' => false,
            'created_by' => $createdBy,
        ]);

        // إرسال عبر topic خاص بالمستخدم
        $topic = 'user_' . $user->id;

        $this->sendToTopic($topic, $title, $body, array_merge($extraData, [
            'notification_id' => $notification->id,
        ]));

        return $notification;
    }

    /**
     * إرسال إشعار لـ topic (مثلاً كل أولياء الأمور)
     */
    public function sendToTopicUsingTemplate(
        string $topic,
        string $templateKey,
        array $templateData = [],
        array $extraData = [],
        ?string $overrideTitle = null,
        ?string $overrideBody = null,
        string $locale = 'ar',
        ?int $createdBy = null,
        ?array $usersForStorage = null // لو بدك تخزن notification لكل user
    ): void {
        $template = NotificationTemplate::where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if (!$template)
            return;

        $title = $overrideTitle ?: $template->getTitleForLocale($locale);
        $body = $overrideBody ?: $template->getBodyForLocale($locale);

        $title = $this->replacePlaceholders($title, $templateData);
        $body = $this->replacePlaceholders($body, $templateData);

        // إرسال مرة واحدة للـ topic
        $this->sendToTopic($topic, $title, $body, $extraData);

        // تخزين في DB (اختياري) لكل user لو مرّرتهم
        if ($usersForStorage) {
            foreach ($usersForStorage as $user) {
                Notification::create([
                    'user_id' => $user,
                    'title' => $title,
                    'body' => $body,
                    'data' => $extraData ?: null,
                    'is_read' => false,
                    'created_by' => $createdBy,
                ]);
            }
        }
    }

    /* ============== إرسال فعلي إلى FCM (v1) ============== */

    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        if (!$this->projectId) {
            Log::error('FCM: projectId مفقود في NotificationService.');
            return;
        }

        try {
            $accessToken = \App\Services\FirebaseAccessTokenService::make();

            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            // ✅ FCM data values must be strings
            $stringData = [];
            foreach ($data as $key => $value) {
                $stringData[$key] = (string) $value;
            }

            $payload = [
                'message' => [
                    'topic' => $topic,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $stringData,
                ],
            ];

            $response = Http::withToken($accessToken)->post($url, $payload);

            Log::info('FCM sendToTopic result', [
                'topic' => $topic,
                'payload' => $payload,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                Log::error('FCM sendToTopic failed', [
                    'topic' => $topic,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('FCM sendToTopic exception', [
                'topic' => $topic,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = [],
        bool $isWeb = false,   // 👈 أضفنا هذا
    ): void {
        if (!$this->projectId) {
            Log::error('FCM: projectId مفقود في NotificationService.');
            return;
        }

        try {
            $accessToken = \App\Services\FirebaseAccessTokenService::make();

            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            // ✅ FCM data values must be strings
            $stringData = [];
            foreach ($data as $key => $value) {
                $stringData[$key] = (string) $value;
            }

            // 👇 الرسالة الأساسية (تشتغل للموبايل والويب)
            $message = [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
            ];

            // 👇 لو Web Admin، نضيف webpush config
            if ($isWeb) {
                $message['webpush'] = [
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'icon' => 'https://app.ghasselha.com/favicon.ico',
                    ],
                    'fcm_options' => [
                        // الرابط اللي يفتح لما يضغط على الإشعار
                        'link' => $stringData['url'] ?? config('app.url') . '/dashboard',
                    ],
                ];
            }

            $payload = ['message' => $message];

            $response = Http::withToken($accessToken)->post($url, $payload);

            Log::info('FCM sendToToken result', [
                'token' => $token,
                'is_web' => $isWeb,
                'payload' => $payload,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            if (!$response->successful()) {
                Log::error('FCM sendToToken failed', [
                    'token' => $token,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }

        } catch (\Throwable $e) {
            Log::error('FCM sendToToken exception', [
                'token' => $token,
                'is_web' => $isWeb,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function pushToAdminWebClients(
        string $title,
        string $body,
        array $data = []
    ): void {
        $admins = User::query()
            ->where('user_type', 'admin')
            ->where('is_active', true)
            ->whereNotNull('web_fcm_token')
            ->pluck('web_fcm_token')
            ->filter()
            ->unique()
            ->values();

        foreach ($admins as $token) {
            $this->sendToToken($token, $title, $body, $data, isWeb: true);
        }
    }

    public function notifyAdminsUsingTemplate(
        string $templateKey,
        array $templateData = [],
        array $extraData = [],
        ?int $createdBy = null,
        string $locale = 'ar',
    ): void {
        $template = NotificationTemplate::where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $title = $this->replacePlaceholders(
            $template->getTitleForLocale($locale),
            $templateData
        );
        $body = $this->replacePlaceholders(
            $template->getBodyForLocale($locale),
            $templateData
        );

        $admins = User::query()
            ->where('user_type', 'admin')
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            Notification::create([
                'user_id' => $admin->id,
                'title' => $title,
                'body' => $body,
                'data' => $extraData ?: null,
                'is_read' => false,
                'created_by' => $createdBy,
            ]);
        }

        // 🚀 إرسال Push للـ web admins
        $this->pushToAdminWebClients($title, $body, $extraData);
    }

    /* ============== Helpers ============== */

    protected function replacePlaceholders(string $text, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $text = str_replace('{' . $key . '}', (string) $value, $text);
        }
        return $text;
    }
}