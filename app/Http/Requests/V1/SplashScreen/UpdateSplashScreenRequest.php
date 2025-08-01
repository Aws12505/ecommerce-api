<?php
// app/Http/Requests/V1/SplashScreen/UpdateSplashScreenRequest.php

namespace App\Http\Requests\V1\SplashScreen;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\SplashScreen;
use Illuminate\Validation\Rule;

class UpdateSplashScreenRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'description' => 'nullable|string|max:1000',
            'type' => [
                'required',
                'string',
                Rule::in(array_keys(SplashScreen::getAvailableTypes()))
            ],
            'is_active' => 'boolean',
            'display_duration' => 'integer|min:1|max:10',
            'sort_order' => 'integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'target_audience' => [
                'nullable',
                'string',
                Rule::in(array_keys(SplashScreen::getAvailableAudiences()))
            ],
            'metadata' => 'nullable|array',
            'metadata.background_color' => 'nullable|string|max:7',
            'metadata.text_color' => 'nullable|string|max:7',
            'metadata.animation_type' => 'nullable|string|in:fade,slide,zoom,none',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'Splash screen title is required.',
            'image.image' => 'The file must be an image.',
            'image.mimes' => 'Image must be a file of type: jpeg, png, jpg, gif, webp.',
            'image.max' => 'Image size must not exceed 2MB.',
            'type.required' => 'Splash screen type is required.',
            'type.in' => 'Invalid splash screen type.',
            'display_duration.min' => 'Display duration must be at least 1 second.',
            'display_duration.max' => 'Display duration must not exceed 10 seconds.',
            'end_date.after' => 'End date must be after start date.',
        ];
    }
}
