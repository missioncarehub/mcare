<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Notifications\EnrollmentStatusUpdatedNotification;
use App\Services\AccountDeletionService;
use App\Services\RollingModuleReleaseService;
use App\Services\StaffVisiblePhoto;
use App\Services\TesdaRegistrationPdfService;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnrollmentReviewController extends Controller
{
    public function index(Request $request): View
    {
        $statuses = EnrollmentApplication::statuses();

        /*
         * Search values are bounded before they reach LIKE queries. Eloquent
         * parameter binding already protects query values from SQL injection;
         * this validation mainly prevents oversized/abusive search payloads.
         */
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
            'enrollment_state' => ['nullable', Rule::in(['open', 'continuous', 'upcoming', 'closed'])],
            'training_state' => ['nullable', Rule::in(['not_started', 'in_progress', 'completed'])],
        ]);

        $selectedStatus = trim((string) ($filters['status'] ?? ''));
        $search = trim((string) ($filters['search'] ?? ''));
        $batchId = isset($filters['batch_id']) ? (int) $filters['batch_id'] : null;
        $schedule = $filters['schedule'] ?? null;
        $enrollmentState = $filters['enrollment_state'] ?? null;
        $trainingState = $filters['training_state'] ?? null;

        $applicationsQuery = EnrollmentApplication::query()
            ->releasedForReview()
            ->with(['user', 'batch'])
            ->latest();

        if (array_key_exists($selectedStatus, $statuses)) {
            $applicationsQuery->where('status', $selectedStatus);
        }

        if ($batchId) {
            $applicationsQuery->where('training_batch_id', $batchId);
        }

        if ($schedule) {
            $applicationsQuery->where('schedule_preference', $schedule);
        }

        if ($enrollmentState) {
            $applicationsQuery->whereHas('batch', function ($batchQuery) use ($enrollmentState) {
                match ($enrollmentState) {
                    'open' => $batchQuery
                        ->where('is_active', true)
                        ->where(fn ($query) => $query->whereNull('enrollment_starts_at')->orWhere('enrollment_starts_at', '<=', now()))
                        ->where(fn ($query) => $query->where('is_continuous_enrollment', true)->orWhere('enrollment_ends_at', '>', now())),
                    'continuous' => $batchQuery
                        ->where('is_active', true)
                        ->where('is_continuous_enrollment', true)
                        ->where(fn ($query) => $query->whereNull('enrollment_starts_at')->orWhere('enrollment_starts_at', '<=', now())),
                    'upcoming' => $batchQuery
                        ->where('is_active', true)
                        ->where('enrollment_starts_at', '>', now()),
                    'closed' => $batchQuery
                        ->where(fn ($query) => $query->where('is_active', false)->orWhere(function ($deadline): void {
                            $deadline->where('is_continuous_enrollment', false)->where('enrollment_ends_at', '<=', now());
                        })),
                };
            });
        }

        if ($trainingState) {
            $applicationsQuery->whereHas('batch', function ($batchQuery) use ($trainingState) {
                match ($trainingState) {
                    'not_started' => $batchQuery
                        ->where(fn ($query) => $query->whereNull('training_starts_at')->orWhere('training_starts_at', '>', now())),
                    'in_progress' => $batchQuery
                        ->where('training_starts_at', '<=', now())
                        ->where(fn ($query) => $query->whereNull('training_ends_at')->orWhere('training_ends_at', '>', now())),
                    'completed' => $batchQuery->where('training_ends_at', '<=', now()),
                };
            });
        }

        if ($search !== '') {
            $applicationsQuery->where(function ($query) use ($search) {
                $query
                    ->where('email', 'like', "%{$search}%")
                    ->orWhere('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('contact_number', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $counts = EnrollmentApplication::query()
            ->releasedForReview()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('admin.enrollments.index', [
            'applications' => $applicationsQuery->paginate(10)->withQueryString(),
            'batches' => TrainingBatch::query()->orderByDesc('year')->orderBy('name')->get(),
            'batchId' => $batchId,
            'counts' => $counts,
            'enrollmentState' => $enrollmentState,
            'reviewableStatuses' => EnrollmentApplication::reviewableStatuses(),
            'search' => $search,
            'selectedStatus' => $selectedStatus,
            'schedule' => $schedule,
            'statuses' => $statuses,
            'totalApplications' => EnrollmentApplication::query()->releasedForReview()->count(),
            'trainingState' => $trainingState,
        ]);
    }

    public function show(EnrollmentApplication $enrollmentApplication): View
    {
        $this->ensureReleasedForReview($enrollmentApplication);

        $enrollmentApplication->load(['user', 'reviewer', 'batch', 'documentReviewer']);

        return view('admin.enrollments.show', [
            'application' => $enrollmentApplication,
            'pendingDocumentApprovals' => $this->pendingDocumentApprovals($enrollmentApplication),
            'reviewableStatuses' => EnrollmentApplication::reviewableStatuses(),
            'statuses' => EnrollmentApplication::statuses(),
        ]);
    }

    public function documentReview(EnrollmentApplication $enrollmentApplication): View
    {
        $this->ensureReleasedForReview($enrollmentApplication);

        $enrollmentApplication->load(['user', 'documentReviewer']);

        return view('admin.enrollments.document-review', [
            'application' => $enrollmentApplication,
            'documents' => $this->documentsForReview($enrollmentApplication),
            'pendingDocumentApprovals' => $this->pendingDocumentApprovals($enrollmentApplication),
        ]);
    }

    public function update(
        Request $request,
        EnrollmentApplication $enrollmentApplication,
        RollingModuleReleaseService $releases,
    ): RedirectResponse {
        $this->ensureReleasedForReview($enrollmentApplication);

        $validated = $request->validate([
            'status' => ['required', Rule::in(EnrollmentApplication::reviewableStatuses())],
            'admin_notes' => [
                Rule::requiredIf($request->input('status') === EnrollmentApplication::STATUS_DENIED),
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'admin_notes.required' => 'Add a clear note before denying an application.',
        ]);

        $pendingDocuments = $this->pendingDocumentApprovals($enrollmentApplication);
        $reviewWasSubmitted = $enrollmentApplication->documents_reviewed_at !== null
            || filled($enrollmentApplication->document_review);

        if (! $reviewWasSubmitted) {
            $message = 'Review the applicant documents first before saving a decision.';

            return redirect()
                ->route('admin.enrollments.show', $enrollmentApplication)
                ->with('error', $message)
                ->withErrors(['status' => $message])
                ->withInput();
        }

        if ($validated['status'] === EnrollmentApplication::STATUS_APPROVED) {
            $approvalIssues = [];

            if ($pendingDocuments !== []) {
                $approvalIssues[] = 'Document review cannot be completed while required documents are pending: '.implode(', ', $pendingDocuments).'. Accept every required document first.';
            }

            if ($enrollmentApplication->documents_reviewed_at === null) {
                $approvalIssues[] = 'Accept every required document and finish document review before approving this account.';
            }

            if (! $enrollmentApplication->hasEnrollmentPaymentClearance()) {
                $approvalIssues[] = 'Verify the required enrollment payment before approving this account.';
            }

            if ($approvalIssues !== []) {
                return redirect()
                    ->route('admin.enrollments.show', $enrollmentApplication)
                    ->with('error', $approvalIssues[0])
                    ->withErrors(['status' => implode(' ', $approvalIssues)])
                    ->withInput();
            }
        }

        $previousStatus = $enrollmentApplication->status;

        $enrollmentApplication->forceFill([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? null,
            'reviewed_at' => now(),
            'reviewed_by_id' => $request->user()->id,
            'learning_started_at' => $validated['status'] === EnrollmentApplication::STATUS_APPROVED
                ? ($enrollmentApplication->learning_started_at ?: now())
                : $enrollmentApplication->learning_started_at,
        ])->save();

        $newRole = $validated['status'] === EnrollmentApplication::STATUS_APPROVED
            ? 'trainee'
            : ($enrollmentApplication->user?->role === 'trainee' ? 'applicant' : $enrollmentApplication->user?->role);

        $enrollee = $enrollmentApplication->user;

        $enrollee?->forceFill([
            'applicant_status' => $validated['status'],
            'role' => $newRole,
        ])->save();

        if ($previousStatus !== EnrollmentApplication::STATUS_APPROVED
            && $validated['status'] === EnrollmentApplication::STATUS_APPROVED) {
            $releases->assignCurrentTo($enrollmentApplication->fresh());
        }

        $verificationSent = $this->sendEnrolleeVerificationLink(
            $enrollee,
            $validated['status'],
        );

        AdminActivityLog::record($request->user(), 'enrollment.review.updated', $enrollmentApplication, [
            'status' => $validated['status'],
            'applicant_email' => $enrollmentApplication->email,
            'verification_link_sent' => $verificationSent,
        ]);

        if ($previousStatus !== $validated['status'] && $enrollee) {
            try {
                $enrollee->notifyNow(
                    new EnrollmentStatusUpdatedNotification($enrollmentApplication->fresh()),
                );
            } catch (Throwable $exception) {
                // A mail outage must not undo an administrator's review decision.
                report($exception);
            }
        }

        $saved = 'Enrollment review decision saved.';
        if ($verificationSent) {
            $saved .= ' A verification link was emailed to '.$enrollee->email.'. The enrollee can log in after verifying that email.';
        }

        return redirect()
            ->route('admin.enrollments.show', $enrollmentApplication)
            ->with('saved', $saved);
    }

    public function destroy(
        Request $request,
        EnrollmentApplication $enrollmentApplication,
        AccountDeletionService $accounts,
    ): RedirectResponse {
        $this->ensureReleasedForReview($enrollmentApplication);

        $applicantName = trim($enrollmentApplication->last_name.', '.$enrollmentApplication->first_name);
        $user = $enrollmentApplication->user;

        if (! $user) {
            return redirect()
                ->route('admin.enrollments.index')
                ->withErrors([
                    'enrollment' => $applicantName.' has no linked account and cannot be deleted from this queue.',
                ]);
        }

        try {
            $deleted = $accounts->delete($user, $request->user());
        } catch (ValidationException $exception) {
            return redirect()
                ->route('admin.enrollments.index')
                ->withErrors($exception->errors());
        }

        return redirect()
            ->route('admin.enrollments.index')
            ->with('saved', "Enrollment for {$applicantName} ({$deleted['email']}) and related records were permanently removed.");
    }

    public function photo(EnrollmentApplication $enrollmentApplication, StaffVisiblePhoto $photos): BinaryFileResponse
    {
        $this->ensureReleasedForReview($enrollmentApplication);

        $enrollmentApplication->loadMissing('user');
        $located = $photos->locate($enrollmentApplication->user, $enrollmentApplication);
        abort_unless($located !== null, 404);

        $fallbackFilename = str($located['filename'])->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();

        return response()->file($located['path'], [
            'Content-Type' => $located['mime'],
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $located['filename'],
                $fallbackFilename
            ),
            'Cache-Control' => 'private, max-age=300',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function tesdaForm(
        Request $request,
        EnrollmentApplication $enrollmentApplication,
        TesdaRegistrationPdfService $pdfService,
    ): Response {
        $this->ensureReleasedForReview($enrollmentApplication);

        $validated = $request->validate([
            'disposition' => ['nullable', Rule::in(['inline', 'attachment'])],
        ]);
        $disposition = $validated['disposition'] ?? 'inline';

        // Shared PHP hosts (Hostinger) often enable zlib.output_compression at
        // the server level. When that fires on a PDF, the browser gets a body
        // that no longer matches Content-Length and Chrome shows
        // "Failed to load PDF document". Turn it off for this response and
        // ask any proxy in front to leave the bytes alone. PHPUnit relies on
        // its own outer buffer so we only clean nested ones.
        @ini_set('zlib.output_compression', 'Off');
        if (! app()->runningUnitTests()) {
            while (ob_get_level() > 1) {
                @ob_end_clean();
            }
        }

        $enrollmentApplication->loadMissing('batch');
        $pdf = $pdfService->generate($enrollmentApplication);
        $filename = $pdfService->filename($enrollmentApplication);
        $fallbackFilename = str($filename)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9._-]/', '-')
            ->toString();

        AdminActivityLog::record($request->user(), 'enrollment.tesda-form.generated', $enrollmentApplication, [
            'disposition' => $disposition,
            'applicant_email' => $enrollmentApplication->email,
        ]);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                $disposition === 'attachment'
                    ? HeaderUtils::DISPOSITION_ATTACHMENT
                    : HeaderUtils::DISPOSITION_INLINE,
                $filename,
                $fallbackFilename
            ),
            'Content-Length' => (string) strlen($pdf),
            'Content-Transfer-Encoding' => 'binary',
            'Content-Encoding' => 'identity',
            'Accept-Ranges' => 'none',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function updateDocumentReview(Request $request, EnrollmentApplication $enrollmentApplication): RedirectResponse
    {
        $this->ensureReleasedForReview($enrollmentApplication);

        $validated = $request->validate([
            'documents' => ['required', 'array'],
            'documents.*.status' => ['required', Rule::in(['unreviewed', 'accepted', 'replace', 'missing'])],
            'documents.*.note' => ['nullable', 'string', 'max:500'],
        ]);
        $review = [];

        // Only the five known enrollment documents can enter the stored review payload.
        foreach ($this->documentFields() as $key => $definition) {
            $submitted = $validated['documents'][$key] ?? [];
            $review[$key] = [
                'status' => $submitted['status'] ?? ($enrollmentApplication->{$definition['field']} ? 'unreviewed' : 'missing'),
                'note' => trim((string) ($submitted['note'] ?? '')) ?: null,
            ];
        }

        $enrollmentApplication->forceFill([
            'document_review' => $review,
        ]);

        $pending = $this->pendingDocumentApprovals($enrollmentApplication);

        if ($pending !== []) {
            $enrollmentApplication->forceFill([
                'documents_reviewed_at' => null,
                'documents_reviewed_by_id' => null,
            ])->save();

            $message = 'Document review cannot be completed while required documents are pending: '.implode(', ', $pending).'. Accept every required document first.';

            return redirect()
                ->route('admin.enrollments.document-review', $enrollmentApplication)
                ->with('error', $message)
                ->withErrors(['documents' => $message])
                ->withInput();
        }

        $enrollmentApplication->forceFill([
            'documents_reviewed_at' => now(),
            'documents_reviewed_by_id' => $request->user()->id,
        ])->save();

        AdminActivityLog::record($request->user(), 'enrollment.documents.reviewed', $enrollmentApplication, [
            'applicant_email' => $enrollmentApplication->email,
            'review' => $review,
        ]);

        return redirect()
            ->route('admin.enrollments.show', $enrollmentApplication)
            ->with('saved', 'Document review completed. You can now save the enrollment decision.');
    }

    public function documentPreview(EnrollmentApplication $enrollmentApplication, string $document): View
    {
        $this->ensureReleasedForReview($enrollmentApplication);

        $definition = $this->documentDefinition($document);
        $path = $enrollmentApplication->{$definition['field']};

        abort_unless($path && Storage::disk('local')->exists($path), 404);

        AdminActivityLog::record(request()->user(), 'enrollment.document.preview.opened', $enrollmentApplication, [
            'document' => $document,
            'applicant_email' => $enrollmentApplication->email,
        ]);

        return view('admin.enrollments.document-preview', [
            'application' => $enrollmentApplication,
            'document' => $document,
            'label' => $definition['label'],
            'mimeType' => $this->documentMimeType($path) ?: 'application/octet-stream',
        ]);
    }

    public function documentContent(EnrollmentApplication $enrollmentApplication, string $document): BinaryFileResponse
    {
        $this->ensureReleasedForReview($enrollmentApplication);

        $definition = $this->documentDefinition($document);
        $path = $enrollmentApplication->{$definition['field']};

        abort_unless($path && Storage::disk('local')->exists($path), 404);
        $filename = basename($path);
        $fallbackFilename = str($filename)->ascii()->replaceMatches('/[^A-Za-z0-9._-]/', '-')->toString();

        AdminActivityLog::record(request()->user(), 'enrollment.document.content.viewed', $enrollmentApplication, [
            'document' => $document,
            'applicant_email' => $enrollmentApplication->email,
        ]);

        return response()->file($this->localDisk()->path($path), [
            'Content-Type' => $this->documentMimeType($path) ?: 'application/octet-stream',
            'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_INLINE, $filename, $fallbackFilename),
        ]);
    }

    private function documentDefinition(string $document): array
    {
        $fields = $this->documentFields();
        abort_unless(array_key_exists($document, $fields), 404);

        return $fields[$document];
    }

    /** @return list<string> */
    private function pendingDocumentApprovals(EnrollmentApplication $enrollmentApplication): array
    {
        $pending = [];
        $review = $enrollmentApplication->document_review ?? [];

        foreach ($this->documentFields() as $key => $definition) {
            $hasDocument = filled($enrollmentApplication->{$definition['field']});
            $isAccepted = data_get($review, $key.'.status') === 'accepted';

            if (! $hasDocument || ! $isAccepted) {
                $pending[] = $definition['label'];
            }
        }

        return $pending;
    }

    private function documentFields(): array
    {
        return [
            'birth-certificate' => ['label' => 'Birth Certificate', 'field' => 'birth_certificate_path'],
            'education-document' => ['label' => 'Form 137/138 or Diploma', 'field' => 'education_document_path'],
            'good-moral-certificate' => ['label' => 'Good Moral Certificate', 'field' => 'good_moral_certificate_path'],
            'id-photo' => ['label' => 'ID Photo', 'field' => 'id_photo_path'],
            'signature' => ['label' => 'E-Signature', 'field' => 'signature_path'],
        ];
    }

    /** @return array<string, array{label: string, path: ?string, mime: ?string}> */
    private function documentsForReview(EnrollmentApplication $enrollmentApplication): array
    {
        $documents = [];

        foreach ($this->documentFields() as $key => $definition) {
            $path = $enrollmentApplication->{$definition['field']};
            $label = $definition['label'];

            if ($key === 'signature' && filled($enrollmentApplication->signature_type)) {
                $label .= ' ('.str($enrollmentApplication->signature_type)->headline().')';
            }

            $documents[$key] = [
                'label' => $label,
                'path' => $path,
                'mime' => $this->documentMimeType($path),
            ];
        }

        return $documents;
    }

    private function documentMimeType(?string $path): ?string
    {
        $disk = $this->localDisk();
        if (! $path || ! $disk->exists($path)) {
            return null;
        }

        return $disk->mimeType($path) ?: 'application/octet-stream';
    }

    private function localDisk(): FilesystemAdapter
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk('local');

        return $disk;
    }

    private function sendEnrolleeVerificationLink(?User $enrollee, string $status): bool
    {
        if (! $enrollee || $enrollee->hasVerifiedEmail()) {
            return false;
        }

        if (! in_array($status, [
            EnrollmentApplication::STATUS_APPROVED,
            EnrollmentApplication::STATUS_DENIED,
        ], true)) {
            return false;
        }

        try {
            $enrollee->sendEmailVerificationNotification();

            return true;
        } catch (Throwable $exception) {
            report($exception);

            return false;
        }
    }

    private function ensureReleasedForReview(EnrollmentApplication $application): void
    {
        abort_unless($application->isReleasedForReview(), 404);
    }
}
