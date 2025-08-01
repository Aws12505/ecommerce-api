<?php
// app/Http/Requests/V1/Lines/StoreLineRequest.php

namespace App\Http\Requests\V1\Lines;

use Illuminate\Foundation\Http\FormRequest;

class StoreLineRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|unique:lines,name',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Line name is required.',
            'name.unique' => 'A line with this name already exists.',
            'description.max' => 'Description cannot exceed 1000 characters.',
        ];
    }
}
