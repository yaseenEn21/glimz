<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppTranslationUploadRequest;
use App\Services\AppTranslationService;
use App\Http\Controllers\Traits\ParsesTranslationFiles;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class AppTranslationController extends Controller
{

    use ParsesTranslationFiles;

    public function __construct(private AppTranslationService $svc)
    {
    }

    public function show(Request $request)
    {
        $locale = $this->svc->normalizeLocale(request_lang(['ar', 'en'], 'en'));

        $meta = $this->svc->meta($locale);

        // ETag (اختياري)
        if (!empty($meta['etag'])) {
            $ifNoneMatch = (string) $request->header('If-None-Match', '');
            if ($ifNoneMatch !== '' && hash_equals($meta['etag'], $ifNoneMatch)) {
                return response('', 304)->header('ETag', $meta['etag']);
            }
        }

        // اقرأ محتوى الملف PHP raw من السيرفس
        $raw = $this->svc->readRaw($locale);
        if ($raw === '') {
            return api_success((object) [], 'Success')->header('ETag', $meta['etag'] ?? '');
        }

        // حوّل ملف PHP إلى array
        $arr = $this->parsePhpLangArray($raw);
        if (!is_array($arr)) {
            return api_error('Invalid translation PHP file structure', 422);
        }

        // رجّع key=>value
        return api_success((object) $arr, 'Success')
            ->header('ETag', $meta['etag'] ?? '');
    }

    public function upload(AppTranslationUploadRequest $request)
    {
        $done = [];

        if ($request->hasFile('ar_file')) {
            $raw = file_get_contents($request->file('ar_file')->getRealPath());
            $res = $this->svc->writeRaw('ar', $raw);

            if (!$res['ok']) {
                return response()->json(['success' => false, 'message' => "AR: {$res['message']}"], 422);
            }

            Cache::forget('translations.ar'); // ✅ امسح الـ cache
            Cache::forget('app_translation_raw.ar'); // ✅ من الـ service
            $done['ar'] = true;
        }

        if ($request->hasFile('en_file')) {
            $raw = file_get_contents($request->file('en_file')->getRealPath());
            $res = $this->svc->writeRaw('en', $raw);

            if (!$res['ok']) {
                return response()->json(['success' => false, 'message' => "EN: {$res['message']}"], 422);
            }

            Cache::forget('translations.en'); // ✅ امسح الـ cache
            Cache::forget('app_translation_raw.en'); // ✅ من الـ service
            $done['en'] = true;
        }

        Cache::put('translations.last_updated', now());

        return response()->json([
            'success' => true,
            'message' => 'Translations uploaded',
            'data' => $done,
        ]);
    }

    /**
     * يقرأ الملف (php/json/txt) ويحوّله إلى JSON ثم يمرره للسيرفس الحالية write().
     */
    private function handleUploadedTranslationFile(Request $request, string $field, string $locale): array
    {
        $file = $request->file($field);
        $raw = file_get_contents($file->getRealPath());
        if ($raw === false) {
            return ['ok' => false, 'message' => 'Unable to read uploaded file'];
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());

        // 1) ملف PHP (Laravel lang style)
        if ($ext === 'php') {
            $arr = $this->parsePhpLangArray($raw);
            if (!is_array($arr)) {
                return ['ok' => false, 'message' => 'Invalid PHP translation file (must return array)'];
            }

            // لو الملف Nested، بنحوّله لمفاتيح dot.key (اختياري لكن عملي)
            $flat = $this->flattenLangArray($arr);

            $rawJson = json_encode($flat, JSON_UNESCAPED_UNICODE);
            if ($rawJson === false) {
                return ['ok' => false, 'message' => 'Failed to encode translations to JSON'];
            }

            return $this->svc->write($locale, $rawJson);
        }

        // 2) json أو txt (نعتبره JSON نصي)
        if (in_array($ext, ['json', 'txt'], true)) {
            return $this->svc->write($locale, $raw);
        }

        return ['ok' => false, 'message' => 'Unsupported file type'];
    }


    /**
     * يحول nested arrays إلى dot keys:
     * ['auth' => ['failed' => '...']] => ['auth.failed' => '...']
     */
    private function flattenLangArray(array $arr, string $prefix = ''): array
    {
        $out = [];

        foreach ($arr as $k => $v) {
            if (!is_string($k) || $k === '') {
                continue;
            }

            $key = $prefix === '' ? $k : ($prefix . '.' . $k);

            if (is_array($v)) {
                $out += $this->flattenLangArray($v, $key);
            } elseif (is_string($v)) {
                $out[$key] = $v;
            } else {
                // تجاهل أي شيء غير string في النهاية
            }
        }

        return $out;
    }

}