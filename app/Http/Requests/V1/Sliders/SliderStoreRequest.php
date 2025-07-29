<?php

namespace App\Http\Requests\V1\Sliders;

use Illuminate\Foundation\Http\FormRequest;

class SliderStoreRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'title' => 'required|string|max:190',
            'subtitle' => 'nullable|string|max:190',
            'description' => 'nullable|string|max:2048',
            'image' => 'required|image|max:2048',
            'action_type' => 'required|in:url,product,category,page',
            'action_value' => 'nullable|string|max:255',
            'extra' => 'nullable|array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
