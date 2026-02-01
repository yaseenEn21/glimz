<?php

namespace App\Http\Controllers\Api;

use App;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Setting;
use App\Models\FAQ;
use App\Models\SchoolEvent;
use App\Models\Announcement;
use App\Models\StudentUpdate;
use App\Models\Post;
use App\Services\AppTranslationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Models\CarouselItem;
use App\Http\Resources\Api\CarouselItemResource;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Traits\ParsesTranslationFiles;

class SettingController extends Controller
{
    use ParsesTranslationFiles;

    public function __construct(private ?AppTranslationService $translationService)
    {
    }

    public function getSettings(Request $request)
    {
        $locale = $this->normalizeLocale($request->input('locale', app()->getLocale()));

        // Set app locale
        app()->setLocale($locale);

        // 🎯 Build settings response
        $settings = [
            'locale' => $locale,
            'translations' => $this->getTranslations($locale),
            'carousel' => $this->getCarousel(),
            'popup' => $this->getPoup(),
            // 'app_config' => $this->getAppConfig(),
            // 'meta' => $this->getMeta(),
        ];

        // 🔐 If user is authenticated, add user-specific settings
        if ($user = auth('sanctum')->user()) {
            $settings['user'] = [
                'locale' => $user->locale ?? $locale,
                'timezone' => $user->timezone ?? config('app.timezone'),
                'currency' => $user->currency ?? config('app.currency', 'SAR'),
            ];
        }

        return api_success($settings, 'Settings retrieved successfully');
    }

    private function getTranslations(string $locale): array
    {
        // ❌ نلغي الـ cache مؤقتاً عشان نشوف المشكلة
        // $cacheKey = "translations.{$locale}";
        // return Cache::remember($cacheKey, now()->addHours(24), function () use ($locale) {

        \Log::info('🔍 getTranslations started', ['locale' => $locale]);

        if (!$this->translationService) {
            \Log::error('❌ translationService is NULL!');
            return [];
        }

        \Log::info('✅ translationService exists');

        $raw = $this->translationService->readRaw($locale);
        \Log::info('📄 Raw content length', ['length' => strlen($raw)]);

        if ($raw === '') {
            \Log::error('❌ Raw content is empty!');
            return [];
        }

        $arr = $this->parsePhpLangArray($raw);
        \Log::info('📊 Parsed array', ['is_array' => is_array($arr), 'count' => is_array($arr) ? count($arr) : 0]);

        if (!is_array($arr)) {
            \Log::error('❌ parsePhpLangArray failed!');
            return [];
        }

        \Log::info('✅ Returning translations', ['count' => count($arr)]);
        return $arr;

        // });
    }

    /**
     * جلب عناصر الـ Carousel
     */
    private function getCarousel(): array
    {
        $cacheKey = 'carousel.slider.active';

        return Cache::remember($cacheKey, now()->addMinutes(30), function () {
            $items = CarouselItem::query()
                ->select([
                    'id',
                    'label',
                    'title',
                    'description',
                    'hint',
                    'cta',
                    'carouselable_type',
                    'carouselable_id',
                    'sort_order',
                    'is_active',
                    'starts_at',
                    'ends_at',
                    'display_type',
                ])
                ->where('display_type', 'slider')
                ->orWhere('display_type', 'both')
                ->active()
                ->with('media')
                ->orderBy('sort_order')
                ->get();

            return [
                'items' => CarouselItemResource::collection($items)->resolve(),
                'count' => $items->count(),
            ];
        });
    }

    private function getPoup(): array
    {
        $cacheKey = 'carousel.popup.active';

        return Cache::remember($cacheKey, now()->addMinutes(30), function () {
            $items = CarouselItem::query()
                ->select([
                    'id',
                    'label',
                    'title',
                    'description',
                    'hint',
                    'cta',
                    'carouselable_type',
                    'carouselable_id',
                    'sort_order',
                    'is_active',
                    'starts_at',
                    'ends_at',
                    'display_type',
                ])
                ->where('display_type', 'popup')
                ->orWhere('display_type', 'both')
                ->active()
                ->with('media')
                ->orderBy('sort_order')
                ->get();

            return [
                'items' => CarouselItemResource::collection($items)->resolve(),
                'count' => $items->count(),
            ];
        });
    }

