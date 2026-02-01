<?php

namespace App\Services;

use App\Models\PromotionalNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PromotionalNotificationService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * إرسال الإشعار الترويجي
     */
    public function send(PromotionalNotification $notification): array
    {
        if (!$notification->canBeSent()) {
            return [
                'success' => false,
                'message' => 'Cannot send notification in current status: ' . $notification->status,
            ];
        }

        try {
            DB::beginTransaction();

            Log::info('🚀 Starting promotional notification send', [
                'notification_id' => $notification->id,
                'target_type' => $notification->target_type,
            ]);

            // تحديث الحالة إلى sending
            $notification->update(['status' => 'sending']);

            // إعداد البيانات للإرسال
            $extraData = [];

            // إضافة معلومات الربط إذا كانت موجودة
            if ($notification->linkable_type && $notification->linkable_id) {
                $extraData['linkable_type'] = $notification->linkable_type;
                $extraData['linkable_id'] = (string) $notification->linkable_id;
            }

            // إضافة معرف الإشعار الترويجي
            $extraData['promotional_notification_id'] = (string) $notification->id;
            $extraData['notification_type'] = 'promotional';

            $successCount = 0;
            $failCount = 0;
            $totalRecipients = 0;

            // 🔥 حالة 1: إرسال لجميع المستخدمين (على topic واحد)
            if ($notification->target_type === 'all_users') {
                
                Log::info('📤 Sending to all users via promo topic');

                // عدد المستخدمين النشطين
                $totalRecipients = User::where('user_type', 'customer')
                    ->where('is_active', true)
                    ->count();

                try {
                    // إرسال عبر topic واحد للجميع
                    $this->notificationService->sendToTopic(
                        'promo', // 👈 topic واحد لجميع المستخدمين
                        $notification->getTitleForLocale('ar'),
                        $notification->getBodyForLocale('ar'),
                        $extraData
                    );

                    $successCount = $totalRecipients;

                    Log::info('✅ Sent to promo topic successfully', [
                        'total_recipients' => $totalRecipients,
                    ]);

                } catch (\Throwable $e) {
                    $failCount = $totalRecipients;
                    
                    Log::error('❌ Failed to send to promo topic', [
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            // 🔥 حالة 2: إرسال لمستخدمين محددين (loop على كل واحد)
            else {
                $users = $notification->getTargetUsers();
                $totalRecipients = $users->count();

                Log::info('📊 Target users loaded', [
                    'total_recipients' => $totalRecipients,
                ]);

                // إرسال لكل مستخدم على حدة
                foreach ($users as $user) {
                    try {
                        $locale = $user->locale ?? 'ar';

                        $title = $notification->getTitleForLocale($locale);
                        $body = $notification->getBodyForLocale($locale);

                        // إرسال عبر topic خاص بالمستخدم
                        $topic = 'user_' . $user->id;

                        $this->notificationService->sendToTopic(
                            $topic,
                            $title,
                            $body,
                            $extraData
                        );

                        // حفظ في جدول notifications للتطبيق
                        \App\Models\Notification::create([
                            'user_id' => $user->id,
                            'title' => $title,
                            'body' => $body,
                            'data' => $extraData,
                            'is_read' => false,
                            'created_by' => $notification->created_by,
                        ]);

                        $successCount++;

                        Log::info('✅ Notification sent to user', [
                            'user_id' => $user->id,
                            'locale' => $locale,
                        ]);

                    } catch (\Throwable $e) {
                        $failCount++;

                        Log::error('❌ Failed to send to user', [
                            'user_id' => $user->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // تحديث الإحصائيات
            $notification->update([
                'status' => 'sent',
                'sent_at' => now(),
                'total_recipients' => $totalRecipients,
                'successful_sends' => $successCount,
                'failed_sends' => $failCount,
            ]);

            DB::commit();

            Log::info('✅ Promotional notification send completed', [
                'notification_id' => $notification->id,
                'total' => $totalRecipients,
                'success' => $successCount,
                'failed' => $failCount,
            ]);

            return [
                'success' => true,
                'message' => 'Notification sent successfully',
                'data' => [
                    'total_recipients' => $totalRecipients,
                    'successful_sends' => $successCount,
                    'failed_sends' => $failCount,
                ],
            ];

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('❌ Promotional notification send failed', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // تحديث الحالة إلى failed
            $notification->update(['status' => 'failed']);

            return [
                'success' => false,
                'message' => 'Failed to send notification: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * معاينة عدد المستلمين قبل الإرسال
     */
    public function previewRecipientsCount(string $targetType, ?array $targetUserIds = null): int
    {
        if ($targetType === 'all_users') {
            return User::where('user_type', 'customer')
                ->where('is_active', true)
                ->count();
        }

        if ($targetType === 'specific_users' && $targetUserIds) {
            return User::whereIn('id', $targetUserIds)
                ->where('is_active', true)
                ->count();
        }

        return 0;
    }

    /**
     * جلب قائمة المستخدمين للاختيار (للـ Select2)
     */
    public function searchUsers(string $query = '', int $limit = 20): array
    {
        $users = User::where('user_type', 'customer')
            ->where('is_active', true)
            ->when($query, function ($q) use ($query) {
                $q->where(function ($q2) use ($query) {
                    $q2->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%")
                        ->orWhere('mobile', 'like', "%{$query}%");
                });
            })
            ->limit($limit)
            ->get(['id', 'name', 'email', 'mobile']);

        return [
            'results' => $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'text' => $user->name . ' (' . ($user->mobile ?? $user->email) . ')',
                ];
            }),
        ];
    }
}