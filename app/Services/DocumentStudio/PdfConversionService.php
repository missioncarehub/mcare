<?php

namespace App\Services\DocumentStudio;

use App\Models\DocumentAsset;
use App\Models\DocumentTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PdfConversionService
{
    public function __construct(private readonly AssetSecurityService $security, private readonly DocumentStudioService $studio) {}

    public function convert(UploadedFile $file, array $data, array $regions, User $user, ?string $operation = null): DocumentTemplate
    {
        if ($operation && ! Cache::store('file')->add('studio-conversion-running:'.$user->id.':'.$operation, true, 900)) {
            throw ValidationException::withMessages(['file' => 'This conversion has already started. Reload before trying again.']);
        }
        $checkCancelled = function () use ($operation, $user): void {
            if ($operation && Cache::store('file')->get('studio-conversion-cancel:'.$user->id.':'.$operation)) {
                throw ValidationException::withMessages(['file' => 'Conversion cancelled. No draft was saved.']);
            }
        };
        $checkCancelled();
        $directory = storage_path('app/temp/document-studio-conversion/'.Str::uuid());
        File::makeDirectory($directory, 0700, true);
        $assets = [];
        try {
            return DB::transaction(function () use ($file, $data, $regions, $user, $directory, &$assets, $checkCancelled) {
                $template = $this->studio->createTemplate([
                    'name' => $data['name'], 'document_kind' => $data['document_kind'],
                    'engine' => DocumentTemplate::ENGINE_PDFME, 'is_active' => false,
                    'training_program_id' => $data['training_program_id'] ?? null,
                ], $user);
                // The existing validator must run before any extraction or rendering.
                $source = $this->security->storeUploadedFile($file, DocumentAsset::TYPE_PDF_BASE, $template->id, $user);
                $assets[] = $source;
                $source->update(['type' => DocumentAsset::TYPE_PDF_SOURCE]);
                File::put($directory.'/regions.json', json_encode($regions, JSON_THROW_ON_ERROR));
                $process = NodeProcess::make([
                    base_path('scripts/document-studio-convert-pdf.mjs'),
                    Storage::disk($source->disk)->path($source->file_path), $directory, $directory.'/regions.json',
                ], 90, 512);
                try {
                    $process->start();
                    while ($process->isRunning()) {
                        $checkCancelled();
                        $process->checkTimeout();
                        usleep(100000);
                    }
                    $checkCancelled();
                    if (! $process->isSuccessful()) {
                        throw new \RuntimeException('Conversion process failed.');
                    }
                } catch (\Throwable $exception) {
                    $process->stop(0);
                    if ($exception instanceof ValidationException) {
                        throw $exception;
                    }
                    // Never expose parser diagnostics (which can contain source text or paths).
                    report($exception);
                    throw ValidationException::withMessages(['file' => 'Conversion failed. Use a valid PDF with at most 20 pages and 500 text fields. For scanned pages, select manual regions on every page.']);
                }
                if (! File::exists($directory.'/result.json') || File::size($directory.'/result.json') > 5 * 1024 * 1024) {
                    throw ValidationException::withMessages(['file' => 'The conversion result exceeds the safety limit.']);
                }
                $result = json_decode(File::get($directory.'/result.json'), true, 32, JSON_THROW_ON_ERROR);
                $background = $this->security->storeUploadedFile(new UploadedFile($directory.'/background.pdf', 'converted-artwork.pdf', 'application/pdf', null, true), DocumentAsset::TYPE_PDF_BASE, $template->id, $user);
                $assets[] = $background;
                $state = $result['state'];
                $state['base_pdf_asset_id'] = $background->id;
                $template = $this->studio->saveDraft($template, $state, $template->draft_revision, $user);
                $checkCancelled();
                $template->forceFill(['conversion_report' => $result['report']])->save();

                return $template;
            });
        } catch (\Throwable $exception) {
            foreach ($assets as $asset) {
                Storage::disk($asset->disk)->delete($asset->file_path);
            }
            throw $exception;
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
