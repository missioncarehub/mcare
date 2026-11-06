<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Models\CertificateTemplate;
use App\Models\CompetencyUnit;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingBatch;
use App\Services\FpdfOfficialDocumentRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CertificateTemplateController extends Controller
{
    public function index(): View
    {
        $sections = collect(OfficialDocument::supportedTypes())
            ->mapWithKeys(function (string $type) {
                $history = CertificateTemplate::query()
                    ->with('createdBy')
                    ->where('type', $type)
                    ->orderByDesc('version')
                    ->get();

                return [$type => [
                    'active' => $history->firstWhere('is_active', true),
                    'history' => $history,
                    'defaults' => CertificateTemplate::formDefaults($type),
                ]];
            });

        return view('admin.learning.certificate-templates', [
            'sections' => $sections,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->validate([
            'type' => ['required', Rule::in(OfficialDocument::supportedTypes())],
        ])['type'];

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'body' => [$type === OfficialDocument::TYPE_COTC ? 'required' : 'nullable', 'string', 'max:2000'],
            'paper_size' => ['required', Rule::in(['letter', 'a4', 'legal'])],
            'orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'signatory_name' => ['nullable', 'string', 'max:180'],
            'signatory_role' => ['nullable', 'string', 'max:120'],
            'second_signatory_name' => ['nullable', 'string', 'max:180'],
            'second_signatory_role' => ['nullable', 'string', 'max:120'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'second_signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $previous = CertificateTemplate::activeFor($type);
        $useArtwork = $type === OfficialDocument::TYPE_COTC && $request->boolean('use_official_artwork');
        $template = new CertificateTemplate([
            'type' => $type,
            'version' => ((int) CertificateTemplate::query()->where('type', $type)->max('version')) + 1,
            'is_active' => false,
            'title' => trim($validated['title']),
            'body' => trim((string) ($validated['body'] ?? '')),
            'paper_size' => $useArtwork ? 'letter' : $validated['paper_size'],
            'orientation' => $useArtwork ? 'landscape' : $validated['orientation'],
            'use_official_artwork' => $useArtwork,
            'logo_path' => $previous?->logo_path,
            'signatory_name' => $this->blankToNull($validated['signatory_name'] ?? null),
            'signatory_role' => $this->blankToNull($validated['signatory_role'] ?? null),
            'signature_path' => $previous?->signature_path,
            'second_signatory_name' => $this->blankToNull($validated['second_signatory_name'] ?? null),
            'second_signatory_role' => $this->blankToNull($validated['second_signatory_role'] ?? null),
            'second_signature_path' => $previous?->second_signature_path,
            'created_by_id' => $request->user()->id,
        ]);

        foreach (['logo' => 'logo_path', 'signature' => 'signature_path', 'second_signature' => 'second_signature_path'] as $input => $column) {
            if ($request->hasFile($input)) {
                $template->{$column} = $request->file($input)->store('certificate-templates', 'local');
            }
        }

        DB::transaction(function () use ($template, $type): void {
            CertificateTemplate::query()->where('type', $type)->where('is_active', true)->update(['is_active' => false]);
            $template->is_active = true;
            $template->save();
        });

        AdminActivityLog::record($request->user(), 'admin.certificate-template.saved', $template, [
            'type' => $type,
            'version' => $template->version,
        ]);

        $label = $type === OfficialDocument::TYPE_COTC ? 'COTC' : 'TOR';

        return redirect()
            ->route('admin.learning.certificate-templates')
            ->with('saved', $label.' template version '.$template->version.' is active. The next generated '.$label.' uses this text and signatory.');
    }

    public function activate(Request $request, CertificateTemplate $certificateTemplate): RedirectResponse
    {
        DB::transaction(function () use ($certificateTemplate): void {
            CertificateTemplate::query()
                ->where('type', $certificateTemplate->type)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            $certificateTemplate->update(['is_active' => true]);
        });

        AdminActivityLog::record($request->user(), 'admin.certificate-template.activated', $certificateTemplate, [
            'type' => $certificateTemplate->type,
            'version' => $certificateTemplate->version,
        ]);

        $label = $certificateTemplate->type === OfficialDocument::TYPE_COTC ? 'COTC' : 'TOR';

        return redirect()
            ->route('admin.learning.certificate-templates')
            ->with('saved', $label.' template version '.$certificateTemplate->version.' is now the active template.');
    }

    public function preview(string $type, FpdfOfficialDocumentRenderer $renderer): Response
    {
        abort_unless(in_array($type, OfficialDocument::supportedTypes(), true), 404);

        $pdf = $renderer->render($this->sampleDocument($type));
        $name = strtoupper($type).'-template-preview.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$name.'"',
        ]);
    }

    public function asset(CertificateTemplate $certificateTemplate, string $slot): Response
    {
        $path = match ($slot) {
            'logo' => $certificateTemplate->logo_path,
            'signature' => $certificateTemplate->signature_path,
            'second-signature' => $certificateTemplate->second_signature_path,
            default => null,
        };
        $absolute = $certificateTemplate->absoluteAsset($path);
        abort_if($absolute === null, 404);

        return response()->file($absolute);
    }

    private function sampleDocument(string $type): OfficialDocument
    {
        $batch = new TrainingBatch([
            'name' => 'Batch 1',
            'year' => 2026,
            'training_starts_at' => '2026-01-15',
            'training_ends_at' => '2026-07-18',
        ]);
        $application = new EnrollmentApplication([
            'enrollment_number' => 'MCE-2026-SAMPLE',
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
            'last_name' => 'Santos',
            'street' => '1 Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'school_name' => 'MCARE School',
            'classification' => 'Student',
        ]);
        $unit = new CompetencyUnit([
            'code' => 'HCS323301',
            'title' => 'Provide Care and Support to Infants and Toddlers',
            'is_tor_included' => true,
            'sort_order' => 1,
        ]);
        $record = new TraineeCompetencyRecord([
            'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
            'tor_grade' => 1.25,
        ]);
        $record->setRelation('unit', $unit);
        $application->setRelation('batch', $batch);
        $application->setRelation('competencyRecords', collect([$record]));

        $document = new OfficialDocument([
            'type' => $type,
            'version' => 1,
            'document_number' => 'MCARE-'.strtoupper($type).'-2026-00002-V1',
            'generated_at' => '2026-07-18 09:00:00',
        ]);
        $document->setRelation('application', $application);

        return $document;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
