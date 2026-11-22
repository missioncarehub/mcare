<?php

namespace App\Http\Requests\DocumentStudio;

use App\Services\DocumentStudio\DocumentStudioService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class PublishVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $template = $this->route('template');

        return $template && $this->user()->can('publish', $template);
    }

    public function rules(): array
    {
        return [
            'changelog' => ['nullable', 'string', 'max:1000'],
            'make_active' => ['nullable', 'boolean'],
            'draft_state' => ['nullable', 'array'],
            'draft_revision' => ['required', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $template = $this->route('template');
                if ($template) {
                    $state = $this->input('draft_state') ?? $template->draft_state ?? [];
                    try {
                        app(DocumentStudioService::class)
                            ->validateTemplateState($template->engine, $state);
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
