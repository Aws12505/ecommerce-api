<?php
// app/Services/LineService.php

namespace App\Services\V1\Products;

use App\Models\Line;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class LineService
{
    public function getAllLines(bool $activeOnly = false): Collection
    {
        $query = Line::with('products');

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function getLineBySlug(string $slug): Line
    {
        return Line::with(['products' => function ($query) {
            $query->where('is_active', true);
        }])->where('slug', $slug)->firstOrFail();
    }

    public function createLine(array $data): Line
    {
        return Line::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    public function updateLine(Line $line, array $data): Line
    {
        $line->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? $line->description,
            'is_active' => $data['is_active'] ?? $line->is_active,
            'sort_order' => $data['sort_order'] ?? $line->sort_order,
        ]);

        return $line->fresh();
    }

    public function deleteLine(Line $line): bool
    {
        return $line->delete();
    }

    public function attachProductToLine(Line $line, int $productId, int $sortOrder = 0): void
    {
        $product = Product::findOrFail($productId);

        if (!$line->products()->where('product_id', $productId)->exists()) {
            $line->products()->attach($productId, ['sort_order' => $sortOrder]);
        }
    }

    public function detachProductFromLine(Line $line, Product $product): void
    {
        $line->products()->detach($product->id);
    }

    public function getLineProducts(Line $line): Collection
    {
        return $line->products()
                   ->where('is_active', true)
                   ->orderByPivot('sort_order')
                   ->get();
    }
}
