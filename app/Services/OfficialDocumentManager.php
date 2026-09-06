<?php

namespace App\Services;

use App\Contracts\OfficialDocumentRenderer;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\TraineeCompetencyRecord;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class OfficialDocumentManager
{
    public function __construct(
        private readonly CompletionEligibilityService $eligibility,
        private readonly OfficialDocumentRenderer $renderer,
    ) {}

    public function queue(
        EnrollmentApplication $application,
        string $type,
        User $admin,
    ): OfficialDocument {
        $type = $this->validatedType($type);
        $this->assertEligible($application);

        $document = DB::transaction(function () use ($application, $type, $admin): OfficialDocument {
            $current = $this->currentDocumentQuery($application, $type)
                ->lockForUpdate()
                ->first();

            if ($current && $current->status !== OfficialDocument::STATUS_FAILED) {
                return $current;
            }

            return $this->createVersion($application, $type, $admin);
        });

        if ($document->status === OfficialDocument::STATUS_QUEUED) {
            $document = $this->generateNow($document);
        }

        AdminActivityLog::record($admin, 'admin.official-document.generated', $document, [
            'requested_type' => $type,
            'generated_type' => $document->type,
            'application_id' => $application->id,
            'version' => $document->version,
        ]);

        return $document;
    }

    public function reissue(
        EnrollmentApplication $application,
        string $type,
        User $admin,
        string $reason,
    ): OfficialDocument {
        $type = $this->validatedType($type);
        $this->assertEligible($application);

        $document = DB::transaction(function () use ($application, $type, $admin, $reason): OfficialDocument {
            $current = $this->currentDocumentQuery($application, $type)
                ->lockForUpdate()
                ->first();

            if ($current) {
                $current->update([
                    'status' => OfficialDocument::STATUS_REVOKED,
                    'revoked_at' => now(),
                    'revocation_reason' => $reason,
                ]);
            }

            return $this->createVersion($application, $type, $admin);
        });

        $document = $this->generateNow($document);
        AdminActivityLog::record($admin, 'admin.official-document.reissued', $document, [
            'requested_type' => $type,
            'generated_type' => $document->type,
            'application_id' => $application->id,
            'version' => $document->version,
            'reason' => $reason,
        ]);

        return $document;
    }

    public function generateNow(OfficialDocument $document): OfficialDocument
    {
        $this->validatedType($document->type);

        $document = DB::transaction(function () use ($document): OfficialDocument {
            $locked = OfficialDocument::query()->lockForUpdate()->findOrFail($document->id);

            if (in_array($locked->status, [
                OfficialDocument::STATUS_GENERATED,
                OfficialDocument::STATUS_RELEASED,
                OfficialDocument::STATUS_DOWNLOADED,
            ], true)) {
                return $locked;
            }

            if ($locked->status === OfficialDocument::STATUS_REVOKED) {
                throw ValidationException::withMessages([
                    'document' => 'A revoked document cannot be generated.',
                ]);
            }

            $locked->update([
                'status' => OfficialDocument::STATUS_GENERATING,
                'generation_error' => null,
            ]);

            return $locked;
        });

        if ($document->status !== OfficialDocument::STATUS_GENERATING) {
            return $document;
        }

        try {
            $pdf = $this->renderer->render($document);
            $disk = $document->storage_disk;
            $path = $this->documentPath($document);

            if (! Storage::disk($disk)->put($path, $pdf)) {
                throw new \RuntimeException('The generated PDF could not be stored.');
            }

            return DB::transaction(function () use ($document, $path, $pdf): OfficialDocument {
                $locked = OfficialDocument::query()->lockForUpdate()->findOrFail($document->id);
                $locked->update([
                    'status' => OfficialDocument::STATUS_GENERATED,
                    'file_path' => $path,
                    'sha256' => hash('sha256', $pdf),
                    'generated_at' => now(),
                    'generation_error' => null,
                ]);

                TraineeCompetencyRecord::query()
                    ->where('enrollment_application_id', $locked->enrollment_application_id)
                    ->whereNull('locked_at')
                    ->update(['locked_at' => now()]);

                return $locked->fresh();
            });
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            OfficialDocument::query()->whereKey($document->id)->update([
                'status' => OfficialDocument::STATUS_FAILED,
                'generation_error' => str($exception->getMessage())->limit(2000)->toString(),
            ]);

            throw ValidationException::withMessages([
                'document' => 'The official document could not be generated. '.$this->publicRenderError($exception),
            ]);
        }
    }

    public function generateTorForExport(
        EnrollmentApplication $application,
        User $admin,
    ): ?OfficialDocument {
        if (! $this->eligibility->evaluate($application)['eligible']) {
            return null;
        }

        $document = DB::transaction(function () use ($application, $admin): OfficialDocument {
            $current = $this->currentDocumentQuery($application, OfficialDocument::TYPE_TOR)
                ->lockForUpdate()
                ->first();

            return $current ?? $this->createVersion($application, OfficialDocument::TYPE_TOR, $admin);
        });

        return $this->generateNow($document);
    }

    public function releaseCotc(OfficialDocument $document, User $admin): OfficialDocument
    {
        $released = DB::transaction(function () use ($document, $admin): OfficialDocument {
            $locked = OfficialDocument::query()->lockForUpdate()->findOrFail($document->id);

            if ($locked->type !== OfficialDocument::TYPE_COTC
                || $locked->status !== OfficialDocument::STATUS_GENERATED) {
                throw ValidationException::withMessages([
                    'document' => 'Only a generated COTC can be released to a trainee.',
                ]);
            }

            $locked->update([
                'status' => OfficialDocument::STATUS_RELEASED,
                'released_by_id' => $admin->id,
                'released_at' => now(),
            ]);

            return $locked->fresh();
        });

        AdminActivityLog::record($admin, 'admin.cotc.released', $released, [
            'application_id' => $released->enrollment_application_id,
            'document_number' => $released->document_number,
        ]);

        return $released;
    }

    private function createVersion(
        EnrollmentApplication $application,
        string $type,
        User $admin,
    ): OfficialDocument {
        $lastVersion = OfficialDocument::query()
            ->where('enrollment_application_id', $application->id)
            ->where('type', $type)
            ->max('version') ?? 0;
        $version = $lastVersion + 1;
        $year = $application->batch?->year ?? now()->year;
        $prefix = strtoupper((string) config('official_documents.download_name_prefix', 'MCARE'));
        $documentNumber = sprintf(
            '%s-%s-%s-%05d-V%d',
            $prefix,
            strtoupper($type),
            $year,
            $application->id,
            $version,
        );

        return OfficialDocument::create([
            'enrollment_application_id' => $application->id,
            'training_batch_id' => $application->training_batch_id,
            'type' => $type,
            'version' => $version,
            'document_number' => $documentNumber,
            'status' => OfficialDocument::STATUS_QUEUED,
            'storage_disk' => config('official_documents.disk', 'local'),
            'template_version' => config('official_documents.template_version', '1.0'),
            'generated_by_id' => $admin->id,
            'metadata' => [
                'eligibility' => $this->eligibility->evaluate($application),
                'trainee_name' => trim("{$application->first_name} {$application->middle_name} {$application->last_name}"),
            ],
        ]);
    }

    private function currentDocumentQuery(EnrollmentApplication $application, string $type)
    {
        return OfficialDocument::query()
            ->where('enrollment_application_id', $application->id)
            ->where('type', $type)
            ->where('status', '!=', OfficialDocument::STATUS_REVOKED)
            ->latest('version');
    }

    private function assertEligible(EnrollmentApplication $application): void
    {
        if ($application->learning_status === EnrollmentApplication::LEARNING_GRADUATED) {
            return;
        }

        $eligibility = $this->eligibility->evaluate($application);

        if ($eligibility['eligible']) {
            return;
        }

        $blocked = collect($eligibility['checks'])
            ->where('passed', false)
            ->pluck('label')
            ->implode(', ');

        throw ValidationException::withMessages([
            'eligibility' => 'Document generation is blocked: '.$blocked.'.',
        ]);
    }

    private function validatedType(mixed $type): string
    {
        return validator(['type' => $type], [
            'type' => ['required', Rule::in(OfficialDocument::supportedTypes())],
        ])->validate()['type'];
    }

    private function documentPath(OfficialDocument $document): string
    {
        return sprintf(
            'official-documents/%s/%d/%s.pdf',
            $document->type,
            $document->training_batch_id ?? 0,
            strtolower($document->document_number),
        );
    }

    private function publicRenderError(Throwable $exception): string
    {
        $message = $exception->getMessage();

        if (str_contains($message, 'command not found')
            || str_contains($message, 'Browser PDF rendering needs Node.js')) {
            return 'This server cannot run the browser PDF engine. Official documents now use the PHP PDF engine after deploy, or set OFFICIAL_DOCUMENT_PDF_ENGINE=fpdf.';
        }

        return str($message)->limit(240)->toString();
    }
}
