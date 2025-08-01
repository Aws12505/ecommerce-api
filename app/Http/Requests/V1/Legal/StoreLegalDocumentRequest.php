<?php
// app/Http/Requests/V1/Legal/StoreLegalDocumentRequest.php

namespace App\Http\Requests\V1\Legal;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\LegalDocument;
use Illuminate\Validation\Rule;

class StoreLegalDocumentRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in(array_keys(LegalDocument::getAvailableTypes())),
                'unique:legal_documents,type'
            ],
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'version' => 'nullable|string|max:50',
            'is_published' => 'boolean',
        ];
    }

    public function messages()
    {
        return [
            'type.required' => 'Document type is required.',
            'type.in' => 'Invalid document type.',
            'type.unique' => 'A document of this type already exists.',
            'title.required' => 'Document title is required.',
            'content.required' => 'Document content is required.',
        ];
    }
}
