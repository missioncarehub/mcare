<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\DocumentStudio\CreateTemplateRequest;
use App\Http\Requests\DocumentStudio\PublishVersionRequest;
use App\Http\Requests\DocumentStudio\SaveDraftRequest;
use App\Models\DocumentAsset;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Services\DocumentStudio\AssetSecurityService;
use App\Services\DocumentStudio\BatchGenerationCoordinator;
use App\Services\DocumentStudio\DocumentStudioRenderer;
use App\Services\DocumentStudio\DocumentStudioService;
use App\Services\DocumentStudio\ExistingDesignService;
use App\Services\DocumentStudio\PdfConversionService;
use App\Services\DocumentStudio\PlaceholderRegistry;
use App\Services\DocumentStudio\TemplateImportExportService;
use App\Services\DocumentStudio\WordDocumentImportService;
use App\Services\StatementOfAccountPdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentStudioController extends Controller
{
    public function __construct(
        private readonly DocumentStudioService $studioService,
        private readonly AssetSecurityService $assetSecurity,
        private readonly DocumentStudioRenderer $renderer,
        private readonly TemplateImportExportService $importExport,
        private readonly BatchGenerationCoordinator $batchCoordinator,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DocumentTemplate::class);

        // Auto-migrate tables if not yet migrated on production
        if (! Schema::hasTable('document_templates')) {
            try {
                Artisan::call('migrate', ['--force' => true]);
            } catch (\Throwable) {
            }
        }

        if (! Schema::hasTable('document_templates')) {
            return view('admin.document-studio.migration-required');
        }

        // Ensure system defaults are seeded and single active per scope is enforced
        $this->studioService->ensureDefaultTemplates();

        $programs = TrainingProgram::query()->orderBy('name')->get();
        $selectedProgramId = $request->filled('program_id')
            ? (int) $request->query('program_id')
            : ($request->filled('training_program_id')
                ? (int) $request->query('training_program_id')
                : ($programs->first()?->id));
        $selectedProgram = $selectedProgramId ? $programs->firstWhere('id', $selectedProgramId) : null;
        $publishedDesigns = $this->studioService->getPublishedDesignsSummary($selectedProgramId);

        $query = DocumentTemplate::query()
            ->with(['activeVersion', 'trainingProgram', 'createdBy'])
            ->whereIn('document_kind', [DocumentTemplate::KIND_COTC, DocumentTemplate::KIND_TOR])
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($request->filled('kind')) {
            $query->where('document_kind', $request->query('kind'));
        }

        if ($selectedProgramId) {
            $query->where('training_program_id', $selectedProgramId);
        }

        if ($request->filled('search')) {
            $term = '%'.trim($request->query('search')).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('slug', 'like', $term);
            });
        }

        $templates = $query->paginate(12)->withQueryString();
        $draftCount = DocumentTemplate::query()
            ->whereIn('document_kind', [DocumentTemplate::KIND_COTC, DocumentTemplate::KIND_TOR])
            ->count();

        $previewFallbacks = [];
        foreach ($publishedDesigns as $kind => $info) {
            $previewTarget = $info['latest_draft'] ?? $info['effective'] ?? $info['global'];
            if (! $previewTarget || $previewTarget->engine !== DocumentTemplate::ENGINE_PDFME) {
                continue;
            }
            $state = $info['latest_draft']
                ? ($previewTarget->draft_state ?? $previewTarget->activeVersion?->editor_state ?? [])
                : ($previewTarget->activeVersion?->editor_state ?? $previewTarget->draft_state ?? []);
            if (is_array($state)) {
                unset($state['_asset_integrity']);
            }
            $assets = $previewTarget->assets()
                ->where('type', '!=', DocumentAsset::TYPE_PDF_SOURCE)
                ->get(['id'])
                ->map(fn (DocumentAsset $asset) => ['id' => $asset->id, 'url' => route('admin.document-studio.assets.stream', $asset, false)])
                ->values()
                ->all();

            $previewFallbacks[$kind] = [
                'state' => $state,
                'samplePayload' => PlaceholderRegistry::samplePayload($kind),
                'assets' => $assets,
            ];
        }

        return view('admin.document-studio.index', [
            'templates' => $templates,
            'programs' => $programs,
            'selectedProgram' => $selectedProgram,
            'selectedProgramId' => $selectedProgramId,
            'kinds' => [DocumentTemplate::KIND_COTC, DocumentTemplate::KIND_TOR],
            'publishedDesigns' => $publishedDesigns,
            'previewFallbacks' => $previewFallbacks,
            'draftCount' => $draftCount,
            'activeView' => $request->query('view', 'published'),
        ]);
    }

    public function existingDesign(Request $request, ExistingDesignService $designs): RedirectResponse
    {
        $this->authorize('create', DocumentTemplate::class);
        $data = $request->validate([
            'kind' => ['required', Rule::in(['soa', 'cotc', 'tor'])],
            'training_program_id' => ['nullable', 'integer', 'exists:training_programs,id'],
        ]);
        $program = isset($data['training_program_id']) ? TrainingProgram::findOrFail($data['training_program_id']) : null;
        $name = null;
        if ($program) {
            $name = strtoupper($data['kind']).' — '.$program->name;
        }
        try {
            $template = $designs->createCurrent($data['kind'], $program, $request->user(), $name);
        } catch (\RuntimeException $exception) {
            report($exception);
            throw ValidationException::withMessages(['kind' => 'The existing design is unavailable for this program. Check its configured document artwork before retrying.']);
        }

        return redirect()->route('admin.document-studio.edit', $template)->with('saved', 'Existing design copied to a new draft. Review it before publishing.');
    }

    public function upload(): RedirectResponse
    {
        $this->authorize('create', DocumentTemplate::class);

        return redirect()->route('admin.document-studio.create', ['source' => 'upload']);
    }

    public function cancelConversion(Request $request): JsonResponse
    {
        $this->authorize('create', DocumentTemplate::class);
        $data = $request->validate(['operation' => ['required', 'uuid']]);
        Cache::store('file')->put('studio-conversion-cancel:'.$request->user()->id.':'.$data['operation'], true, 900);

        return response()->json(['cancel_requested' => true]);
    }

    public function convertPdf(Request $request, PdfConversionService $converter): RedirectResponse
    {
        $this->authorize('create', DocumentTemplate::class);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'document_kind' => ['required', Rule::in(DocumentTemplate::supportedKinds())],
            'training_program_id' => ['nullable', 'integer', 'exists:training_programs,id'],
            'file' => ['required', 'file', 'mimes:pdf', 'max:15360'],
            'regions' => ['nullable', 'json', 'max:250000'],
            'operation' => ['required', 'uuid'],
        ]);
        $regions = json_decode($data['regions'] ?? '[]', true);
        validator(['regions' => $regions], [
            'regions' => ['array', 'max:100'],
            'regions.*' => ['array:page,x,y,width,height,color,content'],
            'regions.*.page' => ['required', 'integer', 'min:1', 'max:20'],
            'regions.*.x' => ['required', 'numeric', 'min:0', 'max:1000'],
            'regions.*.y' => ['required', 'numeric', 'min:0', 'max:1000'],
            'regions.*.width' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'regions.*.height' => ['required', 'numeric', 'gt:0', 'max:1000'],
            'regions.*.color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'regions.*.content' => ['required', 'string', 'max:2000'],
        ])->validate();
        $template = $converter->convert($request->file('file'), $data, $regions, $request->user(), $data['operation']);

        return redirect()->route('admin.document-studio.edit', $template)
            ->with('saved', 'Editable draft created. Review converted text, page layout and remaining artwork before publishing.');
    }

    public function create(): View
    {
        $this->authorize('create', DocumentTemplate::class);

        $programs = TrainingProgram::query()->orderBy('name')->get();

        return view('admin.document-studio.create', [
            'programs' => $programs,
            'kinds' => DocumentTemplate::supportedKinds(),
            'placeholders' => PlaceholderRegistry::definitions(),
        ]);
    }

    public function store(CreateTemplateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        if (($validated['source'] ?? null) === 'current') {
            $program = isset($validated['training_program_id'])
                ? TrainingProgram::findOrFail($validated['training_program_id'])
                : null;
            $template = app(ExistingDesignService::class)->createCurrent(
                $validated['document_kind'], $program, $request->user(), $validated['name']
            );

            return redirect()->route('admin.document-studio.edit', $template)
                ->with('saved', 'Draft created from the current design.');
        }
        $sourceFile = $request->file('source_file');
        if ($sourceFile && $sourceFile->getMimeType() === 'application/pdf' && ($validated['pdf_mode'] ?? 'editable') === 'editable') {
            $template = app(PdfConversionService::class)->convert(
                $sourceFile, $validated, json_decode($validated['regions'] ?? '[]', true),
                $request->user(), $validated['operation']
            );

            return redirect()->route('admin.document-studio.edit', $template)
                ->with('saved', 'Editable draft created. Review its text and layout before publishing.');
        }

        $asset = null;
        try {
            return DB::transaction(function () use ($request, &$asset): RedirectResponse {
                $validated = $request->validated();
                $validated['is_active'] = false;
                $template = $this->studioService->createTemplate($validated, $request->user());

                // 1. Handle base PDF file if provided
                $sourceFile = $request->file('source_file');
                $isPdf = $sourceFile && $sourceFile->getMimeType() === 'application/pdf';
                $isImage = $sourceFile && in_array($sourceFile->getMimeType(), ['image/png', 'image/jpeg'], true);
                if ($isPdf || $request->hasFile('base_pdf')) {
                    $asset = $this->assetSecurity->storeUploadedFile(
                        $isPdf ? $sourceFile : $request->file('base_pdf'),
                        DocumentAsset::TYPE_PDF_BASE,
                        $template->id,
                        $request->user()
                    );

                    $draftState = $template->draft_state ?? [];
                    $draftState['base_pdf_asset_id'] = $asset->id;
                    unset($draftState['basePdfPath']);
                    $template->update(['draft_state' => $draftState]);
                }

                // 2. Handle DOCX file if provided
                if (($sourceFile && ! $isPdf && ! $isImage) || $request->hasFile('docx_file')) {
                    $html = app(WordDocumentImportService::class)->import(
                        $sourceFile ?? $request->file('docx_file'),
                        $template,
                        $request->user()
                    );
                    $draftState = $template->draft_state ?? [];
                    $draftState['html'] = $html;
                    $template->update(['draft_state' => $draftState]);
                }

                // 3. Handle background image (PNG / JPG) if provided
                if ($isImage || $request->hasFile('background_image')) {
                    $asset = $this->assetSecurity->storeUploadedFile(
                        $isImage ? $sourceFile : $request->file('background_image'),
                        DocumentAsset::TYPE_IMAGE,
                        $template->id,
                        $request->user()
                    );
                    $draftState = $template->draft_state ?? [];
                    $draftState['background_asset_id'] = $asset->id;
                    $template->update(['draft_state' => $draftState]);
                }

                return redirect()
                    ->route('admin.document-studio.edit', $template)
                    ->with('saved', 'Draft created.');
            });
        } catch (\Throwable $exception) {
            if ($asset) {
                Storage::disk($asset->disk)->delete($asset->file_path);
            }
            throw $exception;
        }
    }

    public function startDraft(Request $request, DocumentTemplate $template, ExistingDesignService $designs): RedirectResponse
    {
        $this->authorize('update', $template);
        $draft = $designs->openDraft($template, $request->user());

        return redirect()->route('admin.document-studio.edit', $draft);
    }

    public function edit(DocumentTemplate $template): View
    {
        $this->authorize('update', $template);

        $template->loadMissing([
            'activeVersion:id,document_template_id,version_number,sha256,created_at,published_at',
            'assets:id,document_template_id,type',
            'trainingProgram:id,name,code',
        ]);

        // Prevent accidental inline numeric-keyed byte maps from bloating inline Blade JSON
        // when an authoritative external PDF/background asset reference already exists.
        $state = $template->draft_state ?? [];
        if (is_array($state)
            && (! empty($state['background_asset_id']) || ! empty($state['base_pdf_asset_id']) || ! empty($state['base_pdf_url']))
            && is_array($state['basePdf'] ?? null)
            && ! isset($state['basePdf']['width'])) {
            unset($state['basePdf']);
            $template->setAttribute('draft_state', $state);
        }

        $placeholders = PlaceholderRegistry::forKind($template->document_kind);
        $samplePayload = PlaceholderRegistry::samplePayload($template->document_kind);

        $capabilities = [
            'can_import_pdf' => $template->engine === DocumentTemplate::ENGINE_PDFME,
            'can_import_docx' => $template->engine === DocumentTemplate::ENGINE_RK_WORD,
            'can_import_image' => in_array($template->engine, [DocumentTemplate::ENGINE_FABRIC, DocumentTemplate::ENGINE_PDFME], true),
            'supports_layers' => $template->engine === DocumentTemplate::ENGINE_FABRIC,
            'supports_tables' => in_array($template->engine, [DocumentTemplate::ENGINE_PDFME, DocumentTemplate::ENGINE_RK_WORD], true),
            'supports_barcodes' => in_array($template->engine, [DocumentTemplate::ENGINE_PDFME, DocumentTemplate::ENGINE_FABRIC], true),
        ];

        return view('admin.document-studio.editor', [
            'template' => $template,
            'activeVersion' => $template->activeVersion,
            'assets' => $template->assets->where('type', '!=', DocumentAsset::TYPE_PDF_SOURCE),
            'assetUrls' => $template->assets->where('type', '!=', DocumentAsset::TYPE_PDF_SOURCE)->values()->map(fn ($asset) => ['id' => $asset->id, 'url' => route('admin.document-studio.assets.stream', $asset, false)]),
            'placeholders' => $placeholders,
            'samplePayload' => $samplePayload,
            'capabilities' => $capabilities,
            'audit' => $this->studioService->auditTemplate($template),
        ]);
    }

    public function saveDraft(SaveDraftRequest $request, DocumentTemplate $template): JsonResponse
    {
        $validated = $request->validated();

        $updated = $this->studioService->saveDraft(
            $template,
            $validated['draft_state'],
            (int) $validated['draft_revision'],
            $request->user()
        );

        return response()->json([
            'success' => true,
            'draft_revision' => $updated->draft_revision,
            'draft_updated_at' => $updated->draft_updated_at?->toIso8601String(),
            'audit' => $this->studioService->auditTemplate($updated),
        ]);
    }

    public function importDocx(Request $request, DocumentTemplate $template, WordDocumentImportService $importer): JsonResponse
    {
        $this->authorize('update', $template);
        abort_unless($template->engine === DocumentTemplate::ENGINE_RK_WORD, 422);
        $request->validate(['file' => ['required', 'file', 'max:10240']]);

        return response()->json(['html' => $importer->import($request->file('file'), $template, $request->user())]);
    }

    public function publish(PublishVersionRequest $request, DocumentTemplate $template): JsonResponse
    {
        $validated = $request->validated();

        $clientRevision = isset($validated['draft_revision']) ? (int) $validated['draft_revision'] : null;
        if ($clientRevision !== null && $clientRevision !== (int) $template->draft_revision) {
            throw ValidationException::withMessages([
                'revision' => 'Another session changed this draft. Reload before publishing.',
            ]);
        }

        $version = $this->studioService->publishVersion(
            $template,
            $validated['changelog'] ?? null,
            $request->boolean('make_active', true),
            $request->user(),
            $validated['draft_state'] ?? null,
            $clientRevision,
        );

        return response()->json([
            'success' => true,
            'version' => [
                'id' => $version->id,
                'version_number' => $version->version_number,
                'sha256' => $version->sha256,
                'created_at' => $version->created_at->format('M j, Y g:i A'),
            ],
            'active_version_id' => $template->fresh()->active_version_id,
            'draft_revision' => $template->fresh()->draft_revision,
        ]);
    }

    public function versions(DocumentTemplate $template): View
    {
        $this->authorize('view', $template);

        $template->loadMissing(['versions.createdBy', 'activeVersion']);

        return view('admin.document-studio.versions', [
            'template' => $template,
            'versions' => $template->versions,
            'activeVersion' => $template->activeVersion,
        ]);
    }

    public function activateVersion(
        Request $request,
        DocumentTemplate $template,
        DocumentTemplateVersion $version,
    ): RedirectResponse {
        $this->authorize('activate', $template);

        $this->studioService->activateVersion($template, $version, $request->user());

        return back()->with('saved', sprintf('Version %d is now the active version for this template.', $version->version_number));
    }

    public function preview(Request $request, DocumentTemplate $template): Response
    {
        $this->authorize('view', $template);

        $values = PlaceholderRegistry::samplePayload($template->document_kind);

        // If real authorized enrollment is requested
        if ($request->filled('enrollment_id')) {
            $enrollment = EnrollmentApplication::query()->findOrFail($request->query('enrollment_id'));
            if ($template->training_program_id && (int) $template->training_program_id !== (int) ($enrollment->training_program_id ?? $enrollment->batch?->training_program_id)) {
                throw ValidationException::withMessages(['enrollment_id' => 'This template belongs to another training program.']);
            }
            $values = PlaceholderRegistry::resolveForEnrollment($enrollment, $template->document_kind);
        }

        $isDraftPreview = $request->boolean('draft') || $request->query('mode') === 'draft';
        if ($request->filled('version_id')) {
            $version = $template->versions()->findOrFail($request->query('version_id'));
            $editorState = $version->editor_state ?? [];
        } elseif (! $isDraftPreview && $template->activeVersion) {
            $version = $template->activeVersion;
            $editorState = $version->editor_state ?? [];
        } else {
            $version = null;
            $editorState = $template->draft_state ?? $template->activeVersion?->editor_state ?? [];
        }

        // Never override PDFme templates with legacy FPDF baseline during preview.
        $isInitialUneditedDefault = ! $isDraftPreview
            && $template->engine !== DocumentTemplate::ENGINE_PDFME
            && $template->is_system_default
            && (! $version || $version->version_number === 1);
        if ($isInitialUneditedDefault) {
            try {
                if ($template->document_kind === DocumentTemplate::KIND_SOA) {
                    if (isset($enrollment)) {
                        $pdfBinary = app(StatementOfAccountPdfService::class)->generateBaseline($enrollment);
                    } else {
                        [, $pdfBinary] = app(ExistingDesignService::class)->record('soa', $template->trainingProgram);
                    }
                } else {
                    $doc = new OfficialDocument([
                        'type' => $template->document_kind,
                        'document_template_version_id' => $version?->id,
                        'document_number' => 'PREVIEW-SAMPLE',
                        'version' => 1,
                        'generated_at' => now(),
                    ]);
                    $doc->setRelation('documentTemplateVersion', $version);
                    if (isset($enrollment)) {
                        $doc->setRelation('application', $enrollment);
                        $pdfBinary = $this->renderer->render($doc);
                    } else {
                        [, $pdfBinary] = app(ExistingDesignService::class)->record($template->document_kind, $template->trainingProgram);
                    }
                }

                return response($pdfBinary, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.$template->slug.'-preview.pdf"',
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                ]);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        if (! $this->studioService->hasActualContent($editorState + ['engine' => $template->engine])) {
            $message = 'This layout is empty. Open Edit draft from Published to copy the current design, or add content to this draft.';

            return response(['message' => $message, 'errors' => ['preview' => [$message]]], 422, [
                'Cache-Control' => 'private, no-store',
            ]);
        }

        $payload = [
            'template_id' => $template->id,
            'engine' => $template->engine,
            'source_layout' => $editorState['source_layout'] ?? null,
            'page' => $editorState['page'] ?? [
                'width_mm' => ($template->document_kind === 'cotc' ? 279.4 : 215.9),
                'height_mm' => ($template->document_kind === 'cotc' ? 215.9 : 279.4),
            ],
            'schemas' => $editorState['schemas'] ?? null,
            'elements' => $editorState['elements'] ?? null,
            'background' => $editorState['background'] ?? null,
            'basePdf' => $editorState['basePdf'] ?? null,
            'base_pdf_url' => $editorState['base_pdf_url'] ?? null,
            'base_pdf_asset_id' => $editorState['base_pdf_asset_id'] ?? null,
            'background_asset_id' => $editorState['background_asset_id'] ?? null,
            'html' => $editorState['html'] ?? $editorState['content'] ?? null,
            'values' => $values,
        ];

        $previewCacheKey = null;
        $previewPayloadHash = null;
        if (! $request->filled('enrollment_id')
            && ! $request->boolean('fresh')
            && $this->renderer::class === DocumentStudioRenderer::class) {
            $basePdfMtime = null;
            if (! empty($payload['base_pdf_url']) && file_exists(public_path(ltrim($payload['base_pdf_url'], '/')))) {
                $basePdfMtime = @filemtime(public_path(ltrim($payload['base_pdf_url'], '/')));
            }
            $variant = $version ? 'v'.$version->id : 'draft';
            $previewCacheKey = "document-studio:preview:tpl:{$template->id}:{$variant}";
            $previewPayloadHash = sha1(json_encode([
                'payload' => $payload,
                'base_pdf_mtime' => $basePdfMtime,
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            $cachedEntry = Cache::store('file')->get($previewCacheKey);
            if (is_array($cachedEntry)
                && ($cachedEntry['hash'] ?? null) === $previewPayloadHash
                && is_string($cachedEntry['pdf'] ?? null)
                && str_starts_with($cachedEntry['pdf'], '%PDF-')) {
                return response($cachedEntry['pdf'], 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="'.$template->slug.'-preview.pdf"',
                    'Cache-Control' => 'no-cache, no-store, must-revalidate',
                    'X-MCARE-Preview-Cache' => 'hit',
                ]);
            }
        }

        try {
            $pdfBinary = $this->renderer->renderPayloadToPdf($payload);
            if ($previewCacheKey && $previewPayloadHash && str_starts_with($pdfBinary, '%PDF-')) {
                Cache::store('file')->put($previewCacheKey, [
                    'hash' => $previewPayloadHash,
                    'pdf' => $pdfBinary,
                ], now()->addMinutes(15));
            }
        } catch (\Throwable $exception) {
            report($exception);

            if (str_contains($exception->getMessage(), 'rendering process failed')
                && in_array($template->document_kind, [DocumentTemplate::KIND_COTC, DocumentTemplate::KIND_TOR], true)) {
                try {
                    if (isset($enrollment)) {
                        $doc = new OfficialDocument([
                            'type' => $template->document_kind,
                            'document_number' => 'PREVIEW-SAMPLE',
                            'version' => 1,
                            'generated_at' => now(),
                        ]);
                        $doc->setRelation('application', $enrollment);
                        $fallbackPdf = app(\App\Services\FpdfOfficialDocumentRenderer::class)->render($doc);
                    } else {
                        [, $fallbackPdf] = app(ExistingDesignService::class)->record($template->document_kind, $template->trainingProgram);
                    }

                    return response($fallbackPdf, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="'.$template->slug.'-preview.pdf"',
                        'Cache-Control' => 'no-cache, no-store, must-revalidate',
                        'X-MCARE-Preview-Fallback' => 'fpdf',
                    ]);
                } catch (\Throwable $fallbackException) {
                    report($fallbackException);
                }
            }

            return response()->json([
                'message' => 'This layout could not be previewed. Check its fonts, tables and uploaded assets, then try again.',
            ], 422, [
                'Cache-Control' => 'private, no-store',
            ]);
        }

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$template->slug.'-preview.pdf"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    public function export(DocumentTemplate $template): JsonResponse
    {
        $this->authorize('view', $template);

        $exportData = $this->importExport->export($template);

        return response()->json($exportData, 200, [
            'Content-Disposition' => 'attachment; filename="'.$template->slug.'-template.json"',
        ]);
    }

    public function import(Request $request): RedirectResponse
    {
        $this->authorize('create', DocumentTemplate::class);

        $request->validate([
            'template_file' => ['required', 'file', 'mimes:json,txt', 'max:32768'],
        ]);

        $content = file_get_contents($request->file('template_file')->getRealPath());
        $json = json_decode($content, true);

        if (! is_array($json)) {
            throw ValidationException::withMessages(['template_file' => 'Invalid JSON template file.']);
        }

        $template = $this->importExport->import($json, $request->user());

        return redirect()
            ->route('admin.document-studio.edit', $template)
            ->with('saved', 'Template imported successfully.');
    }

    public function batchGenerate(Request $request, DocumentTemplate $template): BinaryFileResponse|RedirectResponse|JsonResponse
    {
        $this->authorize('generate', $template);

        $request->validate([
            'training_batch_id' => ['required', 'integer', 'exists:training_batches,id'],
            'schedule' => ['nullable', Rule::in(['AM', 'PM'])],
        ]);

        $batch = TrainingBatch::query()->findOrFail($request->input('training_batch_id'));
        $schedule = $request->input('schedule');

        // Enforce program applicability between template and batch
        if ($template->training_program_id && (int) $template->training_program_id !== (int) $batch->training_program_id) {
            throw ValidationException::withMessages([
                'training_batch_id' => 'Selected template is scoped to another training program and cannot be applied to this batch.',
            ]);
        }

        $isOfficial = in_array($template->document_kind, [DocumentTemplate::KIND_COTC, DocumentTemplate::KIND_TOR], true);

        // For official credentials (COTC, TOR), non-graduates must not be batch generated
        if ($isOfficial) {
            $hasNonGraduates = EnrollmentApplication::query()
                ->where('training_batch_id', $batch->id)
                ->where('status', EnrollmentApplication::STATUS_APPROVED)
                ->when($schedule, fn ($q) => $q->where('schedule_preference', $schedule))
                ->where('learning_status', '!=', EnrollmentApplication::LEARNING_GRADUATED)
                ->exists();

            if ($hasNonGraduates) {
                throw ValidationException::withMessages([
                    'training_batch_id' => 'Cannot generate official '.strtoupper($template->document_kind).' documents for a batch containing non-graduated trainees. Mark trainees as graduated first.',
                ]);
            }
        }

        $enrollments = EnrollmentApplication::query()
            ->where('training_batch_id', $batch->id)
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->when($schedule, fn ($q) => $q->where('schedule_preference', $schedule))
            ->when($isOfficial, fn ($q) => $q->where('learning_status', EnrollmentApplication::LEARNING_GRADUATED))
            ->get();

        if ($enrollments->isEmpty()) {
            throw ValidationException::withMessages([
                'training_batch_id' => 'No matching eligible trainees found in this batch for document generation.',
            ]);
        }

        $version = $template->activeVersion;
        if (! $version) {
            throw ValidationException::withMessages([
                'template' => 'Please publish and activate a version of this template before generating batch documents.',
            ]);
        }

        $result = $this->batchCoordinator->generateBatch($version, $enrollments, $request->user(), ! $request->expectsJson());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'result' => $result,
            ]);
        }

        if (! $result['zip_path'] || ! file_exists($result['zip_path'])) {
            return back()->with('saved', sprintf('Processed %d documents (%d successful, %d failed).', $result['total'], $result['successful'], $result['failed']));
        }

        return response()->download($result['zip_path'])->deleteFileAfterSend(true);
    }
}
