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
        array $extraData = [],
        ?string $overrideTitle = null,
        ?string $overrideBody = null,
        ?string $locale = null, // ✅ اختياري الآن
        ?int $createdBy = null,
    ): ?Notification {
        $template = NotificationTemplate::where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return null;
        }

        if ($locale === 'ar' && $user->lang && $user->lang !== 'ar') {
            $locale = null; 
        }

        $userLocale = $locale ?? $user->lang ?? config('app.locale', 'ar');

        $title = $overrideTitle ?: $template->getTitleForLocale($userLocale);
        $body = $overrideBody ?: $template->getBodyForLocale($userLocale);

        $title = $this->replacePlaceholders($title, $templateData);
        $body = $this->replacePlaceholders($body, $templateData);

        // 🎯 الحصول على الأيقونة
        $iconPath = $template->getIconPath();

        // حفظ في جدول notifications
        $notification = Notification::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'icon_path' => $iconPath,
            'data' => $extraData ?: null,
            'is_read' => false,
            'created_by' => $createdBy,
        ]);

        // إرسال عبر topic خاص بالمستخدم
        $topic = 'user_' . $user->id;

        $this->sendToTopic($topic, $title, $body, array_merge($extraData, [
            'notification_id' => $notification->id
        ]));

        return $notification;
    }

    /**
     * إرسال إشعار لعدة مستخدمين (كل واحد بلغته)
     */
    public function sendToMultipleUsersUsingTemplate(
        array $userIds,
        string $templateKey,
        array $templateData = [],
        array $extraData = [],
        ?int $createdBy = null,
    ): array {
        $template = NotificationTemplate::where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return [];
        }

        $users = User::whereIn('id', $userIds)->get();
        $notifications = [];

        foreach ($users as $user) {
            $notification = $this->sendToUserUsingTemplate(
                $user,
                $templateKey,
                $templateData,
                $extraData,
                null,
                null,
                null, // سيستخدم لغة المستخدم تلقائياً
                $createdBy
            );

            if ($notification) {
                $notifications[] = $notification;
            }
        }

        return $notifications;
    }

    /**
     * ✅ إرسال إشعار لـ topic (جماعي) - لكن مع تخزين منفصل لكل مستخدم بلغته
     */
    public function sendToTopicUsingTemplate(
        string $topic,
        string $templateKey,
        array $templateData = [],
        array $extraData = [],
        ?string $overrideTitle = null,
        ?string $overrideBody = null,
        ?string $locale = null, // ✅ للـ push notification الجماعي فقط
        ?int $createdBy = null,
        ?array $usersForStorage = null
    ): void {
        $template = NotificationTemplate::where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        // ✅ للـ push notification الجماعي، نستخدم locale افتراضي
        $pushLocale = $locale ?? config('app.locale', 'ar');

        $title = $overrideTitle ?: $template->getTitleForLocale($pushLocale);
        $body = $overrideBody ?: $template->getBodyForLocale($pushLocale);

        $title = $this->replacePlaceholders($title, $templateData);
        $body = $this->replacePlaceholders($body, $templateData);

        $iconPath = $template->getIconPath();

        // إرسال مرة واحدة للـ topic
        $this->sendToTopic($topic, $title, $body, array_merge($extraData, []));

        // ✅ تخزين في DB لكل user بلغته الخاصة
        if ($usersForStorage) {
            $users = User::whereIn('id', $usersForStorage)->get();

            foreach ($users as $user) {
                $userLocale = $user->lang ?? config('app.locale', 'ar');

                $userTitle = $overrideTitle ?: $template->getTitleForLocale($userLocale);
                $userBody = $overrideBody ?: $template->getBodyForLocale($userLocale);

                $userTitle = $this->replacePlaceholders($userTitle, $templateData);
                $userBody = $this->replacePlaceholders($userBody, $templateData);

                Notification::create([
                    'user_id' => $user->id,
                    'title' => $userTitle,
                    'body' => $userBody,
                    'icon_path' => $iconPath,
                    'data' => $extraData ?: null,
                    'is_read' => false,
                    'created_by' => $createdBy,
                ]);
            }
        }
    }

    /**
     * ✅ إشعار للـ Admins (كل واحد بلغته)
     */
    public function notifyAdminsUsingTemplate(
        string $templateKey,
        array $templateData = [],
        array $extraData = [],
        ?int $createdBy = null,
    ): void {
        $template = NotificationTemplate::where('key', $templateKey)
            ->where('is_active', true)
            ->first();

        if (!$template) {
            return;
        }

        $iconPath = $template->getIconPath();
        $iconUrl = $iconPath ? asset('storage/' . $iconPath) : null;

        $admins = User::query()
            ->where('user_type', 'admin')
            ->where('is_active', true)
            ->get();

        foreach ($admins as $admin) {
            // ✅ استخدام لغة كل admin
            $adminLocale = $admin->lang ?? config('app.locale', 'ar');

            $title = $this->replacePlaceholders(
                $template->getTitleForLocale($adminLocale),
                $templateData
            );
            $body = $this->replacePlaceholders(
                $template->getBodyForLocale($adminLocale),
                $templateData
            );

            // حفظ في DB
            Notification::create([
                'user_id' => $admin->id,
                'title' => $title,
                'body' => $body,
                'icon_path' => $iconPath,
                'data' => $extraData ?: null,
                'is_read' => false,
                'created_by' => $createdBy,
            ]);

            // ✅ إرسال Push لكل admin بلغته (إذا كان عنده web_fcm_token)
            if ($admin->web_fcm_token) {
                $this->sendToToken(
                    $admin->web_fcm_token,
                    $title,
                    $body,
                    array_merge($extraData, ['icon' => $iconUrl]),
                    isWeb: true
                );
            }
        }
    }

    /* ============== إرسال فعلي إلى FCM (v1) ============== */

    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = [],
        bool $isWeb = false,
    ): void {
        if (!$this->projectId) {
            Log::error('FCM: projectId مفقود في NotificationService.');
            return;
        }

        try {
            $accessToken = \App\Services\FirebaseAccessTokenService::make();

            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $stringData = [];
            foreach ($data as $key => $value) {
                $stringData[$key] = (string) $value;
            }

            $message = [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => $stringData,
            ];

            // 👇 لو Web Admin، نضيف webpush config مع الأيقونة
            if ($isWeb) {
                $message['webpush'] = [
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                        'icon' => $data['icon'] ?? 'https://glimz.com/favicon.ico',
                    ],
                    'fcm_options' => [
                        'link' => $stringData['url'] ?? config('app.url') . '/dashboard',
                    ],
                ];
            }

            $payload = ['message' => $message];

            $response = Http::withToken($accessToken)->post($url, $payload);

            Log::info('FCM sendToToken result', [
                'token' => $token,
                'is_web' => $isWeb,
                'status' => $response->status(),
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
            ]);
        }
    }

    public function sendToTopic(string $topic, string $title, string $body, array $data = []): void
    {
        if (!$this->projectId) {
            Log::error('FCM: projectId مفقود في NotificationService.');
            return;
        }

        try {
            $accessToken = \App\Services\FirebaseAccessTokenService::make();

            $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

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
                'status' => $response->status(),
                'data' => $payload,
                'user' => User::find(explode('_', $topic)[1]),
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

    /* ============== Helpers ============== */

    protected function replacePlaceholders(string $text, array $vars): string
    {
        foreach ($vars as $key => $value) {
            $text = str_replace('{' . $key . '}', (string) $value, $text);
        }
        return $text;
    }
}