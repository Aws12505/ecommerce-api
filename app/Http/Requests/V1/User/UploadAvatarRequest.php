<?php
// FILE: app/Http/Requests/V1/UploadAvatarRequest.php

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;

class UploadAvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ];
    }

    public function messages(): array
    {
        return [
            'avatar.max' => 'Avatar image must not exceed 2MB.',
            'avatar.mimes' => 'Avatar must be an image file (JPEG, PNG, JPG, GIF).',
        ];
    }
}
