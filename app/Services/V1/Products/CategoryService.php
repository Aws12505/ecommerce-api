<?php
// FILE: app/Services/V1/CategoryService.php

namespace App\Services\V1\Products;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryService
{
    public function getAllCategories(Request $request): array
    {
        $query = Category::with(['parent', 'children'])
            ->active()
            ->ordered();

        if ($request->has('parent_only')) {
            $query->parent();
        }

        $categories = $query->get();

        return [
            'data' => $categories,
            'message' => 'Categories retrieved successfully'
        ];
    }

    public function getCategory(string $slug): array
    {
        $category = Category::with(['parent', 'children', 'products'])
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        return [
            'data' => $category,
            'message' => 'Category retrieved successfully'
        ];
    }

    public function createCategory(array $data): array
    {
        if (isset($data['image'])) {
            $data['image'] = $this->handleImageUpload($data['image']);
        }

        $category = Category::create($data);

        return [
            'data' => $category->load(['parent', 'children']),
            'message' => 'Category created successfully'
        ];
    }

    public function updateCategory(Category $category, array $data): array
    {
        if (isset($data['image'])) {
            // Delete old image
            if ($category->image) {
                Storage::disk('public')->delete($category->image);
            }
            $data['image'] = $this->handleImageUpload($data['image']);
        }

        $category->update($data);

        return [
            'data' => $category->load(['parent', 'children']),
            'message' => 'Category updated successfully'
        ];
    }

    public function deleteCategory(Category $category): array
    {
        // Delete associated image
        if ($category->image) {
            Storage::disk('public')->delete($category->image);
        }

        $category->delete();

        return [
            'data' => null,
            'message' => 'Category deleted successfully'
        ];
    }

    private function handleImageUpload($image): string
    {
        if ($image && $image->isValid()) {
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            return $image->storeAs('categories', $filename, 'public');
        }

        return '';
    }
}
