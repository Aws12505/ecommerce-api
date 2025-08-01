<?php
// app/Http/Requests/V1/Lines/UpdateLineRequest.php

namespace App\Http\Requests\V1\Lines;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLineRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $lineId = $this->route('line')->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('lines', 'name')->ignore($lineId)
            ],
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }
}
