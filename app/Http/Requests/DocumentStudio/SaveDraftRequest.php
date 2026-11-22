<?php

namespace App\Http\Requests\DocumentStudio;

use App\Services\DocumentStudio\AssetSecurityService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class SaveDraftRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('template');

        return $template && $this->user()->can('update', $template);
    }

    public function rules(): array
    {
        return [
            'draft_state' => ['required', 'array'],
            'draft_revision' => ['required', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                if ($this->has('draft_state') && is_array($this->input('draft_state'))) {
                    try {
                        $sanitized = app(AssetSecurityService::class)
                            ->sanitizeEditorState($this->input('draft_state'));
                        $this->merge(['draft_state' => $sanitized]);
                    } catch (ValidationException $e) {
                        foreach ($e->errors() as $key => $messages) {
                            foreach ($messages as $message) {
                                $validator->errors()->add($key, $message);
                            }
                        }
                    }
                }
            },
        ];
    }
}
