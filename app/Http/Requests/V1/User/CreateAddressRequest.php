<?php
// FILE: app/Http/Requests/V1/CreateAddressRequest.php

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\UserAddress;

class CreateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => 'nullable|string|max:255',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'required|string|max:255',
            'governorate' => 'required|string|max:255',
            'country' => 'required|string',
            'is_default' => 'boolean',
        ];
    }
}
