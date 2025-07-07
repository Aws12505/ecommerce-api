<?php
// FILE: app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'sku',
        'price',
        'sale_price',
        'stock_quantity',
        'manage_stock',
        'in_stock',
        'stock_status',
        'weight',
        'dimensions',
        'images',
        'status',
        'featured',
        'attributes',
        'meta_data',
        'published_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'weight' => 'decimal:2',
        'dimensions' => 'array',
        'images' => 'array',
        'manage_stock' => 'boolean',
        'in_stock' => 'boolean',
        'featured' => 'boolean',
        'attributes' => 'array',
        'meta_data' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
            if (empty($product->sku)) {
                $product->sku = 'SKU-' . strtoupper(Str::random(8));
            }
        });
    }

    // Relationships
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInStock($query)
    {
        return $query->where('in_stock', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopePublished($query)
    {
        return $query->where('published_at', '<=', now());
    }

    // Accessors
    public function getCurrentPriceAttribute()
    {
        return $this->sale_price ?? $this->price;
    }

    public function getIsOnSaleAttribute()
    {
        return !is_null($this->sale_price) && $this->sale_price < $this->price;
    }

    public function getDiscountPercentageAttribute()
    {
        if (!$this->is_on_sale) {
            return 0;
        }

        return round((($this->price - $this->sale_price) / $this->price) * 100);
    }

    public function getMainImageAttribute()
    {
        $images = $this->images ?? [];
        return !empty($images) ? asset('storage/' . $images[0]) : null;
    }

    public function getImageGalleryAttribute()
    {
        $images = $this->images ?? [];
        return array_map(function ($image) {
            return asset('storage/' . $image);
        }, $images);
    }

    // Methods
    public function isInStock(): bool
    {
        if (!$this->manage_stock) {
            return $this->in_stock;
        }

        return $this->stock_quantity > 0;
    }

    public function canPurchase(int $quantity = 1): bool
    {
        if (!$this->isInStock()) {
            return false;
        }

        if (!$this->manage_stock) {
            return true;
        }

        return $this->stock_quantity >= $quantity;
    }

    public function decreaseStock(int $quantity): void
    {
        if ($this->manage_stock) {
            $this->decrement('stock_quantity', $quantity);
            
            if ($this->stock_quantity <= 0) {
                $this->update([
                    'in_stock' => false,
                    'stock_status' => 'out_of_stock'
                ]);
            }
        }
    }

    public function increaseStock(int $quantity): void
    {
        if ($this->manage_stock) {
            $this->increment('stock_quantity', $quantity);
            
            if ($this->stock_quantity > 0) {
                $this->update([
                    'in_stock' => true,
                    'stock_status' => 'in_stock'
                ]);
            }
        }
    }
}
