<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AppTranslationService
{
    private string $dir = 'app-translations';

    public function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));
        $locale = substr($locale, 0, 2);
        return in_array($locale, ['ar', 'en'], true) ? $locale : 'en';
    }

    // نخزن الملف PHP كما هو
    public function path(string $locale): string
    {
        $locale = $this->normalizeLocale($locale);
        return $this->dir . "/translations - {$locale}.php"; // نفس الاسم اللي بدك
    }

    // قراءة الملف كنص (raw)
    public function readRaw(string $locale): string
    {
        $locale = $this->normalizeLocale($locale);

        return Cache::remember("app_translation_raw.{$locale}", 60, function () use ($locale) {
            $p = $this->path($locale);

            if (!Storage::disk('local')->exists($p)) {
                return '';
            }

            $raw = Storage::disk('local')->get($p);
            return $this->stripBom($raw);
        });
    }

    // حفظ الملف كنص (raw) بدون تحويل
    public function writeRaw(string $locale, string $rawPhp): array
    {
        $locale = $this->normalizeLocale($locale);
        $rawPhp = $this->stripBom($rawPhp);

        // تحققات بسيطة: لازم يبدأ بـ <?php (اختياري لكن مفيد)
        if (strpos($rawPhp, '<?php') === false) {
            return ['ok' => false, 'message' => 'Invalid PHP translation file (missing <?php)'];
        }

        Storage::disk('local')->put($this->path($locale), $rawPhp);
        Cache::forget("app_translation_raw.{$locale}");

        return ['ok' => true];
    }

    public function meta(string $locale): array
    {
        $locale = $this->normalizeLocale($locale);
        $p = $this->path($locale);

        if (!Storage::disk('local')->exists($p)) {
            return ['exists' => false];
        }

        $fullPath = storage_path('app/' . $p);
        $mtime = @filemtime($fullPath) ?: null;
        $etag = $mtime ? sha1($locale . '|' . $mtime) : null;

        return [
            'exists' => true,
            'updated_at' => $mtime ? date('c', $mtime) : null,
            'etag' => $etag,
        ];
    }

    private function stripBom(string $text): string
    {
        if (substr($text, 0, 3) === "\xEF\xBB\xBF") {
            return substr($text, 3);
        }
        return $text;
    }
}