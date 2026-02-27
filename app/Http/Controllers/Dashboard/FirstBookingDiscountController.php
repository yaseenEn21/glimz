<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FirstBookingDiscountController extends Controller
{
    private function getConfig(): array
    {
        $raw = DB::table('settings')
            ->where('key', 'first_booking_discount')
            ->value('value');

        $config = json_decode($raw ?? '{}', true) ?? [];

        return array_merge([
            'is_active'              => false,
            'discount_type'          => 'percentage',
            'discount_value'         => 0,
            'applies_to_service_ids' => [],
        ], $config);
    }

    public function edit()
    {
        $config = $this->getConfig();

        $services = Service::query()
            ->select('id', 'name')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        view()->share([
            'title'      => app()->getLocale() === 'ar' ? 'خصم أول حجز' : 'First Booking Discount',
            'page_title' => app()->getLocale() === 'ar' ? 'خصم أول حجز' : 'First Booking Discount',
        ]);

        return view('dashboard.settings.first-booking-discount', compact('config', 'services'));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'discount_type'           => ['required', 'in:percentage,fixed,special_price'],
            'discount_value'          => ['required', 'numeric', 'min:0'],
            'is_active'               => ['nullable', 'boolean'],
            'applies_to_service_ids'  => ['nullable', 'array'],
            'applies_to_service_ids.*'=> ['integer', 'exists:services,id'],
        ]);

        // تحقق من قيم منطقية
        if ($data['discount_type'] === 'percentage' && (float)$data['discount_value'] > 100) {
            return back()->withErrors(['discount_value' => 'النسبة لا يمكن أن تتجاوز 100%'])->withInput();
        }

        $config = [
            'is_active'              => $request->boolean('is_active'),
            'discount_type'          => $data['discount_type'],
            'discount_value'         => (float) $data['discount_value'],
            'applies_to_service_ids' => $data['applies_to_service_ids'] ?? [],
        ];

        DB::table('settings')->updateOrInsert(
            ['key' => 'first_booking_discount'],
            [
                'value'      => json_encode($config),
                'type'       => 'json',
                'label'      => 'خصم أول حجز',
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return redirect()
            ->route('dashboard.settings.first-booking-discount.edit')
            ->with('success', app()->getLocale() === 'ar' ? 'تم حفظ الإعدادات بنجاح.' : 'Settings saved successfully.');
    }
}