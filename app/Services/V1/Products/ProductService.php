<?php

namespace App\Services\V1\Products;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    public function getAllProducts(Request $request): array
    {
        $query = Product::with(['categories'])
            ->active()
            ->published();

        // Filtering
        if ($request->has('category')) {
            $query->whereHas('categories', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->has('featured')) {
            $query->featured();
        }

        if ($request->has('in_stock')) {
            $query->inStock();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Price range filtering
        if ($request->has('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->has('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSorts = ['name', 'price', 'created_at', 'featured'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Pagination
        $perPage = min($request->get('per_page', 15), 50);
        $products = $query->paginate($perPage);

        return [
            'data' => $products,
            'message' => 'Products retrieved successfully'
        ];
    }

    public function getProduct(string $slug): array
    {
        $product = Product::with(['categories'])
            ->where('slug', $slug)
            ->active()
            ->published()
            ->firstOrFail();

        return [
            'data' => $product,
            'message' => 'Product retrieved successfully'
        ];
    }

    public function createProduct(array $data): array
    {
        $product = Product::create($data);

        if (isset($data['categories'])) {
            $product->categories()->sync($data['categories']);
        }

        if (isset($data['images'])) {
            $this->handleImageUpload($product, $data['images']);
        }

        return [
            'data' => $product->load('categories'),
            'message' => 'Product created successfully'
        ];
    }

    public function updateProduct(Product $product, array $data): array
    {
        $product->update($data);

        if (isset($data['categories'])) {
            $product->categories()->sync($data['categories']);
        }

        if (isset($data['images'])) {
            $this->handleImageUpload($product, $data['images']);
        }

        return [
            'data' => $product->load('categories'),
            'message' => 'Product updated successfully'
        ];
    }

    public function deleteProduct(Product $product): array
    {
        // Delete associated images
        if ($product->images) {
            foreach ($product->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $product->delete();

        return [
            'data' => null,
            'message' => 'Product deleted successfully'
        ];
    }

    private function handleImageUpload(Product $product, array $images): void
    {
        $uploadedImages = [];

        foreach ($images as $image) {
            if ($image && $image->isValid()) {
                $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('products', $filename, 'public');
                $uploadedImages[] = $path;
            }
        }

        if (!empty($uploadedImages)) {
            $product->update(['images' => $uploadedImages]);
        }
    }
}
