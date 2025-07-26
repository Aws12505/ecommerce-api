<?php
// FILE: app/Http/Requests/V1/UpdateAddressRequest.php

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\UserAddress;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'label' => 'nullable|string|max:255',
            'first_name' => 'sometimes|required|string|max:255',
            'last_name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address_line_1' => 'sometimes|required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'city' => 'sometimes|required|string|max:255',
            'governorate' => 'sometimes|required|string|max:255',
            'country' => 'sometimes|required|string',
            'is_default' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            'address_line_1.required' => 'Address line 1 is required.',
            'city.required' => 'City is required.',
            'governorate.required' => 'Governorate is required.',
            'phone.max' => 'Phone number must not exceed 20 characters.',
        ];
    }
}
