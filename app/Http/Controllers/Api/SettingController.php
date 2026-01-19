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


}