<?php

namespace App\Http\Requests\V1\Currency;

use Illuminate\Foundation\Http\FormRequest;

class StoreCurrencyRateRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'from_currency' => 'required|string|size:3|exists:currencies,code',
            'to_currency'   => 'required|string|size:3|exists:currencies,code|different:from_currency',
            'rate'          => 'required|numeric|min:0.000001',
        ];
    }
}
