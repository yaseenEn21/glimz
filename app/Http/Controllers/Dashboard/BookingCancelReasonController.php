<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;

class BookingCancelReasonController extends Controller
{
    private string $settingKey = 'bookings.cancel_reasons';

    public function __construct()
    {
        $this->middleware('can:cancel_reasons.view')->only(['index', 'datatable']);
        $this->middleware('can:cancel_reasons.create')->only(['create', 'store']);
        $this->middleware('can:cancel_reasons.edit')->only(['edit', 'update']);
        $this->middleware('can:cancel_reasons.delete')->only(['destroy']);
    }

    public function index()
    {
        view()->share([
            'title' => __('bookings.cancel_reasons.title'),
            'page_title' => __('bookings.cancel_reasons.title'),
        ]);

        return view('dashboard.bookings.cancel_reasons.index');
    }

    public function datatable(DataTables $datatable)
    {
        $rows = collect($this->getReasons())
            ->sortBy(fn($r) => (int)($r['sort'] ?? 0))
            ->values()
            ->map(function ($r) {
                return [
                    'id' => (string)($r['id'] ?? ''),
                    'code' => (string)($r['code'] ?? ''),
                    'name_ar' => (string)($r['name']['ar'] ?? ''),
                    'name_en' => (string)($r['name']['en'] ?? ''),
                    'is_active' => (bool)($r['is_active'] ?? true),
                    'sort' => (int)($r['sort'] ?? 0),
                ];
            });

        return $datatable->collection($rows)
            ->addColumn('actions', function ($row) {
                return view('dashboard.bookings.cancel_reasons.partials.actions', compact('row'))->render();
            })
            ->rawColumns(['actions'])
            ->make(true);
    }

    public function create()
    {
        view()->share([
            'title' => __('bookings.cancel_reasons.create'),
            'page_title' => __('bookings.cancel_reasons.create'),
        ]);

        return view('dashboard.bookings.cancel_reasons.create');
    }

    public function store(Request $request)
    {
        $data = $this->validatePayload($request);

        $reasons = $this->getReasons();

        // code unique (case-insensitive)
        $code = strtoupper(trim((string)($data['code'] ?? '')));
        if ($code !== '') {
            $exists = collect($reasons)->first(fn($r) => strtoupper((string)($r['code'] ?? '')) === $code);
            if ($exists) {
                throw ValidationException::withMessages([
                    'code' => [__('bookings.cancel_reasons.code_taken')],
                ]);
            }
        }

        $reasons[] = [
            'id' => (string) Str::uuid(),
            'code' => $code,
            'name' => [
                'ar' => (string) $data['name_ar'],
                'en' => (string) $data['name_en'],
            ],
            'is_active' => (bool)($data['is_active'] ?? true),
            'sort' => (int)($data['sort'] ?? 0),
        ];

        $this->saveReasons($reasons);

        return response()->json([
            'ok' => true,
            'message' => __('bookings.cancel_reasons.created_successfully'),
            'redirect' => route('dashboard.bookings.cancel-reasons.index'),
        ]);
    }

    public function edit(string $id)
    {
        $reason = collect($this->getReasons())->firstWhere('id', $id);
        abort_if(!$reason, 404);

        view()->share([
            'title' => __('bookings.cancel_reasons.edit'),
            'page_title' => __('bookings.cancel_reasons.edit'),
        ]);

        return view('dashboard.bookings.cancel_reasons.edit', compact('reason'));
    }

    public function update(Request $request, string $id)
    {
        $data = $this->validatePayload($request);

        $reasons = $this->getReasons();
        $idx = collect($reasons)->search(fn($r) => (string)($r['id'] ?? '') === (string)$id);

        if ($idx === false) {
            return response()->json([
                'ok' => false,
                'message' => __('bookings.cancel_reasons.not_found'),
            ], 404);
        }

        $code = strtoupper(trim((string)($data['code'] ?? '')));

        if ($code !== '') {
            $exists = collect($reasons)->first(function ($r) use ($code, $id) {
                return strtoupper((string)($r['code'] ?? '')) === $code
                    && (string)($r['id'] ?? '') !== (string)$id;
            });

            if ($exists) {
                throw ValidationException::withMessages([
                    'code' => [__('bookings.cancel_reasons.code_taken')],
                ]);
            }
        }

        $current = (array) $reasons[$idx];

        $reasons[$idx] = array_merge($current, [
            'code' => $code,
            'name' => [
                'ar' => (string) $data['name_ar'],
                'en' => (string) $data['name_en'],
            ],
            'is_active' => (bool)($data['is_active'] ?? true),
            'sort' => (int)($data['sort'] ?? 0),
        ]);

        $this->saveReasons($reasons);

        return response()->json([
            'ok' => true,
            'message' => __('bookings.cancel_reasons.updated_successfully'),
            'redirect' => route('dashboard.bookings.cancel-reasons.index'),
        ]);
    }

    public function destroy(string $id)
    {
        $reasons = $this->getReasons();
        $before = count($reasons);

        $reasons = collect($reasons)
            ->reject(fn($r) => (string)($r['id'] ?? '') === (string)$id)
            ->values()
            ->all();

        if (count($reasons) === $before) {
            return response()->json([
                'ok' => false,
                'message' => __('bookings.cancel_reasons.not_found'),
            ], 404);
        }

        $this->saveReasons($reasons);

        return response()->json([
            'ok' => true,
            'message' => __('bookings.cancel_reasons.deleted_successfully'),
        ]);
    }

    // ==========================
    // Helpers
    // ==========================
    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'code' => ['nullable', 'string', 'max:50'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:999999'],
        ]);
    }

    private function getReasons(): array
    {
        $raw = DB::table('settings')->where('key', $this->settingKey)->value('value');

        $items = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) $items = $decoded;
        }

        // normalize (ensure id/name structure)
        $items = collect($items)->map(function ($r) {
            $r = is_array($r) ? $r : [];

            if (empty($r['id'])) $r['id'] = (string) Str::uuid();

            $r['code'] = isset($r['code']) ? strtoupper(trim((string)$r['code'])) : '';

            $name = $r['name'] ?? [];
            if (!is_array($name)) $name = ['ar' => (string)$name, 'en' => (string)$name];

            $r['name'] = [
                'ar' => (string)($name['ar'] ?? ''),
                'en' => (string)($name['en'] ?? ''),
            ];

            $r['is_active'] = (bool)($r['is_active'] ?? true);
            $r['sort'] = (int)($r['sort'] ?? 0);

            return $r;
        })->values()->all();

        // ✅ optional: save back after normalize (مرة واحدة)
        $this->saveReasons($items);

        return $items;
    }

    private function saveReasons(array $items): void
    {
        DB::transaction(function () use ($items) {
            $row = DB::table('settings')
                ->where('key', $this->settingKey)
                ->lockForUpdate()
                ->first();

            $payload = json_encode(array_values($items), JSON_UNESCAPED_UNICODE);

            if ($row) {
                DB::table('settings')->where('key', $this->settingKey)->update([
                    'value' => $payload,
                    'updated_at' => now(),
                ]);
            } else {
                DB::table('settings')->insert([
                    'key' => $this->settingKey,
                    'value' => $payload,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }
}