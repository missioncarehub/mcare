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
use App\Models\TrainingModule;
use App\Models\TrainingProgram;
use App\Services\FpdfOfficialDocumentRenderer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class CertificateTemplateController extends Controller
{
    /**
     * Resolve competency rows for a program: [code, title, grade, remark]
     *
     * @return list<array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function resolveCompetenciesForProgram(?TrainingProgram $program): array
    {
        $code = strtolower((string) ($program?->code ?? ''));
        $name = strtolower((string) ($program?->name ?? ''));

        // 1. Check if explicit units exist in database
        if ($program) {
            $dbUnits = CompetencyUnit::query()
                ->where(function ($q) use ($code, $name) {
                    $q->where('program_code', $code)
                        ->orWhere('program_code', Str::slug($code, '_'))
                        ->orWhere('program_code', Str::slug($name, '_'));
                })
                ->where('is_tor_included', true)
                ->orderBy('sort_order')
                ->get();

            if ($dbUnits->isNotEmpty()) {
                return $dbUnits->map(fn ($u) => [
                    (string) $u->code,
                    (string) $u->title,
                    '1.25',
                    'COMPETENT',
                ])->all();
            }

            // Check if modules are attached to this program's batches
            $modules = TrainingModule::query()
                ->whereHas('batch', fn ($q) => $q->where('training_program_id', $program->id))
                ->orderBy('id')
                ->get();

            if ($modules->isNotEmpty()) {
                return $modules->map(fn ($m, $idx) => [
                    $m->module_code ?: ($program->code.'-'.str_pad((string) ($idx + 1), 3, '0', STR_PAD_LEFT)),
                    (string) $m->title,
                    '1.25',
                    'COMPETENT',
                ])->all();
            }
        }

        // 2. Specific standard programs
        if (str_contains($code, 'nc-iii') || str_contains($code, 'nc3') || str_contains($name, 'nc iii') || str_contains($name, 'nc 3')) {
            return [
                ['HCS324301', 'Provide Specialized Care and Support to People with Special Needs', '1.25', 'COMPETENT'],
                ['HCS324302', 'Assist with Medication Administration and Health Monitoring', '1.25', 'COMPETENT'],
                ['HCS324303', 'Deliver Palliative Care and Chronic Illness Support', '1.50', 'COMPETENT'],
                ['HCS324304', 'Implement Dementia and Mental Health Care Strategies', '1.25', 'COMPETENT'],
                ['HCS324305', 'Coordinate Patient Care Plans and Multidisciplinary Records', '1.50', 'COMPETENT'],
                ['HCS324306', 'Maintain Specialized Assistive Equipment and Mobility Aids', '1.25', 'COMPETENT'],
                ['HCS324307', 'Respond to Acute Clinical Emergencies in Home and Community', '1.25', 'COMPETENT'],
                ['HCS324308', 'Facilitate Rehabilitation and Physical Therapy Exercises', '1.50', 'COMPETENT'],
            ];
        }

        // 3. For any other newly created program, generate custom tailored units matching the program's code and title
        if ($program && ! str_contains($name, 'caregiving nc ii')) {
            $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $program->code) ?: 'PRG');

            return [
                [$prefix.'-301', 'Foundations and Core Principles of '.$program->name, '1.25', 'COMPETENT'],
                [$prefix.'-302', 'Applied Practical Competencies and Work Procedures', '1.25', 'COMPETENT'],
                [$prefix.'-303', 'Workplace Safety, Quality Assurance and Compliance Standards', '1.50', 'COMPETENT'],
                [$prefix.'-304', 'Professional Client Communication and Documentation', '1.25', 'COMPETENT'],
                [$prefix.'-305', 'Advanced Specialized Skills in '.$program->name, '1.50', 'COMPETENT'],
                [$prefix.'-306', 'Supervised Clinical Practicum and Institutional Assessment', '1.25', 'COMPETENT'],
            ];
        }

        // 4. Caregiving NC II default standard curriculum
        return [
            ['HCS323301', 'Provide Care and Support to Infants and Toddlers', '1.25', 'COMPETENT'],
            ['HCS323302', 'Provide Care and Support to Children', '1.25', 'COMPETENT'],
            ['HCS323303', 'Foster Social, Intellectual, Creative and Emotional Development of Children', '1.50', 'COMPETENT'],
            ['HCS323304', 'Foster Physical Development of Children', '1.25', 'COMPETENT'],
            ['HCS323305', 'Provide Care and Support to Elderly', '1.25', 'COMPETENT'],
            ['HCS323306', 'Provide Care and Support to People with Special Needs', '1.25', 'COMPETENT'],
            ['HCS323307', 'Maintain a Healthy and Safe Environment', '1.25', 'COMPETENT'],
            ['HCS323308', 'Respond to Emergency', '1.25', 'COMPETENT'],
            ['HCS323309', 'Clean Living Room, Dining Room, Bedrooms, Toilet and Bathroom', '1.50', 'COMPETENT'],
            ['HCS323310', 'Wash and Iron Clothes, Linen and Fabric', '1.25', 'COMPETENT'],
            ['HCS323311', 'Prepare Hot and Cold Meals', '1.25', 'COMPETENT'],
        ];
    }
    public function index(Request $request): View
    {
        $programs = TrainingProgram::active()->orderBy('name')->get();
        $selectedProgramId = $request->query('program_id') ? (int) $request->query('program_id') : null;
        $selectedProgram = $selectedProgramId ? $programs->firstWhere('id', $selectedProgramId) : null;

        $sections = collect(CertificateTemplate::managedTypes())
            ->mapWithKeys(function (string $type) use ($selectedProgramId) {
                $history = CertificateTemplate::query()
                    ->with('createdBy', 'trainingProgram')
                    ->where('type', $type)
                    ->when(
                        $selectedProgramId,
                        fn ($q) => $q->where('training_program_id', $selectedProgramId),
                        fn ($q) => $q->whereNull('training_program_id')
                    )
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
            'programs' => $programs,
            'selectedProgram' => $selectedProgram,
            'selectedProgramId' => $selectedProgramId,
            'torRows' => self::resolveCompetenciesForProgram($selectedProgram),
        ]);
    }

    public function edit(Request $request, string $type): View
    {
        abort_unless(in_array($type, CertificateTemplate::managedTypes(), true), 404);

        $programs = TrainingProgram::active()->orderBy('name')->get();
        $selectedProgramId = $request->query('program_id') ? (int) $request->query('program_id') : null;
        $selectedProgram = $selectedProgramId ? $programs->firstWhere('id', $selectedProgramId) : null;

        $history = CertificateTemplate::query()
            ->with('createdBy', 'trainingProgram')
            ->where('type', $type)
            ->when(
                $selectedProgramId,
                fn ($q) => $q->where('training_program_id', $selectedProgramId),
                fn ($q) => $q->whereNull('training_program_id')
            )
            ->orderByDesc('version')
            ->get();
        $label = CertificateTemplate::label($type);
        $torRows = self::resolveCompetenciesForProgram($selectedProgram);
        $competencyUnits = collect($torRows)->map(function ($r) {
            return (object) [
                'code' => $r[0],
                'title' => $r[1],
                'category' => 'Core',
                'grade' => $r[2],
                'remark' => $r[3],
            ];
        });

        return view('admin.learning.certificate-template-edit', [
            'type' => $type,
            'heading' => match ($label) {
                'COTC' => ($selectedProgram ? $selectedProgram->name.' — ' : '').'Certificate of Training Completion',
                default => ($selectedProgram ? $selectedProgram->name.' — ' : '').'Transcript of Records',
            },
            'section' => [
                'active' => $history->firstWhere('is_active', true),
                'history' => $history,
                'defaults' => CertificateTemplate::formDefaults($type),
            ],
            'programs' => $programs,
            'selectedProgram' => $selectedProgram,
            'selectedProgramId' => $selectedProgramId,
            'competencyUnits' => $competencyUnits,
            'torRows' => $torRows,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = $request->validate([
            'type' => ['required', Rule::in(CertificateTemplate::managedTypes())],
        ])['type'];

        $validated = $request->validate([
            'training_program_id' => ['nullable', 'integer', 'exists:training_programs,id'],
            'title' => ['required', 'string', 'max:180'],
            'body' => [$type === OfficialDocument::TYPE_COTC ? 'required' : 'nullable', 'string', 'max:2000'],
            'paper_size' => ['required', Rule::in(['letter', 'a4', 'legal'])],
            'orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            'signatory_name' => ['nullable', 'string', 'max:180'],
            'signatory_role' => ['nullable', 'string', 'max:120'],
            'second_signatory_name' => ['nullable', 'string', 'max:180'],
            'second_signatory_role' => ['nullable', 'string', 'max:120'],
            'background' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:2048'],
            'field_layout' => ['nullable', 'string', 'max:8000'],
            'trainee_name_y' => ['nullable', 'numeric', 'min:5', 'max:95'],
            'trainee_name_x' => ['nullable', 'numeric', 'min:0', 'max:90'],
            'trainee_name_size' => ['nullable', 'numeric', 'min:12', 'max:42'],
            'trainee_name_align' => ['nullable', Rule::in(['C', 'L', 'R'])],
            'include_cert_no' => ['nullable', 'boolean'],
            'include_date' => ['nullable', 'boolean'],
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'second_signature' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $programId = ! empty($validated['training_program_id']) ? (int) $validated['training_program_id'] : null;
        $previous = CertificateTemplate::activeFor($type, $programId);
        $importedBackground = $request->hasFile('background');
        $useArtwork = $type === OfficialDocument::TYPE_COTC && $request->boolean('use_official_artwork') && ! $importedBackground;
        $backgroundPath = null;
        if ($importedBackground) {
            $backgroundPath = $request->file('background')->store('certificate-templates', 'local');
        } elseif (! $useArtwork && ! $request->boolean('clear_background')) {
            $backgroundPath = $previous?->background_path;
        }

        // Build customized field layout prioritizing Trainee Name
        $fieldLayout = null;
        if ($backgroundPath) {
            $baseLayout = $this->fieldLayout($validated['field_layout'] ?? null, $type)
                ?? $previous?->field_layout
                ?? CertificateTemplate::automaticFieldLayout($type, $validated['orientation']);

            if (isset($validated['trainee_name_y'])) {
                $nameX = (float) ($validated['trainee_name_x'] ?? 5);
                $nameY = (float) $validated['trainee_name_y'];
                $nameSize = (float) ($validated['trainee_name_size'] ?? 24);
                $nameAlign = (string) ($validated['trainee_name_align'] ?? 'C');
                $nameW = $nameAlign === 'C' ? 90 : 80;

                $baseLayout['trainee_name'] = [
                    'x' => $nameX,
                    'y' => $nameY,
                    'w' => $nameW,
                    'h' => max(6.0, $nameSize * 0.35),
                    'size' => $nameSize,
                    'align' => $nameAlign,
                ];

                if (! $request->boolean('include_cert_no')) {
                    unset($baseLayout['certificate_no']);
                }
                if (! $request->boolean('include_date')) {
                    unset($baseLayout['completion_date']);
                }
            }
            $fieldLayout = $baseLayout;
        }

        $template = new CertificateTemplate([
            'type' => $type,
            'training_program_id' => $programId,
            'version' => ((int) CertificateTemplate::query()->where('type', $type)->max('version')) + 1,
            'is_active' => false,
            'title' => trim($validated['title']),
            'body' => trim((string) ($validated['body'] ?? '')),
            'paper_size' => $useArtwork ? 'letter' : $validated['paper_size'],
            'orientation' => $useArtwork ? 'landscape' : $validated['orientation'],
            'use_official_artwork' => $useArtwork,
            'background_path' => $backgroundPath,
            'field_layout' => $fieldLayout,
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

        if ($request->boolean('clear_signature')) {
            $template->signature_path = null;
        }
        if ($request->boolean('clear_second_signature')) {
            $template->second_signature_path = null;
        }

        $activateImmediately = $request->boolean('activate_immediately', false);

        DB::transaction(function () use ($template, $type, $programId, $importedBackground, $activateImmediately): void {
            if (! $importedBackground || $activateImmediately) {
                CertificateTemplate::query()
                    ->where('type', $type)
                    ->when($programId, fn ($q) => $q->where('training_program_id', $programId), fn ($q) => $q->whereNull('training_program_id'))
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
                $template->is_active = true;
            }
            $template->save();
        });

        AdminActivityLog::record($request->user(), 'admin.certificate-template.saved', $template, [
            'type' => $type,
            'training_program_id' => $programId,
            'version' => $template->version,
        ]);

        $label = CertificateTemplate::label($type);

        if ($importedBackground && ! $template->is_active) {
            return redirect()
                ->route('admin.learning.certificate-templates.edit', ['type' => $type, 'program_id' => $programId])
                ->with('saved', $label.' template version '.$template->version.' was saved. Choose Make active template when new documents should use this design.');
        }

        $saved = $label.' template version '.$template->version.' is active. The next generated '.$label.' uses this template.';

        return redirect()
            ->route('admin.learning.certificate-templates', ['program_id' => $programId])
            ->with('saved', $saved);
    }

    public function activate(Request $request, CertificateTemplate $certificateTemplate): RedirectResponse
    {
        DB::transaction(function () use ($certificateTemplate): void {
            CertificateTemplate::query()
                ->where('type', $certificateTemplate->type)
                ->when(
                    $certificateTemplate->training_program_id,
                    fn ($q) => $q->where('training_program_id', $certificateTemplate->training_program_id),
                    fn ($q) => $q->whereNull('training_program_id')
                )
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $certificateTemplate->update(['is_active' => true]);
        });

        AdminActivityLog::record($request->user(), 'admin.certificate-template.activated', $certificateTemplate, [
            'type' => $certificateTemplate->type,
            'training_program_id' => $certificateTemplate->training_program_id,
            'version' => $certificateTemplate->version,
        ]);

        $label = CertificateTemplate::label($certificateTemplate->type);

        return back()->with('saved', $label.' template version '.$certificateTemplate->version.' is now the active template.');
    }

    public function destroy(Request $request, CertificateTemplate $certificateTemplate): RedirectResponse
    {
        $type = $certificateTemplate->type;
        $programId = $certificateTemplate->training_program_id;
        $version = $certificateTemplate->version;
        $wasActive = (bool) $certificateTemplate->is_active;
        $label = CertificateTemplate::label($type);

        AdminActivityLog::record($request->user(), 'admin.certificate-template.deleted', $certificateTemplate, [
            'type' => $type,
            'training_program_id' => $programId,
            'version' => $version,
        ]);

        DB::transaction(function () use ($certificateTemplate, $wasActive, $type, $programId): void {
            $certificateTemplate->delete();

            if (! $wasActive) {
                return;
            }

            CertificateTemplate::query()
                ->where('type', $type)
                ->when($programId, fn ($q) => $q->where('training_program_id', $programId), fn ($q) => $q->whereNull('training_program_id'))
                ->orderByDesc('version')
                ->first()
                ?->update(['is_active' => true]);
        });

        $restoredDefault = $wasActive && CertificateTemplate::activeFor($type, $programId) === null;

        return back()->with(
            'saved',
            $restoredDefault
                ? $label.' saved template was deleted. The official layout is used again.'
                : $label.' template version '.$version.' was deleted.',
        );
    }

    public function preview(Request $request, string $type, FpdfOfficialDocumentRenderer $renderer): Response
    {
        abort_unless(in_array($type, CertificateTemplate::managedTypes(), true), 404);

        $programId = $request->query('program_id') ? (int) $request->query('program_id') : null;
        $pdf = $renderer->render($this->sampleDocument($type, $programId));
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
            'background' => $certificateTemplate->background_path,
            default => null,
        };
        $absolute = $certificateTemplate->absoluteAsset($path);
        abort_if($absolute === null, 404);

        return response()->file($absolute);
    }

    private function sampleDocument(string $type, ?int $trainingProgramId = null): OfficialDocument
    {
        $programName = 'Caregiving NC II';
        $programModel = null;
        if ($trainingProgramId) {
            $programModel = TrainingProgram::find($trainingProgramId);
            if ($programModel) {
                $programName = $programModel->name;
            }
        }

        $application = new EnrollmentApplication([
            'enrollment_number' => 'MCE-2026-SAMPLE',
            'training_program_id' => $trainingProgramId,
            'program' => $programName,
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
        if ($programModel) {
            $application->setRelation('trainingProgram', $programModel);
        }
        $batch = new TrainingBatch([
            'name' => 'Batch 1',
            'year' => 2026,
            'training_starts_at' => '2026-01-15',
            'training_ends_at' => '2026-07-18',
        ]);
        $application->setRelation('batch', $batch);

        $resolvedRows = self::resolveCompetenciesForProgram($programModel);
        $records = collect($resolvedRows)->map(function ($row, $idx) {
            $unit = new CompetencyUnit([
                'code' => $row[0],
                'title' => $row[1],
                'is_tor_included' => true,
                'sort_order' => $idx + 1,
            ]);
            $record = new TraineeCompetencyRecord([
                'status' => TraineeCompetencyRecord::STATUS_COMPETENT,
                'tor_grade' => (float) $row[2],
            ]);
            $record->setRelation('unit', $unit);
            return $record;
        });
        $application->setRelation('competencyRecords', $records);

        $document = new OfficialDocument([
            'type' => $type,
            'version' => 1,
            'document_number' => 'MCARE-'.strtoupper($type).'-2026-00002-V1',
            'generated_at' => '2026-07-18 09:00:00',
        ]);
        $document->setRelation('application', $application);

        return $document;
    }

    /** @return array<string, array{x: float, y: float, w: float, h: float, size: float, align: string}>|null */
    private function fieldLayout(?string $json, string $type): ?array
    {
        $decoded = json_decode((string) $json, true);
        if (! is_array($decoded)) {
            return null;
        }

        $layout = [];
        foreach (array_keys(CertificateTemplate::importFields($type)) as $key) {
            $box = $decoded[$key] ?? null;
            if (! is_array($box)) {
                continue;
            }

            $x = max(0, min(92, (float) ($box['x'] ?? 0)));
            $y = max(0, min(92, (float) ($box['y'] ?? 0)));
            $layout[$key] = [
                'x' => $x,
                'y' => $y,
                'w' => max(8, min(100 - $x, (float) ($box['w'] ?? 20))),
                'h' => max(4, min(40, (float) ($box['h'] ?? 6))),
                'size' => max(7, min(42, (float) ($box['size'] ?? 11))),
                'align' => in_array($box['align'] ?? 'C', ['L', 'C', 'R'], true) ? (string) $box['align'] : 'C',
            ];
        }

        return $layout === [] ? null : $layout;
    }

    private function blankToNull(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
