<?php

namespace Tests\Feature\DocumentStudio;

use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\EnrollmentApplication;
use App\Models\OfficialDocument;
use App\Models\PaymentTransaction;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Services\DocumentStudio\AssetSecurityService;
use App\Services\DocumentStudio\DocumentStudioRenderer;
use App\Services\DocumentStudio\DocumentStudioService;
use App\Services\DocumentStudio\PlaceholderRegistry;
use App\Services\FpdfOfficialDocumentRenderer;
use App\Services\OfficialDocumentManager;
use App\Support\RolePermissionMatrix;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

/** Maintained release readiness tests promoted from Codex review probes. */
class DocumentStudioReleaseReadinessTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $runtimePath = storage_path('framework/testing/document-studio-readiness');
        if (! is_dir($runtimePath)) {
            @mkdir($runtimePath, 0777, true);
        }
        $this->app->useStoragePath($runtimePath);
        RolePermissionMatrix::ensureConfigured();
        Storage::fake('local');
        Http::preventStrayRequests();
        Mail::fake();
        Queue::fake();
    }

    private function admin(): User
    {
        $user = $this->lmsUser('admin');
        RolePermissionMatrix::syncUser($user);

        return $user;
    }

    private function template(string $kind = 'cotc', ?int $programId = null, string $engine = 'pdfme'): DocumentTemplate
    {
        $elements = [
            ['id' => 'trainee_name', 'type' => 'text', 'text' => '{{trainee_name}}', 'binding' => '{{trainee_name}}', 'x' => 20, 'y' => 20, 'width' => 120, 'height' => 10],
        ];

        if ($kind === 'tor') {
            $elements[] = ['id' => 'training_program', 'type' => 'text', 'text' => '{{training_program}}', 'binding' => '{{training_program}}', 'x' => 20, 'y' => 35, 'width' => 120, 'height' => 10];
            $elements[] = ['id' => 'tor_competencies', 'type' => 'table', 'binding' => 'tor_competencies', 'x' => 20, 'y' => 50, 'width' => 120, 'height' => 50];
        } elseif ($kind === 'soa') {
            $elements[] = ['id' => 'soa_payments', 'type' => 'table', 'binding' => 'soa_payments', 'x' => 20, 'y' => 50, 'width' => 120, 'height' => 50];
        }

        return app(DocumentStudioService::class)->createTemplate([
            'name' => 'Review '.$kind.' '.uniqid(),
            'document_kind' => $kind,
            'engine' => $engine,
            'training_program_id' => $programId,
            'initial_state' => [
                'schema_version' => '1.0',
                'engine' => $engine,
                'page' => ['width_mm' => 210, 'height_mm' => 297],
                'elements' => $elements,
            ],
        ]);
    }

    public function test_role_boundary_denies_applicant_trainer_trainee_and_returning_alumnus(): void
    {
        foreach (['applicant', 'trainer', 'trainee'] as $role) {
            $this->actingAs($this->lmsUser($role))->get(route('admin.document-studio.index'))->assertForbidden();
        }
        $batch = $this->lmsBatch();
        ['user' => $returning, 'application' => $graduate] = $this->lmsTrainee($batch, ['learning_status' => EnrollmentApplication::LEARNING_GRADUATED]);
        $returning->alumniProfile()->create(['is_available_for_duty' => true]);
        $this->actingAs($returning)->get(route('admin.document-studio.index'))->assertForbidden();
    }

    public function test_admin_without_required_permission_is_denied_and_super_admin_is_allowed(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('admin.document-studio.index'))->assertOk();
        Role::findByName('admin', 'web')->revokePermissionTo('official-documents.manage');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->actingAs($admin->fresh())->get(route('admin.document-studio.index'))->assertForbidden();
        $admin->givePermissionTo('official-documents.manage');
        $this->actingAs($admin->fresh())->get(route('admin.document-studio.index'))->assertOk();
    }

    public function test_svg_rejects_or_removes_namespace_qualified_script(): void
    {
        $input = '<s:svg xmlns:s="http://www.w3.org/2000/svg"><s:script>window.qaStudioXss=true</s:script><s:rect width="10" height="10"/></s:svg>';
        try {
            $clean = app(AssetSecurityService::class)->sanitizeSvg($input);
        } catch (ValidationException) {
            $this->assertTrue(true);

            return;
        }
        $dom = new DOMDocument;
        $dom->loadXML($clean);
        $this->assertSame(0, $dom->getElementsByTagNameNS('http://www.w3.org/2000/svg', 'script')->length);
    }

    public function test_svg_rejects_or_removes_entity_encoded_script_links(): void
    {
        $input = '<svg xmlns="http://www.w3.org/2000/svg"><a href="java&#x73;cript:window.qaStudioXss=true"><text>Click</text></a></svg>';
        try {
            $clean = app(AssetSecurityService::class)->sanitizeSvg($input);
        } catch (ValidationException) {
            $this->assertTrue(true);

            return;
        }
        $this->assertStringNotContainsString('javascript:', strtolower($clean));
    }

    public function test_draft_does_not_accept_client_selected_server_file_paths(): void
    {
        $template = $this->template();
        $state = $template->draft_state;
        $state['basePdfPath'] = __DIR__.'/synthetic-private-document.pdf';
        $this->actingAs($this->admin())->patchJson(route('admin.document-studio.draft.save', $template), [
            'draft_state' => $state,
            'draft_revision' => 1,
        ])->assertUnprocessable();
    }

    public function test_persisted_word_content_does_not_retain_active_html(): void
    {
        $template = $this->template('general', null, 'rk_word');
        $state = ['engine' => 'rk_word', 'html' => '<p>Letter</p><img src="x" onerror="window.qaStudioXss=true">'];
        $response = $this->actingAs($this->admin())->patchJson(route('admin.document-studio.draft.save', $template), [
            'draft_state' => $state,
            'draft_revision' => 1,
        ]);
        if ($response->status() === 422) {
            $response->assertUnprocessable();

            return;
        }
        $response->assertOk();
        $this->assertStringNotContainsString('onerror', $template->fresh()->draft_state['html']);
    }

    public function test_published_versions_cannot_be_modified_through_the_model(): void
    {
        $template = $this->template();
        $version = app(DocumentStudioService::class)->publishVersion($template);
        $original = $version->editor_state;
        $changed = $original;
        $changed['elements'][0]['text'] = 'Replaced after publication';
        try {
            $version->update(['editor_state' => $changed]);
        } catch (\Throwable) {
            $this->assertSame($original, $version->fresh()->editor_state);

            return;
        }
        $this->assertSame($original, $version->fresh()->editor_state);
    }

    public function test_invalid_schema_cannot_be_published_and_activated(): void
    {
        $template = $this->template();
        $template->update(['draft_state' => ['engine' => 'pdfme', 'schemas' => [[['type' => 'not-a-plugin']]]]]);
        $this->actingAs($this->admin())->postJson(route('admin.document-studio.publish', $template), [
            'make_active' => true,
        ])->assertUnprocessable();
    }

    public function test_soa_values_use_the_enrollments_saved_fee_snapshot(): void
    {
        $program = TrainingProgram::create(['name' => 'Review Program', 'code' => 'REVIEW', 'total_program_fee' => 30000]);
        $batch = $this->lmsBatch(['training_program_id' => $program->id]);
        ['application' => $application] = $this->lmsTrainee($batch, [
            'training_program_id' => $program->id,
            'total_program_fee' => 22000,
            'owwa_deduction' => 2000,
        ]);
        PaymentTransaction::create([
            'user_id' => $application->user_id,
            'enrollment_application_id' => $application->id,
            'amount' => 6000,
            'status' => PaymentTransaction::STATUS_VERIFIED,
            'verified_at' => now(),
        ]);
        $values = PlaceholderRegistry::resolveForEnrollment($application, 'soa');
        $this->assertSame('PHP 20,000.00', $values['total_program_fee']);
        $this->assertSame('PHP 14,000.00', $values['outstanding_balance']);
    }

    public function test_soa_running_balance_uses_recorded_date_when_payment_date_is_missing(): void
    {
        $batch = $this->lmsBatch();
        ['application' => $application] = $this->lmsTrainee($batch, ['total_program_fee' => 10000, 'owwa_deduction' => 0]);
        PaymentTransaction::create([
            'user_id' => $application->user_id, 'enrollment_application_id' => $application->id,
            'amount' => 1000, 'status' => PaymentTransaction::STATUS_VERIFIED,
            'paid_at' => '2026-01-01', 'or_number' => 'FIRST',
        ]);
        $later = PaymentTransaction::create([
            'user_id' => $application->user_id, 'enrollment_application_id' => $application->id,
            'amount' => 2000, 'status' => PaymentTransaction::STATUS_VERIFIED, 'or_number' => 'SECOND',
        ]);
        $later->forceFill(['created_at' => '2026-02-01'])->save();
        $rows = PlaceholderRegistry::resolveForEnrollment($application, 'soa')['soa_ledger'];
        $this->assertSame(['FIRST', 'SECOND'], array_column($rows, 'or_number'));
        $this->assertSame(['PHP 9,000.00', 'PHP 7,000.00'], array_column($rows, 'balance'));
    }

    public function test_program_specific_template_takes_priority_over_global_template(): void
    {
        $program = TrainingProgram::create(['name' => 'Review Program', 'code' => 'REVIEW']);
        $studio = app(DocumentStudioService::class);
        $studio->publishVersion($this->template('cotc'));
        $specific = $studio->publishVersion($this->template('cotc', $program->id));
        $batch = $this->lmsBatch(['training_program_id' => $program->id]);
        ['application' => $application] = $this->lmsTrainee($batch, [
            'training_program_id' => $program->id,
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
        ]);
        $document = app(OfficialDocumentManager::class)->findOrCreateQueued($application, 'cotc', $this->admin());
        $this->assertSame($specific->id, $document->document_template_version_id);
    }

    public function test_studio_batch_tor_rejects_an_active_non_graduate(): void
    {
        $batch = $this->lmsBatch();
        $this->lmsTrainee($batch);
        $template = $this->template('tor');
        app(DocumentStudioService::class)->publishVersion($template);
        $renderer = \Mockery::mock(DocumentStudioRenderer::class);
        $renderer->shouldReceive('renderVersionToPdf')->andReturn('%PDF-1.4 synthetic-only');
        $this->app->instance(DocumentStudioRenderer::class, $renderer);
        $this->actingAs($this->admin())->postJson(route('admin.document-studio.batch-generate', $template), [
            'training_batch_id' => $batch->id,
        ])->assertUnprocessable();
    }

    public function test_official_studio_batch_uses_document_records_and_version_history(): void
    {
        $batch = $this->lmsBatch();
        ['application' => $application] = $this->lmsTrainee($batch, ['learning_status' => EnrollmentApplication::LEARNING_GRADUATED]);
        $template = $this->template();
        app(DocumentStudioService::class)->publishVersion($template);
        $renderer = \Mockery::mock(DocumentStudioRenderer::class);
        $renderer->shouldReceive('renderVersionToPdf')->andReturn('%PDF-1.4 synthetic-only');
        $this->app->instance(DocumentStudioRenderer::class, $renderer);
        $this->actingAs($this->admin())->postJson(route('admin.document-studio.batch-generate', $template), [
            'training_batch_id' => $batch->id,
        ])->assertOk();
        $this->assertDatabaseHas('official_documents', ['enrollment_application_id' => $application->id, 'type' => 'cotc']);
    }

    public function test_render_failure_uses_the_baseline_fallback_only_once(): void
    {
        $batch = $this->lmsBatch();
        ['application' => $application] = $this->lmsTrainee($batch, ['learning_status' => EnrollmentApplication::LEARNING_GRADUATED]);
        $version = app(DocumentStudioService::class)->publishVersion($this->template());
        $document = new OfficialDocument(['type' => 'cotc', 'document_template_version_id' => $version->id]);
        $document->setRelation('application', $application);
        $document->setRelation('documentTemplateVersion', $version);
        $fallback = new class extends FpdfOfficialDocumentRenderer
        {
            public int $calls = 0;

            public function render(OfficialDocument $document): string
            {
                if (++$this->calls > 1) {
                    return '%PDF-1.4 bounded recursion guard';
                }

                return parent::render($document);
            }
        };
        $renderer = new class($fallback) extends DocumentStudioRenderer
        {
            public function renderVersionToPdf(DocumentTemplateVersion $version, array $values): string
            {
                throw new \RuntimeException('Synthetic Node failure for fallback probe');
            }
        };
        $this->app->instance(DocumentStudioRenderer::class, $renderer);
        $renderer->render($document);
        $this->assertSame(1, $fallback->calls, 'A second fallback call proves Studio -> FPDF -> Studio recursion.');
    }

    public function test_docx_archive_enforces_the_reported_compression_ratio_limit(): void
    {
        $filename = tempnam(sys_get_temp_dir(), 'docx_').'.docx';
        $zip = new \ZipArchive;
        $zip->open($filename, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFromString('word/document.xml', '<document>'.str_repeat('x', 1024 * 1024).'</document>');
        $zip->close();
        try {
            $upload = new UploadedFile($filename, 'ratio-probe.docx', null, null, true);
            $this->expectException(ValidationException::class);
            app(AssetSecurityService::class)->validateDocxUpload($upload);
        } finally {
            if (file_exists($filename)) {
                unlink($filename);
            }
        }
    }
}
