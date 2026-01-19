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

    /**
     * إرجاع قائمة الإشعارات للمستخدم الحالي + تعليمها كمقروء
     * GET /api/v1/notifications?page=1
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $perPage = (int) $request->input('per_page', 20);

        // 1) نجلب إشعارات المستخدم (أحدث أولاً)
        $paginator = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);

        // 2) نحدد الإشعارات في هذه الصفحة فقط التي ما زالت غير مقروءة
        $idsToMark = $paginator->getCollection()
            ->where('is_read', false)
            ->pluck('id')
            ->all();

        if (!empty($idsToMark)) {
            Notification::whereIn('id', $idsToMark)
                ->update([
                    'is_read' => true,
                    'updated_by' => $user->id,
                    'updated_at' => now(),
                ]);

            // نحدّث الكولكشن في الذاكرة عشان ترجع is_read = true في الـ API
            $paginator->setCollection(
                $paginator->getCollection()->map(function (Notification $n) use ($idsToMark) {
                    if (in_array($n->id, $idsToMark)) {
                        $n->is_read = true;
                    }
                    return $n;
                })
            );
        }

        // 3) حساب عدد غير المقروء بعد التحديث
        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        // 4) تبسيط شكل البيانات داخل الـ paginator (زي posts)
        $paginator->getCollection()->transform(function (Notification $n) {
            return [
                'id' => $n->id,
                'title' => $n->title,
                'body' => $n->body,
                'data' => $n->data ?: (object) [],
                'is_read' => (bool) $n->is_read,
                'created_at' => $n->created_at?->toDateTimeString(),
            ];
        });

        // 5) نحول الـ paginator لمصفوفة ونضيف لها unread_count
        $payload = $paginator->toArray();
        $payload['unread_count'] = $unreadCount;

        return api_success($payload, 'قائمة الإشعارات');
    }

}