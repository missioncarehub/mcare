<?php

namespace App\Http\Controllers;

use App\Models\EnrollmentApplication;
use App\Services\ProfilePhotoStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class EnrollmentDocumentRevisionController extends Controller
{
    public function show(Request $request, EnrollmentApplication $enrollmentApplication): View
    {
        $this->authorizeRevisionAccess($request, $enrollmentApplication);

        $documents = $this->flaggedDocuments($enrollmentApplication);

        return view('enrollment.revise-documents', [
            'application' => $enrollmentApplication,
            'documents' => $documents,
            'formAction' => $this->formAction($request, $enrollmentApplication),
        ]);
    }

    public function update(Request $request, EnrollmentApplication $enrollmentApplication, ProfilePhotoStore $profilePhotos): RedirectResponse
    {
        $this->authorizeRevisionAccess($request, $enrollmentApplication);

        $documents = $this->flaggedDocuments($enrollmentApplication);

        if ($documents === []) {
            return redirect()
                ->to($this->redirectUrl($request, $enrollmentApplication))
                ->with('saved', 'There are no documents currently marked for replacement.');
        }

        $rules = [];
        $messages = [];
        foreach ($documents as $document) {
            $rules[$document['input']] = ['required', ...$document['rules']];
            $messages[$document['input'].'.required'] = 'Upload a replacement for '.$document['label'].'.';
        }

        $request->validate($rules, $messages);

        $review = $enrollmentApplication->document_review ?? [];
        $directory = $enrollmentApplication->user_id
            ? 'enrollment-documents/'.$enrollmentApplication->user_id
            : 'enrollment-documents/application-'.$enrollmentApplication->id;

        foreach ($documents as $key => $document) {
            $file = $request->file($document['input']);
            $previousPath = $enrollmentApplication->{$document['path']};
            $path = $file->store($directory, 'local');

            $attributes = [
                $document['path'] => $path,
            ];

            if (($document['sets_signature_type'] ?? null) === 'upload') {
                $attributes['signature_type'] = 'upload';
            }

            $enrollmentApplication->forceFill($attributes);

            if (filled($previousPath) && $previousPath !== $path) {
                Storage::disk('local')->delete($previousPath);
            }

            $review[$key] = [
                'status' => 'unreviewed',
                'note' => 'Replacement uploaded; awaiting admin review.',
            ];

            if ($key === 'id-photo' && $enrollmentApplication->user) {
                $profilePhotos->syncFromPrivateDisk($enrollmentApplication->user, $path);
            }
        }

        $enrollmentApplication->forceFill([
            'document_review' => $review,
            'documents_reviewed_at' => null,
            'documents_reviewed_by_id' => null,
        ])->save();

        return redirect()
            ->to($this->redirectUrl($request, $enrollmentApplication))
            ->with('saved', 'Replacement files submitted. MCARE administration will review them.');
    }

    private function authorizeRevisionAccess(Request $request, EnrollmentApplication $enrollmentApplication): void
    {
        if ($request->hasValidSignature()) {
            return;
        }

        $user = $request->user();
        if ($user && (int) $user->id === (int) $enrollmentApplication->user_id) {
            return;
        }

        abort(403, 'This document revision link is invalid or has expired.');
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     description: string,
     *     input: string,
     *     path: string,
     *     accept: string,
     *     rules: list<string>,
     *     sets_signature_type?: string,
     *     status: string,
     *     note: ?string
     * }>
     */
    private function flaggedDocuments(EnrollmentApplication $enrollmentApplication): array
    {
        $review = $enrollmentApplication->document_review ?? [];
        $flagged = [];

        foreach ($this->documentCatalog() as $key => $definition) {
            $status = (string) data_get($review, $key.'.status', '');
            if (! in_array($status, ['replace', 'missing'], true)) {
                continue;
            }

            $note = trim((string) data_get($review, $key.'.note', ''));
            $flagged[$key] = [
                ...$definition,
                'status' => $status,
                'note' => $note !== '' ? $note : null,
            ];
        }

        return $flagged;
    }

    /**
     * @return array<string, array{
     *     label: string,
     *     description: string,
     *     input: string,
     *     path: string,
     *     accept: string,
     *     rules: list<string>,
     *     sets_signature_type?: string
     * }>
     */
    private function documentCatalog(): array
    {
        $documentRules = ['file', 'mimes:pdf,jpg,jpeg,png', 'extensions:pdf,jpg,jpeg,png', 'max:5120'];
        $imageRules = ['file', 'mimes:jpg,jpeg,png', 'extensions:jpg,jpeg,png', 'max:5120'];

        return [
            'birth-certificate' => [
                'label' => 'Birth Certificate',
                'description' => 'Clear PSA/NSO or local civil registrar copy.',
                'input' => 'birth_certificate',
                'path' => 'birth_certificate_path',
                'accept' => '.pdf,.jpg,.jpeg,.png',
                'rules' => $documentRules,
            ],
            'education-document' => [
                'label' => 'Form 137/138 or Diploma',
                'description' => 'Upload the document that verifies your educational background.',
                'input' => 'education_document',
                'path' => 'education_document_path',
                'accept' => '.pdf,.jpg,.jpeg,.png',
                'rules' => $documentRules,
            ],
            'good-moral-certificate' => [
                'label' => 'Certificate of Good Moral',
                'description' => 'Upload a readable copy from your school or issuing office.',
                'input' => 'good_moral_certificate',
                'path' => 'good_moral_certificate_path',
                'accept' => '.pdf,.jpg,.jpeg,.png',
                'rules' => $documentRules,
            ],
            'id-photo' => [
                'label' => 'ID Photo',
                'description' => 'Use a recent formal ID photo in JPG or PNG format.',
                'input' => 'id_photo',
                'path' => 'id_photo_path',
                'accept' => '.jpg,.jpeg,.png',
                'rules' => $imageRules,
            ],
            'signature' => [
                'label' => 'E-Signature',
                'description' => 'Upload a clear image of your signature in JPG or PNG format.',
                'input' => 'signature_upload',
                'path' => 'signature_path',
                'accept' => '.jpg,.jpeg,.png',
                'rules' => $imageRules,
                'sets_signature_type' => 'upload',
            ],
        ];
    }

    private function formAction(Request $request, EnrollmentApplication $enrollmentApplication): string
    {
        if ($this->ownsApplication($request, $enrollmentApplication) && ! $request->hasValidSignature()) {
            return route('enrollment.documents.revise.update', $enrollmentApplication);
        }

        return URL::temporarySignedRoute(
            'enrollment.documents.revise.update',
            now()->addDays(14),
            ['enrollmentApplication' => $enrollmentApplication],
        );
    }

    private function redirectUrl(Request $request, EnrollmentApplication $enrollmentApplication): string
    {
        if ($this->ownsApplication($request, $enrollmentApplication) && ! $request->hasValidSignature()) {
            return route('enrollment.documents.revise', $enrollmentApplication);
        }

        return URL::temporarySignedRoute(
            'enrollment.documents.revise',
            now()->addDays(14),
            ['enrollmentApplication' => $enrollmentApplication],
        );
    }

    private function ownsApplication(Request $request, EnrollmentApplication $enrollmentApplication): bool
    {
        $user = $request->user();

        return $user !== null && (int) $user->id === (int) $enrollmentApplication->user_id;
    }
}
