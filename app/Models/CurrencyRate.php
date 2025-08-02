<?php
// app/Models/CurrencyRate.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CurrencyRate extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_currency',
        'to_currency',
        'rate',
        'last_updated_at',
    ];

    protected $casts = [
        'rate' => 'decimal:6',
        'last_updated_at' => 'datetime',
    ];

    // Relationships
    public function fromCurrency()
    {
        return $this->belongsTo(Currency::class, 'from_currency', 'code');
    }

    public function toCurrency()
    {
        return $this->belongsTo(Currency::class, 'to_currency', 'code');
    }

    // Methods
    public static function getRate(string $from, string $to): float
    {
        if ($from === $to) {
            return 1.0;
        }

        $rate = static::where('from_currency', $from)
                     ->where('to_currency', $to)
                     ->first();

        return $rate ? (float) $rate->rate : 1.0;
    }
}
