<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;

class SyncSettings extends Command
{
    protected $signature   = 'settings:sync';
    protected $description = 'Sync config/settings.php into the settings table (insert new, skip existing)';

    public function handle(): int
    {
        $definitions = config('settings', []);

        if (empty($definitions)) {
            $this->warn('config/settings.php is empty or not found.');
            return self::FAILURE;
        }

        $inserted = 0;
        $skipped  = 0;

        foreach ($definitions as $def) {
            $key = $def['key'] ?? null;
            if (!$key) continue;

            $exists = Setting::where('key', $key)->exists();

            if ($exists) {
                // ✅ نحدث فقط label و type (بدون المساس بـ value الموجودة)
                Setting::where('key', $key)->update([
                    'label' => $def['label'] ?? null,
                    'type'  => $def['type']  ?? null,
                ]);
                $this->line("  <fg=yellow>SKIP</>   {$key} (value preserved)");
                $skipped++;
            } else {
                Setting::create([
                    'key'   => $key,
                    'label' => $def['label']   ?? null,
                    'type'  => $def['type']    ?? null,
                    'value' => $def['default'] ?? null,
                ]);
                $this->line("  <fg=green>INSERT</> {$key} = " . ($def['default'] ?? 'null'));
                $inserted++;
            }
        }

        $this->newLine();
        $this->info("✅ Done — Inserted: {$inserted}, Skipped: {$skipped}");

        return self::SUCCESS;
    }
}