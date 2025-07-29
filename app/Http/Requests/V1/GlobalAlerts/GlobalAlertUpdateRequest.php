<?php

namespace App\Http\Requests\V1\GlobalAlerts;

use Illuminate\Foundation\Http\FormRequest;

class GlobalAlertUpdateRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'type' => 'string|max:32',
            'title' => 'string|max:255',
            'body' => 'nullable|string',
            'buttons' => 'nullable|array',
            'metadata' => 'nullable|array',
            'status' => 'in:active,inactive'
        ];
    }
}