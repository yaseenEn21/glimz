<?php

namespace App\Http\Controllers\Traits;

trait ParsesTranslationFiles
{
    /**
     * Parsing آمن: يمنع أي tokens خطيرة ويقبل فقط return + literals/arrays
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
}