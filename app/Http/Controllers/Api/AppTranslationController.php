<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppTranslationUploadRequest;
use App\Services\AppTranslationService;
use Illuminate\Http\Request;

class AppTranslationController extends Controller
{
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

        // لو بدك ترجع nested كما هو: رجعه مباشرة
        // لو بدك flatten dot keys: فعّل السطر التالي
        // $arr = $this->flattenLangArray($arr);

        // رجّع key=>value
        return api_success((object) $arr, 'Success')
            ->header('ETag', $meta['etag'] ?? '');
    }

    /**
     * Parsing آمن-ish: يمنع أي tokens خطيرة ويقبل فقط return + literals/arrays.
     * ثم include داخل scope معزول ويرجع array.
     */
    private function parsePhpLangArray(string $raw): ?array
    {
        $raw = $this->stripBom($raw);

        if (strpos($raw, '<?php') === false) {
            return null;
        }

        $tokens = token_get_all($raw);

        // نمنع أي شيء ممكن ينفّذ كود
        $disallowed = [
            T_VARIABLE,
            T_FUNCTION,
            T_CLASS,
            T_TRAIT,
            T_INTERFACE,
            T_NEW,
            T_INCLUDE,
            T_INCLUDE_ONCE,
            T_REQUIRE,
            T_REQUIRE_ONCE,
            T_EVAL,
            T_EXIT,
            T_GLOBAL,
            T_STATIC,
            T_NAMESPACE,
            T_USE,
            T_THROW,
            T_TRY,
            T_CATCH,
            T_FINALLY,
            T_IF,
            T_ELSE,
            T_ELSEIF,
            T_FOR,
            T_FOREACH,
            T_WHILE,
            T_DO,
            T_SWITCH,
            T_MATCH,
            T_STRING, // يمنع استدعاءات دوال
        ];

        foreach ($tokens as $t) {
            if (is_array($t) && in_array($t[0], $disallowed, true)) {
                return null;
            }
        }

        $tmpDir = storage_path('app/tmp-lang');
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        $tmpFile = $tmpDir . '/lang_' . uniqid('', true) . '.php';
        file_put_contents($tmpFile, $raw);

        try {
            $data = (static function ($file) {
                return include $file;
            })($tmpFile);

            return is_array($data) ? $data : null;
        } finally {
            @unlink($tmpFile);
        }
    }

    private function stripBom(string $text): string
    {
        if (substr($text, 0, 3) === "\xEF\xBB\xBF") {
            return substr($text, 3);
        }
        return $text;
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

            $done['ar'] = true;
        }

        if ($request->hasFile('en_file')) {
            $raw = file_get_contents($request->file('en_file')->getRealPath());
            $res = $this->svc->writeRaw('en', $raw);

            if (!$res['ok']) {
                return response()->json(['success' => false, 'message' => "EN: {$res['message']}"], 422);
            }

            $done['en'] = true;
        }

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