    /**
     * إعدادات التطبيق العامة
     */
    private function getAppConfig(): array
    {
        return Cache::remember('app.config', now()->addHours(24), function () {
            // 🔧 جلب الإعدادات من جدول settings
            $settingKeys = [
                'contact_phone',
                'contact_email',
                'contact_address',
                'contact_whatsapp',
                'whatsapp_number',
                'social_facebook',
                'social_twitter',
                'social_instagram',
                'social_linkedin',
                'min_booking_hours',
                'max_booking_days',
                'cancellation_hours',
            ];

            $settings = Setting::whereIn('key', $settingKeys)->pluck('value', 'key')->toArray();

            // Helper function محلية لجلب القيم
            $getSetting = function ($key, $default = null) use ($settings) {
                return $settings[$key] ?? $default;
            };

            return [
                'name' => config('app.name'),
                'currency' => config('app.currency', 'SAR'),
                'timezone' => config('app.timezone'),
                'date_format' => config('app.date_format', 'Y-m-d'),
                'time_format' => config('app.time_format', 'H:i'),
                'available_locales' => config('app.available_locales', ['ar', 'en']),
                'default_locale' => config('app.locale', 'ar'),

                // إعدادات الدفع
                'payment' => [
                    'currency' => config('services.moyasar.currency', 'SAR'),
                    'methods' => config('services.moyasar.methods', ['creditcard', 'applepay', 'stcpay']),
                ],

                // معلومات الاتصال
                'contact' => [
                    'phone' => $getSetting('contact_phone'),
                    'email' => $getSetting('contact_email'),
                    'address' => $getSetting('contact_address'),
                    'whatsapp' => $getSetting('contact_whatsapp') ?? $getSetting('whatsapp_number'),
                ],

                // روابط السوشيال ميديا
                'social' => [
                    'facebook' => $getSetting('social_facebook'),
                    'twitter' => $getSetting('social_twitter'),
                    'instagram' => $getSetting('social_instagram'),
                    'linkedin' => $getSetting('social_linkedin'),
                ],

                // إعدادات الخدمة
                'service' => [
                    'min_booking_hours' => (int) $getSetting('min_booking_hours', 24),
                    'max_booking_days' => (int) $getSetting('max_booking_days', 30),
                    'cancellation_hours' => (int) $getSetting('cancellation_hours', 24),
                ],

                // الإصدار
                'version' => [
                    'api' => '1.0.0',
                    'min_mobile_version' => config('app.min_mobile_version', '1.0.0'),
                ],
            ];
        });
    }

    /**
     * بيانات Meta (ETag وغيرها)
     */
    private function getMeta(): array
    {
        return [
            'timestamp' => now()->toIso8601String(),
            'etag' => md5(json_encode([
                'translations_updated' => Cache::get('translations.last_updated'),
                'carousel_updated' => Cache::get('carousel.last_updated'),
                'settings_updated' => Cache::get('settings.last_updated'),
            ])),
        ];
    }

    /**
     * تطبيع اللغة (ar أو en فقط)
     */
    private function normalizeLocale(string $locale): string
    {
        $available = config('app.available_locales', ['ar', 'en']);
        $locale = strtolower(trim($locale));

        return in_array($locale, $available) ? $locale : config('app.locale', 'ar');
    }

    /**
     * إبطال الـ Cache (للاستخدام عند تحديث الإعدادات)
     */
    public function clearCache()
    {
        $locales = config('app.available_locales', ['ar', 'en']);

        // Clear translations cache
        foreach ($locales as $locale) {
            Cache::forget("translations.{$locale}");
        }

        // Clear other caches
        Cache::forget('carousel.slider.active');
        Cache::forget('app.config');

        // Update timestamps
        Cache::put('translations.last_updated', now());
        Cache::put('carousel.last_updated', now());
        Cache::put('settings.last_updated', now());

        return api_success(null, 'Cache cleared successfully');
    }

    /**
     * جلب فقط الترجمات (Backward Compatibility)
     * 
     * @deprecated Use getSettings() instead
     */
    public function getTranslationsOnly(Request $request)
    {
        $locale = $this->normalizeLocale($request->input('locale', app()->getLocale()));
        $translations = $this->getTranslations($locale);

        return api_success((object) $translations, 'Translations retrieved');
    }

    /**
     * جلب فقط الـ Carousel (Backward Compatibility)
     * 
     * @deprecated Use getSettings() instead
     */
    public function getCarouselOnly(Request $request)
    {
        $carousel = $this->getCarousel();

        return api_success($carousel['items'], 'Home carousel');
    }

    // ==========================================
    // الدوال الأخرى الموجودة
    // ==========================================

    public function policies()
    {
        $value = Setting::where('key', 'policies_and_terms')->value('value');

        return api_success($value, 'policies html loaded successfully');
    }

    public function privacyPolicy()
    {
        $value = Setting::where('key', 'privacy_policy')->value('value');

        return api_success($value, 'privacy policy us html loaded successfully');
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
        $faqs = FAQ::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $data = $faqs->map(function (FAQ $faq) {
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