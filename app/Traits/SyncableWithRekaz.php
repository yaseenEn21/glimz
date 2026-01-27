<?php

namespace App\Traits;

use App\Models\RekazMapping;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait SyncableWithRekaz
{
    public function rekazMapping(): MorphOne
    {
        return $this->morphOne(RekazMapping::class, 'mappable');
    }

    public function getRekazIdAttribute(): ?string
    {
        return $this->rekazMapping?->rekaz_id;
    }

    public function isSyncedWithRekaz(): bool
    {
        return $this->rekazMapping !== null && 
               $this->rekazMapping->sync_status === 'synced';
    }

    public function syncWithRekaz(string $rekazId, ?string $entityType = null, array $metadata = []): RekazMapping
    {
        return $this->rekazMapping()->updateOrCreate(
            [
                'mappable_type' => get_class($this),
                'mappable_id' => $this->id,
            ],
            [
                'rekaz_id' => $rekazId,
                'rekaz_entity_type' => $entityType,
                'sync_status' => 'synced',
                'last_synced_at' => now(),
                'metadata' => $metadata,
            ]
        );
    }

    public function unsyncFromRekaz(): void
    {
        $this->rekazMapping()?->delete();
    }
}