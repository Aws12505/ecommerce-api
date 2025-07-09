<?php
// FILE: app/Http/Requests/V1/UpdateCouponRequest.php

namespace App\Http\Requests\V1\Checkout;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('coupons', 'code')->ignore($this->coupon)
            ],
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|required|in:fixed,percentage',
            'value' => 'sometimes|required|numeric|min:0',
            'minimum_amount' => 'nullable|numeric|min:0',
            'maximum_discount' => 'nullable|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_user' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'applicable_products' => 'nullable|array',
            'applicable_products.*' => 'exists:products,id',
            'applicable_categories' => 'nullable|array',
            'applicable_categories.*' => 'exists:categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This coupon code is already in use.',
            'expires_at.after' => 'The expiration date must be after the start date.',
            'value.min' => 'The coupon value must be greater than 0.',
            'type.in' => 'Coupon type must be either fixed or percentage.',
        ];
    }

    protected function prepareForValidation()
    {
        // Convert code to uppercase for consistency
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper($this->code)
            ]);
        }
    }
}
