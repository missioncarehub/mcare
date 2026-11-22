<?php

namespace App\Http\Requests\DocumentStudio;

use App\Models\DocumentTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

class CreateTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', DocumentTemplate::class);
    }

    protected function prepareForValidation(): void
    {
        if ($this->file('source_file') instanceof UploadedFile) {
            $mime = $this->file('source_file')->getMimeType();
            $this->merge(['engine' => match ($mime) {
                'application/pdf' => DocumentTemplate::ENGINE_PDFME,
                'image/png', 'image/jpeg' => DocumentTemplate::ENGINE_FABRIC,
                default => DocumentTemplate::ENGINE_RK_WORD,
            }]);
        } elseif (in_array($this->input('source'), ['current', 'blank'], true)) {
            $this->merge(['engine' => $this->input('document_kind') === DocumentTemplate::KIND_COTC
                ? DocumentTemplate::ENGINE_FABRIC
                : DocumentTemplate::ENGINE_PDFME]);
        } elseif ($this->input('format') === 'word' || $this->hasFile('docx_file')) {
            $this->merge(['engine' => DocumentTemplate::ENGINE_RK_WORD]);
        } elseif ($this->input('format') === 'standard' || $this->hasFile('base_pdf')) {
            $this->merge(['engine' => DocumentTemplate::ENGINE_PDFME]);
        } elseif ($this->input('format') === 'certificate' || $this->hasFile('background_image')) {
            $this->merge(['engine' => DocumentTemplate::ENGINE_FABRIC]);
        } elseif (! $this->filled('engine')) {
            if ($this->input('document_kind') === DocumentTemplate::KIND_COTC) {
                $this->merge(['engine' => DocumentTemplate::ENGINE_FABRIC]);
            } else {
                $this->merge(['engine' => DocumentTemplate::ENGINE_PDFME]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'document_kind' => ['required', Rule::in(DocumentTemplate::supportedKinds())],
            'engine' => ['nullable', Rule::in(DocumentTemplate::supportedEngines())],
            'format' => ['nullable', 'string', Rule::in(['standard', 'word', 'certificate'])],
            'training_program_id' => ['nullable', 'integer', 'exists:training_programs,id'],
            'source' => ['nullable', Rule::in(['current', 'upload', 'blank'])],
            'source_file' => ['nullable', 'required_if:source,upload', 'prohibited_unless:source,upload', 'file', 'mimes:pdf,docx,png,jpg,jpeg', 'max:15360'],
            'pdf_mode' => ['nullable', Rule::in(['editable', 'background'])],
            'regions' => ['nullable', 'json', 'max:250000'],
            'operation' => ['nullable', 'required_if:source,upload', 'uuid'],
            'base_pdf' => ['nullable', 'file', 'mimes:pdf', 'max:15360'], // 15MB
            'docx_file' => ['nullable', 'file', 'mimes:docx', 'max:15360'],
            'background_image' => ['nullable', 'file', 'mimes:png,jpg,jpeg', 'max:15360'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $sources = collect(['source_file', 'base_pdf', 'docx_file', 'background_image'])
                ->filter(fn ($field) => $this->hasFile($field))->count();

            if ($sources > 1) {
                $validator->errors()->add($this->hasFile('source_file') ? 'source_file' : 'base_pdf', 'Please provide only one source file.');
            }
            if (in_array($this->input('source'), ['current', 'blank'], true) && $sources > 0) {
                $validator->errors()->add('source', 'Choose Upload file to use a source file.');
            }
            if ($this->input('source') === 'current' && $this->input('document_kind') === DocumentTemplate::KIND_GENERAL) {
                $validator->errors()->add('document_kind', 'Choose an official document, or start from an upload or blank page.');
            }
            if ($this->filled('regions') && ! $validator->errors()->has('regions')) {
                $regions = json_decode($this->input('regions'), true);
                $checks = validator(['regions' => $regions], [
                    'regions' => ['array', 'max:100'],
                    'regions.*' => ['array:page,x,y,width,height,color,content'],
                    'regions.*.page' => ['required', 'integer', 'min:1', 'max:20'],
                    'regions.*.x' => ['required', 'numeric', 'min:0', 'max:1000'],
                    'regions.*.y' => ['required', 'numeric', 'min:0', 'max:1000'],
                    'regions.*.width' => ['required', 'numeric', 'gt:0', 'max:1000'],
                    'regions.*.height' => ['required', 'numeric', 'gt:0', 'max:1000'],
                    'regions.*.color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
                    'regions.*.content' => ['required', 'string', 'max:2000'],
                ]);
                $checks->fails();
                foreach ($checks->errors()->messages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }

            $format = $this->input('format');
            if ($format === 'standard' && ($this->hasFile('docx_file') || $this->hasFile('background_image'))) {
                $validator->errors()->add('format', 'Standard document format only accepts PDF files.');
            }
            if ($format === 'word' && ($this->hasFile('base_pdf') || $this->hasFile('background_image'))) {
                $validator->errors()->add('format', 'Word document format only accepts .docx files.');
            }
            if ($format === 'certificate' && ($this->hasFile('base_pdf') || $this->hasFile('docx_file'))) {
                $validator->errors()->add('format', 'Certificate format accepts image files (PNG/JPG).');
            }
        });
    }
}
