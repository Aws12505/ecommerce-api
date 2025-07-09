<?php
// FILE: app/Http/Requests/V1/ApplyCouponRequest.php

namespace App\Http\Requests\V1\Checkout;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:20',
        ];
    }
}
