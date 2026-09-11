<?php

namespace Tests\Feature;

use App\Models\CareerInquiry;
use App\Models\CareerOpportunity;
use App\Models\EnrollmentApplication;
use App\Models\TrainerAnnouncement;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Notifications\AdminOperationsNotification;
use App\Notifications\CareerOpportunityPublished;
use App\Notifications\LmsAnnouncementPublished;
use App\Services\SemaphoreSmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CareerHubTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_a_career_opportunity_and_notify_alumni(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $alumni = $this->graduatedUser();

        $this->actingAs($admin)
            ->post(route('admin.learning.alumni-jobs.store'), [
                'title' => 'Live-in caregiver, Iriga City',
                'estimated_salary' => '₱18,000 / month',
                'estimated_start_date' => now()->addWeek()->toDateString(),
                'patient_gender' => CareerOpportunity::GENDER_FEMALE,
                'mobility_status' => CareerOpportunity::MOBILITY_AMBULATORY,
                'patient_age' => 72,
                'specific_contraptions' => 'Walker',
                'condition_summary' => 'Needs mobility support during daily routines.',
                // Unapproved fields are ignored even when a crafted request sends them.
                'medical_history' => 'Must never be stored.',
                'application_email' => 'private@example.test',
                'requirements' => 'Upload patient records.',
                'is_published' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved', 'Career opportunity published and alumni were notified.');

        $adminPage = $this->actingAs($admin)->get(route('admin.learning.alumni-jobs'));
        $adminPage
            ->assertOk()
            ->assertSee('Live-in caregiver, Iriga City')
            ->assertSee('₱18,000 / month')
            ->assertDontSee('Duty #')
            ->assertSee('data-dashboard-toast', false)
            ->assertSee('data-auto-dismiss="5000"', false);
        $this->assertSame(1, substr_count(
            $adminPage->getContent(),
            'Career opportunity published and alumni were notified.'
        ));

        $opportunity = CareerOpportunity::query()->firstOrFail();

        $this->assertDatabaseHas('career_opportunities', [
            'id' => $opportunity->id,
            'is_published' => true,
            'patient_gender' => CareerOpportunity::GENDER_FEMALE,
            'mobility_status' => CareerOpportunity::MOBILITY_AMBULATORY,
            'patient_age' => 72,
            'title' => 'Live-in caregiver, Iriga City',
            'estimated_salary' => '₱18,000 / month',
            'application_email' => null,
            'requirements' => null,
        ]);
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $alumni->id,
            'type' => CareerOpportunityPublished::class,
        ]);

        $this->actingAs($alumni)
            ->get(route('alumni.dashboard'))
            ->assertOk()
            ->assertSee('Live-in caregiver, Iriga City')
            ->assertSee('₱18,000 / month')
            ->assertSee('Ambulatory')
            ->assertSee('MCARE-Coordinated Placement')
            ->assertSee('Open opportunity')
            ->assertSee('Contact MCARE for details')
            ->assertSee(route('trainee.career-hub.contact', $opportunity), false)
            ->assertDontSee('private@example.test')
            ->assertDontSee('Must never be stored');
    }

    public function test_immediate_career_sms_goes_only_to_graduates_with_contact_numbers(): void
    {
        Http::fake([
            'api.semaphore.co/*' => Http::response([['message_id' => 11]], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $graduate = $this->graduatedUser();
        $this->graduatedUser([
            'contact_number' => '',
        ]);
        $this->activeTrainee();

        $this->actingAs($admin)
            ->post(route('admin.learning.alumni-jobs.store'), [
                'title' => 'Home caregiver, Pili',
                'estimated_salary' => '₱19,000 / month',
                'estimated_start_date' => now()->addWeek()->toDateString(),
                'patient_gender' => CareerOpportunity::GENDER_FEMALE,
                'mobility_status' => CareerOpportunity::MOBILITY_AMBULATORY,
                'patient_age' => 68,
                'is_published' => '1',
                'sms_send_immediately' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved', 'Career opportunity published and alumni were notified. SMS sent to 1 graduate. 1 graduate had no valid contact number.');

        $opportunity = CareerOpportunity::query()->firstOrFail();
        $this->assertSame(CareerOpportunity::SMS_IMMEDIATE, $opportunity->sms_mode);
        $this->assertNotNull($opportunity->sms_sent_at);
        $this->assertSame(1, $opportunity->sms_sent_count);
        $this->assertSame(1, $opportunity->sms_skipped_count);

        Http::assertSent(function ($request) use ($graduate): bool {
            $number = app(SemaphoreSmsService::class)->normalizePhilippineNumber(
                $graduate->fresh()->contact_number
            );

            return $request->url() === SemaphoreSmsService::ENDPOINT
                && $request['apikey'] === 'testing-semaphore-key'
                && $request['number'] === $number
                && str_contains((string) $request['message'], 'Dear Alumni, we have a job offer for you: Home caregiver, Pili')
                && str_contains((string) $request['message'], 'Salary: ₱19,000 / month')
                && str_contains((string) $request['message'], 'Patient: Female, Ambulatory, age 68')
                && str_contains((string) $request['message'], 'Please open the MCARE Career Hub for details: https://mcarehub.com')
                && ! isset($request['scheduled']);
        });
    }

    public function test_graduate_sms_copy_summarizes_the_career_hub_post(): void
    {
        $start = now()->addWeek()->startOfDay();
        $opportunity = new CareerOpportunity([
            'title' => 'Live-in caregiver, Iriga City',
            'estimated_salary' => '₱18,000 / month',
            'estimated_start_date' => $start->toDateString(),
            'patient_gender' => CareerOpportunity::GENDER_FEMALE,
            'mobility_status' => CareerOpportunity::MOBILITY_AMBULATORY,
            'patient_age' => 72,
        ]);

        $this->assertSame(
            'Dear Alumni, we have a job offer for you: Live-in caregiver, Iriga City. Salary: ₱18,000 / month. Start: '.$start->format('M d, Y').'. Patient: Female, Ambulatory, age 72. Please open the MCARE Career Hub for details: https://mcarehub.com',
            $opportunity->graduateSmsMessage()
        );

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('admin.learning.alumni-jobs'))
            ->assertOk()
            ->assertSee('SMS graduates will receive')
            ->assertSee('Dear Alumni, we have a job offer for you: Career opportunity. Salary: see Career Hub. Start: TBA. Please open the MCARE Career Hub for details: https://mcarehub.com', false);
    }

    public function test_scheduled_career_sms_is_handed_to_semaphore_with_the_chosen_datetime(): void
    {
        Http::fake([
            'api.semaphore.co/*' => Http::response([['message_id' => 12]], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->graduatedUser();
        $sendAt = now()->addDay()->seconds(0);

        $this->actingAs($admin)
            ->post(route('admin.learning.alumni-jobs.store'), [
                'title' => 'Night caregiver, Naga City',
                'estimated_salary' => '₱21,000 / month',
                'estimated_start_date' => now()->addWeek()->toDateString(),
                'patient_gender' => CareerOpportunity::GENDER_MALE,
                'mobility_status' => CareerOpportunity::MOBILITY_BEDRIDDEN,
                'patient_age' => 79,
                'is_published' => '1',
                'sms_scheduled_at' => $sendAt->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect();

        $opportunity = CareerOpportunity::query()->firstOrFail();
        $this->assertSame(CareerOpportunity::SMS_SCHEDULED, $opportunity->sms_mode);
        $this->assertNotNull($opportunity->sms_sent_at);

        Http::assertSent(function ($request) use ($sendAt): bool {
            return $request->url() === SemaphoreSmsService::ENDPOINT
                && $request['scheduled'] === $sendAt->timezone(config('app.timezone'))->format('Y-m-d H:i:s')
                && str_contains((string) $request['number'], '63');
        });
    }

    public function test_invalid_semaphore_sender_name_is_not_marked_as_sent(): void
    {
        Http::fake([
            'api.semaphore.co/*' => Http::response([
                'sendername' => ['The selected sendername is invalid.'],
            ], 200),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->graduatedUser();

        $this->actingAs($admin)
            ->post(route('admin.learning.alumni-jobs.store'), [
                'title' => 'Weekend caregiver, Iriga City',
                'estimated_salary' => '₱15,000 / month',
                'estimated_start_date' => now()->addWeek()->toDateString(),
                'patient_gender' => CareerOpportunity::GENDER_FEMALE,
                'mobility_status' => CareerOpportunity::MOBILITY_AMBULATORY,
                'patient_age' => 64,
                'is_published' => '1',
                'sms_send_immediately' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved', 'Career opportunity published and alumni were notified. SMS was not sent: The selected sendername is invalid.');

        $this->assertDatabaseHas('career_opportunities', [
            'title' => 'Weekend caregiver, Iriga City',
            'sms_mode' => CareerOpportunity::SMS_IMMEDIATE,
            'sms_sent_at' => null,
            'sms_sent_count' => 0,
            'sms_last_error' => 'The selected sendername is invalid.',
        ]);
    }

    public function test_alumni_cannot_see_a_career_draft_or_read_another_account_notification(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $alumni = $this->graduatedUser();
        $otherAlumni = $this->graduatedUser();

        $this->actingAs($admin)->post(route('admin.learning.alumni-jobs.store'), [
            'title' => 'Day-shift caregiver, Iriga City',
            'estimated_salary' => '₱16,000 / month',
            'estimated_start_date' => now()->addDays(5)->toDateString(),
            'patient_gender' => CareerOpportunity::GENDER_FEMALE,
            'mobility_status' => CareerOpportunity::MOBILITY_AMBULATORY,
            'patient_age' => 70,
            'is_published' => '1',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.learning.alumni-jobs.store'), [
            'title' => 'Night-shift caregiver, Naga City',
            'estimated_salary' => '₱17,000 / month',
            'estimated_start_date' => now()->addDays(6)->toDateString(),
            'patient_gender' => CareerOpportunity::GENDER_MALE,
            'mobility_status' => CareerOpportunity::MOBILITY_BEDRIDDEN,
            'patient_age' => 80,
        ])->assertRedirect();

        $notification = $alumni->notifications()->firstOrFail();

        $this->actingAs($alumni)
            ->get(route('alumni.dashboard'))
            ->assertOk()
            ->assertSee('Day-shift caregiver, Iriga City')
            ->assertSee(now()->addDays(5)->format('M d, Y'))
            ->assertDontSee('Night-shift caregiver, Naga City')
            ->assertDontSee(now()->addDays(6)->format('M d, Y'));

        $this->actingAs($otherAlumni)
            ->patch(route('notifications.read', $notification))
            ->assertNotFound();

        $this->actingAs($alumni)
            ->patch(route('notifications.read', $notification))
            ->assertRedirect()
            ->assertSessionHas('saved', 'Notification marked as read.');

        $this->assertNotNull($alumni->notifications()->firstOrFail()->fresh()->read_at);
    }

    public function test_admin_can_preview_the_published_alumni_feed_without_impersonating_an_alumni(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);

        $opportunity = CareerOpportunity::create([
            'created_by_id' => $admin->id,
            'estimated_start_date' => now()->addDays(10)->toDateString(),
            'patient_gender' => CareerOpportunity::GENDER_MALE,
            'mobility_status' => CareerOpportunity::MOBILITY_BEDRIDDEN,
            'patient_age' => 81,
            'title' => 'Bedside caregiver, Naga City',
            'estimated_salary' => '₱20,000 / month',
            'employer' => 'MCARE-Coordinated Placement',
            'description' => 'Privacy-minimal career posting managed through the MCARE Alumni Hub.',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.learning.alumni-jobs.preview'))
            ->assertOk()
            ->assertSee('Admin preview of the shared graduate Career Hub')
            ->assertSee('Bedside caregiver, Naga City')
            ->assertSee('₱20,000 / month')
            ->assertSee(now()->addDays(10)->format('M d, Y'))
            ->assertSee('Open opportunity')
            ->assertSee('MCARE-Coordinated Placement')
            ->assertDontSee(route('trainee.career-hub.contact', $opportunity), false);

        $this->actingAs($trainee)
            ->get(route('admin.learning.alumni-jobs.preview'))
            ->assertForbidden();
    }

    public function test_alumni_can_set_availability_while_trainees_are_kept_out(): void
    {
        $alumni = $this->graduatedUser();
        $trainee = User::factory()->create(['role' => 'trainee']);

        $this->actingAs($alumni)
            ->patch(route('alumni.availability.update'), ['is_available_for_duty' => '1'])
            ->assertRedirect()
            ->assertSessionHas('saved', 'You are now marked Available for Duty.')
            ->assertSessionHas('saved_icon', 'circle-check');

        $this->assertDatabaseHas('alumni_profiles', [
            'user_id' => $alumni->id,
            'is_available_for_duty' => true,
        ]);

        $availablePage = $this->actingAs($alumni)->get(route('alumni.dashboard'));
        $availablePage
            ->assertOk()
            ->assertSee('data-availability-state="available"', false)
            ->assertSee('data-auto-dismiss="5000"', false)
            ->assertSee('data-flash-icon="circle-check"', false);
        $this->assertSame(1, substr_count(
            $availablePage->getContent(),
            'You are now marked Available for Duty.'
        ));

        $this->actingAs($alumni)
            ->patch(route('alumni.availability.update'), ['is_available_for_duty' => '0'])
            ->assertRedirect()
            ->assertSessionHas('saved', 'Your availability is now set to unavailable.')
            ->assertSessionHas('saved_icon', 'circle-minus');

        $unavailablePage = $this->actingAs($alumni)->get(route('alumni.dashboard'));
        $unavailablePage
            ->assertOk()
            ->assertSee('data-availability-state="unavailable"', false)
            ->assertSee('data-flash-icon="circle-minus"', false);
        $this->assertSame(1, substr_count(
            $unavailablePage->getContent(),
            'Your availability is now set to unavailable.'
        ));

        $this->actingAs($trainee)
            ->get(route('alumni.dashboard'))
            ->assertForbidden();

        $this->actingAs($trainee)
            ->patch(route('alumni.availability.update'), ['is_available_for_duty' => '1'])
            ->assertForbidden();
    }

    public function test_graduate_can_submit_a_career_inquiry_for_admin_review(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $graduate = $this->graduatedUser();
        $trainee = User::factory()->create(['role' => 'trainee']);

        $this->actingAs($admin)->post(route('admin.learning.alumni-jobs.store'), [
            'title' => 'Live-in caregiver, Pili',
            'estimated_salary' => '₱18,000 / month',
            'estimated_start_date' => now()->addWeek()->toDateString(),
            'patient_gender' => CareerOpportunity::GENDER_FEMALE,
            'mobility_status' => CareerOpportunity::MOBILITY_AMBULATORY,
            'patient_age' => 70,
            'is_published' => '1',
        ])->assertRedirect();

        $opportunity = CareerOpportunity::query()->firstOrFail();

        $this->actingAs($graduate)
            ->from(route('alumni.dashboard'))
            ->post(route('trainee.career-hub.contact', $opportunity), [
                'name' => $graduate->name,
                'email' => $graduate->email,
                'contact_number' => '09170000000',
                'message' => 'I am available for live-in duty starting next week.',
            ])
            ->assertRedirect(route('alumni.dashboard'))
            ->assertSessionHas('saved', 'Your inquiry was sent to MCARE administration.');

        $this->assertDatabaseHas('career_inquiries', [
            'user_id' => $graduate->id,
            'career_opportunity_id' => $opportunity->id,
            'email' => $graduate->email,
            'message' => 'I am available for live-in duty starting next week.',
            'status' => CareerInquiry::STATUS_PENDING,
        ]);

        $inquiry = CareerInquiry::query()->firstOrFail();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'type' => AdminOperationsNotification::class,
        ]);

        $adminPage = $this->actingAs($admin)->get(route('admin.learning.alumni-jobs'));
        $adminPage
            ->assertOk()
            ->assertSee('Career inquiries')
            ->assertSee($graduate->name)
            ->assertSee('I am available for live-in duty starting next week.')
            ->assertSee('Pending');

        $this->actingAs($admin)
            ->from(route('admin.learning.alumni-jobs'))
            ->patch(route('admin.learning.alumni-jobs.inquiries.update', $inquiry), [
                'status' => CareerInquiry::STATUS_REVIEWED,
                'admin_notes' => 'Called the graduate to confirm availability.',
            ])
            ->assertRedirect(route('admin.learning.alumni-jobs'))
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('career_inquiries', [
            'id' => $inquiry->id,
            'status' => CareerInquiry::STATUS_REVIEWED,
            'admin_notes' => 'Called the graduate to confirm availability.',
            'reviewed_by_id' => $admin->id,
        ]);

        $this->actingAs($graduate)
            ->get(route('alumni.dashboard'))
            ->assertOk()
            ->assertSee('Inquiry sent')
            ->assertDontSee('data-dashboard-dialog-open="career-contact-'.$opportunity->id.'"', false);

        $this->actingAs($graduate)
            ->from(route('alumni.dashboard'))
            ->post(route('trainee.career-hub.contact', $opportunity), [
                'name' => $graduate->name,
                'email' => $graduate->email,
                'contact_number' => '09170000000',
                'message' => 'Sending again.',
            ])
            ->assertRedirect(route('alumni.dashboard'))
            ->assertSessionHas('saved', 'MCARE administration already received your inquiry for this career.');

        $this->assertSame(1, CareerInquiry::query()->count());

        $this->actingAs($admin)
            ->from(route('admin.learning.alumni-jobs'))
            ->delete(route('admin.learning.alumni-jobs.inquiries.destroy', $inquiry))
            ->assertRedirect(route('admin.learning.alumni-jobs'));

        $this->assertDatabaseMissing('career_inquiries', ['id' => $inquiry->id]);

        $this->actingAs($trainee)
            ->post(route('trainee.career-hub.contact', $opportunity), [
                'name' => 'Trainee',
                'email' => $trainee->email,
                'contact_number' => '09171111111',
                'message' => 'Should be blocked.',
            ])
            ->assertForbidden();
    }

    public function test_unpublished_career_cannot_receive_an_inquiry(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $graduate = $this->graduatedUser();

        $opportunity = CareerOpportunity::create([
            'created_by_id' => $admin->id,
            'estimated_start_date' => now()->addDays(8)->toDateString(),
            'patient_gender' => CareerOpportunity::GENDER_MALE,
            'mobility_status' => CareerOpportunity::MOBILITY_BEDRIDDEN,
            'title' => 'Draft caregiver posting',
            'estimated_salary' => '₱12,000 / month',
            'employer' => 'MCARE-Coordinated Placement',
            'description' => 'Privacy-minimal career posting managed through the MCARE Alumni Hub.',
            'is_published' => false,
        ]);

        $this->actingAs($graduate)
            ->post(route('trainee.career-hub.contact', $opportunity), [
                'name' => $graduate->name,
                'email' => $graduate->email,
                'contact_number' => '09170000000',
                'message' => 'I would like details.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('career_inquiries', 0);
    }

    public function test_published_batch_announcement_notifies_only_approved_trainees_in_that_batch(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $otherTrainee = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Batch Notification',
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        EnrollmentApplication::create([
            'user_id' => $trainee->id,
            'training_batch_id' => $batch->id,
            'email' => $trainee->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Approved',
            'last_name' => 'Trainee',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => '1 Training Street',
            'barangay' => 'Central',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_ACTIVE,
        ]);

        $this->actingAs($trainer)
            ->post(route('trainer.announcements.store'), [
                'training_batch_id' => $batch->id,
                'kind' => TrainerAnnouncement::KIND_ANNOUNCEMENT,
                'audience' => 'trainees',
                'title' => 'Bring your assessment kit',
                'message' => 'Please bring your assessment kit to the next session.',
                'is_published' => '1',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $trainee->id,
            'type' => LmsAnnouncementPublished::class,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $otherTrainee->id,
            'type' => LmsAnnouncementPublished::class,
        ]);
    }

    private function graduatedUser(array $overrides = []): User
    {
        return $this->makeTrainee(EnrollmentApplication::LEARNING_GRADUATED, $overrides);
    }

    private function activeTrainee(array $overrides = []): User
    {
        return $this->makeTrainee(EnrollmentApplication::LEARNING_ACTIVE, $overrides);
    }

    private function makeTrainee(string $learningStatus, array $overrides = []): User
    {
        $user = User::factory()->create(['role' => 'trainee']);
        $batch = TrainingBatch::create([
            'name' => 'Career Batch '.$user->id,
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);

        EnrollmentApplication::create([
            'user_id' => $user->id,
            'training_batch_id' => $batch->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Graduate',
            'last_name' => 'User',
            'birth_date' => '1995-01-01',
            'gender' => 'Female',
            'contact_number' => $overrides['contact_number'] ?? '09170000000',
            'schedule_preference' => 'AM',
            'street' => '1 Training Street',
            'barangay' => 'Central',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'MCARE School',
            'year_graduated' => 2022,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => $learningStatus,
            ...$overrides,
        ]);

        return $user->fresh();
    }
}
