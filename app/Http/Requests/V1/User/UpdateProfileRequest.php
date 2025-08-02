<?php

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->user()->id)
            ],
            'currency' => [
                'sometimes',
                'required',
                'string',
                'size:3',
                'exists:currencies,code',
                Rule::exists('currencies', 'code')->where(function ($query) {
                    return $query->where('is_active', true);
                }),
            ]
        ];
    }

    public function messages(): array
    {
        return [
            'currency.exists' => 'Invalid or inactive currency code.',
            'currency.size' => 'Currency code must be exactly 3 characters.',
        ];
    }
}
