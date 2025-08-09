<?php

namespace App\Services\V1\Products;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Services\V1\Currency\CurrencyService;

class ProductService
{
    protected CurrencyService $currencyService;

    public function __construct(CurrencyService $currencyService)
    {
        $this->currencyService = $currencyService;
    }
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
            $minPriceInBase = $this->currencyService->convertPrice(
                $request->min_price, 
                $this->currencyService->getUserCurrency(), 
                $this->currencyService->getBaseCurrency()
            );
            $query->where('price', '>=', $minPriceInBase);
        }

        if ($request->has('max_price')) {
            $maxPriceInBase = $this->currencyService->convertPrice(
                $request->max_price, 
                $this->currencyService->getUserCurrency(), 
                $this->currencyService->getBaseCurrency()
            );
            $query->where('price', '<=', $maxPriceInBase);
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

        $product = $this->convertProductPrices($product);

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

    protected function convertProductPrices(Product $product): Product
    {
        $userCurrency = $this->currencyService->getUserCurrency();
        // Convert main price
        $product->price_converted = $this->currencyService->convertPrice($product->price,$userCurrency);
        $product->price_formatted = $this->currencyService->formatPrice($product->price_converted, $userCurrency);

        // Convert sale price if exists
        if ($product->sale_price) {
            $product->sale_price_converted = $this->currencyService->convertPrice($product->sale_price,$userCurrency);
            $product->sale_price_formatted = $this->currencyService->formatPrice($product->sale_price_converted, $userCurrency);
        }

        // Update current price (converted)
        $product->current_price_converted = $product->sale_price_converted ?? $product->price_converted;
        $product->current_price_formatted = $this->currencyService->formatPrice($product->current_price_converted, $userCurrency);

        // Add currency info
        $product->currency = $userCurrency;
        $product->original_currency = $this->currencyService->getBaseCurrency();

        return $product;
    }

}
