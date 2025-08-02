<?php
// app/Models/Currency.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'symbol',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // Relationships
    public function ratesFrom()
    {
        return $this->hasMany(CurrencyRate::class, 'from_currency', 'code');
    }

    public function ratesTo()
    {
        return $this->hasMany(CurrencyRate::class, 'to_currency', 'code');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // Methods
    public static function getDefault()
    {
        return static::default()->first() ?? static::where('code', 'USD')->first();
    }
}
