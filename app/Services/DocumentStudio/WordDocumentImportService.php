<?php

namespace App\Services\DocumentStudio;

use App\Models\DocumentAsset;
use App\Models\DocumentTemplate;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WordDocumentImportService
{
    public function __construct(private readonly AssetSecurityService $security) {}

    public function import(UploadedFile $file, DocumentTemplate $template, User $user): string
    {
        $this->security->validateDocxUpload($file);
        $directory = storage_path('app/temp/document-studio/docx-'.Str::uuid());
        File::ensureDirectoryExists($directory);
        $assets = [];
        try {
            File::copy($file->getRealPath(), $directory.'/input.docx');
            $process = NodeProcess::make([
                base_path('scripts/document-studio-import-docx.mjs'),
                $directory.'/input.docx', $directory.'/output.json',
            ], 30, 256);
            $process->mustRun();
            $result = json_decode(File::get($directory.'/output.json'), true, 32, JSON_THROW_ON_ERROR);
            $html = $result['html'];
            foreach ($result['images'] as $index => $image) {
                $extension = match ($image['mime']) {
                    'image/png' => 'png', 'image/jpeg' => 'jpg', 'image/webp' => 'webp',
                    default => throw ValidationException::withMessages(['file' => 'DOCX images must be PNG, JPEG, or WEBP.']),
                };
                $contents = base64_decode($image['contents'], true);
                if ($contents === false || strlen($contents) > AssetSecurityService::MAX_IMAGE_BYTES) {
                    throw ValidationException::withMessages(['file' => 'An embedded DOCX image is invalid or too large.']);
                }
                $path = $directory.'/image-'.$index.'.'.$extension;
                File::put($path, $contents);
                $asset = $this->security->storeUploadedFile(new UploadedFile($path, basename($path), null, null, true), DocumentAsset::TYPE_IMAGE, $template->id, $user);
                $assets[] = $asset;
                $html = str_replace('mcare-image-'.$index.'"', route('admin.document-studio.assets.stream', $asset).'"', $html);
            }

            return $this->security->sanitizeHtml($html);
        } catch (\Throwable $exception) {
            foreach ($assets as $asset) {
                Storage::disk($asset->disk)->delete($asset->file_path);
                $asset->delete();
            }
            report($exception);
            throw ValidationException::withMessages(['file' => 'DOCX import failed. Use a supported document without macros or external links.']);
        } finally {
            File::deleteDirectory($directory);
        }
    }
}
