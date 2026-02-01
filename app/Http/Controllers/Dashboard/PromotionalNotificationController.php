<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\PromotionalNotificationStoreRequest;
use App\Http\Requests\PromotionalNotificationUpdateRequest;
use App\Models\PromotionalNotification;
use App\Models\Service;
use App\Models\Package;
use App\Models\Product;
use App\Services\PromotionalNotificationService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class PromotionalNotificationController extends Controller
{
    public function __construct(
        private PromotionalNotificationService $service
    ) {
        $this->middleware('can:promotional_notifications.send');
    }

    /**
     * عرض قائمة الإشعارات
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = PromotionalNotification::query()
                ->with(['creator', 'linkable'])
                ->orderBy('created_at', 'desc');

            // 🔍 البحث بالعنوان
            if ($search = $request->get('search_custom')) {
                $search = trim($search);
                $query->where(function ($q) use ($search) {
                    $q->where('title->ar', 'like', "%{$search}%")
                        ->orWhere('title->en', 'like', "%{$search}%")
                        ->orWhere('body->ar', 'like', "%{$search}%")
                        ->orWhere('body->en', 'like', "%{$search}%");
                });
            }

            // 🔍 تصفية حسب الحالة
            if ($status = $request->get('status')) {
                $query->where('status', $status);
            }

            // 🔍 تصفية حسب نوع الجمهور
            if ($targetType = $request->get('target_type')) {
                $query->where('target_type', $targetType);
            }

            return DataTables::eloquent($query)
                ->editColumn('title', function (PromotionalNotification $row) {
                    $locale = app()->getLocale();
                    $title = $row->title[$locale] ?? reset($row->title ?? []) ?? '';
                    $body = $row->body[$locale] ?? reset($row->body ?? []) ?? '';
                    
                    $html = '<div class="d-flex flex-column">';
                    $html .= '<a href="' . route('dashboard.promotional-notifications.show', $row->id) . '" class="text-gray-800 text-hover-primary fw-bold mb-1">';
                    $html .= e($title);
                    $html .= '</a>';
                    $html .= '<span class="text-muted fw-semibold d-block fs-7">' . e(\Illuminate\Support\Str::limit($body, 50)) . '</span>';
                    $html .= '</div>';
                    
                    return $html;
                })
                ->addColumn('target_type_badge', function (PromotionalNotification $row) {
                    $badges = [
                        'all_users' => 'badge-light-success',
                        'specific_users' => 'badge-light-primary',
                    ];
                    $badge = $badges[$row->target_type] ?? 'badge-light-secondary';
                    
                    $label = __('promotional_notifications.target_types.' . $row->target_type);
                    $html = '<span class="badge ' . $badge . '">' . $label . '</span>';
                    
                    if ($row->target_type === 'specific_users' && $row->target_user_ids) {
                        $count = count($row->target_user_ids);
                        $html .= '<span class="text-muted fs-7 d-block mt-1">(' . $count . ' ' . __('promotional_notifications.users') . ')</span>';
                    }
                    
                    return $html;
                })
                ->addColumn('status_badge', function (PromotionalNotification $row) {
                    $badges = [
                        'draft' => 'badge-light-secondary',
                        'scheduled' => 'badge-light-info',
                        'sending' => 'badge-light-warning',
                        'sent' => 'badge-light-success',
                        'failed' => 'badge-light-danger',
                        'cancelled' => 'badge-light-dark',
                    ];
                    $badge = $badges[$row->status] ?? 'badge-light-secondary';
                    
                    $label = __('promotional_notifications.statuses.' . $row->status);
                    return '<span class="badge ' . $badge . '">' . $label . '</span>';
                })
                ->addColumn('recipients_info', function (PromotionalNotification $row) {
                    if ($row->status === 'sent') {
                        $html = '<div class="d-flex flex-column">';
                        $html .= '<span class="fw-bold">' . number_format($row->total_recipients) . '</span>';
                        $html .= '<span class="text-success fs-7">✓ ' . number_format($row->successful_sends) . '</span>';
                        if ($row->failed_sends > 0) {
                            $html .= '<span class="text-danger fs-7">✗ ' . number_format($row->failed_sends) . '</span>';
                        }
                        $html .= '</div>';
                        return $html;
                    }
                    return '<span class="text-muted">—</span>';
                })
                ->editColumn('scheduled_at', function (PromotionalNotification $row) {
                    if ($row->scheduled_at) {
                        $html = '<span class="fw-semibold">' . $row->scheduled_at->format('Y-m-d') . '</span>';
                        $html .= '<span class="text-muted d-block fs-7">' . $row->scheduled_at->format('H:i') . '</span>';
                        return $html;
                    }
                    return '<span class="text-muted">—</span>';
                })
                ->editColumn('created_at', fn(PromotionalNotification $row) => optional($row->created_at)->format('Y-m-d'))
                ->addColumn('actions', function (PromotionalNotification $row) {
                    return view('dashboard.promotional_notifications._actions', ['notification' => $row])->render();
                })
                ->rawColumns(['title', 'target_type_badge', 'status_badge', 'recipients_info', 'scheduled_at', 'actions'])
                ->make(true);
        }

        view()->share([
            'title' => __('promotional_notifications.title'),
            'page_title' => __('promotional_notifications.list'),
        ]);

        return view('dashboard.promotional_notifications.index');
    }

    /**
     * عرض نموذج الإنشاء
     */
    public function create()
    {
        view()->share([
            'title' => __('promotional_notifications.create'),
            'page_title' => __('promotional_notifications.create'),
        ]);

        // جلب الخدمات والباقات والمنتجات للربط
        $services = Service::where('is_active', true)->get(['id', 'name']);
        $packages = Package::where('is_active', true)->get(['id', 'name']);
        $products = Product::where('is_active', true)->get(['id', 'name']);

        return view('dashboard.promotional_notifications.create', compact(
            'services',
            'packages',
            'products'
        ));
    }

    /**
     * حفظ إشعار جديد
     */
    public function store(PromotionalNotificationStoreRequest $request)
    {
        $data = $request->validated();

        $notification = PromotionalNotification::create([
            'title' => $data['title'],
            'body' => $data['body'],
            'target_type' => $data['target_type'],
            'target_user_ids' => $data['target_type'] === 'specific_users' ? $data['target_user_ids'] : null,
            'linkable_type' => $data['linkable_type'] ?? null,
            'linkable_id' => $data['linkable_id'] ?? null,
            'status' => $data['send_type'] === 'now' ? 'draft' : 'scheduled',
            'scheduled_at' => $data['send_type'] === 'scheduled' ? $data['scheduled_at'] : null,
            'internal_notes' => $data['internal_notes'] ?? null,
        ]);

        // إذا كان الإرسال فورياً
        if ($data['send_type'] === 'now') {
            $result = $this->service->send($notification);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => __('promotional_notifications.messages.sent_successfully'),
                    'redirect' => route('dashboard.promotional-notifications.show', $notification->id),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('promotional_notifications.messages.send_failed') . ': ' . $result['message'],
                ], 500);
            }
        }

        // إذا كان مجدولاً
        return response()->json([
            'success' => true,
            'message' => __('promotional_notifications.messages.scheduled_successfully'),
            'redirect' => route('dashboard.promotional-notifications.show', $notification->id),
        ]);
    }

    /**
     * عرض تفاصيل إشعار
     */
    public function show(PromotionalNotification $promotionalNotification)
    {
        view()->share([
            'title' => __('promotional_notifications.show'),
            'page_title' => __('promotional_notifications.show'),
        ]);

        $promotionalNotification->load(['creator', 'updater', 'linkable']);

        return view('dashboard.promotional_notifications.show', [
            'notification' => $promotionalNotification,
        ]);
    }

    /**
     * عرض نموذج التعديل
     */
    public function edit(PromotionalNotification $promotionalNotification)
    {
        if (!$promotionalNotification->canBeEdited()) {
            return redirect()
                ->route('dashboard.promotional-notifications.show', $promotionalNotification->id)
                ->with('error', __('promotional_notifications.cannot_edit'));
        }

        view()->share([
            'title' => __('promotional_notifications.edit'),
            'page_title' => __('promotional_notifications.edit'),
        ]);

        // جلب الخدمات والباقات والمنتجات للربط
        $services = Service::where('is_active', true)->get(['id', 'name']);
        $packages = Package::where('is_active', true)->get(['id', 'name']);
        $products = Product::where('is_active', true)->get(['id', 'name']);

        return view('dashboard.promotional_notifications.edit', [
            'notification' => $promotionalNotification,
            'services' => $services,
            'packages' => $packages,
            'products' => $products,
        ]);
    }

    /**
     * تحديث إشعار
     */
    public function update(PromotionalNotificationUpdateRequest $request, PromotionalNotification $promotionalNotification)
    {
        $data = $request->validated();

        $promotionalNotification->update([
            'title' => $data['title'],
            'body' => $data['body'],
            'target_type' => $data['target_type'],
            'target_user_ids' => $data['target_type'] === 'specific_users' ? $data['target_user_ids'] : null,
            'linkable_type' => $data['linkable_type'] ?? null,
            'linkable_id' => $data['linkable_id'] ?? null,
            'status' => $data['send_type'] === 'now' ? 'draft' : 'scheduled',
            'scheduled_at' => $data['send_type'] === 'scheduled' ? $data['scheduled_at'] : null,
            'internal_notes' => $data['internal_notes'] ?? null,
        ]);

        // إذا كان الإرسال فورياً
        if ($data['send_type'] === 'now') {
            $result = $this->service->send($promotionalNotification);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => __('promotional_notifications.messages.sent_successfully'),
                    'redirect' => route('dashboard.promotional-notifications.show', $promotionalNotification->id),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => __('promotional_notifications.messages.send_failed') . ': ' . $result['message'],
                ], 500);
            }
        }

        return response()->json([
            'success' => true,
            'message' => __('promotional_notifications.messages.updated_successfully'),
            'redirect' => route('dashboard.promotional-notifications.show', $promotionalNotification->id),
        ]);
    }

    /**
     * حذف إشعار
     */
    public function destroy(PromotionalNotification $promotionalNotification)
    {
        if (!$promotionalNotification->canBeEdited()) {
            return response()->json([
                'success' => false,
                'message' => __('promotional_notifications.messages.cannot_delete'),
            ], 403);
        }

        $promotionalNotification->delete();

        return response()->json([
            'success' => true,
            'message' => __('promotional_notifications.messages.deleted_successfully'),
        ]);
    }

    /**
     * إرسال إشعار يدوياً
     */
    public function send(PromotionalNotification $promotionalNotification)
    {
        if (!$promotionalNotification->canBeSent()) {
            return response()->json([
                'success' => false,
                'message' => __('promotional_notifications.messages.cannot_send'),
            ], 400);
        }

        $result = $this->service->send($promotionalNotification);

        return response()->json($result);
    }

    /**
     * إلغاء إشعار مجدول
     */
    public function cancel(PromotionalNotification $promotionalNotification)
    {
        if (!$promotionalNotification->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => __('promotional_notifications.messages.cannot_cancel'),
            ], 400);
        }

        $promotionalNotification->cancel();

        return response()->json([
            'success' => true,
            'message' => __('promotional_notifications.messages.cancelled_successfully'),
        ]);
    }

    /**
     * معاينة عدد المستلمين (AJAX)
     */
    public function previewRecipients(Request $request)
    {
        $request->validate([
            'target_type' => 'required|in:specific_users,all_users',
            'target_user_ids' => 'required_if:target_type,specific_users|array',
        ]);

        $count = $this->service->previewRecipientsCount(
            $request->target_type,
            $request->target_user_ids
        );

        return response()->json([
            'success' => true,
            'count' => $count,
        ]);
    }
}