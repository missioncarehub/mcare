<?php

namespace App\Services\DocumentStudio;

use App\Models\AdminActivityLog;
use App\Models\DocumentAsset;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class DocumentStudioService
{
    public function __construct(
        private readonly AssetSecurityService $assetSecurity,
    ) {}

    /**
     * Create a new document template with initial draft.
     */
    public function createTemplate(array $data, ?User $user = null): DocumentTemplate
    {
        return DB::transaction(function () use ($data, $user) {
            $documentKind = $data['document_kind'] ?? DocumentTemplate::KIND_GENERAL;
            $engine = $data['engine'] ?? DocumentTemplate::ENGINE_PDFME;

            $initialState = $data['initial_state'] ?? [
                'schema_version' => '1.0',
                'engine' => $engine,
                'page' => $data['page_settings'] ?? [
                    'size' => ($documentKind === 'tor' ? 'a4' : 'letter'),
                    'orientation' => ($documentKind === 'cotc' ? 'landscape' : 'portrait'),
                    'width_mm' => ($documentKind === 'cotc' ? 279.4 : 210.0),
                    'height_mm' => ($documentKind === 'cotc' ? 215.9 : 297.0),
                ],
                'elements' => [],
            ];
            $initialState = $this->assetSecurity->sanitizeEditorState($initialState);
            $this->validateTemplateState($engine, $initialState);
            if ($this->assetSecurity->assetIds($initialState)) {
                throw ValidationException::withMessages(['draft_state' => 'Upload assets into the new template before linking them.']);
            }

            $template = DocumentTemplate::create([
                'name' => trim($data['name']),
                'slug' => $data['slug'] ?? null,
                'document_kind' => $documentKind,
                'engine' => $engine,
                'training_program_id' => $data['training_program_id'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_system_default' => false,
                'draft_revision' => 1,
                'draft_state' => $initialState,
                'draft_updated_at' => now(),
                'created_by_id' => $user?->id,
                'updated_by_id' => $user?->id,
            ]);

            if ($user) {
                AdminActivityLog::record($user, 'admin.document-studio.template-created', $template, [
                    'name' => $template->name,
                    'kind' => $template->document_kind,
                    'engine' => $template->engine,
                ]);
            }

            return $template;
        });
    }

    /**
     * Autosave or save draft state with optimistic concurrency check.
     */
    public function saveDraft(
        DocumentTemplate $template,
        array $draftState,
        int $clientRevision,
        ?User $user = null,
    ): DocumentTemplate {
        return DB::transaction(function () use ($template, $draftState, $clientRevision, $user) {
            $locked = DocumentTemplate::query()->lockForUpdate()->findOrFail($template->id);

            if ($clientRevision !== (int) $locked->draft_revision) {
                throw ValidationException::withMessages([
                    'revision' => sprintf(
                        'Optimistic concurrency conflict: draft was updated by another session (Server rev: %d, Your rev: %d). Please refresh.',
                        $locked->draft_revision,
                        $clientRevision
                    ),
                ]);
            }

            $sanitizedState = $this->assetSecurity->sanitizeEditorState($draftState);
            $this->validateTemplateState($locked->engine, $sanitizedState);
            $this->assetSecurity->assetIntegrity($sanitizedState, $locked->id);

            $locked->update([
                'draft_state' => $sanitizedState,
                'draft_revision' => $locked->draft_revision + 1,
                'draft_updated_at' => now(),
                'updated_by_id' => $user?->id,
            ]);

            return $locked->fresh();
        });
    }

    /**
     * Publish current draft as a new immutable version and optionally activate it.
     */
    public function publishVersion(
        DocumentTemplate $template,
        ?string $changelog = null,
        bool $makeActive = true,
        ?User $user = null,
        ?array $draftState = null,
        ?int $clientRevision = null,
    ): DocumentTemplateVersion {
        return DB::transaction(function () use ($template, $changelog, $makeActive, $user, $draftState, $clientRevision) {
            $locked = DocumentTemplate::query()->lockForUpdate()->findOrFail($template->id);

            if ($clientRevision !== null && $clientRevision !== (int) $locked->draft_revision) {
                throw ValidationException::withMessages(['revision' => 'Another session changed this draft. Reload before publishing.']);
            }
            if ($draftState !== null) {
                if ($clientRevision === null) {
                    throw ValidationException::withMessages(['revision' => 'A draft revision is required to publish changes.']);
                }
                $locked->draft_state = $this->assetSecurity->sanitizeEditorState($draftState);
            }

            $editorState = $locked->draft_state ?? [];
            if (empty($editorState)) {
                throw ValidationException::withMessages([
                    'draft_state' => 'Cannot publish a template with an empty draft state.',
                ]);
            }

            $this->validateTemplateState($locked->engine, $editorState);

            $audit = $this->auditTemplate($locked, $editorState);
            if (! $audit['is_ready_for_issuance']) {
                $requiredWarnings = array_filter($audit['warnings'] ?? [], fn ($w) => str_contains($w, 'Required for publication'));
                $reason = ! empty($requiredWarnings)
                    ? implode(' ', $requiredWarnings)
                    : ($audit['summary'] ?? 'Template does not meet official issuance requirements.');
                throw ValidationException::withMessages([
                    'draft_state' => 'Template does not meet official issuance requirements: '.$reason,
                ]);
            }

            $editorState['_asset_integrity'] = $this->assetSecurity->assetIntegrity($editorState, $locked->id);
            if ($draftState !== null) {
                $locked->update([
                    'draft_revision' => $locked->draft_revision + 1,
                    'draft_updated_at' => now(),
                    'updated_by_id' => $user?->id,
                ]);
            }

            $serialized = json_encode($editorState, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $sha256 = hash('sha256', $serialized);

            $lastVersion = (int) ($locked->versions()->max('version_number') ?? 0);
            $nextVersionNumber = $lastVersion + 1;

            $pageSettings = $editorState['page'] ?? [
                'size' => 'letter',
                'orientation' => ($locked->document_kind === 'cotc' ? 'landscape' : 'portrait'),
            ];

            // Extract placeholder contract from elements/schemas
            $contract = $this->extractUsedPlaceholders($editorState);

            $version = DocumentTemplateVersion::create([
                'document_template_id' => $locked->id,
                'version_number' => $nextVersionNumber,
                'schema_version' => $editorState['schema_version'] ?? '1.0',
                'engine' => $locked->engine,
                'engine_version' => $editorState['engine_version'] ?? '1.0',
                'page_settings' => $pageSettings,
                'editor_state' => $editorState,
                'placeholder_contract' => $contract,
                'sha256' => $sha256,
                'changelog' => $changelog,
                'created_by_id' => $user?->id,
            ]);

            if ($makeActive) {
                $scopeQuery = DocumentTemplate::query()
                    ->where('id', '!=', $locked->id)
                    ->where('document_kind', $locked->document_kind);

                if ($locked->training_program_id !== null) {
                    $scopeQuery->where('training_program_id', $locked->training_program_id);
                } else {
                    $scopeQuery->whereNull('training_program_id');
                }

                $scopeQuery->update(['is_active' => false]);

                $locked->update([
                    'active_version_id' => $version->id,
                    'is_active' => true,
                    'updated_by_id' => $user?->id,
                ]);
            }

            if ($user) {
                AdminActivityLog::record($user, 'admin.document-studio.version-published', $version, [
                    'template_id' => $locked->id,
                    'version_number' => $version->version_number,
                    'sha256' => $sha256,
                    'is_active' => $makeActive,
                ]);
            }

            return $version;
        });
    }

    /**
     * Activate a specific past version.
     */
    public function activateVersion(
        DocumentTemplate $template,
        DocumentTemplateVersion $version,
        ?User $user = null,
    ): DocumentTemplate {
        if ((int) $version->document_template_id !== (int) $template->id) {
            throw ValidationException::withMessages([
                'version' => 'Selected version does not belong to this template.',
            ]);
        }

        if (! $version->verifyChecksum()) {
            throw ValidationException::withMessages([
                'version' => 'Version integrity check failed: SHA-256 does not match canonical editor state.',
            ]);
        }

        return DB::transaction(function () use ($template, $version, $user) {
            $locked = DocumentTemplate::query()->lockForUpdate()->findOrFail($template->id);

            $scopeQuery = DocumentTemplate::query()
                ->where('id', '!=', $locked->id)
                ->where('document_kind', $locked->document_kind);

            if ($locked->training_program_id !== null) {
                $scopeQuery->where('training_program_id', $locked->training_program_id);
            } else {
                $scopeQuery->whereNull('training_program_id');
            }

            $scopeQuery->update(['is_active' => false]);

            $locked->update([
                'active_version_id' => $version->id,
                'is_active' => true,
                'updated_by_id' => $user?->id,
            ]);

            if ($user) {
                AdminActivityLog::record($user, 'admin.document-studio.version-activated', $version, [
                    'template_id' => $locked->id,
                    'version_number' => $version->version_number,
                ]);
            }

            return $locked->fresh(['activeVersion']);
        });
    }

    /**
     * Extract placeholders used in editor state elements.
     */
    public function extractUsedPlaceholders(array $state): array
    {
        $serialized = json_encode($state);
        $used = [];
        foreach (PlaceholderRegistry::definitions() as $key => $def) {
            if (str_contains($serialized, $def['token']) || str_contains($serialized, $key)) {
                $used[] = $key;
            }
        }

        return $used;
    }

    /**
     * Ensure standard default templates exist for COTC, TOR, and SOA.
     */
    public function ensureDefaultTemplates(): void
    {
        if (! Schema::hasTable('document_templates')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable) {
            }
            if (! Schema::hasTable('document_templates')) {
                return;
            }
        }

        // 1. COTC Default Template
        if (! DocumentTemplate::where('slug', 'default-cotc')->exists()) {
            $cotcState = [
                'schema_version' => '1.0',
                'engine' => DocumentTemplate::ENGINE_PDFME,
                'page' => [
                    'size' => 'letter',
                    'orientation' => 'landscape',
                    'width_mm' => 279.4,
                    'height_mm' => 215.9,
                ],
                'base_pdf_url' => '/assets/cotc-base-template.pdf',
                'elements' => $this->defaultCotcElements('CAREGIVING NC II'),
            ];

            $cotc = DocumentTemplate::create([
                'name' => 'Official Certificate of Training Completion (Default)',
                'slug' => 'default-cotc',
                'document_kind' => DocumentTemplate::KIND_COTC,
                'engine' => DocumentTemplate::ENGINE_PDFME,
                'is_active' => true,
                'is_system_default' => true,
                'draft_revision' => 1,
                'draft_state' => $cotcState,
                'draft_updated_at' => now(),
            ]);

            $this->publishVersion($cotc, 'Initial system release', true);
        }

        // 2. TOR Default Template
        if (! DocumentTemplate::where('slug', 'default-tor')->exists()) {
            $torState = [
                'schema_version' => '1.0',
                'engine' => DocumentTemplate::ENGINE_PDFME,
                'page' => [
                    'size' => 'a4',
                    'orientation' => 'portrait',
                    'width_mm' => 210.0,
                    'height_mm' => 297.0,
                ],
                'elements' => [
                    [
                        'id' => 'title',
                        'type' => 'text',
                        'x' => 15.0,
                        'y' => 20.0,
                        'width' => 180.0,
                        'height' => 10.0,
                        'fontSize' => 16,
                        'fontWeight' => 'bold',
                        'textAlign' => 'center',
                        'binding' => 'OFFICIAL TRANSCRIPT OF RECORDS',
                    ],
                    [
                        'id' => 'trainee_name',
                        'type' => 'text',
                        'x' => 15.0,
                        'y' => 45.0,
                        'width' => 180.0,
                        'height' => 8.0,
                        'fontSize' => 12,
                        'fontWeight' => 'bold',
                        'binding' => 'Name: {{trainee_name}}',
                    ],
                    [
                        'id' => 'program_name',
                        'type' => 'text',
                        'x' => 15.0,
                        'y' => 55.0,
                        'width' => 180.0,
                        'height' => 8.0,
                        'fontSize' => 11,
                        'binding' => 'Program: {{training_program}} ({{batch_name}})',
                    ],
                    [
                        'id' => 'competencies_table',
                        'type' => 'table',
                        'x' => 15.0,
                        'y' => 70.0,
                        'width' => 180.0,
                        'binding' => 'tor_competencies',
                    ],
                ],
            ];

            $tor = DocumentTemplate::create([
                'name' => 'Official Transcript of Records (Default)',
                'slug' => 'default-tor',
                'document_kind' => DocumentTemplate::KIND_TOR,
                'engine' => DocumentTemplate::ENGINE_PDFME,
                'is_active' => true,
                'is_system_default' => true,
                'draft_revision' => 1,
                'draft_state' => $torState,
                'draft_updated_at' => now(),
            ]);

            $this->publishVersion($tor, 'Initial system release', true);
        }

        // 3. SOA Default Template
        if (! DocumentTemplate::where('slug', 'default-soa')->exists()) {
            $soaState = [
                'schema_version' => '1.0',
                'engine' => DocumentTemplate::ENGINE_PDFME,
                'page' => [
                    'size' => 'letter',
                    'orientation' => 'portrait',
                    'width_mm' => 215.9,
                    'height_mm' => 279.4,
                ],
                'elements' => [
                    [
                        'id' => 'title',
                        'type' => 'text',
                        'x' => 16.0,
                        'y' => 30.0,
                        'width' => 183.9,
                        'height' => 8.0,
                        'fontSize' => 14,
                        'fontWeight' => 'bold',
                        'textAlign' => 'center',
                        'binding' => 'STATEMENT OF ACCOUNT',
                    ],
                    [
                        'id' => 'trainee_info',
                        'type' => 'text',
                        'x' => 16.0,
                        'y' => 45.0,
                        'width' => 183.9,
                        'height' => 12.0,
                        'fontSize' => 10,
                        'binding' => 'Trainee: {{trainee_name}} | Enrollment No: {{enrollment_number}}',
                    ],
                    [
                        'id' => 'soa_summary',
                        'type' => 'text',
                        'x' => 16.0,
                        'y' => 60.0,
                        'width' => 183.9,
                        'height' => 15.0,
                        'fontSize' => 10,
                        'binding' => 'Total Program Fees: {{total_program_fee}} | Credited: {{total_paid}} | Balance: {{outstanding_balance}}',
                    ],
                    [
                        'id' => 'payments_table',
                        'type' => 'table',
                        'x' => 16.0,
                        'y' => 80.0,
                        'width' => 183.9,
                        'binding' => 'soa_payments',
                    ],
                ],
            ];

            $soa = DocumentTemplate::create([
                'name' => 'Statement of Account (Default)',
                'slug' => 'default-soa',
                'document_kind' => DocumentTemplate::KIND_SOA,
                'engine' => DocumentTemplate::ENGINE_PDFME,
                'is_active' => true,
                'is_system_default' => true,
                'draft_revision' => 1,
                'draft_state' => $soaState,
                'draft_updated_at' => now(),
            ]);

            $this->publishVersion($soa, 'Initial system release', true);
        }

        // 4. Ensure Program-Specific Credentials (Caregiving NC II and NC III)
        if (Schema::hasTable('training_programs')) {
            $nc2 = \App\Models\TrainingProgram::firstOrCreate(
                ['code' => 'CAREGIVING-NC-II'],
                [
                    'name' => 'Caregiving NC II',
                    'description' => 'Caregiving NC II Qualification Program',
                    'total_program_fee' => 22000.00,
                    'downpayment_amount' => 2000.00,
                    'is_active' => true,
                ]
            );

            $nc3 = \App\Models\TrainingProgram::firstOrCreate(
                ['code' => 'CAREGIVING-NC-III'],
                [
                    'name' => 'Caregiving NC III',
                    'description' => 'Caregiving NC III Qualification Program',
                    'total_program_fee' => 28000.00,
                    'downpayment_amount' => 3000.00,
                    'is_active' => true,
                ]
            );

            $activePrograms = \App\Models\TrainingProgram::where('is_active', true)->get();
            foreach ($activePrograms as $prog) {
                $this->ensureProgramSpecificTemplates($prog);
            }
        }
    }

    /**
     * Build default certificate-grade text elements for a COTC template.
     */
    private function defaultCotcElements(string $programName, string $nameFont = 'PlayfairDisplay-Bold'): array
    {
        return [
            [
                'id' => 'name_text',
                'type' => 'text',
                'x' => 25.0,
                'y' => 90.0,
                'width' => 230.0,
                'height' => 18.0,
                'fontSize' => $nameFont === 'GreatVibes' ? 32 : 26,
                'fontName' => $nameFont,
                'fontWeight' => 'bold',
                'textAlign' => 'center',
                'binding' => '{{trainee_name}}',
                'content' => 'MARIA REYES SANTOS',
            ],
            [
                'id' => 'completion_statement',
                'type' => 'text',
                'x' => 25.0,
                'y' => 109.0,
                'width' => 230.0,
                'height' => 7.0,
                'fontSize' => 12,
                'fontName' => 'CormorantGaramond',
                'textAlign' => 'center',
                'content' => 'for having successfully completed the training program in',
            ],
            [
                'id' => 'program_text',
                'type' => 'text',
                'x' => 25.0,
                'y' => 118.0,
                'width' => 230.0,
                'height' => 10.0,
                'fontSize' => 16,
                'fontName' => 'Cinzel-Bold',
                'fontWeight' => 'bold',
                'textAlign' => 'center',
                'binding' => '{{training_program}}',
                'content' => $programName,
            ],
            [
                'id' => 'date_text',
                'type' => 'text',
                'x' => 25.0,
                'y' => 135.0,
                'width' => 230.0,
                'height' => 8.0,
                'fontSize' => 11,
                'fontName' => 'CormorantGaramond',
                'textAlign' => 'center',
                'binding' => 'Given this {{completion_date}} at {{organization_name}}',
                'content' => 'Given this August 28, 2026 at Mission Care Training Center',
            ],
            [
                'id' => 'release_date_text',
                'type' => 'text',
                'x' => 25.0,
                'y' => 145.0,
                'width' => 230.0,
                'height' => 6.0,
                'fontSize' => 8.5,
                'fontName' => 'Montserrat',
                'textAlign' => 'center',
                'binding' => 'Date of Release: {{release_date}}',
                'content' => 'Date of Release: ' . now()->format('F j, Y'),
            ],
            [
                'id' => 'doc_number_text',
                'type' => 'text',
                'x' => 18.0,
                'y' => 195.0,
                'width' => 90.0,
                'height' => 6.0,
                'fontSize' => 8,
                'fontName' => 'Montserrat',
                'textAlign' => 'left',
                'binding' => 'No. {{certificate_number}}',
                'content' => 'No. MCARE-COTC-2026-00001',
            ],
            [
                'id' => 'registrar_name',
                'type' => 'text',
                'x' => 185.0,
                'y' => 186.0,
                'width' => 75.0,
                'height' => 6.0,
                'fontSize' => 9.5,
                'fontName' => 'Cinzel-Bold',
                'fontWeight' => 'bold',
                'textAlign' => 'center',
                'binding' => '{{registrar_name}}',
                'content' => (string) config('official_documents.organization.registrar_name', 'Salvacion A. Collao'),
            ],
            [
                'id' => 'registrar_role',
                'type' => 'text',
                'x' => 185.0,
                'y' => 192.0,
                'width' => 75.0,
                'height' => 5.0,
                'fontSize' => 8,
                'fontName' => 'Montserrat',
                'textAlign' => 'center',
                'binding' => '{{registrar_role}}',
                'content' => 'Authorized Registrar',
            ],
        ];
    }

    /**
     * Build a 100% dynamic native PDFme schema page for COTC on the clean border base PDF.
     */
    private function defaultCotcSchemas(string $programName, string $nameFont = 'PlayfairDisplay-Bold', ?DocumentAsset $logoAsset = null): array
    {
        $schemas = [];

        if ($logoAsset) {
            $schemas[] = [
                'name' => 'logo_image',
                'type' => 'image',
                'position' => ['x' => 16.0, 'y' => 14.0],
                'width' => 22.0,
                'height' => 22.0,
                'asset_id' => $logoAsset->id,
                'content' => route('admin.document-studio.assets.stream', $logoAsset->id, false),
            ];
        }

        $textStartX = $logoAsset ? 42.0 : 15.0;
        $headerWidth = $logoAsset ? 218.0 : 245.0;

        array_push(
            $schemas,
            [
                'name' => 'organization_heading',
                'type' => 'text',
                'position' => ['x' => $textStartX, 'y' => 16.0],
                'width' => $headerWidth,
                'height' => 7.5,
                'fontSize' => 13,
                'fontName' => 'Cinzel-Bold',
                'fontColor' => '#4c1d95',
                'alignment' => 'left',
                'content' => 'MISSION CARE TRAINING AND ASSESSMENT CENTER',
            ],
            [
                'name' => 'organization_subtitle',
                'type' => 'text',
                'position' => ['x' => $textStartX, 'y' => 26.5],
                'width' => $headerWidth,
                'height' => 6.0,
                'fontSize' => 8.5,
                'fontName' => 'Montserrat',
                'fontColor' => '#6b7280',
                'alignment' => 'left',
                'content' => 'TESDA-Accredited Caregiving Training & Assessment Institution',
            ],
            [
                'name' => 'certificate_title',
                'type' => 'text',
                'position' => ['x' => 15.0, 'y' => 44.0],
                'width' => 245.0,
                'height' => 12.0,
                'fontSize' => 24,
                'fontName' => 'PlayfairDisplay-Bold',
                'fontColor' => '#1e1b4b',
                'alignment' => 'left',
                'content' => 'CERTIFICATE OF TRAINING COMPLETION',
            ],
            [
                'name' => 'salutation',
                'type' => 'text',
                'position' => ['x' => 15.0, 'y' => 66.0],
                'width' => 180.0,
                'height' => 7.0,
                'fontSize' => 13,
                'fontName' => 'CormorantGaramond',
                'fontColor' => '#374151',
                'alignment' => 'left',
                'content' => 'This certificate is proudly presented to',
            ],
            [
                'name' => 'trainee_name',
                'type' => 'text',
                'position' => ['x' => 15.0, 'y' => 88.5],
                'width' => 178.0,
                'height' => 16.0,
                'fontSize' => $nameFont === 'GreatVibes' ? 34 : 28,
                'fontName' => $nameFont,
                'fontColor' => '#111827',
                'alignment' => 'left',
                'binding' => '{{trainee_name}}',
                'content' => '{{trainee_name}}',
            ],
            [
                'name' => 'completion_statement',
                'type' => 'text',
                'position' => ['x' => 15.0, 'y' => 112.0],
                'width' => 235.0,
                'height' => 7.5,
                'fontSize' => 12,
                'fontName' => 'CormorantGaramond',
                'fontColor' => '#374151',
                'alignment' => 'left',
                'content' => 'for having successfully completed the competency-based training requirements in',
            ],
            [
                'name' => 'training_program',
                'type' => 'text',
                'position' => ['x' => 15.0, 'y' => 124.0],
                'width' => 235.0,
                'height' => 9.5,
                'fontSize' => 16,
                'fontName' => 'Cinzel-Bold',
                'fontColor' => '#5b21b6',
                'alignment' => 'left',
                'binding' => '{{training_program}}',
                'content' => '{{training_program}}',
            ],
            [
                'name' => 'completion_date',
                'type' => 'text',
                'position' => ['x' => 15.0, 'y' => 138.0],
                'width' => 235.0,
                'height' => 7.5,
                'fontSize' => 11.5,
                'fontName' => 'CormorantGaramond',
                'fontColor' => '#374151',
                'alignment' => 'left',
                'binding' => 'Given this {{completion_date}} at {{organization_name}}',
                'content' => 'Given this {{completion_date}} at {{organization_name}}',
            ],
            [
                'name' => 'release_date',
                'type' => 'text',
                'position' => ['x' => 15.0, 'y' => 150.0],
                'width' => 180.0,
                'height' => 6.0,
                'fontSize' => 9,
                'fontName' => 'Montserrat',
                'fontColor' => '#4b5563',
                'alignment' => 'left',
                'binding' => 'Date of Release: {{release_date}}',
                'content' => 'Date of Release: {{release_date}}',
            ],
            [
                'name' => 'trainer_name',
                'type' => 'text',
                'position' => ['x' => 15.0, 'y' => 171.0],
                'width' => 85.0,
                'height' => 6.0,
                'fontSize' => 10,
                'fontName' => 'Cinzel-Bold',
                'fontColor' => '#111827',
                'alignment' => 'left',
                'binding' => '{{trainer_name}}',
                'content' => '{{trainer_name}}',
            ],
            [
                'name' => 'trainer_role',
                'type' => 'text',
                'position' => ['x' => 15.0, 'y' => 181.5],
                'width' => 85.0,
                'height' => 5.0,
                'fontSize' => 8,
                'fontName' => 'Montserrat',
                'fontColor' => '#6b7280',
                'alignment' => 'left',
                'binding' => '{{trainer_role}}',
                'content' => '{{trainer_role}}',
            ],
            [
                'name' => 'registrar_name',
                'type' => 'text',
                'position' => ['x' => 115.0, 'y' => 171.0],
                'width' => 85.0,
                'height' => 6.0,
                'fontSize' => 10,
                'fontName' => 'Cinzel-Bold',
                'fontColor' => '#111827',
                'alignment' => 'left',
                'binding' => '{{registrar_name}}',
                'content' => '{{registrar_name}}',
            ],
            [
                'name' => 'registrar_role',
                'type' => 'text',
                'position' => ['x' => 115.0, 'y' => 181.5],
                'width' => 85.0,
                'height' => 5.0,
                'fontSize' => 8,
                'fontName' => 'Montserrat',
                'fontColor' => '#6b7280',
                'alignment' => 'left',
                'binding' => '{{registrar_role}}',
                'content' => '{{registrar_role}}',
            ],
            [
                'name' => 'certificate_number',
                'type' => 'text',
                'position' => ['x' => 205.0, 'y' => 181.5],
                'width' => 60.0,
                'height' => 5.0,
                'fontSize' => 8,
                'fontName' => 'Montserrat',
                'fontColor' => '#6b7280',
                'alignment' => 'right',
                'binding' => 'No. {{certificate_number}}',
                'content' => 'No. {{certificate_number}}',
            ],
        );

        return $schemas;
    }

    private function seedDefaultLogoAsset(DocumentTemplate $template): ?DocumentAsset
    {
        $existing = $template->assets()->where('type', DocumentAsset::TYPE_LOGO)->first();
        if ($existing) {
            return $existing;
        }

        $logoPath = public_path('assets/official-logo.png');
        if (! is_file($logoPath)) {
            return null;
        }

        try {
            return $this->assetSecurity->storeUploadedFile(
                new UploadedFile($logoPath, 'official-logo.png', 'image/png', null, true),
                DocumentAsset::TYPE_LOGO,
                $template->id,
                null
            );
        } catch (\Throwable) {
            return null;
        }
    }

    public function ensureProgramSpecificTemplates(\App\Models\TrainingProgram $program): void
    {
        $programUpper = mb_strtoupper($program->name);
        $nameFont = str_contains($programUpper, 'NC III') ? 'GreatVibes' : 'PlayfairDisplay-Bold';

        // 1. Program-Specific COTC Template (100% Dynamic PDFme)
        $cotcSlug = "cotc-program-{$program->id}";
        $existingCotc = DocumentTemplate::where('document_kind', DocumentTemplate::KIND_COTC)
            ->where('training_program_id', $program->id)
            ->first();

        $buildCotcState = function (DocumentTemplate $template) use ($programUpper, $nameFont): array {
            $logoAsset = $this->seedDefaultLogoAsset($template);

            return [
                'schema_version' => '1.0',
                'engine' => DocumentTemplate::ENGINE_PDFME,
                'page' => [
                    'size' => 'letter',
                    'orientation' => 'landscape',
                    'width_mm' => 279.4,
                    'height_mm' => 215.9,
                ],
                'base_pdf_url' => '/assets/cotc-base-template.pdf',
                'schemas' => [$this->defaultCotcSchemas($programUpper, $nameFont, $logoAsset)],
            ];
        };

        if (! $existingCotc && ! DocumentTemplate::where('slug', $cotcSlug)->exists()) {
            $cotc = DocumentTemplate::create([
                'name' => "Certificate of Training Completion - {$program->name}",
                'slug' => $cotcSlug,
                'document_kind' => DocumentTemplate::KIND_COTC,
                'engine' => DocumentTemplate::ENGINE_PDFME,
                'training_program_id' => $program->id,
                'is_active' => true,
                'is_system_default' => false,
                'draft_revision' => 1,
                'draft_state' => [
                    'schema_version' => '1.0',
                    'engine' => DocumentTemplate::ENGINE_PDFME,
                    'page' => ['size' => 'letter', 'orientation' => 'landscape', 'width_mm' => 279.4, 'height_mm' => 215.9],
                    'base_pdf_url' => '/assets/cotc-base-template.pdf',
                    'schemas' => [[]],
                ],
                'draft_updated_at' => now(),
            ]);
            $cotcState = $buildCotcState($cotc);
            $cotc->update(['draft_state' => $cotcState]);
            $this->publishVersion($cotc->fresh(), "Initial release for {$program->name}", true);
        } elseif ($existingCotc && $this->shouldUpgradeBaselineTemplate($existingCotc, $cotcSlug)) {
            $this->upgradeBaselineTemplate($existingCotc, $buildCotcState($existingCotc), "Clean blank-border dynamic PDFme layout for {$program->name}");
        }

        // 2. Program-Specific TOR Template (Native PDFme Schemas)
        $torSlug = "tor-program-{$program->id}";
        $existingTor = DocumentTemplate::where('document_kind', DocumentTemplate::KIND_TOR)
            ->where('training_program_id', $program->id)
            ->first();

        $torState = [
            'schema_version' => '1.0',
            'engine' => DocumentTemplate::ENGINE_PDFME,
            'page' => [
                'size' => 'a4',
                'orientation' => 'portrait',
                'width_mm' => 210.0,
                'height_mm' => 297.0,
            ],
            'schemas' => [[
                [
                    'name' => 'title',
                    'type' => 'text',
                    'position' => ['x' => 15.0, 'y' => 20.0],
                    'width' => 180.0,
                    'height' => 8.0,
                    'fontSize' => 16,
                    'fontName' => 'Cinzel-Bold',
                    'fontColor' => '#1e1b4b',
                    'alignment' => 'center',
                    'content' => 'OFFICIAL TRANSCRIPT OF RECORDS',
                ],
                [
                    'name' => 'trainee_name',
                    'type' => 'text',
                    'position' => ['x' => 15.0, 'y' => 38.0],
                    'width' => 180.0,
                    'height' => 7.0,
                    'fontSize' => 12,
                    'fontName' => 'PlayfairDisplay-Bold',
                    'fontColor' => '#111827',
                    'alignment' => 'left',
                    'binding' => 'Name: {{trainee_name}}',
                    'content' => 'Name: {{trainee_name}}',
                ],
                [
                    'name' => 'program_name',
                    'type' => 'text',
                    'position' => ['x' => 15.0, 'y' => 46.0],
                    'width' => 180.0,
                    'height' => 7.0,
                    'fontSize' => 11,
                    'fontName' => 'Montserrat',
                    'fontColor' => '#374151',
                    'alignment' => 'left',
                    'binding' => 'Course: {{training_program}}',
                    'content' => 'Course: {{training_program}}',
                ],
                [
                    'name' => 'release_date',
                    'type' => 'text',
                    'position' => ['x' => 15.0, 'y' => 54.0],
                    'width' => 180.0,
                    'height' => 6.0,
                    'fontSize' => 9,
                    'fontName' => 'Montserrat',
                    'fontColor' => '#4b5563',
                    'alignment' => 'left',
                    'binding' => 'Date Released: {{release_date}}',
                    'content' => 'Date Released: {{release_date}}',
                ],
                [
                    'name' => 'competencies_table',
                    'type' => 'table',
                    'position' => ['x' => 15.0, 'y' => 64.0],
                    'width' => 180.0,
                    'height' => 150.0,
                    'binding' => 'tor_competencies',
                    'content' => '[]',
                ],
                [
                    'name' => 'trainer_signatory',
                    'type' => 'text',
                    'position' => ['x' => 15.0, 'y' => 245.0],
                    'width' => 80.0,
                    'height' => 12.0,
                    'fontSize' => 9,
                    'fontName' => 'Montserrat',
                    'fontColor' => '#111827',
                    'alignment' => 'left',
                    'binding' => 'Lead Trainer: {{trainer_name}}',
                    'content' => 'Lead Trainer: {{trainer_name}}',
                ],
                [
                    'name' => 'registrar_signatory',
                    'type' => 'text',
                    'position' => ['x' => 115.0, 'y' => 245.0],
                    'width' => 80.0,
                    'height' => 12.0,
                    'fontSize' => 9,
                    'fontName' => 'Montserrat',
                    'fontColor' => '#111827',
                    'alignment' => 'left',
                    'binding' => 'Registrar: {{registrar_name}}',
                    'content' => 'Registrar: {{registrar_name}}',
                ],
            ]],
        ];

        if (! $existingTor && ! DocumentTemplate::where('slug', $torSlug)->exists()) {
            $tor = DocumentTemplate::create([
                'name' => "Official Transcript of Records - {$program->name}",
                'slug' => $torSlug,
                'document_kind' => DocumentTemplate::KIND_TOR,
                'engine' => DocumentTemplate::ENGINE_PDFME,
                'training_program_id' => $program->id,
                'is_active' => true,
                'is_system_default' => false,
                'draft_revision' => 1,
                'draft_state' => $torState,
                'draft_updated_at' => now(),
            ]);
            $this->publishVersion($tor, "Initial release for {$program->name}", true);
        } elseif ($existingTor && $this->shouldUpgradeBaselineTemplate($existingTor, $torSlug)) {
            $this->upgradeBaselineTemplate($existingTor, $torState, "Native PDFme transcript layout for {$program->name}");
        }
    }

    private function shouldUpgradeBaselineTemplate(DocumentTemplate $template, string $expectedSlug): bool
    {
        if ($template->slug !== $expectedSlug || (int) $template->draft_revision !== 1) {
            return false;
        }
        if ($template->versions()->count() > 1) {
            return false;
        }
        $state = $template->draft_state ?? [];
        if (! empty($state['schemas'])) {
            return false;
        }
        if ($template->engine !== DocumentTemplate::ENGINE_PDFME) {
            return true;
        }

        return ! collect($state['elements'] ?? [])->contains(fn ($el) => ! empty($el['fontName']));
    }

    private function upgradeBaselineTemplate(DocumentTemplate $template, array $state, string $changelog): void
    {
        $template->update([
            'engine' => DocumentTemplate::ENGINE_PDFME,
            'draft_state' => $state,
            'draft_updated_at' => now(),
        ]);
        $this->publishVersion($template->fresh(), $changelog, true);
    }

    public function hasActualContent(array $state): bool
    {
        $engine = $state['engine'] ?? DocumentTemplate::ENGINE_PDFME;
        if ($engine === DocumentTemplate::ENGINE_RK_WORD) {
            return trim((string) ($state['html'] ?? $state['content'] ?? $state['body'] ?? '')) !== '';
        }

        // The renderer uses schemas whenever they are present. Older elements
        // must not make an emptied PDFme design appear publishable or nonblank.
        if ($engine === DocumentTemplate::ENGINE_PDFME && isset($state['schemas']) && is_array($state['schemas'])) {
            foreach ($state['schemas'] as $schemaPage) {
                if (is_array($schemaPage) && $schemaPage !== []) {
                    return true;
                }
            }
        } elseif (! empty($state['elements']) && is_array($state['elements'])) {
            return true;
        }

        if ($engine === DocumentTemplate::ENGINE_PDFME && ! empty($state['base_pdf_asset_id'])) {
            return true;
        }

        if ($engine === DocumentTemplate::ENGINE_PDFME && ! empty($state['basePdf']) && is_array($state['basePdf']) && (! empty($state['basePdf']['asset_id']) || ! empty($state['basePdf']['url']) || ! empty($state['basePdf']['file_path']))) {
            return true;
        }

        if ($engine === DocumentTemplate::ENGINE_FABRIC && ! empty($state['background_asset_id'])) {
            return true;
        }

        return false;
    }

    /** Initial system versions describe the established institutional renderer. */
    public function isBaselineVersion(?DocumentTemplateVersion $version): bool
    {
        return $version !== null && $version->version_number === 1 && (bool) $version->template?->is_system_default;
    }

    /**
     * Validate template state before publishing to prevent corrupt or unsupported schema publication.
     */
    public function validateTemplateState(string $engine, array $state): void
    {
        if (empty($state)) {
            throw ValidationException::withMessages([
                'draft_state' => 'Cannot publish a template with an empty draft state.',
            ]);
        }
        if (isset($state['engine']) && $state['engine'] !== $engine) {
            throw ValidationException::withMessages(['draft_state' => 'Editor state does not match the template engine.']);
        }
        if (isset($state['basePdf']) && ! is_array($state['basePdf'])) {
            throw ValidationException::withMessages(['draft_state' => 'Upload a base PDF as a private asset.']);
        }
        if (isset($state['basePdf']) && (empty($state['basePdf']['width']) || empty($state['basePdf']['height'])
            || ! is_numeric($state['basePdf']['width']) || ! is_numeric($state['basePdf']['height'])
            || $state['basePdf']['width'] < 20 || $state['basePdf']['width'] > 1000
            || $state['basePdf']['height'] < 20 || $state['basePdf']['height'] > 1000)) {
            throw ValidationException::withMessages(['draft_state' => 'Use the asset upload button to add a base PDF.']);
        }
        foreach (['width_mm', 'height_mm'] as $dimension) {
            if (isset($state['page'][$dimension]) && (! is_numeric($state['page'][$dimension])
                || $state['page'][$dimension] < 20 || $state['page'][$dimension] > 1000)) {
                throw ValidationException::withMessages(['draft_state' => 'Page dimensions must be between 20 and 1000 mm.']);
            }
        }
        if (preg_match_all('/\{\{\s*(\w+)\s*\}\}/', json_encode($state), $matches)) {
            foreach ($matches[1] as $key) {
                if (! array_key_exists($key, PlaceholderRegistry::definitions())) {
                    throw ValidationException::withMessages(['draft_state' => 'Unsupported placeholder: '.$key]);
                }
            }
        }
        if (isset($state['source_layout']) && ($engine !== 'pdfme' || ! in_array($state['source_layout'], ['soa', 'cotc', 'tor'], true) || ! isset($state['basePdf']['width']))) {
            throw ValidationException::withMessages(['draft_state' => 'Invalid existing-design layout mode.']);
        }
        $pages = $state['schemas'] ?? [$state['elements'] ?? []];
        if (! is_array($pages) || count($pages) > 20) {
            throw ValidationException::withMessages(['draft_state' => 'A template supports at most 20 pages.']);
        }
        $count = 0;
        foreach ($pages as $fields) {
            if (! is_array($fields)) {
                throw ValidationException::withMessages(['draft_state' => 'Invalid page schema.']);
            }
            foreach ($fields as $field) {
                if (! is_array($field) || ++$count > 500) {
                    throw ValidationException::withMessages(['draft_state' => 'A template supports at most 500 fields.']);
                }
                foreach (['x', 'y', 'width', 'height', 'fontSize', 'radius', 'radius_y', 'strokeWidth', 'angle', 'rotate', 'layout_slot_height'] as $property) {
                    if (isset($field[$property]) && (! is_numeric($field[$property]) || ! is_finite((float) $field[$property])
                        || abs((float) $field[$property]) > 2000
                        || (in_array($property, ['width', 'height', 'radius', 'radius_y', 'fontSize', 'strokeWidth', 'layout_slot_height'], true) && $field[$property] < 0))) {
                        throw ValidationException::withMessages(['draft_state' => 'Invalid element dimensions or position.']);
                    }
                }
                foreach (['x', 'y'] as $axis) {
                    if (isset($field['position'][$axis]) && (! is_numeric($field['position'][$axis]) || ! is_finite((float) $field['position'][$axis]) || abs((float) $field['position'][$axis]) > 2000)) {
                        throw ValidationException::withMessages(['draft_state' => 'Invalid field position.']);
                    }
                }
                if ($engine === 'fabric' && ! in_array($field['type'] ?? '', ['text', 'rect', 'rectangle', 'circle', 'qr', 'qrcode', 'image'], true)) {
                    throw ValidationException::withMessages(['draft_state' => 'Unsupported canvas element type.']);
                }
                if (in_array($field['type'] ?? '', ['image', 'signature'], true)) {
                    $source = $field['src'] ?? $field['url'] ?? $field['content'] ?? '';
                    if (! empty($field['asset_id']) || $this->assetSecurity->assetIdFromUrl($source)) {
                        continue;
                    }
                    if (! preg_match('~^data:image/(png|jpeg);base64,([A-Za-z0-9+/=]+)$~D', $source, $imageMatch)
                        || strlen($source) > 7 * 1024 * 1024
                        || ! @getimagesizefromstring(base64_decode($imageMatch[2], true) ?: '')) {
                        throw ValidationException::withMessages(['draft_state' => 'Images require an uploaded asset or a valid embedded PNG/JPEG.']);
                    }
                }
            }
        }

        if ($engine === DocumentTemplate::ENGINE_PDFME) {
            $allowedTypes = [
                'text', 'image', 'signature', 'table', 'line', 'rectangle', 'ellipse', 'svg',
                'qrcode', 'japanpost', 'ean13', 'ean8', 'code39', 'code128', 'nw7', 'itf14', 'upca', 'upce', 'gs1datamatrix', 'pdf417',
            ];

            if (isset($state['schemas']) && is_array($state['schemas'])) {
                foreach ($state['schemas'] as $pageIndex => $pageSchema) {
                    if (is_array($pageSchema)) {
                        foreach ($pageSchema as $field) {
                            if (isset($field['type']) && ! in_array($field['type'], $allowedTypes, true)) {
                                throw ValidationException::withMessages([
                                    'draft_state' => sprintf('Invalid schema element type "%s" on page %d.', $field['type'], $pageIndex + 1),
                                ]);
                            }
                        }
                    }
                }
            }

            if (isset($state['elements']) && is_array($state['elements'])) {
                foreach ($state['elements'] as $element) {
                    if (isset($element['type']) && ! in_array($element['type'], $allowedTypes, true)) {
                        throw ValidationException::withMessages([
                            'draft_state' => sprintf('Invalid element type "%s".', $element['type']),
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Centralized deterministic template version resolution.
     * Prioritizes active program-specific templates over global templates, with fallback to system defaults.
     */
    public function resolveActiveVersion(string $documentKind, ?int $programId = null): ?DocumentTemplateVersion
    {
        if (! Schema::hasTable('document_templates')) {
            return null;
        }

        // 1. Program-specific active template with an active published version
        if ($programId) {
            $specific = DocumentTemplate::query()
                ->active()
                ->where('document_kind', $documentKind)
                ->where('training_program_id', $programId)
                ->whereNotNull('active_version_id')
                ->with('activeVersion')
                ->latest('id')
                ->first();

            if ($specific && $specific->activeVersion) {
                return $specific->activeVersion;
            }
        }

        // 2. Global active template (training_program_id is null) with an active published version
        $global = DocumentTemplate::query()
            ->active()
            ->where('document_kind', $documentKind)
            ->whereNull('training_program_id')
            ->whereNotNull('active_version_id')
            ->with('activeVersion')
            ->latest('id')
            ->first();

        if ($global && $global->activeVersion) {
            return $global->activeVersion;
        }

        return null;
    }

    /**
     * Enforce exactly one active template per scope across existing records.
     */
    public function enforceSingleActivePerScope(): void
    {
        if (! Schema::hasTable('document_templates')) {
            return;
        }

        $groups = DocumentTemplate::query()
            ->where('is_active', true)
            ->whereNotNull('active_version_id')
            ->select('document_kind', 'training_program_id', DB::raw('count(*) as count'))
            ->groupBy('document_kind', 'training_program_id')
            ->having('count', '>', 1)
            ->get();

        foreach ($groups as $group) {
            $templates = DocumentTemplate::query()
                ->where('is_active', true)
                ->whereNotNull('active_version_id')
                ->where('document_kind', $group->document_kind)
                ->when(
                    $group->training_program_id,
                    fn ($q) => $q->where('training_program_id', $group->training_program_id),
                    fn ($q) => $q->whereNull('training_program_id')
                )
                ->orderByDesc('id')
                ->get();

            // Keep the first (highest ID winner) active, deactivate others
            $templates->slice(1)->each(fn ($t) => $t->update(['is_active' => false]));
        }
    }

    /**
     * Get summary of currently published designs for COTC and TOR (with optional program filter).
     */
    public function getPublishedDesignsSummary(?int $programId = null): array
    {
        if (! Schema::hasTable('document_templates')) {
            return [];
        }

        $kinds = [
            DocumentTemplate::KIND_COTC => 'Certificate of Training Completion (COTC)',
            DocumentTemplate::KIND_TOR => 'Official Transcript of Records (TOR)',
        ];

        $summary = [];

        foreach ($kinds as $kind => $label) {
            $globalTemplate = DocumentTemplate::query()
                ->where('document_kind', $kind)
                ->whereNull('training_program_id')
                ->where('is_active', true)
                ->whereNotNull('active_version_id')
                ->with(['activeVersion', 'createdBy'])
                ->orderByDesc('id')
                ->first();

            $programOverrides = DocumentTemplate::query()
                ->where('document_kind', $kind)
                ->whereNotNull('training_program_id')
                ->where('is_active', true)
                ->whereNotNull('active_version_id')
                ->with(['activeVersion', 'trainingProgram', 'createdBy'])
                ->orderByDesc('id')
                ->get()
                ->unique('training_program_id')
                ->values();

            $selectedProgramTemplate = null;
            if ($programId) {
                $selectedProgramTemplate = DocumentTemplate::query()
                    ->where('document_kind', $kind)
                    ->where('training_program_id', $programId)
                    ->where('is_active', true)
                    ->whereNotNull('active_version_id')
                    ->with(['activeVersion', 'trainingProgram', 'createdBy'])
                    ->orderByDesc('id')
                    ->first();
            }

            $effectiveTemplate = $selectedProgramTemplate ?? $globalTemplate;

            $latestDraft = null;
            if ($programId) {
                $publishedAt = $selectedProgramTemplate?->activeVersion?->published_at
                    ?? $selectedProgramTemplate?->activeVersion?->created_at;
                $activeStateClean = $selectedProgramTemplate?->activeVersion?->editor_state ?? [];
                unset($activeStateClean['_asset_integrity']);

                $candidateDraft = DocumentTemplate::query()
                    ->where('document_kind', $kind)
                    ->where('training_program_id', $programId)
                    ->where('is_active', false)
                    ->whereNull('active_version_id')
                    ->with(['createdBy', 'updatedBy'])
                    ->orderByRaw('COALESCE(draft_updated_at, updated_at) DESC')
                    ->orderByDesc('id')
                    ->first();

                if ($candidateDraft && $this->hasActualContent(($candidateDraft->draft_state ?? []) + ['engine' => $candidateDraft->engine])) {
                    $draftTime = $candidateDraft->draft_updated_at ?? $candidateDraft->updated_at;
                    $isNotOlderThanPublish = ! $publishedAt || ($draftTime && $draftTime->greaterThanOrEqualTo($publishedAt));
                    if ($isNotOlderThanPublish) {
                        $draftClean = $candidateDraft->draft_state ?? [];
                        unset($draftClean['_asset_integrity']);
                        if (! empty($draftClean) && $draftClean != $activeStateClean) {
                            $latestDraft = $candidateDraft;
                        }
                    }
                }

                if (! $latestDraft && $selectedProgramTemplate && $selectedProgramTemplate->activeVersion) {
                    $ownDraftClean = $selectedProgramTemplate->draft_state ?? [];
                    unset($ownDraftClean['_asset_integrity']);
                    if (! empty($ownDraftClean) && $ownDraftClean != $activeStateClean) {
                        $latestDraft = $selectedProgramTemplate;
                    }
                }
            }

            $summary[$kind] = [
                'label' => $label,
                'kind' => $kind,
                'global' => $globalTemplate,
                'program_overrides' => $programOverrides,
                'program_specific' => $selectedProgramTemplate,
                'effective' => $effectiveTemplate,
                'latest_draft' => $latestDraft,
                'has_unpublished_draft' => $latestDraft !== null,
                'is_program_override' => $selectedProgramTemplate !== null,
            ];
        }

        return $summary;
    }

    /**
     * Perform an authoritative readiness and token-binding audit on a document template or version.
     * Evaluates page dimensions, editable fields vs background artwork, mapped dynamic tokens,
     * structured table slots (TOR competencies, SOA ledger), font substitutions, and unmapped literals.
     */
    public function auditTemplate(DocumentTemplate|DocumentTemplateVersion $target, ?array $stateOverride = null): array
    {
        if ($target instanceof DocumentTemplateVersion) {
            $template = $target->template;
            $state = $stateOverride ?? $target->editor_state ?? [];
            $versionNumber = $target->version_number;
        } else {
            $template = $target;
            $state = $stateOverride ?? $template->draft_state ?? $template->activeVersion?->editor_state ?? [];
            $versionNumber = $template->activeVersion?->version_number;
        }

        $kind = $template->document_kind;
        $definitions = PlaceholderRegistry::definitions();
        $kindDefinitions = PlaceholderRegistry::forKind($kind);

        // 1. Page dimensions
        $page = $state['page'] ?? [];
        $pageDimensions = [
            'size' => $page['size'] ?? ($kind === DocumentTemplate::KIND_TOR ? 'a4' : 'letter'),
            'orientation' => $page['orientation'] ?? ($kind === DocumentTemplate::KIND_COTC ? 'landscape' : 'portrait'),
            'width_mm' => (float) ($page['width_mm'] ?? ($kind === DocumentTemplate::KIND_COTC ? 279.4 : 215.9)),
            'height_mm' => (float) ($page['height_mm'] ?? ($kind === DocumentTemplate::KIND_COTC ? 215.9 : 279.4)),
        ];

        // Baseline templates use the established institutional renderer only when auditing the immutable version 1
        if ($target instanceof DocumentTemplateVersion && $target->version_number === 1 && $template->is_system_default && empty($state['elements']) && empty($state['schemas']) && empty($state['html'])) {
            return [
                'is_ready_for_issuance' => true,
                'summary' => 'Institutional baseline layout active and ready for official issuance.',
                'page_dimensions' => $pageDimensions,
                'retained_background' => [
                    'has_base_pdf' => false,
                    'has_background_image' => false,
                ],
                'editable_field_count' => 0,
                'recognized_tokens' => array_values(array_map(fn ($tok, $def) => [
                    'token' => $tok,
                    'label' => $def['label'] ?? $tok,
                    'type' => $def['type'] ?? 'text',
                    'applies_to_kind' => true,
                ], array_keys($kindDefinitions), $kindDefinitions)),
                'unmapped_literals' => [],
                'missing_required' => [],
                'table_slots' => [
                    'has_competencies_table' => ($kind === DocumentTemplate::KIND_TOR),
                    'has_payments_table' => ($kind === DocumentTemplate::KIND_SOA),
                ],
                'font_substitutions' => [],
                'warnings' => [],
            ];
        }

        // 2. Editable elements vs retained background
        $elements = $state['elements'] ?? [];
        $schemas = $state['schemas'] ?? [];
        $html = $state['html'] ?? $state['content'] ?? '';
        $hasBasePdf = filled($state['base_pdf_asset_id'] ?? null) || filled($state['basePdf'] ?? null);
        $hasBackgroundImage = filled($state['background_asset_id'] ?? null) || filled($state['background'] ?? null);

        $editableFieldCount = 0;
        $detectedTokens = [];
        $unmappedLiterals = [];
        $hasCompetenciesTable = false;
        $hasPaymentsTable = false;
        $fontSubstitutions = [];
        $standardFonts = ['helvetica', 'times', 'courier', 'arial', 'sans-serif', 'serif', 'monospace', 'times-roman'];

        $checkFont = function ($font) use (&$fontSubstitutions, $standardFonts) {
            if (is_string($font) && filled($font)) {
                $cleanFont = strtolower(trim($font));
                if (! in_array($cleanFont, $standardFonts, true) && ! in_array($font, $fontSubstitutions, true)) {
                    $fontSubstitutions[] = $font;
                }
            }
        };

        $checkToken = function ($rawToken) use (&$detectedTokens, &$unmappedLiterals, $definitions) {
            $cleaned = trim((string) $rawToken, "{} \t\n\r\0\x0B");
            if ($cleaned === '') {
                return;
            }
            if (isset($definitions[$cleaned])) {
                $detectedTokens[] = $cleaned;
            } else {
                $unmappedLiterals[] = $cleaned;
            }
        };

        $scanStringForTokens = function ($text) use ($checkToken) {
            if (! is_string($text) || $text === '') {
                return;
            }
            if (preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $text, $matches)) {
                foreach ($matches[1] as $tok) {
                    $checkToken($tok);
                }
            }
        };

        $engine = $state['engine'] ?? $template->engine ?? DocumentTemplate::ENGINE_PDFME;

        if ($engine === DocumentTemplate::ENGINE_RK_WORD || $engine === 'word') {
            if (filled($html)) {
                $editableFieldCount += max(1, substr_count((string) $html, '<p>') + substr_count((string) $html, '<tr>'));
                $scanStringForTokens($html);

                // Word dynamic tables expand ONLY when a standalone <p> matches ^{{key}}$ strictly without internal spaces
                if (preg_match_all('/<p[^>]*>(.*?)<\/p>/is', (string) $html, $matches)) {
                    foreach ($matches[1] as $pInner) {
                        $pText = trim(strip_tags($pInner));
                        if (preg_match('/^\{\{([a-zA-Z0-9_]+)\}\}$/', $pText, $pMatch)) {
                            $key = $pMatch[1];
                            if ($key === 'tor_competencies') {
                                $hasCompetenciesTable = true;
                            }
                            if (in_array($key, ['soa_ledger', 'soa_payments'], true)) {
                                $hasPaymentsTable = true;
                            }
                        }
                    }
                }
            }
        } elseif ($engine === DocumentTemplate::ENGINE_FABRIC || $engine === 'canvas') {
            if (is_array($elements)) {
                foreach ($elements as $el) {
                    $editableFieldCount++;
                    $elBinding = $el['binding'] ?? null;
                    $elName = $el['name'] ?? $el['id'] ?? null;
                    $elText = $el['text'] ?? $el['content'] ?? null;
                    $elType = $el['type'] ?? 'text';

                    if (isset($el['fontFamily'])) {
                        $checkFont($el['fontFamily']);
                    }

                    if (filled($elBinding)) {
                        $cleanBinding = trim((string) $elBinding, "{} \t\n\r\0\x0B");
                        if (isset($definitions[$cleanBinding])) {
                            $detectedTokens[] = $cleanBinding;
                        } else {
                            $scanStringForTokens($elBinding);
                        }
                    }
                    if (filled($elName)) {
                        $cleanName = trim((string) $elName, "{} \t\n\r\0\x0B");
                        if (isset($definitions[$cleanName])) {
                            $detectedTokens[] = $cleanName;
                        } else {
                            $scanStringForTokens($elName);
                        }
                    }
                    if (filled($elText)) {
                        $scanStringForTokens($elText);
                    }

                    if ($elType === 'table') {
                        $tableBindingKey = trim((string) ($elBinding ?: $elName ?: $elText), "{} \t\n\r\0\x0B");
                        if ($tableBindingKey === 'tor_competencies') {
                            $hasCompetenciesTable = true;
                        }
                        if (in_array($tableBindingKey, ['soa_ledger', 'soa_payments'], true)) {
                            $hasPaymentsTable = true;
                        }
                    }
                }
            }
        } else {
            // PDFme: If schemas is an array in state, schemas takes precedence over elements!
            // Even if empty array [[]], schemas is active and older elements are superseded.
            $hasSchemas = isset($state['schemas']) && is_array($state['schemas']);
            if ($hasSchemas) {
                foreach ($schemas as $pageSchemas) {
                    if (is_array($pageSchemas)) {
                        foreach ($pageSchemas as $fieldKey => $field) {
                            $editableFieldCount++;
                            $fieldName = $field['name'] ?? (is_string($fieldKey) && ! is_numeric($fieldKey) ? $fieldKey : null);
                            $fieldBinding = $field['binding'] ?? null;
                            $fieldContent = $field['content'] ?? $field['text'] ?? null;
                            $fieldType = $field['type'] ?? 'text';

                            if (isset($field['fontName'])) {
                                $checkFont($field['fontName']);
                            }

                            if (filled($fieldBinding)) {
                                $cleanBinding = trim((string) $fieldBinding, "{} \t\n\r\0\x0B");
                                if (isset($definitions[$cleanBinding])) {
                                    $detectedTokens[] = $cleanBinding;
                                } else {
                                    $scanStringForTokens($fieldBinding);
                                }
                            }
                            if (filled($fieldName)) {
                                $cleanName = trim((string) $fieldName, "{} \t\n\r\0\x0B");
                                if (isset($definitions[$cleanName])) {
                                    $detectedTokens[] = $cleanName;
                                } else {
                                    $scanStringForTokens($fieldName);
                                }
                            }
                            if (filled($fieldContent)) {
                                $scanStringForTokens($fieldContent);
                            }

                            if ($fieldType === 'table') {
                                $tableBindingKey = trim((string) ($fieldBinding ?: $fieldName ?: $fieldContent), "{} \t\n\r\0\x0B");
                                if ($tableBindingKey === 'tor_competencies') {
                                    $hasCompetenciesTable = true;
                                }
                                if (in_array($tableBindingKey, ['soa_ledger', 'soa_payments'], true)) {
                                    $hasPaymentsTable = true;
                                }
                            }
                        }
                    }
                }
            } elseif (is_array($elements)) {
                // Only if schemas is not present in state, scan elements for PDFme
                foreach ($elements as $el) {
                    $editableFieldCount++;
                    $elBinding = $el['binding'] ?? null;
                    $elName = $el['name'] ?? $el['id'] ?? null;
                    $elText = $el['text'] ?? $el['content'] ?? null;
                    $elType = $el['type'] ?? 'text';

                    if (isset($el['fontFamily'])) {
                        $checkFont($el['fontFamily']);
                    }

                    if (filled($elBinding)) {
                        $cleanBinding = trim((string) $elBinding, "{} \t\n\r\0\x0B");
                        if (isset($definitions[$cleanBinding])) {
                            $detectedTokens[] = $cleanBinding;
                        } else {
                            $scanStringForTokens($elBinding);
                        }
                    }
                    if (filled($elName)) {
                        $cleanName = trim((string) $elName, "{} \t\n\r\0\x0B");
                        if (isset($definitions[$cleanName])) {
                            $detectedTokens[] = $cleanName;
                        } else {
                            $scanStringForTokens($elName);
                        }
                    }
                    if (filled($elText)) {
                        $scanStringForTokens($elText);
                    }

                    // A text element is NEVER a structured table slot!
                    if ($elType === 'table') {
                        $tableBindingKey = trim((string) ($elBinding ?: $elName ?: $elText), "{} \t\n\r\0\x0B");
                        if ($tableBindingKey === 'tor_competencies') {
                            $hasCompetenciesTable = true;
                        }
                        if (in_array($tableBindingKey, ['soa_ledger', 'soa_payments'], true)) {
                            $hasPaymentsTable = true;
                        }
                    }
                }
            }
        }

        $detectedTokens = array_values(array_unique($detectedTokens));

        $recognizedTokens = [];
        foreach ($detectedTokens as $token) {
            if (isset($definitions[$token])) {
                $recognizedTokens[] = [
                    'token' => $token,
                    'label' => $definitions[$token]['label'] ?? $token,
                    'type' => $definitions[$token]['type'] ?? 'text',
                    'applies_to_kind' => isset($kindDefinitions[$token]),
                ];
            }
        }

        $tableSlots = [
            'has_competencies_table' => $hasCompetenciesTable,
            'has_payments_table' => $hasPaymentsTable,
        ];

        // Authoritative required tokens from PlaceholderRegistry
        $requiredForKind = match ($kind) {
            DocumentTemplate::KIND_COTC => ['trainee_name'],
            DocumentTemplate::KIND_TOR => ['trainee_name', 'training_program'],
            DocumentTemplate::KIND_SOA => ['trainee_name'],
            default => [],
        };
        $missingRequiredTokens = array_values(array_diff($requiredForKind, $detectedTokens));

        // 5. Warnings and readiness assessment
        $warnings = [];
        $unmappedLiterals = array_values(array_unique($unmappedLiterals));
        if (! empty($unmappedLiterals)) {
            $warnings[] = 'Unrecognized placeholders detected: '.implode(', ', array_map(fn ($t) => '{{ '.$t.' }}', $unmappedLiterals)).'. Staff should map them to authoritative MCARE fields (Advisory).';
        }
        if (! empty($missingRequiredTokens)) {
            $warnings[] = 'Key dynamic tokens not yet bound: '.implode(', ', array_map(fn ($t) => '{{ '.$t.' }}', $missingRequiredTokens)).' (Required for publication).';
        }
        if ($kind === DocumentTemplate::KIND_TOR && ! $tableSlots['has_competencies_table']) {
            $warnings[] = 'Official TOR requires a structured tor_competencies table slot (Required for publication).';
        }
        if ($kind === DocumentTemplate::KIND_SOA && ! $tableSlots['has_payments_table']) {
            $warnings[] = 'Statement of Account requires a structured soa_ledger or soa_payments table slot (Required for publication).';
        }
        $hasActualContent = $this->hasActualContent($state + ['engine' => $engine]);
        if (! $hasActualContent) {
            $warnings[] = 'The template has no content, background, or editable fields (Required for publication).';
        }

        $isReady = empty($missingRequiredTokens) && (
            ($kind === DocumentTemplate::KIND_TOR ? $tableSlots['has_competencies_table'] : true)
            && ($kind === DocumentTemplate::KIND_SOA ? $tableSlots['has_payments_table'] : true)
        ) && $hasActualContent;

        return [
            'is_ready_for_issuance' => $isReady,
            'summary' => $isReady
                ? 'All required authoritative fields and structural bindings are configured.'
                : 'Template requires configuration of essential bindings before official credential issuance.',
            'page_dimensions' => $pageDimensions,
            'retained_background' => [
                'has_base_pdf' => $hasBasePdf,
                'has_background_image' => $hasBackgroundImage,
            ],
            'editable_field_count' => $editableFieldCount,
            'recognized_tokens' => $recognizedTokens,
            'unmapped_literals' => $unmappedLiterals,
            'missing_required' => $missingRequiredTokens,
            'table_slots' => $tableSlots,
            'font_substitutions' => $fontSubstitutions,
            'warnings' => $warnings,
        ];
    }
}
