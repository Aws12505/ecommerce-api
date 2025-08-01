<?php
// app/Http/Requests/AttachProductToLineRequest.php

namespace App\Http\Requests\V1\Lines;

use Illuminate\Foundation\Http\FormRequest;

class AttachProductToLineRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'product_id' => 'required|exists:products,id',
            'sort_order' => 'integer|min:0',
        ];
    }
}
