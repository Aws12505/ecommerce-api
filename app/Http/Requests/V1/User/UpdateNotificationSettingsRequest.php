<?php
// FILE: app/Http/Requests/V1/UpdateNotificationSettingsRequest.php

namespace App\Http\Requests\V1\User;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\NotificationSetting;

class UpdateNotificationSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'settings' => 'required|array',
            'settings.*.type' => 'required|in:' . implode(',', array_keys(NotificationSetting::TYPES)),
            'settings.*.category' => 'required|in:' . implode(',', array_keys(NotificationSetting::CATEGORIES)),
            'settings.*.enabled' => 'required|boolean',
            'settings.*.preferences' => 'nullable|array',
        ];
    }
}
