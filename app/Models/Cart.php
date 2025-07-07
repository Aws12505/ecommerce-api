<?php
// FILE: app/Models/Cart.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subtotal',
        'tax_amount',
        'total',
        'applied_coupons',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'applied_coupons' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Accessors
    public function getItemsCountAttribute()
    {
        return $this->items->sum('quantity');
    }

    public function getIsEmptyAttribute()
    {
        return $this->items->count() === 0;
    }

    // Methods
    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum('total');
        $taxAmount = $subtotal * 0.1; // 10% tax rate - make this configurable
        $total = $subtotal + $taxAmount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }

    public function addItem(Product $product, int $quantity = 1, array $options = []): CartItem
    {
        $existingItem = $this->items()
            ->where('product_id', $product->id)
            ->first();

        if ($existingItem) {
            $existingItem->update([
                'quantity' => $existingItem->quantity + $quantity,
                'total' => ($existingItem->quantity + $quantity) * $product->current_price,
            ]);
            $cartItem = $existingItem;
        } else {
            $cartItem = $this->items()->create([
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $product->current_price,
                'total' => $quantity * $product->current_price,
                'product_options' => $options,
            ]);
        }

        $this->calculateTotals();
        return $cartItem;
    }

    public function removeItem(int $cartItemId): bool
    {
        $item = $this->items()->find($cartItemId);
        
        if ($item) {
            $item->delete();
            $this->calculateTotals();
            return true;
        }

        return false;
    }

    public function updateItemQuantity(int $cartItemId, int $quantity): bool
    {
        $item = $this->items()->find($cartItemId);
        
        if ($item) {
            if ($quantity <= 0) {
                return $this->removeItem($cartItemId);
            }

            $item->update([
                'quantity' => $quantity,
                'total' => $quantity * $item->price,
            ]);
            
            $this->calculateTotals();
            return true;
        }

        return false;
    }

    public function clear(): void
    {
        $this->items()->delete();
        $this->update([
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
        ]);
    }
}
