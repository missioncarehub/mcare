<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use App\Notifications\EnrollmentSubmittedNotification;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnrollmentSubmissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_applicant_can_submit_documents_and_drawn_signature(): void
    {
        Notification::fake();
        Storage::fake('local');

        $batch = TrainingBatch::create([
            'training_program_id' => TrainingProgram::query()->value('id'),
            'name' => 'Batch 1',
            'year' => 2026,
            'is_active' => true,
            'show_on_enrollment_page' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addWeek(),
        ]);

        $response = $this->post(route('enrollment.store'), $this->validEnrollmentPayload([
            'training_batch_id' => $batch->id,
            'email' => 'applicant@gmail.com',
        ]));

        $response->assertRedirect(route('payment.show'));
        // Completing the public enrollment handoff must not silently create an
        // authenticated account session; payment continuation is session-bound.
        $this->assertGuest();
        $this->get(route('payment.show'))->assertOk();

        $this->assertDatabaseHas('enrollment_applications', [
            'email' => 'applicant@gmail.com',
            'signature_type' => 'draw',
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'payment_status' => EnrollmentApplication::PAYMENT_NOT_SELECTED,
            'payment_method' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'applicant@gmail.com',
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
            'last_name' => 'Santos',
            'contact_email' => 'applicant@gmail.com',
            'contact_number' => '09170000000',
            'gender' => 'Female',
            'city' => 'Quezon City',
            'guardian_name' => 'Ana Santos',
            'trainee_status' => null,
        ]);

        $application = EnrollmentApplication::firstOrFail();
        $this->assertTrue($application->user?->birth_date?->isSameDay('2000-01-01'));
        $this->assertMatchesRegularExpression('/^MCE-'.now()->year.'-[A-Z0-9]{6}$/', $application->enrollment_number);
        $this->assertNull($application->paymongo_checkout_reference);
        $this->get(route('payment.show'))
            ->assertOk()
            ->assertSee($application->enrollment_number, false)
            ->assertSee('Copy', false)
            ->assertSee('Continue with selected method', false)
            ->assertSee('data-payment-confirm', false)
            ->assertSee('Confirm PayMongo payment', false)
            ->assertSee('Confirm pay on site', false)
            ->assertSee('value="online"', false)
            ->assertSee('value="onsite"', false)
            ->assertDontSee('https://checkout.paymongo.com', false);

        Storage::disk('local')->assertExists($application->birth_certificate_path);
        Storage::disk('local')->assertExists($application->education_document_path);
        Storage::disk('local')->assertExists($application->good_moral_certificate_path);
        Storage::disk('local')->assertExists($application->id_photo_path);
        Storage::disk('local')->assertExists($application->signature_path);

        $this->assertStringStartsWith('/storage/avatars/'.$application->user_id.'/', (string) $application->user?->profilePhotoUrl());
        $this->assertNotNull($application->user?->profile_photo_path);
        $this->assertTrue(Storage::disk('public')->exists($application->user->profile_photo_path));

        Notification::assertNotSentTo($application->user, QueuedVerifyEmail::class);
        Notification::assertSentTo(
            $application->user,
            EnrollmentSubmittedNotification::class,
            fn (EnrollmentSubmittedNotification $notification, array $channels): bool => $notification instanceof ShouldQueue
                && in_array('database', $channels, true)
                && ! in_array('mail', $channels, true),
        );
    }

    public function test_enrollment_locks_educational_attainment_from_the_approved_application(): void
    {
        Notification::fake();
        $batch = TrainingBatch::create([
            'training_program_id' => TrainingProgram::query()->value('id'),
            'name' => 'Locked Education Batch',
            'year' => 2026,
            'is_active' => true,
            'show_on_enrollment_page' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addWeek(),
        ]);
        $admission = $this->makeApprovedAdmission([
            'email' => 'locked.edu@gmail.com',
            'educational_attainment' => 'College Graduate',
        ]);

        $this->withSession([
            'enrollment.admission_application_id' => $admission->id,
        ])->get(route('enrollment.create', ['batch' => $batch->id]))
            ->assertOk()
            ->assertSee('Highest educational attainment')
            ->assertSee('College Graduate')
            ->assertSee('data-locked-educational-attainment', false)
            ->assertSee('Copied from your approved application and cannot be changed.')
            ->assertDontSee('<select id="educational_attainment"', false);

        $this->post(route('enrollment.store'), $this->validEnrollmentPayload([
            'admission' => $admission,
            'training_batch_id' => $batch->id,
            'email' => 'locked.edu@gmail.com',
            'educational_attainment' => 'Doctorate',
        ]))->assertRedirect(route('payment.show'));

        $this->assertDatabaseHas('enrollment_applications', [
            'email' => 'locked.edu@gmail.com',
            'educational_attainment' => 'College Graduate',
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'locked.edu@gmail.com',
            'educational_attainment' => 'College Graduate',
        ]);
    }

    public function test_enrollment_sets_year_graduated_to_null_when_attainment_is_not_graduate(): void
    {
        Notification::fake();
        $batch = TrainingBatch::create([
            'training_program_id' => TrainingProgram::query()->value('id'),
            'name' => 'Undergraduate Batch',
            'year' => 2026,
            'is_active' => true,
            'show_on_enrollment_page' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addWeek(),
        ]);
        $admission = $this->makeApprovedAdmission([
            'email' => 'undergrad.edu@gmail.com',
            'educational_attainment' => 'College Undergraduate',
        ]);

        $this->withSession([
            'enrollment.admission_application_id' => $admission->id,
        ])->get(route('enrollment.create', ['batch' => $batch->id]))
            ->assertOk()
            ->assertSee('College Undergraduate')
            ->assertSee('value="N/A"', false)
            ->assertSee('not a graduate level');

        $this->post(route('enrollment.store'), $this->validEnrollmentPayload([
            'admission' => $admission,
            'training_batch_id' => $batch->id,
            'email' => 'undergrad.edu@gmail.com',
            'educational_attainment' => 'College Graduate',
            'year_graduated' => 2018,
        ]))->assertRedirect(route('payment.show'));

        $application = EnrollmentApplication::query()->where('email', 'undergrad.edu@gmail.com')->firstOrFail();
        $this->assertSame('College Undergraduate', $application->educational_attainment);
        $this->assertNull($application->year_graduated);
        $this->assertNull($application->user?->year_graduated);
    }

    public function test_enrollment_form_uses_a_simple_browser_submit_and_json_handoff_still_works(): void
    {
        Notification::fake();
        Storage::fake('local');

        $batch = TrainingBatch::create([
            'training_program_id' => TrainingProgram::query()->value('id'),
            'name' => 'Mobile Enrollment Batch',
            'year' => 2026,
            'is_active' => true,
            'show_on_enrollment_page' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addWeek(),
        ]);

        $this->withSession([
            'enrollment.admission_application_id' => $this->makeApprovedAdmission([
                'email' => 'mobile.applicant@gmail.com',
            ])->id,
        ])->get(route('enrollment.create', ['batch' => $batch->id]))
            ->assertOk()
            ->assertSee('id="enrollment-submit-progress"', false)
            ->assertSee('mcare-spinner', false)
            ->assertSee('action="'.route('enrollment.store', absolute: false).'"', false)
            ->assertSee('formaction="'.route('enrollment.store', absolute: false).'"', false)
            ->assertSee('id="privacy_consent"', false)
            ->assertSee('data-enrollment-submit', false)
            ->assertSee('Submitting…', false)
            ->assertDontSee('new XMLHttpRequest()', false)
            ->assertDontSee('The phone lost its connection to MCARE.', false)
            ->assertDontSee('No upload progress was received for one minute.', false);

        $response = $this->post(route('enrollment.store'), $this->validEnrollmentPayload([
            'training_batch_id' => $batch->id,
            'email' => 'mobile.applicant@gmail.com',
        ]), [
            'Accept' => 'application/json',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('redirect', route('payment.show'))
            ->assertJsonPath('message', 'Caregiving NC II enrollment registration saved. Choose your payment method to continue.');

        $this->assertGuest();
        $this->assertDatabaseHas('enrollment_applications', [
            'email' => 'mobile.applicant@gmail.com',
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
        ]);
        $this->get(route('payment.show'))->assertOk();
    }

    public function test_google_applicant_submits_without_password_and_receives_confirmation(): void
    {
        Notification::fake();
        Storage::fake('local');

        $batch = TrainingBatch::create([
            'training_program_id' => TrainingProgram::query()->value('id'),
            'name' => 'Batch 1',
            'year' => 2026,
            'is_active' => true,
            'show_on_enrollment_page' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addWeek(),
        ]);

        $user = User::factory()->create([
            'name' => 'Maria Santos',
            'email' => 'verified.applicant@gmail.com',
            'google_id' => 'google-verified-123',
            'role' => 'applicant',
        ]);
        $originalPassword = $user->password;
        $payload = $this->validEnrollmentPayload([
            'training_batch_id' => $batch->id,
            // The authenticated account email wins over a tampered form value.
            'email' => 'different.account@gmail.com',
            'admission' => $this->makeApprovedAdmission(['email' => 'verified.applicant@gmail.com']),
        ]);
        unset($payload['password'], $payload['password_confirmation']);

        $this->actingAs($user)
            ->post(route('enrollment.store'), $payload)
            ->assertRedirect(route('payment.show'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame($originalPassword, $user->refresh()->password);
        $this->assertDatabaseHas('enrollment_applications', [
            'user_id' => $user->id,
            'email' => 'verified.applicant@gmail.com',
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
        ]);

        Notification::assertSentTo(
            $user,
            EnrollmentSubmittedNotification::class,
            fn (EnrollmentSubmittedNotification $notification, array $channels): bool => $notification instanceof ShouldQueue
                && in_array('database', $channels, true)
                && ! in_array('mail', $channels, true),
        );
    }

    public function test_applicant_can_generate_pay_on_site_receipt(): void
    {
        $user = User::factory()->create();
        EnrollmentApplication::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'employment_status' => 'Unemployed',
            'contact_number' => '09170000000',
            'nationality' => 'Filipino',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'region' => 'NCR',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'guardian_name' => 'Ana Santos',
            'guardian_address' => '123 Training Street',
            'privacy_consent' => true,
            'signature_name' => 'Maria Santos',
            'date_accomplished' => now()->toDateString(),
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
            'payment_status' => EnrollmentApplication::PAYMENT_NOT_SELECTED,
        ]);

        $this->actingAs($user)
            ->post(route('payment.select'), [
                'payment_method' => 'onsite',
            ])
            ->assertRedirect(route('payment.receipt'));

        $application = EnrollmentApplication::firstOrFail();

        $this->assertSame(EnrollmentApplication::PAYMENT_ONSITE_PENDING, $application->payment_status);
        $this->assertSame('onsite', $application->payment_method);
        $this->assertNotNull($application->payment_reference);
        $this->assertStringStartsWith('MCARE-SITE-', $application->payment_reference);
        $this->assertNotNull($application->payment_receipt_number);
        $this->assertStringStartsWith('MCARE-OR-', $application->payment_receipt_number);
        $this->assertTrue($application->payment_receipt_expires_at->isFuture());

        $this->actingAs($user)
            ->get(route('payment.show'))
            ->assertOk()
            ->assertSee('Pay on site is already selected', false)
            ->assertSee('View receipt', false)
            ->assertSeeInOrder([
                'data-payment-continue',
                'disabled',
                'Continue with selected method',
            ], false);

        $this->actingAs($user)
            ->post(route('payment.select'), [
                'payment_method' => 'online',
            ])
            ->assertRedirect(route('payment.receipt'));

        $this->assertSame(EnrollmentApplication::PAYMENT_ONSITE_PENDING, $application->refresh()->payment_status);
        $this->assertSame('onsite', $application->payment_method);
    }

    public function test_denied_applicant_can_correct_and_resubmit_without_losing_verified_payment(): void
    {
        Notification::fake();
        Storage::fake('local');
        $batch = TrainingBatch::create([
            'name' => 'Reapplication Batch',
            'year' => 2026,
            'is_active' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addWeek(),
        ]);
        $user = User::factory()->create([
            'email' => 'reapply@gmail.com',
            'role' => 'applicant',
            'applicant_status' => EnrollmentApplication::STATUS_DENIED,
            'email_verified_at' => now(),
        ]);
        $application = EnrollmentApplication::create([
            'user_id' => $user->id,
            'training_batch_id' => $batch->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_DENIED,
            'admin_notes' => 'Replace the unclear documents and submit again.',
            'reviewed_at' => now(),
            'reviewed_by_id' => User::factory()->create(['role' => 'admin'])->id,
            'total_paid_amount' => 2000,
            'downpayment_amount' => 2000,
            'payment_status' => EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
            'payment_verified_at' => now(),
            'document_review' => [
                'birth-certificate' => ['status' => 'replace', 'note' => 'Upload a clear copy.'],
            ],
        ]);
        $payload = $this->validEnrollmentPayload(['email' => 'tampered@gmail.com']);
        unset($payload['password'], $payload['password_confirmation']);

        $this->actingAs($user)
            ->post(route('enrollment.store'), $payload)
            ->assertRedirect(route('payment.show'))
            ->assertSessionHas('payment_notice', 'Your corrected enrollment was resubmitted for admin review. Your existing verified payment remains recorded.');

        $application->refresh();
        $this->assertSame(EnrollmentApplication::STATUS_PRE_ENLISTMENT, $application->status);
        $this->assertSame(EnrollmentApplication::STATUS_PRE_ENLISTMENT, $user->refresh()->applicant_status);
        $this->assertNull($application->admin_notes);
        $this->assertNull($application->reviewed_at);
        $this->assertNull($application->reviewed_by_id);
        $this->assertTrue($application->hasEnrollmentPaymentClearance());
        $this->assertSame('unreviewed', $application->document_review['birth-certificate']['status']);

        $log = AdminActivityLog::query()->where('action', 'enrollment.denied.resubmitted')->firstOrFail();
        $this->assertSame('Replace the unclear documents and submit again.', $log->meta['previous_admin_notes']);
        $this->assertTrue($log->meta['payment_clearance_preserved']);

        Notification::assertSentTo(
            $user,
            EnrollmentSubmittedNotification::class,
            fn (EnrollmentSubmittedNotification $notification): bool => $notification->application->is($application),
        );
    }

    public function test_selected_program_and_fees_are_snapshotted_from_the_published_batch(): void
    {
        Notification::fake();
        Storage::fake('local');

        $program = TrainingProgram::create([
            'name' => 'Caregiving NC III',
            'code' => 'CAREGIVING-NC-III',
            'total_program_fee' => 30000,
            'downpayment_amount' => 3500,
            'is_active' => true,
        ]);
        $batch = TrainingBatch::create([
            'training_program_id' => $program->id,
            'name' => 'NC III Batch Alpha',
            'year' => 2026,
            'is_active' => true,
            'show_on_enrollment_page' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addMonth(),
            'am_days' => 'MWF',
            'pm_days' => 'TTH',
        ]);

        $this->post(route('enrollment.store'), $this->validEnrollmentPayload([
            'training_batch_id' => $batch->id,
            'email' => 'nc3.applicant@gmail.com',
        ]))->assertRedirect(route('payment.show'));

        $application = EnrollmentApplication::query()
            ->where('email', 'nc3.applicant@gmail.com')
            ->firstOrFail();

        $this->assertSame('Caregiving NC III', $application->program);
        $this->assertSame($program->id, $application->training_program_id);
        $this->assertSame($batch->id, $application->training_batch_id);
        $this->assertSame(30000.0, (float) $application->total_program_fee);
        $this->assertSame(3500.0, (float) $application->downpayment_amount);
        $this->assertSame(3500.0, (float) $application->payment_amount);
        $this->assertNull($application->review_released_at);
    }

    public function test_official_geographic_names_with_apostrophes_and_enye_are_accepted(): void
    {
        Notification::fake();
        Storage::fake('local');

        $batch = TrainingBatch::create([
            'training_program_id' => TrainingProgram::query()->value('id'),
            'name' => 'Bicol Enrollment Batch',
            'year' => 2026,
            'is_active' => true,
            'show_on_enrollment_page' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addWeek(),
        ]);

        $this->post(route('enrollment.store'), $this->validEnrollmentPayload([
            'training_batch_id' => $batch->id,
            'email' => 'bicol.applicant@gmail.com',
            'first_name' => "Ma. D'Angelo",
            'last_name' => "O'Brien",
            'region' => 'Bicol Region',
            'province' => 'Camarines Sur',
            'city' => 'Pili',
            'barangay' => 'Santo Niño',
            'school_name' => "St. Mary's Academy",
            'guardian_name' => 'Ana Dela Torre',
            'guardian_address' => '24 E. Corporal Street, Santo Niño',
        ]))->assertRedirect(route('payment.show'));

        $this->assertDatabaseHas('enrollment_applications', [
            'email' => 'bicol.applicant@gmail.com',
            'barangay' => 'Santo Niño',
            'first_name' => "Ma. D'Angelo",
            'school_name' => "St. Mary's Academy",
        ]);
    }

    public function test_failed_enrollment_returns_to_the_form_instead_of_the_address_lookup(): void
    {
        $admission = $this->makeApprovedAdmission([
            'email' => 'retry.applicant@gmail.com',
        ]);
        $lookupUrl = route('enrollment.address.barangays', ['city_code' => '051706000']);

        $this->from($lookupUrl)
            ->withSession([
                '_previous.url' => $lookupUrl,
                'enrollment.admission_application_id' => $admission->id,
            ])
            ->post(route('enrollment.store'), [
                'application_number' => $admission->application_number,
                'email' => 'retry.applicant@gmail.com',
            ])
            ->assertRedirect(route('enrollment.create'))
            ->assertSessionHasErrors(['first_name', 'barangay']);
    }

    public function test_submit_stays_disabled_until_the_certification_checkbox_is_checked(): void
    {
        $batch = TrainingBatch::create([
            'training_program_id' => TrainingProgram::query()->value('id'),
            'name' => 'Consent Batch',
            'year' => 2026,
            'is_active' => true,
            'show_on_enrollment_page' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addWeek(),
        ]);

        $html = $this->withSession([
            'enrollment.admission_application_id' => $this->makeApprovedAdmission()->id,
        ])->get(route('enrollment.create', ['batch' => $batch->id]))
            ->assertOk()
            ->assertSee('id="privacy_consent"', false)
            ->assertSee('I agree and certify that the information stated above is true and correct.', false)
            ->getContent();

        $this->assertDoesNotMatchRegularExpression('/id="privacy_consent"[^>]*\bchecked\b/', $html);
        $this->assertMatchesRegularExpression('/data-enrollment-submit[^>]*\bdisabled\b/', $html);
        $this->assertStringContainsString('privacyConsentInput?.addEventListener(\'change\', syncEnrollmentSubmitButton)', $html);
    }

    /** @return array<string, mixed> */
    private function validEnrollmentPayload(array $overrides = []): array
    {
        $email = $overrides['email'] ?? 'applicant@gmail.com';
        $admission = $overrides['admission'] ?? $this->makeApprovedAdmission(['email' => $email]);
        unset($overrides['admission']);

        return array_merge([
            'application_number' => $admission->application_number,
            'training_batch_id' => TrainingBatch::query()->value('id'),
            'email' => $email,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
            'last_name' => 'Santos',
            'extension_name' => null,
            'birth_date' => '2000-01-01',
            'birthplace_city' => 'Quezon City',
            'birthplace_province' => 'Metro Manila',
            'birthplace_region' => 'NCR',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'employment_status' => 'Unemployed',
            'employment_type' => null,
            'contact_number' => '09170000000',
            'nationality' => 'Filipino',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'region' => 'NCR',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'guardian_name' => 'Ana Santos',
            'guardian_address' => '123 Training Street',
            'classification' => null,
            'disability_type' => null,
            'disability_cause' => null,
            'scholarship_type' => null,
            'privacy_consent' => '1',
            'signature_name' => 'Maria Santos',
            'signature_type' => 'draw',
            'signature_data' => 'data:image/png;base64,'.base64_encode('fake-signature-bytes'),
            'birth_certificate' => UploadedFile::fake()->create('birth-certificate.pdf', 100, 'application/pdf'),
            'education_document' => UploadedFile::fake()->create('diploma.pdf', 100, 'application/pdf'),
            'good_moral_certificate' => UploadedFile::fake()->create('good-moral.pdf', 100, 'application/pdf'),
            'id_photo' => UploadedFile::fake()->create('id-photo.jpg', 100, 'image/jpeg'),
        ], $overrides);
    }
}
