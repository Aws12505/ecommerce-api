<?php

namespace App\Http\Requests\V1\Theme;

use Illuminate\Foundation\Http\FormRequest;

class ThemeUpdateRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'name' => 'string|max:190',
            'palette' => 'array',
            'palette.light' => 'array',
            'palette.dark' => 'array',
            'is_active' => 'boolean'
        ];
    }
}
