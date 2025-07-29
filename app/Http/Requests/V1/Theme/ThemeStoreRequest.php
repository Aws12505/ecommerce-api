<?php

namespace App\Http\Requests\V1\Theme;

use Illuminate\Foundation\Http\FormRequest;

class ThemeStoreRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'name' => 'required|string|max:190',
            'palette' => 'required|array',
            'palette.light' => 'required|array',
            'palette.dark' => 'required|array'
        ];
    }
}
