<?php

namespace Tests\Feature;

use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Notifications\EnrollmentStatusUpdatedNotification;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdminEnrollmentReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get('/admin/enrollments')
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_open_admin_queue(): void
    {
        $user = User::factory()->create(['role' => 'applicant']);

        $this->actingAs($user)
            ->get('/admin/enrollments')
            ->assertForbidden();
    }

    public function test_admin_can_update_enrollment_review_status(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create([
            'role' => 'applicant',
            'applicant_status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
        ]);

        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => 'applicant@gmail.com',
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
            'review_released_at' => now(),
            'documents_reviewed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.update', $application), [
                'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
                'admin_notes' => 'Ready for document verification.',
            ])
            ->assertRedirect(route('admin.enrollments.show', $application));

        $this->assertDatabaseHas('enrollment_applications', [
            'id' => $application->id,
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'admin_notes' => 'Ready for document verification.',
            'reviewed_by_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $applicant->id,
            'applicant_status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
        ]);

        Notification::assertSentTo(
            $applicant,
            EnrollmentStatusUpdatedNotification::class,
            fn (EnrollmentStatusUpdatedNotification $notification, array $channels): bool => $notification instanceof ShouldQueue
                && $notification->queue === 'mail'
                && $notification->application->is($application)
                && in_array('database', $channels, true)
                && in_array('mail', $channels, true),
        );
        Notification::assertNotSentTo($applicant, QueuedVerifyEmail::class);
    }

    public function test_saving_a_decision_requires_document_review_first(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'review_released_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.enrollments.show', $application))
            ->patch(route('admin.enrollments.update', $application), [
                'status' => EnrollmentApplication::STATUS_APPROVED,
            ])
            ->assertRedirect(route('admin.enrollments.show', $application))
            ->assertSessionHasErrors([
                'status' => 'Review the applicant documents first before saving a decision.',
            ]);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.show', $application))
            ->assertOk()
            ->assertSee('Review the applicant documents first before saving a decision.')
            ->assertSee('Document review cannot be completed while required documents are pending.')
            ->assertDontSee('popover="manual"', false);

        $this->assertSame(EnrollmentApplication::STATUS_PRE_ENLISTMENT, $application->fresh()->status);
        $this->assertSame('applicant', $applicant->fresh()->role);
    }

    public function test_review_page_starts_with_a_review_documents_action(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'review_released_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.show', $application))
            ->assertOk()
            ->assertSee('Review documents')
            ->assertSee(route('admin.enrollments.document-review', $application), false)
            ->assertDontSee('>Done for review</button>', false);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.document-review', $application))
            ->assertOk()
            ->assertSee('Review documents')
            ->assertSee('Done for review')
            ->assertSee('Back to application');
    }

    public function test_admin_can_preview_and_download_filled_tesda_registration_form(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => 'maria.santos@example.test',
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'middle_name' => 'Reyes',
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
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE College',
            'year_graduated' => 2022,
            'guardian_name' => 'Juan Santos',
            'guardian_address' => 'Quezon City',
            'privacy_consent' => true,
            'signature_name' => 'Maria Reyes Santos',
            'date_accomplished' => '2026-07-12',
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
            'review_released_at' => now(),
        ]);

        $preview = $this->actingAs($admin)->get(route('admin.enrollments.tesda-form', [
            $application,
            'disposition' => 'inline',
        ]));

        $preview->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $preview->getContent());
        $this->assertStringContainsString('inline;', (string) $preview->headers->get('Content-Disposition'));

        $download = $this->actingAs($admin)->get(route('admin.enrollments.tesda-form', [
            $application,
            'disposition' => 'attachment',
        ]));

        $download->assertOk();
        $this->assertStringContainsString('attachment;', (string) $download->headers->get('Content-Disposition'));
    }

    public function test_tesda_registration_form_embeds_the_id_photo_and_signature(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'trainee']);
        $applicant->forceFill([
            'profile_photo_path' => 'avatars/'.$applicant->id.'/missing.png',
        ])->save();
        $photoPath = 'enrollment-documents/'.$applicant->id.'/id-photo.png';
        $signaturePath = 'enrollment-documents/'.$applicant->id.'/signature.png';
        $this->storeTesdaJpeg($photoPath, 320, 240);
        $this->storeTesdaSignaturePng($signaturePath);

        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'John Lloyd',
            'last_name' => 'Blanquera',
            'birth_date' => '2000-01-01',
            'gender' => 'Male',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE College',
            'year_graduated' => 2022,
            'privacy_consent' => true,
            'id_photo_path' => $photoPath,
            'signature_path' => $signaturePath,
            'signature_type' => 'draw',
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'review_released_at' => now(),
        ]);

        $photoUrl = route('admin.enrollments.photo', $application, absolute: false);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.show', $application))
            ->assertOk()
            ->assertSee($photoUrl, false)
            ->assertDontSee('/storage/'.$applicant->profile_photo_path, false);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.index'))
            ->assertOk()
            ->assertSee($photoUrl, false);

        $this->actingAs($admin)
            ->get($photoUrl)
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $preview = $this->actingAs($admin)->get(route('admin.enrollments.tesda-form', [
            $application,
            'disposition' => 'inline',
        ]));

        $preview->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $preview->getContent());
        $this->assertStringContainsString('/DCTDecode', $preview->getContent());
        $this->assertGreaterThanOrEqual(3, preg_match_all('/\/Subtype\s*\/Image/', $preview->getContent()));
    }

    public function test_enrollment_photo_prefers_the_trainee_uploaded_avatar_over_the_id_photo(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'trainee']);

        // The ID photo taken during enrollment stays on the private disk. When
        // the trainee later replaces their avatar from Account Settings, admin
        // views must reflect the new picture instead of the old ID.
        $idPhotoPath = 'enrollment-documents/'.$applicant->id.'/id-photo.jpg';
        $this->storeTesdaJpeg($idPhotoPath, 320, 240);

        $publicPath = 'avatars/'.$applicant->id.'/face.png';
        Storage::disk('public')->put($publicPath, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='
        ));
        $applicant->forceFill(['profile_photo_path' => $publicPath])->save();

        $applicationWithIdOnly = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Julianne',
            'last_name' => 'Alipio',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Street',
            'barangay' => 'Barangay',
            'city' => 'City',
            'province' => 'Province',
            'zip_code' => '1000',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE College',
            'year_graduated' => 2022,
            'id_photo_path' => $idPhotoPath,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'review_released_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.photo', $applicationWithIdOnly))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_enrollment_photo_falls_back_to_the_public_profile_photo(): void
    {
        Storage::fake('local');
        Storage::fake('public');

        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'trainee']);
        $publicPath = 'avatars/'.$applicant->id.'/face.jpg';
        $this->storeTesdaJpeg($publicPath, 80, 80);
        Storage::disk('public')->put($publicPath, Storage::disk('local')->get($publicPath));
        $applicant->forceFill(['profile_photo_path' => $publicPath])->save();

        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Julianne',
            'last_name' => 'Alipio',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Street',
            'barangay' => 'Barangay',
            'city' => 'City',
            'province' => 'Province',
            'zip_code' => '1000',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE College',
            'year_graduated' => 2022,
            'id_photo_path' => 'enrollment-documents/'.$applicant->id.'/missing.jpg',
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'review_released_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.photo', $application))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $this->actingAs($applicant)
            ->get(route('admin.enrollments.photo', $application))
            ->assertForbidden();
    }

    public function test_non_admin_cannot_generate_tesda_registration_form(): void
    {
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => 'private@example.test',
            'program' => 'Caregiving NC II',
            'first_name' => 'Private',
            'last_name' => 'Applicant',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Street',
            'barangay' => 'Barangay',
            'city' => 'City',
            'province' => 'Province',
            'zip_code' => '1000',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'Private College',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
            'review_released_at' => now(),
        ]);

        $this->actingAs($applicant)
            ->get(route('admin.enrollments.tesda-form', $application))
            ->assertForbidden();
    }

    public function test_admin_can_preview_documents_and_save_file_specific_feedback(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        Storage::disk('local')->put('enrollment-documents/1/birth.pdf', '%PDF-1.4 sample');
        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Document',
            'last_name' => 'Applicant',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Street',
            'barangay' => 'Barangay',
            'city' => 'City',
            'province' => 'Province',
            'zip_code' => '1000',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE College',
            'year_graduated' => 2022,
            'birth_certificate_path' => 'enrollment-documents/1/birth.pdf',
            'status' => EnrollmentApplication::STATUS_PROFILE_SUBMITTED,
            'review_released_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.documents.show', [$application, 'birth-certificate']))
            ->assertOk()
            ->assertSee('Back to document review');

        $this->actingAs($admin)
            ->get(route('admin.enrollments.documents.content', [$application, 'birth-certificate']))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Content-Disposition', 'inline; filename=birth.pdf');

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.documents.review', $application), [
                'documents' => [
                    'birth-certificate' => ['status' => 'replace', 'note' => 'Upload a clearer complete copy.'],
                    'education-document' => ['status' => 'missing', 'note' => 'Diploma is required.'],
                    'good-moral-certificate' => ['status' => 'unreviewed', 'note' => null],
                    'id-photo' => ['status' => 'unreviewed', 'note' => null],
                    'signature' => ['status' => 'unreviewed', 'note' => null],
                ],
            ])
            ->assertRedirect(route('admin.enrollments.document-review', $application))
            ->assertSessionHasErrors('documents')
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'Document review cannot be completed while required documents are pending'));

        $application->refresh();
        $this->assertSame('replace', $application->document_review['birth-certificate']['status']);
        $this->assertSame('Upload a clearer complete copy.', $application->document_review['birth-certificate']['note']);
        $this->assertNull($application->documents_reviewed_at);
        $this->assertNull($application->documents_reviewed_by_id);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.show', $application))
            ->assertOk()
            ->assertSee('Incomplete')
            ->assertSee('pending')
            ->assertDontSee('>Completed</span>', false);

        $this->actingAs($applicant)
            ->get(route('enrollment.create'))
            ->assertOk()
            ->assertSee('Upload a clearer complete copy.');
    }

    public function test_document_review_completes_only_when_every_required_document_is_accepted(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = $this->approvalReadyApplication($applicant);
        $application->forceFill([
            'document_review' => null,
            'documents_reviewed_at' => null,
            'documents_reviewed_by_id' => null,
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
        ])->save();

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.documents.review', $application), [
                'documents' => [
                    'birth-certificate' => ['status' => 'accepted', 'note' => null],
                    'education-document' => ['status' => 'accepted', 'note' => null],
                    'good-moral-certificate' => ['status' => 'accepted', 'note' => null],
                    'id-photo' => ['status' => 'accepted', 'note' => null],
                    'signature' => ['status' => 'accepted', 'note' => null],
                ],
            ])
            ->assertRedirect(route('admin.enrollments.show', $application))
            ->assertSessionHas('saved', 'Document review completed. You can now save the enrollment decision.');

        $application->refresh();
        $this->assertNotNull($application->documents_reviewed_at);
        $this->assertSame($admin->id, $application->documents_reviewed_by_id);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.show', $application))
            ->assertOk()
            ->assertSee('Completed')
            ->assertSee('All accepted');
    }

    public function test_show_page_does_not_mark_document_review_completed_when_documents_are_pending(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'birth_certificate_path' => 'enrollment-documents/test/birth-certificate.pdf',
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'review_released_at' => now(),
            'documents_reviewed_at' => now(),
            'document_review' => [
                'birth-certificate' => ['status' => 'accepted', 'note' => null],
                'education-document' => ['status' => 'unreviewed', 'note' => null],
                'good-moral-certificate' => ['status' => 'unreviewed', 'note' => null],
                'id-photo' => ['status' => 'unreviewed', 'note' => null],
                'signature' => ['status' => 'unreviewed', 'note' => null],
            ],
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.show', $application))
            ->assertOk()
            ->assertSee('Incomplete')
            ->assertSee('4 pending')
            ->assertSee('Document review stays incomplete while required documents are pending')
            ->assertDontSee('All accepted');
    }

    public function test_saving_an_approved_decision_emails_a_verification_link_and_login_waits_for_it(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->unverified()->create([
            'role' => 'applicant',
            'password' => 'Password123',
        ]);
        $application = $this->approvalReadyApplication($applicant);

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.update', $application), [
                'status' => EnrollmentApplication::STATUS_APPROVED,
                'admin_notes' => 'Documents and payment verified.',
            ])
            ->assertRedirect(route('admin.enrollments.show', $application))
            ->assertSessionHas('saved', fn (string $message): bool => str_contains($message, 'A verification link was emailed to '.$applicant->email));

        $this->assertSame('trainee', $applicant->refresh()->role);
        $this->assertFalse($applicant->hasVerifiedEmail());

        Notification::assertSentTo($applicant, QueuedVerifyEmail::class);
        Notification::assertSentTo(
            $applicant,
            EnrollmentStatusUpdatedNotification::class,
            fn (EnrollmentStatusUpdatedNotification $notification): bool => $notification->toMail($applicant)->subject === 'Verify your email to open your approved MCARE account',
        );

        Auth::logout();
        $this->post(route('login.store'), [
            'email' => $applicant->email,
            'password' => 'Password123',
        ])
            ->assertSessionHasErrors('email');
        $this->assertGuest();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $applicant->id, 'hash' => sha1($applicant->getEmailForVerification())],
        );

        $this->get($verificationUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('verified');
        $this->assertTrue($applicant->refresh()->hasVerifiedEmail());

        $this->post(route('login.store'), [
            'email' => $applicant->email,
            'password' => 'Password123',
        ])->assertRedirect(route('trainee.dashboard'));
        $this->assertAuthenticatedAs($applicant);
    }

    public function test_admin_queue_shows_a_delete_action_for_each_enrollment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'review_released_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.index'))
            ->assertOk()
            ->assertSee('Delete')
            ->assertSee(route('admin.enrollments.destroy', $application), false);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.show', $application))
            ->assertOk()
            ->assertSee('Delete')
            ->assertSee(route('admin.enrollments.destroy', $application), false);
    }

    public function test_admin_can_delete_an_enrollment_from_the_queue(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create([
            'role' => 'applicant',
            'email' => 'delete.enrollee@gmail.com',
        ]);
        $photoPath = 'enrollment-documents/test/id-photo.jpg';
        Storage::disk('local')->put($photoPath, 'id-photo');

        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'id_photo_path' => $photoPath,
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'review_released_at' => now(),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.enrollments.index'))
            ->delete(route('admin.enrollments.destroy', $application))
            ->assertRedirect(route('admin.enrollments.index'))
            ->assertSessionHas('saved', 'Enrollment for Santos, Maria (delete.enrollee@gmail.com) and related records were permanently removed.');

        $this->assertDatabaseMissing('enrollment_applications', ['id' => $application->id]);
        $this->assertDatabaseMissing('users', ['id' => $applicant->id]);
        $this->assertFalse(Storage::disk('local')->exists($photoPath));
    }

    public function test_admin_can_delete_a_historical_enrollment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->create(['role' => 'alumni']);
        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'is_historical_record' => true,
            'review_released_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.enrollments.show', $application))
            ->assertOk()
            ->assertSee('Delete verified alumni record?')
            ->assertSee('verified historical alumni claim');

        $this->actingAs($admin)
            ->from(route('admin.enrollments.index'))
            ->delete(route('admin.enrollments.destroy', $application))
            ->assertRedirect(route('admin.enrollments.index'))
            ->assertSessionHas('saved');

        $this->assertDatabaseMissing('enrollment_applications', ['id' => $application->id]);
        $this->assertDatabaseMissing('users', ['id' => $applicant->id]);
    }

    public function test_non_admin_cannot_delete_an_enrollment(): void
    {
        $applicant = User::factory()->create(['role' => 'applicant']);
        $application = EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Maria',
            'last_name' => 'Santos',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Quezon City',
            'province' => 'Metro Manila',
            'zip_code' => '1100',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'review_released_at' => now(),
        ]);

        $this->actingAs($applicant)
            ->delete(route('admin.enrollments.destroy', $application))
            ->assertForbidden();

        $this->assertDatabaseHas('enrollment_applications', ['id' => $application->id]);
    }

    public function test_saving_a_denied_decision_emails_a_verification_link_when_the_enrollee_is_unverified(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $applicant = User::factory()->unverified()->create(['role' => 'applicant']);
        $application = $this->approvalReadyApplication($applicant);

        $this->actingAs($admin)
            ->patch(route('admin.enrollments.update', $application), [
                'status' => EnrollmentApplication::STATUS_DENIED,
                'admin_notes' => 'The submitted documents do not meet MCARE requirements.',
            ])
            ->assertRedirect(route('admin.enrollments.show', $application));

        Notification::assertSentTo($applicant, QueuedVerifyEmail::class);
    }

    private function approvalReadyApplication(User $user): EnrollmentApplication
    {
        $batch = TrainingBatch::create([
            'name' => 'Caregiving Batch Review',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        return EnrollmentApplication::create([
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
            'street' => '123 Training Street',
            'barangay' => 'Central',
            'city' => 'Pili',
            'province' => 'Camarines Sur',
            'zip_code' => '4418',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'MCARE High School',
            'year_graduated' => 2020,
            'birth_certificate_path' => 'enrollment-documents/test/birth-certificate.pdf',
            'education_document_path' => 'enrollment-documents/test/education-document.pdf',
            'good_moral_certificate_path' => 'enrollment-documents/test/good-moral-certificate.pdf',
            'id_photo_path' => 'enrollment-documents/test/id-photo.jpg',
            'signature_path' => 'enrollment-documents/test/signature.png',
            'document_review' => [
                'birth-certificate' => ['status' => 'accepted', 'note' => null],
                'education-document' => ['status' => 'accepted', 'note' => null],
                'good-moral-certificate' => ['status' => 'accepted', 'note' => null],
                'id-photo' => ['status' => 'accepted', 'note' => null],
                'signature' => ['status' => 'accepted', 'note' => null],
            ],
            'documents_reviewed_at' => now(),
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'total_program_fee' => 22000.00,
            'downpayment_amount' => 2000.00,
            'total_paid_amount' => 2000.00,
            'payment_status' => EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
            'payment_method' => 'onsite',
            'payment_verified_at' => now(),
            'review_released_at' => now(),
        ]);
    }

    private function storeTesdaJpeg(string $path, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, $width, $height, imagecolorallocate($image, 30, 90, 180));
        ob_start();
        imagejpeg($image, null, 90);
        Storage::disk('local')->put($path, (string) ob_get_clean());
        imagedestroy($image);
    }

    private function storeTesdaSignaturePng(string $path): void
    {
        $image = imagecreatetruecolor(240, 80);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        imagefilledrectangle($image, 0, 0, 240, 80, imagecolorallocatealpha($image, 0, 0, 0, 127));
        imagealphablending($image, true);
        $ink = imagecolorallocate($image, 20, 20, 20);
        imageline($image, 12, 60, 90, 18, $ink);
        imageline($image, 90, 18, 228, 58, $ink);
        ob_start();
        imagepng($image);
        Storage::disk('local')->put($path, (string) ob_get_clean());
        imagedestroy($image);
    }
}
