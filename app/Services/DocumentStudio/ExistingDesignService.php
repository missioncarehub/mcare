<?php

namespace App\Services\DocumentStudio;

use App\Models\CompetencyUnit;
use App\Models\DocumentAsset;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\TraineeCompetencyRecord;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\FpdfOfficialDocumentRenderer;
use App\Services\StatementOfAccountPdfService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ExistingDesignService
{
    /**
     * Resume saved work, or prepare an inactive editable copy of the effective
     * published design. The original template and immutable version stay intact.
     */
    public function openDraft(DocumentTemplate $template, User $actor): DocumentTemplate
    {
        Gate::forUser($actor)->authorize('update', $template);

        return DB::transaction(function () use ($template, $actor) {
            $source = DocumentTemplate::query()->lockForUpdate()->findOrFail($template->id);
            $source->load(['activeVersion', 'trainingProgram']);
            $version = $source->activeVersion;
            if (! $version || (! app(DocumentStudioService::class)->isBaselineVersion($version) && $this->hasSavedDraftChanges($source, $version))) {
                return $source;
            }

            // Provenance is set by this service, outside client-editable state.
            $copy = DocumentTemplate::query()
                ->where('created_by_id', $actor->id)
                ->where('document_kind', $source->document_kind)
                ->where('training_program_id', $source->training_program_id)
                ->where('is_active', false)
                ->whereNull('active_version_id')
                ->whereDoesntHave('versions')
                ->where('conversion_report->draft_source_template_id', $source->id)
                ->where('conversion_report->draft_source_version_id', $version->id)
                ->latest('id')->first();
            if ($copy) {
                return $copy;
            }

            return $this->copyEffectiveVersion($version, $source->trainingProgram, $actor);
        });
    }

    /** Start a new draft from the design actually selected for this scope. */
    public function createCurrent(string $kind, ?TrainingProgram $program, User $actor, ?string $name = null): DocumentTemplate
    {
        Gate::forUser($actor)->authorize('create', DocumentTemplate::class);
        if (! in_array($kind, [DocumentTemplate::KIND_COTC, DocumentTemplate::KIND_TOR, DocumentTemplate::KIND_SOA], true)) {
            throw ValidationException::withMessages(['document_kind' => 'Choose COTC, TOR or Statement of Account for an existing design.']);
        }
        $version = app(DocumentStudioService::class)->resolveActiveVersion($kind, $program?->id);
        if ($version) {
            return $this->copyEffectiveVersion($version, $program, $actor, $name);
        }

        $copy = $this->create($kind, $program, $actor);
        if ($name !== null) {
            $copy->update(['name' => trim($name)]);
        }

        return $copy;
    }

    private function copyEffectiveVersion(DocumentTemplateVersion $version, ?TrainingProgram $program, User $actor, ?string $name = null): DocumentTemplate
    {
        $studio = app(DocumentStudioService::class);
        if (! $version->verifyChecksum()) {
            throw ValidationException::withMessages(['template' => 'The published design failed its integrity check.']);
        }

        // An empty legacy schema is not a usable current design. Issuance falls
        // back to the institutional renderer; the repair draft starts there too.
        $useBaseline = $studio->isBaselineVersion($version)
            || ! $studio->hasActualContent($version->editor_state + ['engine' => $version->engine]);
        if ($useBaseline) {
            $copy = $this->create($version->template->document_kind, $program, $actor);
            if ($name !== null) {
                $copy->update(['name' => trim($name)]);
            }
        } else {
            $source = $version->template;
            $source->setRelation('activeVersion', $version);
            $portable = app(TemplateImportExportService::class)->export($source);
            $copyName = trim($name ?? $source->name.' — Draft');
            $portable['name'] = mb_substr($copyName, 0, 139);
            $copy = app(TemplateImportExportService::class)->import($portable, $actor);
            $copy->update([
                'name' => mb_substr($copyName, 0, 150),
                'training_program_id' => $program?->id,
            ]);
        }

        $report = $copy->conversion_report ?? [];
        $report['draft_source_template_id'] = $version->document_template_id;
        $report['draft_source_version_id'] = $version->id;
        if ($useBaseline && ! $studio->isBaselineVersion($version)) {
            $report['warnings'][] = 'The published design has no printable content. This draft starts from the current MCARE baseline.';
        }
        $copy->forceFill(['conversion_report' => $report])->save();

        return $copy->fresh();
    }

    private function hasSavedDraftChanges(DocumentTemplate $template, DocumentTemplateVersion $version): bool
    {
        $draft = $template->draft_state ?? [];
        $published = $version->editor_state ?? [];
        unset($draft['_asset_integrity'], $published['_asset_integrity']);

        return $draft !== [] && $draft != $published;
    }

    /** Unsaved synthetic records: no real enrollment is read or changed. */
    public function record(string $kind, ?TrainingProgram $program = null): array
    {
        $program ??= new TrainingProgram(['name' => 'Caregiving NC II', 'code' => 'caregiving_nc_ii']);
        $batch = new TrainingBatch(['name' => 'MCARE_BATCH', 'training_starts_at' => '2001-01-01', 'training_ends_at' => '2001-02-03']);
        $batch->setRelation('program', $program);
        $application = new EnrollmentApplication([
            'first_name' => 'MCARE_TRAINEE', 'last_name' => '', 'program' => 'MCARE_PROGRAM',
            'trainee_id_number' => 'MCARE_ID', 'enrollment_number' => 'MCARE_ENROLLMENT',
            'street' => 'MCARE_ADDRESS', 'school_name' => 'MCARE_SCHOOL', 'classification' => 'MCARE_CLASSIFICATION',
            'graduated_at' => '2001-02-03', 'total_program_fee' => 12345.67, 'owwa_deduction' => 123.45,
            'total_paid_amount' => 0, 'schedule_preference' => 'AM',
        ]);
        $application->setRelation('batch', $batch)->setRelation('trainingProgram', new TrainingProgram(['name' => 'MCARE_PROGRAM']))
            ->setRelation('user', new User(['name' => 'MCARE_TRAINEE']))->setRelation('paymentTransactions', new Collection);
        $records = [];
        for ($i = 0; $i < 3; $i++) {
            $record = new TraineeCompetencyRecord(['status' => 'competent', 'tor_grade' => 1.5]);
            $record->setRelation('unit', new CompetencyUnit(['code' => 'SAMPLE'.$i, 'title' => 'Synthetic competency', 'category' => 'core', 'is_tor_included' => true, 'sort_order' => $i]));
            $records[] = $record;
        }
        $application->setRelation('competencyRecords', new Collection($records));
        $pdf = new LayoutRecordingPdf($kind === 'cotc' ? 'L' : 'P', 'mm', $kind === 'tor' ? 'A4' : 'Letter');
        if ($kind === 'soa') {
            $baseline = app(StatementOfAccountPdfService::class)->generateOnCanvas($application, $pdf);
        } else {
            $document = new OfficialDocument(['type' => $kind, 'document_number' => 'MCARE_DOCUMENT', 'version' => 1, 'generated_at' => '2001-02-03']);
            $document->setRelation('application', $application);
            $baseline = app(FpdfOfficialDocumentRenderer::class)->renderOnCanvas($document, $pdf);
        }

        return [$pdf, $baseline];
    }

    public function create(string $kind, ?TrainingProgram $program, User $user): DocumentTemplate
    {
        [$pdf] = $this->record($kind, $program);
        $assets = [];
        try {
            return DB::transaction(function () use ($pdf, $kind, $program, $user, &$assets) {
                $studio = app(DocumentStudioService::class);
                $security = app(AssetSecurityService::class);
                $template = $studio->createTemplate(['name' => ($kind === 'soa' ? 'Statement of Account' : strtoupper($kind)).' — Existing design',
                    'document_kind' => $kind, 'engine' => 'pdfme', 'is_active' => false, 'training_program_id' => $program?->id], $user);
                $imageIds = [];
                File::ensureDirectoryExists(storage_path('app'));
                foreach ($pdf->capturedImages as $key => $image) {
                    $extension = $image['mime'] === 'image/jpeg' ? 'jpg' : 'png';
                    $temporary = tempnam(storage_path('app'), 'studio-source-');
                    try {
                        $bytes = $kind === 'cotc' ? $this->cleanCertificateArtwork($image['bytes']) : $image['bytes'];
                        file_put_contents($temporary, $bytes);
                        $asset = $security->storeUploadedFile(new UploadedFile($temporary, 'existing-design.'.$extension, $image['mime'], null, true), DocumentAsset::TYPE_IMAGE, $template->id, $user);
                        $assets[] = $asset;
                        $imageIds[$key] = $asset->id;
                    } finally {
                        @unlink($temporary);
                    }
                }
                $fields = $pdf->fields;
                foreach ($fields as &$field) {
                    if (isset($field['source_image'])) {
                        $field['asset_id'] = $imageIds[$field['source_image']];
                        $field['content'] = route('admin.document-studio.assets.stream', $field['asset_id']);
                        unset($field['source_image']);
                    }
                    if ($field['type'] === 'text') {
                        $originalText = $field['content'];
                        $field['content'] = $this->bind($originalText, $kind);
                        if ($kind === 'soa' && trim($originalText) === '12,222.22' && $field['position']['y'] < 120) {
                            $field['content'] = '{{net_amount_due}}';
                        }
                        // The source coordinates and colour stay editable. Font substitution is disclosed.
                        $field['dynamicFontSize'] = ['min' => max(5, $field['fontSize'] * .6), 'max' => $field['fontSize'], 'fit' => 'horizontal'];
                    }
                }
                unset($field);
                if ($kind === 'cotc') {
                    foreach ([['{{trainer_name}}', 88, 177.5, 49, 7, 10], ['{{registrar_name}}', 145, 177.5, 47, 7, 10], ['TVI Trainer', 88, 184.5, 49, 5, 8.5], ['TVI Registrar', 145, 184.5, 47, 5, 8.5]] as [$content, $x, $y, $width, $height, $size]) {
                        $fields[] = ['type' => 'text', 'content' => $content, 'position' => ['x' => $x, 'y' => $y], 'width' => $width, 'height' => $height, 'fontSize' => $size, 'alignment' => 'center', 'verticalAlignment' => 'middle', 'source_style' => 'B'];
                    }
                }
                if ($kind === 'tor') {
                    // Replace the source's three sample rows and NOTHING FOLLOWS with the authoritative repeater.
                    $fields = array_values(array_filter($fields, fn ($f) => ! ($f['position']['x'] >= 60 && $f['position']['y'] >= 76 && $f['position']['y'] < 228) || ($f['type'] === 'rectangle' && abs($f['width'] - 142) < .01 && abs($f['height'] - 152) < .01)));
                    $fields[] = $this->table('tor_competencies', 60, 76, 142, 140, '#facbfa');
                }
                if ($kind === 'soa') {
                    $heading = collect($fields)->first(fn ($f) => ($f['content'] ?? '') === 'OFFICIAL PAYMENT RECORD & TRANSACTION LEDGER');
                    $summary = collect($fields)->first(fn ($f) => ($f['content'] ?? '') === 'SUMMARY OF ACCOUNT');
                    $top = $heading['position']['y'] + 5;
                    $bottom = $summary['position']['y'] - 3.5 - 7.8;
                    $fields = array_values(array_filter($fields, fn ($f) => ! ($f['position']['y'] >= $top - .01 && $f['position']['y'] < $bottom - .01)));
                    $fields[] = $this->table('soa_ledger', 16, $top, 183.9, $bottom - $top, '#eef2f7');
                }
                foreach ($fields as &$field) {
                    if ($field['type'] === 'text' && ! empty($field['source_style'])) {
                        $marker = str_contains($field['source_style'], 'B') ? '**' : '*';
                        $field['textFormat'] = 'inline-markdown';
                        $field['readOnly'] = true;
                        $field['fontVariantFallback'] = 'synthetic';
                        $field['content'] = $marker.$field['content'].$marker;
                    }
                }
                unset($field);
                // Preserve drawing order after replacing source table regions.
                foreach ($fields as $index => &$field) {
                    $field['name'] = 'existing_'.$index;
                }
                unset($field);
                $state = ['schema_version' => '1.0', 'engine' => 'pdfme', 'source_layout' => $kind, 'page' => ['width_mm' => $pdf->GetPageWidth(), 'height_mm' => $pdf->GetPageHeight()],
                    'basePdf' => ['width' => $pdf->GetPageWidth(), 'height' => $pdf->GetPageHeight(), 'padding' => [0, 0, 10, 0]], 'schemas' => [$fields]];
                $template = $studio->saveDraft($template, $state, $template->draft_revision, $user);
                $template->forceFill(['conversion_report' => ['source' => $kind === 'soa' ? 'StatementOfAccountPdfService' : 'FpdfOfficialDocumentRenderer',
                    'editable_fields' => count($fields), 'warnings' => [
                        'Created from existing drawing commands. Review substituted fonts and table pagination before publishing.',
                        $kind === 'cotc' ? 'Certificate title, program, logos and competency artwork remain flattened. Replace the artwork to change those parts; trainee name, date, serial and signatory fields are editable.' : 'Text, logos, lines, colours and tables are editable. This copy does not change previously issued documents.',
                    ]]])->save();

                return $template;
            });
        } catch (\Throwable $exception) {
            foreach ($assets as $asset) {
                Storage::disk($asset->disk)->delete($asset->file_path);
            }
            throw $exception;
        }
    }

    private function cleanCertificateArtwork(string $bytes): string
    {
        $image = imagecreatefromstring($bytes);
        if (! $image) {
            throw new \RuntimeException('Certificate artwork cannot be decoded.');
        }
        $scaleX = imagesx($image) / 279.4;
        $scaleY = imagesy($image) / 215.9;
        $fill = imagecolorallocate($image, 255, 252, 255);
        foreach ([[14, 91, 178.5, 15.5], [28, 137, 163, 11.5], [87, 177, 51, 13], [144, 177, 49, 13]] as [$x, $y, $width, $height]) {
            imagefilledrectangle($image, (int) floor($x * $scaleX), (int) floor($y * $scaleY), (int) ceil(($x + $width) * $scaleX), (int) ceil(($y + $height) * $scaleY), $fill);
        }
        ob_start();
        imagejpeg($image, null, 90);
        $clean = ob_get_clean();
        imagedestroy($image);

        return $clean;
    }

    private function bind(string $text, string $kind): string
    {
        $organization = config('official_documents.organization', []);
        $map = [
            'MCARE_TRAINEE' => '{{trainee_name}}', 'MCARE_PROGRAM' => '{{training_program}}', 'MCARE_ID' => '{{trainee_id_number}}',
            'MCARE_ENROLLMENT' => '{{enrollment_number}}', 'MCARE_BATCH (AM CLASS)' => '{{batch_schedule}}', 'MCARE_BATCH' => '{{batch_name}}',
            'MCARE_ADDRESS' => '{{trainee_address}}', 'MCARE_SCHOOL' => '{{last_school}}', 'MCARE_CLASSIFICATION' => '{{education_category}}',
            'MCARE_DOCUMENT' => '{{certificate_number}}', 'Version 1' => 'Version {{document_version}}',
            'January 1, 2001' => '{{entrance_date}}', 'February 3, 2001' => '{{completion_date}}',
        ];
        if (str_starts_with($text, 'Given this ')) {
            return '{{completion_line}}';
        }
        foreach (['trainer_name', 'registrar_name'] as $key) {
            if (! empty($organization[$key]) && mb_strtoupper($text) === mb_strtoupper($organization[$key])) {
                return '{{'.$key.'}}';
            }
        }
        if ($kind === 'soa') {
            if (trim($text) === '0.00') {
                return '{{total_paid}}';
            }
            $map += [now()->format('F j, Y') => '{{statement_date}}', 'PHP 12,345.67' => '{{gross_program_fee}}', '12,345.67' => '{{gross_program_fee}}',
                '-123.45' => '-{{total_deductions}}', 'PHP 12,222.22' => '{{outstanding_balance}}', '12,222.22' => '{{outstanding_balance}}', 'PHP 0.00' => '{{total_paid}}'];
            // Do not introduce certified financial claims or named certifiers in the new draft.
            $map += ['Prepared and Certified Correct by:' => 'Prepared by:', 'TVI Accountant' => 'Accounts', 'TVI Administrator' => 'Administration'];
        }

        return strtr($text, $map);
    }

    private function table(string $binding, float $x, float $y, float $width, float $height, string $color): array
    {
        return ['name' => $binding, 'type' => 'table', 'binding' => '{{'.$binding.'}}', 'content' => '[]',
            'position' => ['x' => $x, 'y' => $y], 'width' => $width, 'height' => $height, 'layout_slot_height' => $height,
            'headStyles' => ['fontSize' => 8, 'backgroundColor' => $color, 'fontColor' => '#111111'],
            'bodyStyles' => ['fontSize' => 8, 'backgroundColor' => $binding === 'tor_competencies' ? $color : '#ffffff', 'fontColor' => '#111111', 'alternateBackgroundColor' => $binding === 'tor_competencies' ? $color : '#ffffff']];
    }
}
