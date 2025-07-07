<?php
// FILE: app/Models/CartItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_id',
        'product_id',
        'quantity',
        'price',
        'total',
        'product_options',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'product_options' => 'array',
    ];

    // Relationships
    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // Boot method to auto-calculate total
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($cartItem) {
            $cartItem->total = $cartItem->quantity * $cartItem->price;
        });
    }
}
