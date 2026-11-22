<?php

namespace App\Services\DocumentStudio;

use App\Models\AdminActivityLog;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\EnrollmentApplication;
use App\Models\User;
use App\Services\OfficialDocumentManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use ZipArchive;

class BatchGenerationCoordinator
{
    public function __construct(
        private readonly DocumentStudioRenderer $renderer,
    ) {}

    /**
     * Generate documents in batch for a list of enrollments using a specific template version.
     * Returns an array with results, item counts, and path to the generated zip or file.
     *
     * @param  Collection<int, EnrollmentApplication>  $enrollments
     * @return array{
     *   total: int,
     *   successful: int,
     *   failed: int,
     *   items: array<int, array<string, mixed>>,
     *   zip_path: string|null
     * }
     */
    public function generateBatch(
        DocumentTemplateVersion $version,
        Collection $enrollments,
        User $admin,
        bool $createZip = true,
    ): array {
        $tempDir = storage_path('app/temp/document-studio/batch-'.Str::uuid());
        File::makeDirectory($tempDir, 0755, true);

        $successful = 0;
        $failed = 0;
        $items = [];
        $pdfFiles = [];

        $isOfficial = in_array($version->template->document_kind, [DocumentTemplate::KIND_COTC, DocumentTemplate::KIND_TOR], true);

        try {
            foreach ($enrollments as $enrollment) {
                try {
                    $officialDoc = null;
                    if ($isOfficial) {
                        $manager = app(OfficialDocumentManager::class);
                        $officialDoc = $manager->findOrCreateQueued(
                            $enrollment,
                            $version->template->document_kind,
                            $admin,
                            $version,
                        );
                        // Use the same locks, eligibility checks, generation token, pinned
                        // version and fallback as individual issuance. Existing files are reused.
                        $officialDoc = $manager->generateNow($officialDoc);
                    }

                    if ($officialDoc) {
                        if (! filled($officialDoc->file_path) || ! Storage::disk($officialDoc->storage_disk)->exists($officialDoc->file_path)) {
                            throw new RuntimeException("Official document file {$officialDoc->file_path} is missing from storage.");
                        }
                        $pdfBinary = Storage::disk($officialDoc->storage_disk)->get($officialDoc->file_path);
                        $filename = sprintf('%s.pdf', strtolower($officialDoc->document_number));
                    } else {
                        $values = PlaceholderRegistry::resolveForEnrollment(
                            $enrollment,
                            $version->template->document_kind,
                            $officialDoc
                        );

                        $pdfBinary = $this->renderer->renderVersionToPdf($version, $values);

                        $slug = Str::slug($enrollment->first_name.' '.$enrollment->last_name) ?: 'trainee-'.$enrollment->id;
                        $filename = sprintf(
                            '%s-%s-%s.pdf',
                            strtoupper($version->template->document_kind),
                            $enrollment->enrollment_number ?: $enrollment->id,
                            $slug
                        );
                    }

                    $filePath = $tempDir.'/'.$filename;
                    File::put($filePath, $pdfBinary);
                    $pdfFiles[] = $filePath;

                    $successful++;
                    $items[] = [
                        'enrollment_id' => $enrollment->id,
                        'trainee_name' => trim($enrollment->first_name.' '.$enrollment->last_name),
                        'status' => 'success',
                        'filename' => $filename,
                    ];
                } catch (\Throwable $e) {
                    report($e);
                    $failed++;
                    $items[] = [
                        'enrollment_id' => $enrollment->id,
                        'trainee_name' => trim($enrollment->first_name.' '.$enrollment->last_name),
                        'status' => 'failed',
                        'error' => $e instanceof ValidationException
                            ? collect($e->errors())->flatten()->first()
                            : 'Document generation failed. Review the server log and retry.',
                    ];
                }
            }

            $zipPath = null;
            if ($createZip && count($pdfFiles) > 0) {
                $zipName = sprintf(
                    'MCARE-%s-Batch-v%d-%s.zip',
                    strtoupper($version->template->document_kind),
                    $version->version_number,
                    Str::uuid()->toString()
                );
                $zipFullPath = storage_path('app/document-studio/archives/'.$zipName);
                File::ensureDirectoryExists(dirname($zipFullPath));

                $zip = new ZipArchive;
                if ($zip->open($zipFullPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                    foreach ($pdfFiles as $pdfPath) {
                        $zip->addFile($pdfPath, basename($pdfPath));
                    }
                    if (! $zip->close()) {
                        throw new \RuntimeException('Could not finalize document archive.');
                    }
                    $zipPath = $zipFullPath;
                } else {
                    throw new \RuntimeException('Could not create document archive.');
                }
            }
        } finally {
            File::deleteDirectory($tempDir);
        }

        AdminActivityLog::record($admin, 'admin.document-studio.batch-generated', $version->template, [
            'template_id' => $version->document_template_id,
            'version_number' => $version->version_number,
            'total' => $enrollments->count(),
            'successful' => $successful,
            'failed' => $failed,
        ]);

        return [
            'total' => $enrollments->count(),
            'successful' => $successful,
            'failed' => $failed,
            'items' => $items,
            'zip_path' => $zipPath,
        ];
    }
}
