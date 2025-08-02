<?php
// app/Http/Requests/V1/Currency/UpdateCurrencyRequest.php

namespace App\Http\Requests\V1\Currency;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCurrencyRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $currencyId = $this->route('currency')->id;

        return [
            'code' => [
                'required',
                'string',
                'size:3',
                Rule::unique('currencies', 'code')->ignore($currencyId)
            ],
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:10',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'code.required' => 'Currency code is required.',
            'code.size' => 'Currency code must be exactly 3 characters.',
            'code.unique' => 'This currency code already exists.',
            'name.required' => 'Currency name is required.',
            'symbol.required' => 'Currency symbol is required.',
        ];
    }

    protected function prepareForValidation()
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper($this->code),
            ]);
        }
    }
}
