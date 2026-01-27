<?php

namespace App\Models;

use App\Traits\SyncableWithRekaz;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Branch extends Model
{
    use HasFactory, SoftDeletes, SyncableWithRekaz;

    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'name' => 'array',
    ];

    // Relations
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function zones()
    {
        return $this->hasMany(Zone::class);
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // Accessors
    public function getNameArAttribute(): string
    {
        return $this->name['ar'] ?? '';
    }

    public function getNameEnAttribute(): string
    {
        return $this->name['en'] ?? '';
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name[app()->getLocale()] ?? $this->name['ar'] ?? $this->name['en'] ?? '';
    }
}