<?php

namespace App\Http\Controllers;

use App\Models\OfficialDocument;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CredentialVerificationController extends Controller
{
    public function verify(Request $request, string $documentNumber): View
    {
        $sanitizedNumber = trim(strip_tags($documentNumber));

        $document = OfficialDocument::with(['application.trainingProgram', 'batch'])
            ->where('document_number', $sanitizedNumber)
            ->first();

        if (! $document) {
            return view('verify.credential', [
                'isValid' => false,
                'documentNumber' => $sanitizedNumber,
                'message' => 'No official record was found matching this credential number.',
            ]);
        }

        $app = $document->application;
        // Partially mask name for public privacy
        $maskedName = 'Verified Learner';
        if ($app) {
            $first = mb_substr($app->first_name ?? '', 0, 1);
            $last = mb_substr($app->last_name ?? '', 0, 1);
            $maskedName = strtoupper($first.'*** '.$last.'***');
        }

        $typeName = match ($document->type) {
            OfficialDocument::TYPE_COTC => 'Certificate of Training Completion (COTC)',
            OfficialDocument::TYPE_TOR => 'Official Transcript of Records (TOR)',
            default => strtoupper((string) $document->type),
        };

        $programName = $app?->trainingProgram?->name ?? $app?->program ?? 'Technical Vocational Training';
        $isReleased = in_array($document->status, [OfficialDocument::STATUS_RELEASED, OfficialDocument::STATUS_DOWNLOADED], true);
        $isRevoked = $document->status === OfficialDocument::STATUS_REVOKED || $document->revoked_at !== null;

        return view('verify.credential', [
            'isValid' => $isReleased && ! $isRevoked && ! $app?->graduation_test_override,
            'isRevoked' => $isRevoked,
            'documentNumber' => $document->document_number,
            'typeName' => $typeName,
            'traineeName' => $maskedName,
            'programName' => $programName,
            'batchName' => $document->batch?->name ?? 'Completed Cohort',
            'issuedAt' => ($document->released_at ?? $document->generated_at ?? $document->created_at)?->format('F j, Y'),
            'institution' => 'Mission Care Training Center (MCARE)',
        ]);
    }
}
