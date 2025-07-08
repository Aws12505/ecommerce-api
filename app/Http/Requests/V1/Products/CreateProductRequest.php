<?php
// FILE: app/Http/Requests/V1/Products/CreateProductRequest.php

namespace App\Http\Requests\V1\Products;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'short_description' => 'nullable|string|max:500',
            'sku' => 'nullable|string|unique:products,sku',
            'price' => 'required|numeric|min:0',
            'sale_price' => 'nullable|numeric|min:0|lt:price',
            'stock_quantity' => 'required|integer|min:0',
            'manage_stock' => 'boolean',
            'in_stock' => 'boolean',
            'stock_status' => 'in:in_stock,out_of_stock',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'images' => 'nullable|array|max:5',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'in:active,inactive,draft',
            'featured' => 'boolean',
            'published_at' => 'required|date',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:categories,id',
            'attributes' => 'nullable|array',
            'meta_data' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'published_at.required' => 'The publication date is required.',
            'published_at.date' => 'The publication date must be a valid date.',
            'sale_price.lt' => 'The sale price must be less than the regular price.',
            'images.max' => 'You can upload a maximum of 5 images.',
            'images.*.image' => 'Each file must be an image.',
            'images.*.max' => 'Each image must not exceed 2MB.',
            'stock_status.in' => 'Stock status must be either in_stock or out_of_stock.',
        ];
    }
}
