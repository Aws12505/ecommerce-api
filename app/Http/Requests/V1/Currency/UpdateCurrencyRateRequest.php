<?php

namespace App\Http\Requests\V1\Currency;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCurrencyRateRequest extends FormRequest
{
    public function authorize() { return true; }

    public function rules()
    {
        return [
            'rate' => 'required|numeric|min:0.000001',
        ];
    }
}
