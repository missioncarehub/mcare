<?php

namespace Tests\Feature\Lms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class LmsResponsiveRenderingTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    public function test_shared_dialog_backdrop_handler_ignores_clicks_from_file_controls(): void
    {
        $script = file_get_contents(resource_path('js/app.js'));

        $this->assertIsString($script);
        $this->assertStringContainsString('if (event.target !== dialog) return;', $script);
    }

    public function test_mobile_quiz_footer_cannot_cover_activity_file_controls(): void
    {
        $css = file_get_contents(resource_path('css/universal_dashboard_design.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('.universal-dashboard .lms-activity-file-input', $css);
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 700px\).*?\.universal-dashboard \.lms-sticky-submit \{.*?position: static;.*?bottom: auto;/s',
            $css,
        );
    }

    public function test_quiz_builder_cards_are_square_and_do_not_cover_the_form(): void
    {
        $css = file_get_contents(resource_path('css/admin_official.css'));

        $this->assertIsString($css);
        $this->assertStringContainsString('[data-quiz-builder] .lms-builder-section', $css);
        $this->assertMatchesRegularExpression(
            '/\[data-quiz-builder\] \.lms-sticky-submit \{[^}]*position: static;[^}]*bottom: auto;/s',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\[data-quiz-builder\] \.lms-builder-section,[^}]*border-radius: 0;/s',
            $css,
        );
        $this->assertStringContainsString('.lms-quiz-overview', $css);
        $this->assertDoesNotMatchRegularExpression(
            '/\[data-quiz-builder\][^{]*\.lms-quiz-overview \{[^}]*border-radius: 1[26]px;/',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '/\[data-quiz-builder\] \.primary-action,[^}]*border-radius: 0 !important;/',
            $css,
        );
        $this->assertStringContainsString('.lms-quiz-taking-page', $css);

        $takingCss = file_get_contents(resource_path('css/universal_dashboard_design.css'));
        $this->assertIsString($takingCss);
        $this->assertMatchesRegularExpression(
            '/\.lms-quiz-taking-page \.lms-answer-option \{[^}]*border: 0;[^}]*background: transparent;/s',
            $takingCss,
        );
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 700px\).*?\.lms-quiz-taking-page \.lms-sticky-submit \{.*?flex-direction: column;/s',
            $takingCss,
        );
        $this->assertStringContainsString('.lms-take-question-heading', $takingCss);
    }

    public function test_trainer_lms_pages_expose_stable_mobile_safe_structure(): void
    {
        $trainer = $this->lmsUser('trainer');
        $trainer->forceFill(['avatar_url' => 'https://example.test/trainer-avatar.jpg'])->save();

        $this->actingAs($trainer)
            ->get(route('trainer.stream'))
            ->assertOk()
            ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1.0">', false)
            ->assertSee('data-lms-stream', false)
            ->assertSee('data-lms-role="trainer"', false)
            ->assertSee('data-announcement-composer', false)
            ->assertSee('https://example.test/trainer-avatar.jpg', false)
            ->assertSee('data-dashboard-mobile-menu-open="dashboard-mobile-menu-trainer"', false)
            ->assertSee('Teaching Day')
            ->assertSee('Competency Records')
            ->assertSee('Certificates')
            ->assertSee('Reports');

        $this->actingAs($trainer)
            ->get(route('trainer.resources'))
            ->assertOk()
            ->assertSee('data-lms-classwork', false)
            ->assertSee('id="module-creator-dialog"', false)
            ->assertSee('id="quiz-creator-dialog"', false)
            ->assertSee('lms-workflow-dialog is-landscape', false)
            ->assertSee('data-dashboard-dialog', false)
            ->assertSee('PDF or image')
            ->assertSee('.pdf,.jpg,.jpeg,.png,.webp,.gif', false)
            ->assertDontSee('PPTX, Video')
            ->assertDontSee('data-classwork-tab="all"', false)
            ->assertDontSee('Attached Assessments');

        $this->actingAs($trainer)
            ->get(route('trainer.assessments'))
            ->assertRedirect(route('trainer.resources'));
    }

    public function test_trainee_stream_and_quiz_pages_expose_mobile_safe_structure(): void
    {
        $batch = $this->lmsBatch();
        ['user' => $trainee] = $this->lmsTrainee($batch);
        $trainee->forceFill(['avatar_url' => 'https://example.test/trainee-avatar.jpg'])->save();

        $this->actingAs($trainee)
            ->get(route('trainee.stream'))
            ->assertOk()
            ->assertSee('<meta name="viewport" content="width=device-width, initial-scale=1.0">', false)
            ->assertSee('data-lms-stream', false)
            ->assertSee('data-lms-role="trainee"', false)
            ->assertSee('data-dashboard-mobile-menu-open="dashboard-mobile-menu-trainee"', false)
            ->assertSee('https://example.test/trainee-avatar.jpg', false)
            ->assertSee('Home')
            ->assertSee('Payments')
            ->assertSee('Documents');

        $this->actingAs($trainee)
            ->get(route('trainee.quizzes.index'))
            ->assertRedirect(route('trainee.modules.index'));
    }

    public function test_module_and_roster_surfaces_render_google_account_avatars_for_both_roles(): void
    {
        $trainer = $this->lmsUser('trainer');
        $trainer->forceFill(['avatar_url' => 'https://example.test/module-trainer-avatar.jpg'])->save();
        $batch = $this->lmsBatch(['trainer_id' => $trainer->id]);
        ['user' => $trainee] = $this->lmsTrainee($batch);
        $trainee->forceFill(['avatar_url' => 'https://example.test/module-trainee-avatar.jpg'])->save();
        $module = $this->lmsModule($trainer, $batch);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.index'))
            ->assertOk()
            ->assertSee('https://example.test/module-trainer-avatar.jpg', false);

        $this->actingAs($trainee)
            ->get(route('trainee.modules.show', $module))
            ->assertOk()
            ->assertSee('https://example.test/module-trainer-avatar.jpg', false);

        $this->actingAs($trainer)
            ->get(route('trainer.modules.show', $module))
            ->assertOk()
            ->assertSee('lms-module-back', false)
            ->assertSee('Back to Classwork')
            ->assertDontSee('Classwork and trainer evaluation required')
            ->assertDontSee('Learning Materials & Files')
            ->assertSee('data-module-file-preview', false)
            ->assertSee('data-pdf-fit-mode="page"', false);

        $this->actingAs($trainer)
            ->get(route('trainer.modules.show', ['module' => $module, 'tab' => 'evaluations']))
            ->assertOk()
            ->assertSee('https://example.test/module-trainee-avatar.jpg', false)
            ->assertDontSee('data-module-file-preview', false);

        $this->actingAs($trainer)
            ->get(route('trainer.trainees'))
            ->assertOk()
            ->assertSee('https://example.test/module-trainee-avatar.jpg', false)
            ->assertSee('class="w-full space-y-7"', false)
            ->assertDontSee('mx-auto max-w-7xl', false);
    }

    public function test_pdf_canvas_stage_keeps_horizontal_overflow_scrollable_in_both_directions(): void
    {
        $css = file_get_contents(resource_path('css/universal_dashboard_design.css'));
        $script = file_get_contents(resource_path('js/app.js'));
        $traineeShow = file_get_contents(resource_path('views/trainee/modules/show.blade.php'));
        $preview = file_get_contents(resource_path('views/components/module-file-preview.blade.php'));

        $this->assertIsString($css);
        $this->assertIsString($script);
        $this->assertMatchesRegularExpression(
            '/\[data-pdf-canvas-container\] \[data-pdf-scroll-sizer\] \{[^}]*min-width:\s*100%/s',
            $css,
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\.universal-dashboard \.lms-module-pdf-stage \{[^}]*justify-content:\s*center/s',
            $css,
        );
        $this->assertStringContainsString('syncHorizontalPan', $script);
        $this->assertStringContainsString('containerWrapper.scrollLeft', $script);
        $this->assertStringContainsString('/vendor/pdfjs/pdf.worker.min.js', $script);
        $this->assertStringContainsString('watermarkHtml', $script);
        $this->assertStringNotContainsString('pdf.worker.min.mjs?url', $script);
        $this->assertFileExists(public_path('vendor/pdfjs/pdf.worker.min.js'));
        $this->assertStringContainsString('data-pdf-scroll-sizer', $traineeShow);
        $this->assertStringContainsString('pdf-page-watermark', $traineeShow);
        $this->assertStringContainsString('pdf-page-watermark', $preview);
        $this->assertStringNotContainsString('items-start justify-center overflow-auto', $preview);
    }
}
