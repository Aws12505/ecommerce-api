<?php
// app/Models/SplashScreen.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SplashScreen extends Model
{
    use HasFactory;

    const TYPE_EVENT = 'event';
    const TYPE_PROMOTION = 'promotion';
    const TYPE_ANNOUNCEMENT = 'announcement';
    const TYPE_SEASONAL = 'seasonal';
    const TYPE_GENERAL = 'general';

    const AUDIENCE_ALL = 'all';
    const AUDIENCE_NEW_USERS = 'new_users';
    const AUDIENCE_RETURNING_USERS = 'returning_users';

    protected $fillable = [
        'title',
        'image_url',
        'description',
        'type',
        'is_active',
        'display_duration',
        'sort_order',
        'start_date',
        'end_date',
        'target_audience',
        'metadata',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'metadata' => 'array',
    ];

    // Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCurrentlyValid($query)
    {
        $now = Carbon::now();
        return $query->where(function ($q) use ($now) {
            $q->where(function ($subQ) use ($now) {
                $subQ->whereNotNull('start_date')
                     ->whereNotNull('end_date')
                     ->where('start_date', '<=', $now)
                     ->where('end_date', '>=', $now);
            })->orWhere(function ($subQ) {
                $subQ->whereNull('start_date')
                     ->whereNull('end_date');
            });
        });
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('created_at', 'desc');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByAudience($query, string $audience)
    {
        return $query->where(function ($q) use ($audience) {
            $q->where('target_audience', $audience)
              ->orWhere('target_audience', self::AUDIENCE_ALL)
              ->orWhereNull('target_audience');
        });
    }

    // Accessors
    public function getTypeDisplayAttribute()
    {
        return match($this->type) {
            self::TYPE_EVENT => 'Event',
            self::TYPE_PROMOTION => 'Promotion',
            self::TYPE_ANNOUNCEMENT => 'Announcement',
            self::TYPE_SEASONAL => 'Seasonal',
            self::TYPE_GENERAL => 'General',
            default => ucfirst($this->type)
        };
    }

    public function getIsCurrentlyValidAttribute()
    {
        if (!$this->start_date || !$this->end_date) {
            return true; // No date restrictions
        }

        $now = Carbon::now();
        return $now->between($this->start_date, $this->end_date);
    }

    public function getStatusAttribute()
    {
        if (!$this->is_active) {
            return 'inactive';
        }

        if (!$this->is_currently_valid) {
            return 'expired';
        }

        return 'active';
    }

    // Methods
    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }

    public static function getAvailableTypes()
    {
        return [
            self::TYPE_EVENT => 'Event',
            self::TYPE_PROMOTION => 'Promotion',
            self::TYPE_ANNOUNCEMENT => 'Announcement',
            self::TYPE_SEASONAL => 'Seasonal',
            self::TYPE_GENERAL => 'General',
        ];
    }

    public static function getAvailableAudiences()
    {
        return [
            self::AUDIENCE_ALL => 'All Users',
            self::AUDIENCE_NEW_USERS => 'New Users',
            self::AUDIENCE_RETURNING_USERS => 'Returning Users',
        ];
    }
}
