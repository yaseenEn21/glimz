<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class NotificationController extends Controller
{

    public function __construct()
    {
        // 🔐 Permissions middleware
        $this->middleware('can:notifications.view')->only(['index', 'show']);
        $this->middleware('can:notifications.create')->only(['create', 'store']);
        $this->middleware('can:notifications.edit')->only(['edit', 'update']);
        $this->middleware('can:notifications.delete')->only(['destroy']);
    }
    
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $user = $request->user();

            $q = Notification::where('user_id', $user->id)
                ->orderByDesc('created_at');

            return DataTables::of($q)
                ->addColumn('created_at_formatted', fn($row) => $row->created_at?->format('Y-m-d H:i'))

                ->addColumn('is_read_badge', function ($row) {
                    // نضيف data-notification-status عشان JS يقدر يحدّثه
                    if ($row->is_read) {
                        return '<span class="badge bg-light text-muted" data-notification-status>
                                مقروء
                            </span>';
                    }

                    return '<span class="badge bg-warning" data-notification-status>
                            غير مقروء
                        </span>';
                })

                ->addColumn('actions', function ($row) {
                    // رابط العرض (لو عندك URL مخزّن في data)
                    $showUrl = $row->data['url'] ?? '#';

                    $html = null;

                    if ($showUrl !== '#') {
                        $html .= '<a href="' . $showUrl . '" class="btn btn-sm btn-light-primary me-2">عرض</a>';
                    }

                    // لو الإشعار لسه غير مقروء نضيف زر "تعيين كمقروء"
                    if (!$row->is_read) {
                        $markUrl = route('dashboard.notifications.mark-read', $row->id);

                        $html .= '<button type="button"
                                   class="btn btn-sm btn-light-success btn-mark-read"
                                   data-id="' . $row->id . '"
                                   data-mark-url="' . $markUrl . '">
                                   تعيين كمقروء
                              </button>';
                    }

                    return $html;
                })

                ->rawColumns(['is_read_badge', 'actions'])
                ->make(true);
        }

        return view('dashboard.notifications.index', [
            'title' => 'إشعارات النظام',
        ]);
    }

    public function markAsRead(Notification $notification)
    {
        $user = auth()->user();

        // نتأكد إن الإشعار تابع للمستخدم الحالي (الأدمن)
        // if ($notification->user_id !== $user->id) {
        //     abort(403);
        // }

        if (!$notification->is_read) {
            $notification->update([
                'is_read' => true,
                'updated_by' => $user->id,
            ]);
        }

        // نرجّع عدد الغير مقروء بعد التحديث
        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين الإشعار كمقروء.',
            'data' => [
                'unread_count' => $unreadCount,
            ],
        ]);
    }

}