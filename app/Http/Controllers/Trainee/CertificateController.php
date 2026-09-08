<?php

namespace App\Http\Controllers\Trainee;

use App\Http\Controllers\Controller;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\OfficialDocumentDownload;
use App\Services\OfficialDocumentManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CertificateController extends Controller
{
    public function download(
        Request $request,
        OfficialDocument $officialDocument,
        OfficialDocumentManager $manager,
    ): StreamedResponse|RedirectResponse {
        $officialDocument = $manager->refreshClippedCotcPdf($officialDocument);
        $applicationId = EnrollmentApplication::query()
            ->where('user_id', $request->user()->id)
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->latest()
            ->value('id');

        if ((int) $officialDocument->enrollment_application_id !== (int) $applicationId) {
            abort(404);
        }

        $claimed = DB::transaction(function () use ($request, $officialDocument): ?OfficialDocument {
            $locked = OfficialDocument::query()->lockForUpdate()->findOrFail($officialDocument->id);

            if (! $locked->isDownloadableByTrainee()
                || ! Storage::disk($locked->storage_disk)->exists($locked->file_path)) {
                return null;
            }

            // The row lock makes rapid double-clicks consume exactly one download claim.
            $locked->update([
                'status' => OfficialDocument::STATUS_DOWNLOADED,
                'downloaded_at' => now(),
                'download_count' => 1,
            ]);
            OfficialDocumentDownload::create([
                'official_document_id' => $locked->id,
                'user_id' => $request->user()->id,
                'actor_role' => 'trainee',
                'ip_address' => $request->ip(),
                'user_agent' => str($request->userAgent() ?? '')->limit(1000)->toString(),
                'downloaded_at' => now(),
            ]);

            return $locked;
        });

        if (! $claimed) {
            return redirect()
                ->route('trainee.documents')
                ->with('warning', 'This COTC download was already used or is no longer available. Ask the admin for a reissue if needed.');
        }

        return Storage::disk($claimed->storage_disk)->download(
            $claimed->file_path,
            $claimed->document_number.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }
}
