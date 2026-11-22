<?php

namespace App\Http\Requests\DocumentStudio;

use App\Models\DocumentAsset;
use App\Models\DocumentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DocumentTemplate::class);
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:15360'], // 15MB limit
            'type' => ['required', Rule::in(DocumentAsset::supportedTypes())],
            'document_template_id' => ['nullable', 'integer', 'exists:document_templates,id'],
        ];
    }
}
