<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class AppTranslationUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ar_file' => ['nullable', 'file', 'max:1024'],
            'en_file' => ['nullable', 'file', 'max:1024'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($v) {

            if (!$this->hasFile('ar_file') && !$this->hasFile('en_file')) {
                $v->errors()->add('file', 'You must upload ar_file or en_file at least.');
                return;
            }

            foreach (['ar_file', 'en_file'] as $key) {
                if (!$this->hasFile($key))
                    continue;

                $file = $this->file($key);

                // 1) تحقق من الامتداد الحقيقي
                $ext = strtolower($file->getClientOriginalExtension());
                if ($ext !== 'php') {
                    $v->errors()->add($key, "The {$key} must be a .php file.");
                    continue;
                }

                // 2) (اختياري) تحقق من أول الملف يحتوي <?php
                $head = file_get_contents($file->getRealPath(), false, null, 0, 20);
                if ($head === false || strpos($head, '<?php') === false) {
                    $v->errors()->add($key, "The {$key} must start with <?php.");
                }
            }
        });
    }
}