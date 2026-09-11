<?php

namespace Tests\Feature;

use App\Mail\AdmissionApplicationReceivedMail;
use App\Mail\AdmissionApplicationReviewedMail;
use App\Models\AdmissionApplication;
use App\Models\EnrollmentApplication;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Notifications\AdminOperationsNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdmissionApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_an_application_and_receive_a_number(): void
    {
        Notification::fake();
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->get(route('applications.create'))
            ->assertOk()
            ->assertSee('Submit a training application')
            ->assertSee('id="training_program_id"', false)
            ->assertSee('Caregiving NC II')
            ->assertSee('data-application-form', false)
            ->assertSee('data-application-submit', false)
            ->assertDontSee('id="enrollment-form"', false);

        $programId = TrainingProgram::query()->value('id');

        $this->post(route('applications.store'), [
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
            'last_name' => 'Santos',
            'email' => 'maria.applicant@gmail.com',
            'contact_number' => '09170000000',
            'training_program_id' => $programId,
            'schedule_preference' => 'AM',
            'educational_attainment' => 'High School Graduate',
            'notes' => 'I want to train as a caregiver.',
            'privacy_consent' => '1',
        ])->assertRedirect(route('applications.received'));

        $admission = AdmissionApplication::query()->firstOrFail();

        $this->assertSame(AdmissionApplication::STATUS_PENDING, $admission->status);
        $this->assertMatchesRegularExpression('/^MCA-'.now()->year.'-[A-Z0-9]{6}$/', $admission->application_number);

        $this->get(route('applications.received'))
            ->assertOk()
            ->assertSee($admission->application_number);

        Mail::assertSent(AdmissionApplicationReceivedMail::class, function (AdmissionApplicationReceivedMail $mail) use ($admission): bool {
            return $mail->hasTo($admission->email)
                && $mail->admission->is($admission)
                && str_contains($mail->envelope()->subject, $admission->application_number)
                && str_contains($mail->render(), $admission->application_number);
        });
        Notification::assertSentTo($admin, AdminOperationsNotification::class);
    }

    public function test_enrollment_form_stays_hidden_until_the_application_number_is_approved(): void
    {
        $pending = AdmissionApplication::query()->create([
            'application_number' => 'MCA-2026-PENDING',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'pending.applicant@gmail.com',
            'contact_number' => '09170000000',
            'educational_attainment' => 'High School Graduate',
            'status' => AdmissionApplication::STATUS_PENDING,
            'privacy_consent_at' => now(),
        ]);

        $this->get(route('enrollment.create'))
            ->assertOk()
            ->assertSee('Enter your approved application number')
            ->assertDontSee('id="enrollment-form"', false);

        $this->post(route('enrollment.unlock'), [
            'application_number' => $pending->application_number,
        ])->assertSessionHasErrors('application_number');

        $approved = $this->makeApprovedAdmission(['email' => 'approved.applicant@gmail.com']);

        $this->post(route('enrollment.unlock'), [
            'application_number' => $approved->application_number,
        ])->assertRedirect(route('enrollment.create'))
            ->assertSessionHas('saved');

        $this->get(route('enrollment.create'))
            ->assertOk()
            ->assertSee('id="enrollment-form"', false)
            ->assertSee($approved->application_number)
            ->assertSee('value="approved.applicant@gmail.com"', false)
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-password-toggle="password_confirmation"', false);
    }

    public function test_applicant_can_check_status_with_the_application_number(): void
    {
        $admission = $this->makeApprovedAdmission([
            'email' => 'status.applicant@gmail.com',
            'status' => AdmissionApplication::STATUS_PENDING,
            'reviewed_at' => null,
        ]);

        $this->post(route('applications.lookup'), [
            'application_number' => strtolower($admission->application_number),
        ])->assertRedirect(route('applications.status', [
            'application_number' => $admission->application_number,
        ]));

        $this->get(route('applications.status', ['application_number' => $admission->application_number]))
            ->assertOk()
            ->assertSee('Pending review')
            ->assertDontSee('Continue to enrollment');
    }

    public function test_status_page_keeps_the_official_footer_on_the_layout(): void
    {
        $this->get(route('applications.status'))
            ->assertOk()
            ->assertSee('Check application status')
            ->assertSee('application-status-page', false)
            ->assertSee('auth-footer', false);
    }

    public function test_admin_can_approve_an_application_and_unlock_enrollment(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $admission = AdmissionApplication::query()->create([
            'application_number' => AdmissionApplication::generateNumber(),
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'review.applicant@gmail.com',
            'contact_number' => '09170000000',
            'educational_attainment' => 'College Graduate',
            'status' => AdmissionApplication::STATUS_PENDING,
            'privacy_consent_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee($admission->application_number)
            ->assertSee('Review')
            ->assertSee('Delete')
            ->assertSee(route('admin.applications.show', $admission), false)
            ->assertSee(route('admin.applications.destroy', $admission), false);

        $approvedNotice = 'Application '.$admission->application_number.' is approved. The applicant can now enroll with this number.';

        $this->actingAs($admin)
            ->patch(route('admin.applications.update', $admission), [
                'status' => AdmissionApplication::STATUS_APPROVED,
            ])
            ->assertRedirect(route('admin.applications.show', $admission))
            ->assertSessionHas('saved', $approvedNotice);

        $reviewPage = $this->actingAs($admin)->get(route('admin.applications.show', $admission));
        $reviewPage->assertOk()->assertSee($approvedNotice);
        $this->assertSame(1, substr_count($reviewPage->getContent(), $approvedNotice));

        $this->assertSame(AdmissionApplication::STATUS_APPROVED, $admission->fresh()->status);

        Mail::assertSent(AdmissionApplicationReviewedMail::class, function (AdmissionApplicationReviewedMail $mail) use ($admission): bool {
            return $mail->hasTo($admission->email)
                && $mail->admission->is($admission)
                && str_contains($mail->envelope()->subject, 'approved')
                && str_contains($mail->render(), $admission->application_number)
                && str_contains($mail->render(), $admission->enrollmentUrl());
        });

        $this->post(route('logout'));

        $this->get($admission->fresh()->enrollmentUrl())
            ->assertOk()
            ->assertSee('id="enrollment-form"', false);
    }

    public function test_duplicate_open_application_email_is_rejected(): void
    {
        $this->makeApprovedAdmission(['email' => 'repeat.applicant@gmail.com']);

        $programId = TrainingProgram::query()->value('id');

        $response = $this->from(route('applications.create'))
            ->post(route('applications.store'), [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'email' => 'repeat.applicant@gmail.com',
                'contact_number' => '09170000000',
                'training_program_id' => $programId,
                'educational_attainment' => 'High School Graduate',
                'privacy_consent' => '1',
            ]);

        $response
            ->assertRedirect(route('applications.create'))
            ->assertSessionHasErrors([
                'email' => AdmissionApplication::EMAIL_IN_USE_MESSAGE,
            ]);

        $this->followRedirects($response)
            ->assertSee(AdmissionApplication::EMAIL_IN_USE_MESSAGE, false)
            ->assertSee('Check status', false)
            ->assertDontSee('Please review the highlighted application fields', false);
    }

    public function test_application_form_preselects_the_program_from_the_landing_page(): void
    {
        $program = TrainingProgram::query()->create([
            'name' => 'Caregiving NC III',
            'code' => 'CAREGIVING-NC-III',
            'description' => 'Advanced caregiving catalog offering.',
            'total_program_fee' => 30000.00,
            'downpayment_amount' => 3500.00,
            'is_active' => true,
        ]);

        $this->get(route('applications.create', ['training_program_id' => $program->id]))
            ->assertOk()
            ->assertSee('value="'.$program->id.'" selected', false);
    }

    public function test_application_submit_stays_disabled_and_incomplete_posts_are_rejected(): void
    {
        $html = $this->get(route('applications.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<button\b[^>]*(?:\bdisabled\b[^>]*type="submit"|type="submit"[^>]*\bdisabled\b)/i',
            $html
        );

        $this->from(route('applications.create'))
            ->post(route('applications.store'), [
                'first_name' => 'Maria',
                'last_name' => 'Santos',
                'email' => 'maria.applicant@gmail.com',
                'contact_number' => '09170000000',
                'educational_attainment' => 'High School Graduate',
            ])
            ->assertSessionHasErrors('privacy_consent');
    }

    public function test_admin_can_delete_an_application_from_the_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admission = AdmissionApplication::query()->create([
            'application_number' => AdmissionApplication::generateNumber(),
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'email' => 'delete.applicant@gmail.com',
            'contact_number' => '09170000000',
            'educational_attainment' => 'College Graduate',
            'status' => AdmissionApplication::STATUS_PENDING,
            'privacy_consent_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.applications.index'))
            ->delete(route('admin.applications.destroy', $admission))
            ->assertRedirect(route('admin.applications.index'))
            ->assertSessionHas('saved', 'Application '.$admission->application_number.' was deleted.');

        $this->assertDatabaseMissing('admission_applications', ['id' => $admission->id]);
    }

    public function test_admin_cannot_delete_an_application_linked_to_enrollment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admission = $this->makeApprovedAdmission(['email' => 'linked.applicant@gmail.com']);
        $trainee = User::factory()->create(['role' => 'trainee', 'email' => $admission->email]);

        EnrollmentApplication::query()->create([
            'user_id' => $trainee->id,
            'admission_application_id' => $admission->id,
            'email' => $admission->email,
            'program' => 'Caregiving NC II',
            'first_name' => $admission->first_name,
            'last_name' => $admission->last_name,
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => $admission->contact_number,
            'schedule_preference' => 'AM',
            'street' => '1 Training Street',
            'barangay' => 'Central',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.applications.index'))
            ->delete(route('admin.applications.destroy', $admission))
            ->assertRedirect(route('admin.applications.index'))
            ->assertSessionHasErrors('application');

        $this->assertDatabaseHas('admission_applications', ['id' => $admission->id]);
    }
}
