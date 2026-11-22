<?php

namespace App\Services\DocumentStudio;

use App\Models\DocumentAsset;
use App\Models\DocumentTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TemplateImportExportService
{
    public function __construct(
        private readonly AssetSecurityService $assetSecurity,
        private readonly DocumentStudioService $studioService,
    ) {}

    /**
     * Export template to portable JSON representation.
     */
    public function export(DocumentTemplate $template): array
    {
        $template->loadMissing(['activeVersion']);
        $activeVersion = $template->activeVersion;
        $state = $activeVersion?->editor_state ?? $template->draft_state;
        $hashes = $this->assetSecurity->assetIntegrity($state, $template->id);
        if ($activeVersion && (! $activeVersion->verifyChecksum() || ($state['_asset_integrity'] ?? []) != $hashes)) {
            throw ValidationException::withMessages(['template' => 'Published template integrity check failed.']);
        }
        $assets = [];
        $total = 0;
        foreach ($hashes as $id => $hash) {
            $asset = DocumentAsset::findOrFail($id);
            $bytes = Storage::disk($asset->disk)->get($asset->file_path);
            $total += strlen($bytes);
            if ($total > 20 * 1024 * 1024) {
                throw ValidationException::withMessages(['template' => 'Template assets exceed the 20 MB export limit.']);
            }
            $assets[] = ['id' => $id, 'type' => $asset->type, 'extension' => pathinfo($asset->file_path, PATHINFO_EXTENSION), 'sha256' => $hash, 'contents' => base64_encode($bytes)];
        }

        return [
            '$schema' => 'https://mcarehub.com/schemas/document-template-v1.json',
            'exported_at' => now()->toIso8601String(),
            'name' => $template->name,
            'document_kind' => $template->document_kind,
            'engine' => $template->engine,
            'version_number' => $activeVersion?->version_number ?? 1,
            'schema_version' => $activeVersion?->schema_version ?? '1.0',
            'page_settings' => $activeVersion?->page_settings ?? $template->draft_state['page'] ?? null,
            'editor_state' => $state,
            'assets' => $assets,
        ];
    }

    /**
     * Import a template from parsed JSON payload as an inactive editable draft.
     */
    public function import(array $payload, ?User $user = null): DocumentTemplate
    {
        if (empty($payload['name']) || ! is_string($payload['name']) || mb_strlen($payload['name']) > 139) {
            throw ValidationException::withMessages(['file' => 'Template name is missing or invalid.']);
        }

        $kind = $payload['document_kind'] ?? DocumentTemplate::KIND_GENERAL;
        if (! is_string($kind) || ! in_array($kind, DocumentTemplate::supportedKinds(), true)) {
            throw ValidationException::withMessages(['file' => 'Unsupported document kind.']);
        }

        $engine = $payload['engine'] ?? DocumentTemplate::ENGINE_PDFME;
        if (! is_string($engine) || ! in_array($engine, DocumentTemplate::supportedEngines(), true)) {
            throw ValidationException::withMessages(['file' => 'Unsupported engine.']);
        }

        $editorState = $payload['editor_state'] ?? [];
        if (! is_array($editorState)) {
            throw ValidationException::withMessages(['file' => 'Invalid or malformed editor state.']);
        }

        $bundledAssets = $payload['assets'] ?? [];
        if (! is_array($bundledAssets) || count($bundledAssets) > 50) {
            throw ValidationException::withMessages(['file' => 'Invalid template assets.']);
        }
        $stored = [];
        try {
            return DB::transaction(function () use ($payload, $kind, $engine, $editorState, $bundledAssets, $user, &$stored) {
                $template = $this->studioService->createTemplate([
                    'name' => $payload['name'].' (Imported)', 'document_kind' => $kind,
                    'engine' => $engine, 'is_active' => false,
                ], $user);
                $mapping = [];
                $total = 0;
                foreach ($bundledAssets as $entry) {
                    if (! is_array($entry) || ! isset($entry['id'], $entry['contents'], $entry['extension'], $entry['sha256'], $entry['type'])
                        || ! is_numeric($entry['id']) || (int) $entry['id'] < 1 || isset($mapping[(int) $entry['id']])
                        || ! is_string($entry['contents']) || strlen($entry['contents']) > 21 * 1024 * 1024
                        || ! in_array($entry['type'], [DocumentAsset::TYPE_IMAGE, DocumentAsset::TYPE_LOGO, DocumentAsset::TYPE_SIGNATURE, DocumentAsset::TYPE_PDF_BASE], true)
                        || ! in_array($entry['extension'], ['png', 'jpg', 'jpeg', 'svg', 'pdf'], true)) {
                        throw ValidationException::withMessages(['file' => 'Invalid bundled asset.']);
                    }
                    $bytes = base64_decode($entry['contents'], true);
                    $total += is_string($bytes) ? strlen($bytes) : 0;
                    if (! is_string($bytes) || $total > 20 * 1024 * 1024 || ! is_string($entry['sha256'])
                        || ! hash_equals(hash('sha256', $bytes), $entry['sha256'])) {
                        throw ValidationException::withMessages(['file' => 'Bundled asset integrity check failed.']);
                    }
                    $temp = tempnam(sys_get_temp_dir(), 'mcare-studio-import-');
                    try {
                        file_put_contents($temp, $bytes);
                        $asset = $this->assetSecurity->storeUploadedFile(new UploadedFile($temp, 'asset.'.$entry['extension'], null, null, true), $entry['type'], $template->id, $user);
                        $stored[] = $asset;
                        $mapping[(int) $entry['id']] = $asset->id;
                    } finally {
                        if (is_file($temp)) {
                            unlink($temp);
                        }
                    }
                }
                unset($editorState['_asset_integrity']);
                $rewrite = function ($value, $key = '', $depth = 0) use (&$rewrite, $mapping) {
                    if ($depth > 30) {
                        throw ValidationException::withMessages(['file' => 'Template state is nested too deeply.']);
                    }
                    if (is_array($value)) {
                        foreach ($value as $childKey => $child) {
                            $value[$childKey] = $rewrite($child, (string) $childKey, $depth + 1);
                        }
                    } elseif (in_array($key, ['asset_id', 'assetId', 'base_pdf_asset_id', 'background_asset_id'], true)) {
                        if ($value === null) {
                            return null;
                        }
                        if (! isset($mapping[(int) $value])) {
                            throw ValidationException::withMessages(['file' => 'An asset is missing from the template bundle.']);
                        }
                        $value = $mapping[(int) $value];
                    } elseif (is_string($value)) {
                        $value = preg_replace_callback('~(?:https?://[^\s"<>]+)?/admin/document-studio/assets/(\d+)(?=[\s"<>]|$)~', function ($match) use ($mapping) {
                            if (! isset($mapping[(int) $match[1]])) {
                                throw ValidationException::withMessages(['file' => 'An asset is missing from the template bundle.']);
                            }

                            return '/admin/document-studio/assets/'.$mapping[(int) $match[1]];
                        }, $value);
                    }

                    return $value;
                };
                $state = $rewrite($editorState);

                return $this->studioService->saveDraft($template, $state, $template->draft_revision, $user);
            });
        } catch (\Throwable $exception) {
            foreach ($stored as $asset) {
                Storage::disk($asset->disk)->delete($asset->file_path);
            }
            throw $exception;
        }
    }
}
