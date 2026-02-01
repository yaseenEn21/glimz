<?php

namespace App\Console\Commands;

use App\Models\PromotionalNotification;
use App\Services\PromotionalNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendScheduledPromotionalNotifications extends Command
{
    protected $signature = 'promotional-notifications:send-scheduled';

    protected $description = 'Send scheduled promotional notifications that are due';

    public function handle(PromotionalNotificationService $service): int
    {
        $this->info('🔍 Checking for scheduled promotional notifications...');

        // جلب الإشعارات المجدولة التي حان وقتها
        $notifications = PromotionalNotification::scheduled()->get();

        if ($notifications->isEmpty()) {
            $this->info('✅ No scheduled notifications found.');
            return self::SUCCESS;
        }

        $this->info("📤 Found {$notifications->count()} notification(s) to send.");

        foreach ($notifications as $notification) {
            $this->info("📨 Sending notification ID: {$notification->id}");

            Log::info('📨 Sending scheduled promotional notification', [
                'notification_id' => $notification->id,
                'scheduled_at' => $notification->scheduled_at,
            ]);

            $result = $service->send($notification);

            if ($result['success']) {
                $this->info("✅ Notification ID {$notification->id} sent successfully.");
                $this->info("   📊 Total: {$result['data']['total_recipients']}, Success: {$result['data']['successful_sends']}, Failed: {$result['data']['failed_sends']}");
            } else {
                $this->error("❌ Failed to send notification ID {$notification->id}: {$result['message']}");
            }
        }

        $this->info('✅ Scheduled notifications processing completed.');

        return self::SUCCESS;
    }
}