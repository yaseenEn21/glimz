<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\NotificationResource;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * إرجاع عدد الإشعارات غير المقروءة للمستخدم الحالي
     * GET /api/v1/notifications/unread-count
     */
    public function unreadCount(Request $request)
    {
        $user = $request->user();

        $count = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $count,
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = (int) $request->input('per_page', 20);

        // 1) نجلب إشعارات المستخدم
        $paginator = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        // 2) نحدد الإشعارات غير المقروءة في هذه الصفحة (هذي راح تصير جديدة)
        $idsToMark = $paginator->getCollection()
            ->where('is_read', false)
            ->pluck('id')
            ->all();

        // 3) نحدد الإشعارات المقروءة سابقاً (هذي نخليها مش جديدة)
        $alreadyReadIds = $paginator->getCollection()
            ->where('is_read', true)
            ->where('is_new', true)
            ->pluck('id')
            ->all();

        // 4) تحديث الإشعارات الجديدة اللي للتو قريناها
        if (!empty($idsToMark)) {
            Notification::whereIn('id', $idsToMark)
                ->update([
                    'is_read' => true,
                    'updated_by' => $user->id,
                    'updated_at' => now(),
                    // is_new يبقى true (القيمة الافتراضية من الـ migration)
                ]);

            // تحديث الكولكشن في الذاكرة
            $paginator->setCollection(
                $paginator->getCollection()->map(function (Notification $n) use ($idsToMark) {
                    if (in_array($n->id, $idsToMark)) {
                        $n->is_read = true;
                        // is_new يبقى true (من الـ database)
                    }
                    return $n;
                })
            );
        }

        // 5) تحديث الإشعارات المقروءة من قبل لتصبح مش جديدة
        if (!empty($alreadyReadIds)) {
            Notification::whereIn('id', $alreadyReadIds)
                ->update(['is_new' => false]);

            // تحديث الكولكشن في الذاكرة
            $paginator->setCollection(
                $paginator->getCollection()->map(function (Notification $n) use ($alreadyReadIds) {
                    if (in_array($n->id, $alreadyReadIds)) {
                        $n->is_new = false;
                    }
                    return $n;
                })
            );
        }

        // 6) حساب عدد غير المقروء
        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        // 7) استخدام Resource
        $notifications = NotificationResource::collection($paginator);
        $payload = $notifications->response()->getData(true);
        $payload['unread_count'] = $unreadCount;

        return api_success($payload, 'قائمة الإشعارات');
    }

}