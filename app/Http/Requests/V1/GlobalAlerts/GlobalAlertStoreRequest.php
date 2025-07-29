<?php

namespace App\Http\Requests\V1\GlobalAlerts;

// app/Http/Requests/V1/GlobalAlertStoreRequest.php

use Illuminate\Foundation\Http\FormRequest;

class GlobalAlertStoreRequest extends FormRequest
{
    public function authorize() { return true; }
    public function rules()
    {
        return [
            'type' => 'required|string|max:32',
            'title' => 'required|string|max:255',
            'body' => 'nullable|string',
            'buttons' => 'nullable|array',
            'metadata' => 'nullable|array',
            'status' => 'required|in:active,inactive'
        ];
    }
}
