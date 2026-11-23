<?php

namespace App\Services\DocumentStudio;

use App\Contracts\OfficialDocumentRenderer;
use App\Models\DocumentAsset;
use App\Models\DocumentTemplateVersion;
use App\Models\OfficialDocument;
use App\Services\FpdfOfficialDocumentRenderer;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DocumentStudioRenderer implements OfficialDocumentRenderer
{
    public function __construct(
        private readonly FpdfOfficialDocumentRenderer $fallbackRenderer,
    ) {}

    /**
     * Render an official document to PDF string.
     */
    public function render(OfficialDocument $document): string
    {
        // 1. Check if pinned to a specific template version
        $version = $document->documentTemplateVersion;

        // If no custom studio version is active, or if it is the unedited initial version 1 of a system default, fallback to FPDF
        $isInitialUneditedDefault = app(DocumentStudioService::class)->isBaselineVersion($version);
        // The established renderer supplies the mandatory test-record watermark.
        if (! $version || $isInitialUneditedDefault || $document->application?->graduation_test_override) {
            return $this->fallbackRenderer->render($document);
        }

        // Resolve authoritative values
        $values = PlaceholderRegistry::resolveForEnrollment(
            $document->application,
            $document->type,
            $document
        );

        try {
            return $this->renderVersionToPdf($version, $values);
        } catch (\Throwable $e) {
            Log::warning('Document Studio used the institutional document fallback.', [
                'document_id' => $document->id,
                'template_version_id' => $version->id,
                'reason' => 'studio_render_failed',
            ]);

            if ($document->exists) {
                $document->metadata = array_merge($document->metadata ?? [], [
                    'rendered_via_fallback' => true,
                    'fallback_renderer' => 'fpdf',
                    'fallback_reason' => 'studio_render_failed',
                ]);
            }

            // Graceful baseline fallback
            return $this->fallbackRenderer->render($document);
        }
    }

    /**
     * Render a DocumentTemplateVersion with data dictionary to PDF binary string.
     */
    public function renderVersionToPdf(DocumentTemplateVersion $version, array $values): string
    {
        if (! $version->verifyChecksum()) {
            throw new RuntimeException('Template integrity check failed.');
        }
        $state = $version->editor_state;
        $engine = $version->engine;
        $this->assertRenderableContent($state + ['engine' => $engine]);

        $payload = [
            'template_id' => $version->document_template_id,
            'engine' => $engine,
            'source_layout' => $state['source_layout'] ?? null,
            'page' => $version->page_settings ?? $state['page'] ?? null,
            'schemas' => $state['schemas'] ?? null,
            'elements' => $state['elements'] ?? null,
            'background' => $state['background'] ?? null,
            'basePdf' => $state['basePdf'] ?? null,
            'html' => $state['html'] ?? $state['content'] ?? $state['body'] ?? null,
            'values' => $values,
        ];

        $payload['base_pdf_asset_id'] = $state['base_pdf_asset_id'] ?? null;
        $payload['background_asset_id'] = $state['background_asset_id'] ?? null;
        if (!empty($state['base_pdf_url']) && file_exists(public_path(ltrim($state['base_pdf_url'], '/')))) {
            $payload['base_pdf_path'] = public_path(ltrim($state['base_pdf_url'], '/'));
        }

        return $this->executeRenderProcess($this->resolveAssets($payload, $state['_asset_integrity'] ?? null));
    }

    /**
     * Render a raw template payload (for design previews) to PDF binary string.
     */
    public function renderPayloadToPdf(array $payload): string
    {
        $clean = app(AssetSecurityService::class)->sanitizeEditorState($payload);
        $this->assertRenderableContent($clean);
        $clean['preview'] = true;
        if (!empty($clean['base_pdf_url']) && file_exists(public_path(ltrim($clean['base_pdf_url'], '/')))) {
            $clean['base_pdf_path'] = public_path(ltrim($clean['base_pdf_url'], '/'));
        }

        return $this->executeRenderProcess($this->resolveAssets($clean));
    }

    private function resolveAssets(array $payload, ?array $expectedHashes = null): array
    {
        $security = app(AssetSecurityService::class);
        $ids = $security->assetIds($payload);
        if ($ids && empty($payload['template_id'])) {
            throw new RuntimeException('A template is required for asset resolution.');
        }
        $hashes = $ids ? $security->assetIntegrity($payload, (int) $payload['template_id']) : [];
        if ($expectedHashes !== null && $expectedHashes != $hashes) {
            throw new RuntimeException('Published asset integrity check failed.');
        }
        $payload['asset_paths'] = [];
        foreach ($ids as $id) {
            $asset = DocumentAsset::findOrFail($id);
            if ($asset->disk !== AssetSecurityService::DISK
                || ! str_starts_with($asset->file_path, AssetSecurityService::STORAGE_PATH.'/')
                || str_contains($asset->file_path, '..')) {
                throw new RuntimeException('Invalid asset storage location.');
            }
            $path = Storage::disk($asset->disk)->path($asset->file_path);
            $payload['asset_paths'][$id] = $path;
            if ($id === (int) ($payload['base_pdf_asset_id'] ?? 0)) {
                if ($asset->type !== DocumentAsset::TYPE_PDF_BASE) {
                    throw new RuntimeException('Invalid base PDF asset.');
                }
                $payload['basePdfVerifiedPath'] = $path;
            }
            if ($id === (int) ($payload['background_asset_id'] ?? 0)) {
                $payload['backgroundVerifiedPath'] = $path;
            }
        }

        return $payload;
    }

    private function assertRenderableContent(array $state): void
    {
        if (! app(DocumentStudioService::class)->hasActualContent($state)) {
            throw new RuntimeException('This document layout has no printable content. Create a draft from the current MCARE design or add content before previewing.');
        }
    }

    private function executeRenderProcess(array $payload): string
    {
        $tempDir = storage_path('app/temp/document-studio');
        if (! File::exists($tempDir)) {
            File::makeDirectory($tempDir, 0755, true);
        }

        $id = Str::uuid()->toString();
        $inputFile = $tempDir.'/'.$id.'_in.json';
        $outputFile = $tempDir.'/'.$id.'_out.pdf';

        try {
            File::put($inputFile, json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            $scriptPath = base_path('scripts/document-studio-render.mjs');

            $process = NodeProcess::make([
                $scriptPath,
                '--input',
                $inputFile,
                '--output',
                $outputFile,
            ]);

            $process->run();

            if (! $process->isSuccessful() || ! File::exists($outputFile)) {
                $errorOutput = $process->getErrorOutput() ?: $process->getOutput();
                throw new RuntimeException('Document Studio rendering process failed: '.$errorOutput);
            }

            $bytes = File::get($outputFile);
            if (! str_starts_with($bytes, '%PDF-') || strlen($bytes) > 50 * 1024 * 1024) {
                throw new RuntimeException('Renderer returned an invalid or oversized PDF.');
            }

            return $bytes;
        } finally {
            if (File::exists($inputFile)) {
                File::delete($inputFile);
            }
            if (File::exists($outputFile)) {
                File::delete($outputFile);
            }
        }
    }
}
