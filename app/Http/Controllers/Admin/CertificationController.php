<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\GenerateBatchTorExport;
use App\Models\AdminActivityLog;
use App\Models\BatchDocumentExport;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\OfficialDocumentDownload;
use App\Models\TrainingBatch;
use App\Services\CompletionEligibilityService;
use App\Services\OfficialDocumentManager;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificationController extends Controller
{
    public function index(Request $request, CompletionEligibilityService $eligibility): View
    {
        $validated = $request->validate([
            'batch_id' => ['nullable', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
            'eligibility' => ['nullable', Rule::in(['eligible', 'blocked'])],
            'tab' => ['nullable', Rule::in(['active', 'graduates'])],
        ]);
        $activeTab = ($validated['tab'] ?? 'active') === 'graduates' ? 'graduates' : 'active';
        $filters = collect($validated)->except('tab')->all();
        $records = EnrollmentApplication::query()
            ->with(['batch', 'officialDocuments' => fn ($query) => $query->latest('version')])
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->when($filters['batch_id'] ?? null, fn ($query, $batchId) => $query
                ->where('training_batch_id', $batchId))
            ->when($filters['schedule'] ?? null, fn ($query, $schedule) => $query
                ->where('schedule_preference', $schedule))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get()
            ->transform(function ($application) use ($eligibility) {
                $isGraduated = $application->learning_status === EnrollmentApplication::LEARNING_GRADUATED;
                $eval = $eligibility->evaluate($application);
                if ($isGraduated) {
                    $eval['eligible'] = true;
                }
                $application->completion_eligibility = $eval;

                return $application;
            });

        if (isset($filters['eligibility'])) {
            $wanted = $filters['eligibility'] === 'eligible';
            $records = $records->filter(
                fn ($application) => $application->completion_eligibility['eligible'] === $wanted
            )->values();
        }

        $graduates = $records
            ->filter(fn ($application) => $application->learning_status === EnrollmentApplication::LEARNING_GRADUATED)
            ->values();
        $activeTrainees = $records
            ->reject(fn ($application) => $application->learning_status === EnrollmentApplication::LEARNING_GRADUATED)
            ->values();

        return view('admin.learning.certificates', [
            'activeTab' => $activeTab,
            'activeTrainees' => $activeTrainees,
            'graduates' => $graduates,
            'filters' => $filters,
            'batches' => TrainingBatch::query()->orderByDesc('year')->orderBy('name')->get(),
            'exports' => BatchDocumentExport::query()
                ->with(['batch', 'requestedBy'])
                ->latest()
                ->take(8)
                ->get(),
        ]);
    }

    public function generate(
        Request $request,
        EnrollmentApplication $enrollmentApplication,
        string $type,
        OfficialDocumentManager $manager,
    ): RedirectResponse {
        $document = $manager->queue($enrollmentApplication, $type, $request->user());

        return back()->with(
            'saved',
            strtoupper($document->type).' generated successfully as '.$document->document_number.'.',
        );
    }

    public function release(
        Request $request,
        OfficialDocument $officialDocument,
        OfficialDocumentManager $manager,
    ): RedirectResponse {
        $manager->releaseCotc($officialDocument, $request->user());

        return back()->with('saved', 'COTC released. The trainee now has one download.');
    }

    public function preview(OfficialDocument $officialDocument, OfficialDocumentManager $manager): StreamedResponse
    {
        $officialDocument = $manager->refreshClippedCotcPdf($officialDocument);
        $this->assertAvailable($officialDocument);
        $stream = Storage::disk($officialDocument->storage_disk)->readStream($officialDocument->file_path);
        abort_unless(is_resource($stream), 404);

        return response()->stream(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                $officialDocument->document_number.'.pdf',
            ),
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function reissue(
        Request $request,
        EnrollmentApplication $enrollmentApplication,
        string $type,
        OfficialDocumentManager $manager,
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ]);
        $document = $manager->reissue($enrollmentApplication, $type, $request->user(), $validated['reason']);

        return back()->with('saved', strtoupper($document->type).' reissued successfully as '.$document->document_number.'.');
    }

    public function download(Request $request, OfficialDocument $officialDocument, OfficialDocumentManager $manager): StreamedResponse
    {
        $officialDocument = $manager->refreshClippedCotcPdf($officialDocument);
        $this->assertAvailable($officialDocument);

        OfficialDocumentDownload::create([
            'official_document_id' => $officialDocument->id,
            'user_id' => $request->user()->id,
            'actor_role' => 'admin',
            'ip_address' => $request->ip(),
            'user_agent' => str($request->userAgent() ?? '')->limit(1000)->toString(),
            'downloaded_at' => now(),
        ]);
        AdminActivityLog::record($request->user(), 'admin.official-document.downloaded', $officialDocument, [
            'document_number' => $officialDocument->document_number,
            'type' => $officialDocument->type,
        ]);

        return $this->documentDisk($officialDocument->storage_disk)->download(
            $officialDocument->file_path,
            $officialDocument->document_number.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function assertAvailable(OfficialDocument $officialDocument): void
    {
        abort_unless(
            in_array($officialDocument->type, OfficialDocument::supportedTypes(), true)
            && in_array($officialDocument->status, [
                OfficialDocument::STATUS_GENERATED,
                OfficialDocument::STATUS_RELEASED,
                OfficialDocument::STATUS_DOWNLOADED,
            ], true)
            && filled($officialDocument->file_path)
            && Storage::disk($officialDocument->storage_disk)->exists($officialDocument->file_path),
            404,
        );
    }

    public function requestBatchTorExport(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'training_batch_id' => ['required', 'integer', 'exists:training_batches,id'],
        ]);
        $export = BatchDocumentExport::create([
            'training_batch_id' => $validated['training_batch_id'],
            'type' => OfficialDocument::TYPE_TOR,
            'status' => BatchDocumentExport::STATUS_QUEUED,
            'storage_disk' => config('official_documents.disk', 'local'),
            'requested_by_id' => $request->user()->id,
        ]);

        GenerateBatchTorExport::dispatch($export->id);
        AdminActivityLog::record($request->user(), 'admin.tor.batch-export.queued', $export, [
            'training_batch_id' => $export->training_batch_id,
        ]);

        return back()->with('saved', 'Batch TOR export queued. Refresh this page to see its progress.');
    }

    public function downloadBatchExport(
        Request $request,
        BatchDocumentExport $batchDocumentExport,
    ): StreamedResponse {
        abort_unless(
            $batchDocumentExport->isDownloadable()
            && Storage::disk($batchDocumentExport->storage_disk)->exists($batchDocumentExport->file_path),
            404,
        );
        AdminActivityLog::record($request->user(), 'admin.tor.batch-export.downloaded', $batchDocumentExport, [
            'training_batch_id' => $batchDocumentExport->training_batch_id,
        ]);

        return $this->documentDisk($batchDocumentExport->storage_disk)->download(
            $batchDocumentExport->file_path,
            'MCARE-batch-'.$batchDocumentExport->training_batch_id.'-TOR.zip',
            ['Content-Type' => 'application/zip'],
        );
    }

    private function documentDisk(string $disk): FilesystemAdapter
    {
        /** @var FilesystemAdapter $filesystem */
        $filesystem = Storage::disk($disk);

        return $filesystem;
    }
}
