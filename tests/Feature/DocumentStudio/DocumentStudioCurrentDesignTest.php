<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\DocumentAsset;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\PaymentTransaction;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\DocumentStudio\AssetSecurityService;
use App\Services\DocumentStudio\DocumentStudioRenderer;
use App\Services\DocumentStudio\DocumentStudioService;
use App\Services\DocumentStudio\ExistingDesignService;
use App\Services\DocumentStudio\NodeProcess;
use App\Services\StatementOfAccountPdfService;
use App\Support\RolePermissionMatrix;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class DocumentStudioCurrentDesignTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $runtime = storage_path('framework/testing/document-studio-current-design');
        if (! is_dir($runtime)) {
            mkdir($runtime, 0777, true);
        }
        $this->app->useStoragePath($runtime);
        RolePermissionMatrix::ensureConfigured();
        Storage::fake('local');
        Http::preventStrayRequests();
        Mail::fake();
        Queue::fake();
        config(['official_documents.browsershot.node_binary' => PHP_OS_FAMILY === 'Windows' ? 'C:/Program Files/nodejs/node.exe' : 'node']);
    }

    private function actor(string $role = 'admin'): User
    {
        $actor = $this->lmsUser($role);
        RolePermissionMatrix::syncUser($actor);

        return $actor;
    }

    private function custom(string $marker, ?TrainingProgram $program = null): DocumentTemplate
    {
        return app(DocumentStudioService::class)->createTemplate([
            'name' => $marker, 'document_kind' => 'soa', 'engine' => 'pdfme',
            'training_program_id' => $program?->id,
            'initial_state' => [
                'engine' => 'pdfme',
                'page' => ['width_mm' => 215.9, 'height_mm' => 279.4],
                'elements' => [
                    ['id' => 'title', 'type' => 'text', 'content' => $marker, 'x' => 16, 'y' => 20, 'width' => 180, 'height' => 10],
                    ['id' => 'name', 'type' => 'text', 'binding' => '{{trainee_name}}', 'x' => 16, 'y' => 35, 'width' => 180, 'height' => 10],
                    ['id' => 'ledger', 'type' => 'table', 'binding' => 'soa_ledger', 'x' => 16, 'y' => 50, 'width' => 180, 'height' => 50],
                ],
            ],
        ]);
    }

    private function pdfText(string $bytes): string
    {
        $this->assertStringStartsWith('%PDF-', $bytes);
        $process = NodeProcess::make(['--input-type=module', '--eval', <<<'JS'
import { readFileSync } from 'node:fs';
import { getDocument } from 'pdfjs-dist/legacy/build/pdf.mjs';
const task = getDocument({ data: new Uint8Array(Buffer.from(readFileSync(0, 'utf8'), 'base64')), isEvalSupported: false });
const pdf = await task.promise;
const pages = [];
try {
    for (let number = 1; number <= pdf.numPages; number++) {
        pages.push((await (await pdf.getPage(number)).getTextContent()).items.map(item => item.str).join(' '));
    }
    process.stdout.write(JSON.stringify(pages));
} finally { await task.destroy?.(); }
JS]);
        $process->setInput(base64_encode($bytes));
        $process->run();
        $this->assertTrue($process->isSuccessful(), 'A genuine generated PDF must be readable.');
        $pages = json_decode($process->getOutput(), true, flags: JSON_THROW_ON_ERROR);
        $this->assertNotEmpty($pages);

        return implode(' ', $pages);
    }

    public function test_opening_pristine_baselines_creates_complete_inactive_editable_drafts(): void
    {
        $studio = app(DocumentStudioService::class);
        $studio->ensureDefaultTemplates();
        $actor = $this->actor();
        foreach (['soa', 'cotc', 'tor'] as $kind) {
            $source = DocumentTemplate::where('slug', 'default-'.$kind)->firstOrFail();
            $before = $source->draft_state;
            $version = $source->activeVersion;
            $copy = app(ExistingDesignService::class)->openDraft($source, $actor);
            $this->assertNotSame($source->id, $copy->id);
            $this->assertSame('pdfme', $copy->engine);
            $this->assertFalse($copy->is_active);
            $this->assertNull($copy->active_version_id);
            $this->assertGreaterThan(8, count($copy->draft_state['schemas'][0]));
            $this->assertSame($before, $source->fresh()->draft_state);
            $this->assertSame($version->id, $source->fresh()->active_version_id);
            $this->assertTrue($version->fresh()->verifyChecksum());
            $this->assertSame($version->id, $copy->conversion_report['draft_source_version_id']);
        }
    }

    public function test_reopening_reuses_only_the_actors_unfinished_copy_and_preserves_its_edits(): void
    {
        $studio = app(DocumentStudioService::class);
        $studio->ensureDefaultTemplates();
        $source = DocumentTemplate::where('slug', 'default-soa')->firstOrFail();
        $actor = $this->actor();
        $designs = app(ExistingDesignService::class);
        $copy = $designs->openDraft($source, $actor);
        $state = $copy->draft_state;
        $state['schemas'][0][0]['content'] = 'Saved staff draft';
        $studio->saveDraft($copy, $state, $copy->draft_revision, $actor);
        $reopened = $designs->openDraft($source, $actor);
        $this->assertSame($copy->id, $reopened->id);
        $this->assertSame('Saved staff draft', $reopened->draft_state['schemas'][0][0]['content']);
        $other = $designs->openDraft($source, $this->actor());
        $this->assertNotSame($copy->id, $other->id);
    }

    public function test_one_field_modified_default_stays_intact_while_published_edit_opens_the_complete_baseline(): void
    {
        $studio = app(DocumentStudioService::class);
        $studio->ensureDefaultTemplates();
        $source = DocumentTemplate::where('slug', 'default-soa')->firstOrFail();
        $original = $source->activeVersion;
        $state = $source->draft_state;
        $state['schemas'] = [[['name' => 'staff_field', 'type' => 'text', 'content' => 'Preserve this edit', 'position' => ['x' => 25, 'y' => 40], 'width' => 100, 'height' => 12]]];
        $source = $studio->saveDraft($source, $state, $source->draft_revision);
        $opened = app(ExistingDesignService::class)->openDraft($source, $this->actor());
        $this->assertNotSame($source->id, $opened->id);
        $this->assertGreaterThan(30, count($opened->draft_state['schemas'][0]));
        $this->assertSame($state, $source->fresh()->draft_state);
        $this->assertSame($original->id, $source->fresh()->active_version_id);
        $this->assertTrue($original->fresh()->verifyChecksum());
    }

    public function test_current_design_uses_the_active_program_version_and_never_an_unpublished_draft(): void
    {
        $studio = app(DocumentStudioService::class);
        $global = $this->custom('GLOBAL PUBLISHED');
        $globalVersion = $studio->publishVersion($global);
        $program = TrainingProgram::create(['name' => 'Caregiving scoped', 'code' => 'DESIGN-SCOPE']);
        $specific = $this->custom('PROGRAM PUBLISHED', $program);
        $version = $studio->publishVersion($specific);
        $state = $specific->fresh()->draft_state;
        $state['elements'][0]['content'] = 'UNPUBLISHED STAFF CHANGE';
        $specific = $studio->saveDraft($specific->fresh(), $state, $specific->fresh()->draft_revision);
        $designs = app(ExistingDesignService::class);
        $copy = $designs->createCurrent('soa', $program, $this->actor(), 'New statement');
        $this->assertSame('New statement', $copy->name);
        $this->assertSame($program->id, $copy->training_program_id);
        $this->assertStringContainsString('PROGRAM PUBLISHED', json_encode($copy->draft_state));
        $this->assertStringNotContainsString('UNPUBLISHED STAFF CHANGE', json_encode($copy->draft_state));
        $this->assertStringNotContainsString('GLOBAL PUBLISHED', json_encode($copy->draft_state));
        $this->assertSame($version->id, $studio->resolveActiveVersion('soa', $program->id)->id);
        $globalCopy = $designs->createCurrent('soa', null, $this->actor());
        $this->assertStringContainsString('GLOBAL PUBLISHED', json_encode($globalCopy->draft_state));
        $this->assertSame($globalVersion->id, $studio->resolveActiveVersion('soa')->id);
        $this->assertSame($specific->id, $designs->openDraft($specific, $this->actor())->id);
        $this->assertTrue($version->fresh()->verifyChecksum());
    }

    public function test_current_design_copies_private_assets_and_rewrites_their_template_ownership(): void
    {
        $studio = app(DocumentStudioService::class);
        $template = $this->custom('PRIVATE ASSET DESIGN');
        $asset = app(AssetSecurityService::class)->storeUploadedFile(UploadedFile::fake()->image('logo.png', 40, 40), DocumentAsset::TYPE_LOGO, $template->id);
        $state = $template->draft_state;
        $state['elements'][] = ['id' => 'logo', 'type' => 'image', 'asset_id' => $asset->id, 'content' => route('admin.document-studio.assets.stream', $asset), 'x' => 16, 'y' => 10, 'width' => 15, 'height' => 15];
        $template = $studio->saveDraft($template, $state, $template->draft_revision);
        $version = $studio->publishVersion($template);
        $copy = app(ExistingDesignService::class)->createCurrent('soa', null, $this->actor());
        $newAsset = $copy->assets()->firstOrFail();
        $this->assertNotSame($asset->id, $newAsset->id);
        $this->assertSame($asset->sha256, $newAsset->sha256);
        $this->assertSame($copy->id, $newAsset->document_template_id);
        $this->assertSame($newAsset->id, $copy->draft_state['elements'][3]['asset_id']);
        $this->assertStringContainsString('/assets/'.$newAsset->id, $copy->draft_state['elements'][3]['content']);
        $this->assertTrue($version->fresh()->verifyChecksum());
        $this->assertSame($asset->sha256, hash('sha256', $asset->fileContents()));
    }

    public function test_a_tampered_asset_cannot_be_cloned_into_a_new_draft(): void
    {
        $studio = app(DocumentStudioService::class);
        $template = $this->custom('Asset integrity');
        $asset = app(AssetSecurityService::class)->storeUploadedFile(UploadedFile::fake()->image('logo.png'), DocumentAsset::TYPE_IMAGE, $template->id);
        $state = $template->draft_state;
        $state['elements'][] = ['id' => 'logo', 'type' => 'image', 'asset_id' => $asset->id];
        $template = $studio->saveDraft($template, $state, $template->draft_revision);
        $studio->publishVersion($template);
        Storage::disk('local')->put($asset->file_path, 'Changed bytes');
        $before = DocumentTemplate::count();
        try {
            app(ExistingDesignService::class)->createCurrent('soa', null, $this->actor());
            $this->fail('A changed published asset cannot seed a new draft.');
        } catch (ValidationException $exception) {
            $this->assertNotEmpty($exception->errors());
        }
        $this->assertSame($before, DocumentTemplate::count());
    }

    public function test_non_admins_cannot_use_the_current_design_actions(): void
    {
        $source = $this->custom('Denied');
        foreach (['trainer', 'trainee', 'alumni', 'applicant'] as $role) {
            $actor = $this->actor($role);
            foreach ([
                fn () => app(ExistingDesignService::class)->openDraft($source, $actor),
                fn () => app(ExistingDesignService::class)->createCurrent('soa', null, $actor),
            ] as $action) {
                try {
                    $action();
                    $this->fail('Document Studio must remain restricted to authorized administrators.');
                } catch (AuthorizationException) {
                    $this->assertTrue(true);
                }
            }
        }
    }

    public function test_empty_schemas_cannot_use_stale_elements_to_pass_readiness_or_publication(): void
    {
        $studio = app(DocumentStudioService::class);
        $template = $this->custom('Stale fields');
        $state = $template->draft_state;
        $state['schemas'] = [[]];
        $state['basePdf'] = ['width' => 215.9, 'height' => 279.4, 'padding' => [0, 0, 0, 0]];
        $template = $studio->saveDraft($template, $state, $template->draft_revision);
        $this->assertFalse($studio->hasActualContent($state));
        $this->assertFalse($studio->auditTemplate($template)['is_ready_for_issuance']);
        $this->expectException(ValidationException::class);
        $studio->publishVersion($template);
    }

    public function test_legacy_blank_schema_preview_errors_and_soa_issuance_keeps_its_baseline_fallback(): void
    {
        $template = $this->custom('Legacy blank');
        $state = $template->draft_state + ['schemas' => [[]]];
        $version = DocumentTemplateVersion::create([
            'document_template_id' => $template->id, 'version_number' => 1,
            'engine' => 'pdfme', 'editor_state' => $state, 'page_settings' => $state['page'],
            'sha256' => hash('sha256', json_encode($state, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)),
        ]);
        $template->update(['active_version_id' => $version->id]);
        try {
            app(DocumentStudioRenderer::class)->renderVersionToPdf($version, []);
            $this->fail('A legacy empty schema must produce a reviewable error, not an accepted blank PDF.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('no printable content', $exception->getMessage());
        }
        $batch = $this->lmsBatch();
        ['application' => $application] = $this->lmsTrainee($batch, ['first_name' => 'Baseline', 'last_name' => 'Recovery']);
        Log::shouldReceive('warning')->once()->with('Document Studio used the institutional statement fallback.', [
            'template_version_id' => $version->id, 'reason' => 'studio_render_failed',
        ]);
        $text = $this->pdfText(app(StatementOfAccountPdfService::class)->generate($application));
        $this->assertStringContainsString('Baseline Recovery', $text);
        $this->assertStringContainsString('OFFICIAL PAYMENT RECORD & TRANSACTION LEDGER', $text);
        $this->assertTrue($version->fresh()->verifyChecksum());
    }

    public function test_published_full_soa_design_reaches_real_learner_and_admin_pdfs_with_authoritative_data(): void
    {
        $admin = $this->actor();
        $studio = app(DocumentStudioService::class);
        $copy = app(ExistingDesignService::class)->createCurrent('soa', null, $admin);
        $state = $copy->draft_state;
        foreach ($state['schemas'][0] as &$field) {
            if ($field['type'] === 'text' && str_contains($field['content'], 'S T A T E M E N T')) {
                $field['content'] = 'STUDIO PUBLISHED STATEMENT';
            }
        }
        unset($field);
        $state['schemas'][0][] = ['name' => 'enrollment_reference', 'type' => 'text', 'content' => '{{enrollment_number}}', 'position' => ['x' => 16, 'y' => 268], 'width' => 180, 'height' => 5, 'fontSize' => 8];
        $copy = $studio->saveDraft($copy, $state, $copy->draft_revision, $admin);
        $version = $studio->publishVersion($copy, 'Use this layout', true, $admin);
        $batch = $this->lmsBatch();
        $first = $this->lmsTrainee($batch, ['first_name' => 'Alpha', 'last_name' => 'Learner', 'enrollment_number' => 'STUDIO-ENR-A', 'total_program_fee' => 10000, 'owwa_deduction' => 1000, 'total_paid_amount' => 1234]);
        $second = $this->lmsTrainee($batch, ['first_name' => 'Beta', 'last_name' => 'Learner', 'enrollment_number' => 'STUDIO-ENR-B', 'total_program_fee' => 20000, 'owwa_deduction' => 5000, 'total_paid_amount' => 3000]);
        foreach ([[$first, 1234, 'STUDIO-OR-A'], [$second, 3000, 'STUDIO-OR-B']] as [$learner, $amount, $receipt]) {
            PaymentTransaction::create(['user_id' => $learner['user']->id, 'enrollment_application_id' => $learner['application']->id, 'amount' => $amount, 'status' => PaymentTransaction::STATUS_VERIFIED, 'or_number' => $receipt, 'paid_at' => now()->subDay()]);
            PaymentTransaction::create(['user_id' => $learner['user']->id, 'enrollment_application_id' => $learner['application']->id, 'amount' => 999, 'status' => PaymentTransaction::STATUS_PENDING, 'or_number' => 'DO-NOT-CREDIT', 'paid_at' => now()]);
        }
        $learnerResponse = $this->actingAs($first['user'])->get(route('trainee.payments.soa.preview'))->assertOk();
        $firstText = $this->pdfText($learnerResponse->getContent());
        $adminResponse = $this->actingAs($admin)->get(route('admin.payment-schedules.soa', $second['application']))->assertOk();
        $secondText = $this->pdfText($adminResponse->getContent());
        foreach ([$firstText, $secondText] as $text) {
            $this->assertStringContainsString('STUDIO PUBLISHED STATEMENT', $text);
            $this->assertStringNotContainsString('DO-NOT-CREDIT', $text);
            $this->assertStringNotContainsString('{{', $text);
        }
        foreach (['ALPHA LEARNER', 'STUDIO-ENR-A', 'STUDIO-OR-A', '7,766.00'] as $expected) {
            $this->assertStringContainsString($expected, $firstText);
            $this->assertStringNotContainsString($expected, $secondText);
        }
        foreach (['BETA LEARNER', 'STUDIO-ENR-B', 'STUDIO-OR-B', '12,000.00'] as $expected) {
            $this->assertStringContainsString($expected, $secondText);
            $this->assertStringNotContainsString($expected, $firstText);
        }
        $this->assertSame($version->id, $studio->resolveActiveVersion('soa')->id);
        $this->assertTrue($version->fresh()->verifyChecksum());
    }
}
