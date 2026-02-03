<?php

use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;

if (!function_exists('t')) {
    /**
     * ترجمة مع افتراض ملف messages إذا لم يحدد
     *
     * @param string $key
     * @param array $replace
     * @param string|null $locale
     * @return string
     */
    function t($key, $replace = [], $locale = null)
    {
        if (!str_contains($key, '.')) {
            $key = "messages.$key";
        }
        return __($key, $replace, $locale);
    }

    function defaultImage($name = 'service.svg')
    {
        return asset('assets/media/avatars/' . $name);
    }
}

if (!function_exists('invoiceable_label')) {

    /**
     * @return array{label:string,id:int,route:?string}
     */
    function invoiceable_label(string $short, ?int $invoiceableId, ?Model $invoiceable = null): array
    {
        $id = (int) ($invoiceableId ?? 0);

        if ($id <= 0 || $short === '') {
            return ['label' => '—', 'id' => $id, 'route' => null];
        }

        $label = '';
        $route = null;

        if ($short === 'Package') {
            $pkg = $invoiceable instanceof \App\Models\Package
                ? $invoiceable
                : \App\Models\Package::query()->select(['id', 'name'])->find($id);

            $label = translatable_value($pkg, 'name') ?: __('invoices.Package');

            $route = \Illuminate\Support\Facades\Route::has('dashboard.packages.show')
                ? route('dashboard.packages.show', $id)
                : null;

            return ['label' => $label, 'id' => $id, 'route' => $route];
        }

        if ($short === 'PackageSubscription') {
            $sub = $invoiceable instanceof \App\Models\PackageSubscription
                ? $invoiceable
                : \App\Models\PackageSubscription::query()->select(['id', 'name'])->find($id);

            $label = translatable_value($sub, 'name') ?: __('invoices.PackageSubscription');

            $route = \Illuminate\Support\Facades\Route::has('dashboard.package_subscriptions.show')
                ? route('dashboard.package_subscriptions.show', $id)
                : null;

            return ['label' => $label, 'id' => $id, 'route' => $route];
        }

        if ($short === 'Booking') {
            // ✅ استخدام الأعمدة الصحيحة فقط
            $booking = $invoiceable instanceof \App\Models\Booking
                ? $invoiceable
                : \App\Models\Booking::query()->select(['id', 'external_id'])->find($id);

            // ✅ استخدام external_id إذا موجود، وإلا رقم الحجز
            $number = $booking?->external_id
                ? $booking->external_id
                : '#' . $id;

            $label = __('invoices.Booking') . ' ' . $number;

            $route = \Illuminate\Support\Facades\Route::has('dashboard.bookings.show')
                ? route('dashboard.bookings.show', $id)
                : null;

            return ['label' => $label, 'id' => $id, 'route' => $route];
        }

        // fallback
        $translated = __('invoices.' . $short);
        $label = ($translated !== 'invoices.' . $short ? $translated : $short) . ' #' . $id;

        return ['label' => $label, 'id' => $id, 'route' => null];
    }
}

if (!function_exists('translatable_value')) {

    function translatable_value(?\Illuminate\Database\Eloquent\Model $model, string $field): string
    {
        if (!$model)
            return '';

        if (method_exists($model, 'getTranslation')) {
            return (string) $model->getTranslation($field, app()->getLocale());
        }

        $val = $model->{$field} ?? '';

        if (is_array($val)) {
            return (string) ($val[app()->getLocale()] ?? $val['en'] ?? (count($val) ? reset($val) : '') ?? '');
        }

        if (is_string($val)) {
            $trim = trim($val);

            if ($trim !== '' && ($trim[0] === '{' || $trim[0] === '[')) {
                $decoded = json_decode($trim, true);
                if (is_array($decoded)) {
                    return (string) ($decoded[app()->getLocale()] ?? $decoded['en'] ?? (count($decoded) ? reset($decoded) : '') ?? '');
                }
            }
        }

        return (string) $val;
    }
}

