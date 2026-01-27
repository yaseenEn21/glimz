<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RekazMapping extends Model
{
    protected $fillable = [
        'mappable_type',
        'mappable_id',
        'rekaz_id',
        'rekaz_entity_type',
        'sync_status',
        'last_synced_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function mappable(): MorphTo
    {
        return $this->morphTo();
    }

    public function markAsSynced(): void
    {
        $this->update([
            'sync_status' => 'synced',
            'last_synced_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'sync_status' => 'failed',
        ]);
    }
}