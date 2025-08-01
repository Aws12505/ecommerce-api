<?php
// app/Models/LegalDocument.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class LegalDocument extends Model
{
    use HasFactory;

    const TYPE_TERMS_OF_SERVICE = 'terms_of_service';
    const TYPE_PRIVACY_POLICY = 'privacy_policy';

    protected $fillable = [
        'type',
        'title',
        'content',
        'plain_content',
        'version',
        'is_published',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
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
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeTermsOfService($query)
    {
        return $query->where('type', self::TYPE_TERMS_OF_SERVICE);
    }

    public function scopePrivacyPolicy($query)
    {
        return $query->where('type', self::TYPE_PRIVACY_POLICY);
    }

    // Mutators
    public function setContentAttribute($value)
    {
        $this->attributes['content'] = $value;
        // Strip HTML tags to create plain text version
        $this->attributes['plain_content'] = strip_tags($value);
    }

    // Accessors
    public function getTypeDisplayAttribute()
    {
        return match($this->type) {
            self::TYPE_TERMS_OF_SERVICE => 'Terms of Service',
            self::TYPE_PRIVACY_POLICY => 'Privacy Policy',
            default => ucfirst(str_replace('_', ' ', $this->type))
        };
    }

    // Methods
    public function publish()
    {
        $this->update([
            'is_published' => true,
            'published_at' => Carbon::now(),
        ]);
    }

    public function unpublish()
    {
        $this->update([
            'is_published' => false,
            'published_at' => null,
        ]);
    }

    public static function getAvailableTypes()
    {
        return [
            self::TYPE_TERMS_OF_SERVICE => 'Terms of Service',
            self::TYPE_PRIVACY_POLICY => 'Privacy Policy',
        ];
    }
}