if (!function_exists('extract_lat_lng_from_maps_link')) {

    /**
     * Extract coordinates from (almost) any Google Maps link.
     *
     * @param  string       $url
     * @param  array        $opts
     *    - resolve_short_links (bool) default true
     *    - google_api_key (string|null) default null (for fallback geocoding)
     *    - timeout (int|float) default 6
     *
     * @return array|null   ['lat' => float, 'lng' => float, 'source' => string]
     */
    function extract_lat_lng_from_maps_link(string $url, array $opts = []): ?array
    {
        $resolveShort = $opts['resolve_short_links'] ?? true;
        $apiKey = $opts['google_api_key'] ?? null;
        $timeout = $opts['timeout'] ?? 6;

        $url = trim($url);
        if ($url === '')
            return null;

        // Accept raw "lat,lng" input too
        if ($pair = _maps_parse_lat_lng_pair($url)) {
            return ['lat' => $pair['lat'], 'lng' => $pair['lng'], 'source' => 'raw_pair'];
        }

        // Add scheme if missing
        if (!preg_match('~^https?://~i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        // Resolve short links (maps.app.goo.gl / goo.gl/maps, etc.)
        if ($resolveShort) {
            $url = _maps_resolve_final_url($url, $timeout) ?? $url;
        }

        // Try parse with regex from full URL string (works even if query parsing fails)
        if ($pair = _maps_extract_from_text($url)) {
            return ['lat' => $pair['lat'], 'lng' => $pair['lng'], 'source' => $pair['source'] ?? 'text_regex'];
        }

        $parts = parse_url($url);
        $query = [];
        if (!empty($parts['query']))
            parse_str($parts['query'], $query);

        // Common query params that may contain coordinates
        foreach (['q', 'query', 'll', 'center', 'sll', 'daddr', 'destination', 'origin'] as $k) {
            if (!empty($query[$k]) && ($pair = _maps_extract_from_text((string) $query[$k]))) {
                return ['lat' => $pair['lat'], 'lng' => $pair['lng'], 'source' => "query:$k"];
            }
        }

        // "api=1" links sometimes have query_place_id / query
        $placeId =
            ($query['place_id'] ?? null)
            ?: ($query['query_place_id'] ?? null)
            ?: ($query['q'] ?? null);

        // place_id:ChIJ... pattern
        if (is_string($placeId) && str_starts_with($placeId, 'place_id:')) {
            $placeId = substr($placeId, strlen('place_id:'));
        }

        // If no coordinates exist in URL, try Geocoding fallback (optional)
        if ($apiKey) {
            // 1) Place ID geocode
            if (is_string($placeId) && preg_match('~^ChI[a-zA-Z0-9_-]+$~', $placeId)) {
                if ($pair = _maps_geocode_place_id($placeId, $apiKey, $timeout)) {
                    return ['lat' => $pair['lat'], 'lng' => $pair['lng'], 'source' => 'geocode:place_id'];
                }
            }

            // 2) Text query geocode (address or plus code)
            $address =
                (isset($query['query']) ? (string) $query['query'] : null)
                ?: (isset($query['q']) ? (string) $query['q'] : null);

            if ($address && ($pair = _maps_geocode_address($address, $apiKey, $timeout))) {
                return ['lat' => $pair['lat'], 'lng' => $pair['lng'], 'source' => 'geocode:address'];
            }
        }

        if ($pair = _maps_extract_from_html($url, $timeout)) {
            return ['lat' => $pair['lat'], 'lng' => $pair['lng'], 'source' => $pair['source'] ?? 'html'];
        }

        return null;
    }

    /**
     * Resolve final URL after redirects (supports short maps links).
     */
    function _maps_resolve_final_url(string $url, $timeout = 6): ?string
    {
        try {
            $effectiveUrl = null;

            $client = new Client([
                'timeout' => $timeout,
                'http_errors' => false,
                'allow_redirects' => ['max' => 10],
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'Accept-Language' => 'en-US,en;q=0.9,ar;q=0.8',
                ],
                'on_stats' => function (TransferStats $stats) use (&$effectiveUrl) {
                    $effectiveUrl = (string) $stats->getEffectiveUri();
                },
            ]);

            $res = $client->request('GET', $url);

            $final = $effectiveUrl ?: $url;

            // ✅ لو انتهى على consent.google.com استخرج continue=
            $host = parse_url($final, PHP_URL_HOST);
            if ($host && str_contains($host, 'consent.google.com')) {
                parse_str(parse_url($final, PHP_URL_QUERY) ?: '', $q);
                if (!empty($q['continue'])) {
                    $final = urldecode($q['continue']);
                }
            }

            return $final ?: $url;
        } catch (\Throwable $e) {
            return null;
        }
    }


    function _maps_extract_from_html(string $url, $timeout = 6): ?array
    {
        try {
            $html = Http::timeout($timeout)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                    'Accept-Language' => 'en-US,en;q=0.9,ar;q=0.8',
                ])
                ->get($url)
                ->body();

            if (!$html)
                return null;

            // 1) og:image غالبًا فيه center=LAT,LNG
            if (preg_match('~content="[^"]*center=(-?\d+(?:\.\d+)?),(-?\d+(?:\.\d+)?)~', $html, $m)) {
                return _maps_validate_pair((float) $m[1], (float) $m[2], 'html_og_center');
            }

            // 2) أحيانًا يكون داخل الصفحة @lat,lng أو !3d !4d
            if ($pair = _maps_extract_from_text($html)) {
                return ['lat' => $pair['lat'], 'lng' => $pair['lng'], 'source' => 'html_regex'];
            }

            return null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Extract from full text using multiple patterns.
     */
    function _maps_extract_from_text(string $text): ?array
    {
        $text = trim($text);

        // Pattern 1: /@lat,lng,zoom
        if (preg_match('~@(-?\d+(?:\.\d+)?),\s*(-?\d+(?:\.\d+)?)~', $text, $m)) {
            return _maps_validate_pair((float) $m[1], (float) $m[2], 'at_segment');
        }

        // Pattern 2: !3dLAT!4dLNG (common in "data=")
        if (preg_match('~!3d(-?\d+(?:\.\d+)?)!4d(-?\d+(?:\.\d+)?)~', $text, $m)) {
            return _maps_validate_pair((float) $m[1], (float) $m[2], 'data_3d4d');
        }

        // Pattern 3: lat,lng anywhere
        if ($pair = _maps_parse_lat_lng_pair($text)) {
            return ['lat' => $pair['lat'], 'lng' => $pair['lng'], 'source' => 'pair_anywhere'];
        }

        return null;
    }

    /**
     * Parse "lat,lng" pair strictly.
     */
    function _maps_parse_lat_lng_pair(string $s): ?array
    {
        $s = trim($s);

        // Examples: "24.7136,46.6753" or "24.7136 46.6753"
        if (preg_match('~^\s*(-?\d+(?:\.\d+)?)\s*[, ]\s*(-?\d+(?:\.\d+)?)\s*$~', $s, $m)) {
            $lat = (float) $m[1];
            $lng = (float) $m[2];
            $v = _maps_validate_pair($lat, $lng);
            return $v ? ['lat' => $v['lat'], 'lng' => $v['lng']] : null;
        }

        return null;
    }

    /**
     * Validate ranges.
     */
    function _maps_validate_pair(float $lat, float $lng, string $source = 'validated'): ?array
    {
        if ($lat < -90 || $lat > 90)
            return null;
        if ($lng < -180 || $lng > 180)
            return null;

        return ['lat' => $lat, 'lng' => $lng, 'source' => $source];
    }

    /**
     * Fallback: Geocode by place_id.
     */
    function _maps_geocode_place_id(string $placeId, string $apiKey, $timeout = 6): ?array
    {
        try {
            $res = Http::timeout($timeout)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'place_id' => $placeId,
                'key' => $apiKey,
            ]);

            if (!$res->ok())
                return null;

            $json = $res->json();
            $loc = data_get($json, 'results.0.geometry.location');
            if (!$loc)
                return null;

            return _maps_validate_pair((float) $loc['lat'], (float) $loc['lng'], 'geocode_place_id');
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Fallback: Geocode by address/query (works for plus codes too).
     */
    function _maps_geocode_address(string $address, string $apiKey, $timeout = 6): ?array
    {
        try {
            $res = Http::timeout($timeout)->get('https://maps.googleapis.com/maps/api/geocode/json', [
                'address' => $address,
                'key' => $apiKey,
            ]);

            if (!$res->ok())
                return null;

            $json = $res->json();
            $loc = data_get($json, 'results.0.geometry.location');
            if (!$loc)
                return null;

            return _maps_validate_pair((float) $loc['lat'], (float) $loc['lng'], 'geocode_address');
        } catch (\Throwable $e) {
            return null;
        }
    }


    if (!function_exists('format_currency')) {
        function format_currency(float $amount, ?string $currency = null): string
        {
            $currency = $currency ?? config('currency.default');

            $formattedAmount = number_format($amount, 2);

            return match ($currency) {
                'SAR' => '<span class="currency-icon">' . $formattedAmount . ' ' . sar_svg() . '</span>',
                'USD' => '<span class="currency-icon">$' . $formattedAmount . '</span>',
                default => $formattedAmount,
            };
        }
    }

    if (!function_exists('sar_svg')) {
        function sar_svg(): string
        {
            return <<<SVG
<svg class="currency-symbol" width="14" height="14" viewBox="0 0 20 21" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
<path d="M11.1387 5.19542C11.7013 4.56387 12.0471 4.28034 12.7262 3.92188V14.4382L11.1387 14.7663V5.19542Z"/>
<path d="M16.2105 10.4816C16.5396 9.78697 16.5784 9.47822 16.6665 8.76562L4.49817 11.4079C4.20962 12.0506 4.11674 12.4099 4.07227 13.0481L16.2105 10.4816Z"/>
<path d="M16.211 13.7043C16.54 13.0097 16.5788 12.7009 16.6669 11.9883L11.1969 13.146C11.1582 13.7829 11.2026 14.1095 11.1582 14.7476L16.211 13.7043Z"/>
<path d="M16.211 16.923C16.54 16.2284 16.5788 15.9196 16.6669 15.207L11.6809 16.2894C11.4292 16.6367 11.2743 17.2156 11.1582 17.9664L16.211 16.923Z"/>
<path d="M8.29247 15.8275C8.77642 15.2294 9.27976 14.4768 9.62826 13.8594L3.75892 15.1315C3.47036 15.7743 3.37747 16.1336 3.33301 16.7717L8.29247 15.8275Z"/>
<path d="M8.04102 4.3087C8.60364 3.67716 8.94939 3.39362 9.62847 3.03516V13.8988L8.04102 14.2268V4.3087Z"/>
</svg>
SVG;
        }
    }
}

