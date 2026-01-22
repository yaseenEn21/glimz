<?php

namespace App\Http\Controllers\Api;

use App;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use App\Models\Faq;
use App\Models\SchoolEvent;
use App\Models\Announcement;
use App\Models\StudentUpdate;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class SettingController extends Controller
{

    public function policies()
    {
        $value = Setting::where('key', 'policies_and_terms')->value('value');

        return api_success($value, 'policies html loaded successfully');
    }

    public function aboutUs()
    {
        $value = Setting::where('key', 'about_us')->value('value');

        return api_success($value, 'about us html loaded successfully');
    }

    public function cancellationAndRefund()
    {
        $value = Setting::where('key', 'cancellation_and_refund')->value('value');

        return api_success($value, 'cancellation and refund html loaded successfully');
    }

    public function bookingCancelReasons()
    {
        $raw = DB::table('settings')
            ->where('key', 'bookings.cancel_reasons')
            ->value('value');

        $items = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $items = $decoded;
            }
        }

        $locale = app()->getLocale() ?: 'en';
        if (!in_array($locale, ['ar', 'en']))
            $locale = 'en';

        $out = collect($items)->map(function ($r) use ($locale) {
            $name = $r['name'] ?? [];
            $label = is_array($name)
                ? ($name[$locale] ?? $name['en'] ?? $name['ar'] ?? '')
                : (string) $name;

            return (string) $label;
        })->filter(fn($label) => $label !== '')->values();

        return api_success($out, 'OK');
    }

    public function faqs(Request $request)
    {

        $faqs = Faq::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $data = $faqs->map(function (Faq $faq) {
            return [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
            ];
        })->values();

        return api_success($data, 'FAQs loaded successfully');
    }

    public function contactInfo(Request $request)
    {
        $keys = ['contact_whatsapp', 'contact_phone', 'contact_email'];

        $settings = Setting::whereIn('key', $keys)->pluck('value', 'key');

        $data = [
            [
                'type' => 'phone',
                'label' => 'الاتصال بالدعم',
                'value' => $settings['contact_phone'] ?? null,
                'icon' => asset('assets/media/svg/social-logos/phone.png'),
            ],
            [
                'type' => 'whatsapp',
                'label' => 'تواصل عبر واتساب',
                'value' => $settings['contact_whatsapp'] ?? null,
                'icon' => asset('assets/media/svg/social-logos/whatsapp.png'),
            ],
            [
                'type' => 'email',
                'label' => 'بريد إلكتروني',
                'value' => $settings['contact_email'] ?? null,
                'icon' => asset('assets/media/svg/social-logos/email.png'),
            ],
        ];

        return api_success($data, 'Contact info loaded successfully');
    }


}