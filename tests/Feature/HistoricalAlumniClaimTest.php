<?php

namespace Tests\Feature;

use App\Models\AlumniProfile;
use App\Models\EnrollmentApplication;
use App\Models\HistoricalAlumniClaim;
use App\Models\TrainingModule;
use App\Models\User;
use App\Notifications\HistoricalAlumniClaimStatusUpdated;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class HistoricalAlumniClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_claim_form_shows_password_strength_checks_and_educational_attainment_options(): void
    {
        $this->get(route('alumni.claim.create'))
            ->assertOk()
            ->assertSee('data-password-toggle="password"', false)
            ->assertSee('data-password-toggle="password_confirmation"', false)
            ->assertSee('At least 10 characters')
            ->assertSee('Contains a number')
            ->assertSee('Contains upper and lowercase letters')
            ->assertSee('Contains a symbol')
            ->assertSee('Passwords match')
            ->assertSee('name="educational_attainment"', false)
            ->assertSee('High School Graduate')
            ->assertSee('College Graduate')
            ->assertSee('Doctorate')
            ->assertSee('id="privacy_consent"', false)
            ->assertSee('data-claim-submit', false)
            ->assertSee('aria-disabled="true"', false);
    }

    public function test_claim_rejects_a_weak_password_and_an_invalid_educational_attainment(): void
    {
        $this->from(route('alumni.claim.create'))
            ->post(route('alumni.claim.store'), [
                ...$this->claimPayload(),
                'password' => 'short',
                'password_confirmation' => 'short',
                'educational_attainment' => 'Not a TESDA option',
            ])
            ->assertRedirect(route('alumni.claim.create'))
            ->assertSessionHasErrors(['password', 'educational_attainment'], errorBag: 'alumniClaim');

        $this->assertDatabaseMissing('users', ['email' => 'legacy.graduate@example.test']);
    }

    public function test_claim_success_page_requires_a_completed_submission(): void
    {
        $this->get(route('alumni.claim.received'))
            ->assertRedirect(route('alumni.claim.create'));
    }

    public function test_historical_graduate_can_submit_and_verify_email_but_cannot_sign_in_before_onsite_review(): void
    {
        Notification::fake();
        Storage::fake('local');

        $this->post(route('alumni.claim.store'), [
            ...$this->claimPayload(),
            'evidence_document' => UploadedFile::fake()->create('cotc-page-1.pdf', 100, 'application/pdf'),
            'evidence_document_page_2' => UploadedFile::fake()->create('cotc-page-2.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('alumni.claim.received'))
            ->assertSessionHas('claim_submitted')
            ->assertSessionHas('claim_email', 'legacy.graduate@example.test');

        $this->get(route('alumni.claim.received'))
            ->assertOk()
            ->assertSee('Claim received')
            ->assertSee('legacy.graduate@example.test')
            ->assertSee('A verification link was sent');

        $user = User::query()->where('email', 'legacy.graduate@example.test')->firstOrFail();
        $claim = $user->historicalAlumniClaim()->firstOrFail();

        $this->assertSame('applicant', $user->role);
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertSame(HistoricalAlumniClaim::STATUS_PENDING_EMAIL, $claim->status);
        $this->assertSame('Bicol Region', $claim->region);
        $this->assertSame('Camarines Sur', $claim->province);
        Storage::disk('local')->assertExists($claim->evidence_document_path);
        Storage::disk('local')->assertExists($claim->evidence_document_page_2_path);
        Notification::assertSentTo($user, QueuedVerifyEmail::class);

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addHour(),
            ['id' => $user->id, 'hash' => sha1($user->email)],
        );

        $this->get($verificationUrl)
            ->assertRedirect(route('login'))
            ->assertSessionHas('verified');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertSame(
            HistoricalAlumniClaim::STATUS_PENDING_ONSITE,
            $claim->fresh()->status,
        );

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_approval_creates_a_graduated_record_and_historical_alumni_never_inherits_modules(): void
    {
        Notification::fake();
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'name' => 'Legacy Graduate',
            'email' => 'verified.legacy@example.test',
            'role' => 'applicant',
            'applicant_status' => 'historical_claim_pending_onsite',
        ]);
        $claim = $this->createClaim($user);

        $this->actingAs($admin)
            ->patch(route('admin.accounts.historical-alumni.update', $claim), [
                'decision' => 'approve',
                'identity_verified' => '1',
                'training_evidence_verified' => '1',
                'archive_record_verified' => '1',
                'admin_notes' => 'Original COTC 2018-114 and paper graduate registry entry were verified onsite.',
            ])
            ->assertRedirect(route('admin.historical-alumni.show', $claim))
            ->assertSessionHas('saved');

        $application = EnrollmentApplication::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertDatabaseHas('historical_alumni_claims', [
            'id' => $claim->id,
            'status' => HistoricalAlumniClaim::STATUS_APPROVED,
            'onsite_verified_by_id' => $admin->id,
        ]);
        $this->assertDatabaseHas('enrollment_applications', [
            'id' => $application->id,
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
            'intake_channel' => 'historical_alumni',
            'is_historical_record' => true,
            'training_batch_id' => null,
        ]);
        $this->assertDatabaseHas('alumni_profiles', [
            'user_id' => $user->id,
            'rank' => AlumniProfile::RANK_JUNIOR,
        ]);
        $this->assertSame('trainee', $user->fresh()->role);
        Notification::assertSentTo($user, HistoricalAlumniClaimStatusUpdated::class);

        $trainer = User::factory()->create(['role' => 'trainer']);
        $module = TrainingModule::create([
            'trainer_id' => $trainer->id,
            'training_batch_id' => null,
            'title' => 'Current Global Module That Legacy Alumni Must Not See',
            'description' => 'Current classroom content.',
            'file_path' => 'modules/current-global.pdf',
            'original_file_name' => 'current-global.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
            'is_published' => true,
            'published_at' => now(),
        ]);

        $this->actingAs($user->fresh())
            ->get(route('trainee.modules.index'))
            ->assertForbidden();

        $this->actingAs($user->fresh())
            ->get(route('trainee.modules.show', $module))
            ->assertForbidden();

        $this->actingAs($user->fresh())
            ->get(route('trainee.grades'))
            ->assertOk()
            ->assertSee('No trainer-validated grades yet')
            ->assertDontSee($module->title);
    }

    public function test_admin_cannot_approve_a_claim_until_email_is_verified(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->unverified()->create([
            'role' => 'applicant',
            'applicant_status' => 'historical_claim_pending_email',
        ]);
        $claim = $this->createClaim($user, HistoricalAlumniClaim::STATUS_PENDING_EMAIL);

        $this->actingAs($admin)
            ->patch(route('admin.accounts.historical-alumni.update', $claim), [
                'decision' => 'approve',
                'identity_verified' => '1',
                'training_evidence_verified' => '1',
                'archive_record_verified' => '1',
                'admin_notes' => 'Documents appear valid but email remains pending.',
            ])
            ->assertSessionHasErrors('historical_alumni');

        $this->assertDatabaseMissing('enrollment_applications', ['user_id' => $user->id]);
        $this->assertSame(HistoricalAlumniClaim::STATUS_PENDING_EMAIL, $claim->fresh()->status);
    }

    public function test_admin_can_open_the_alumni_claims_pages(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create([
            'name' => 'Legacy Graduate',
            'email' => 'legacy.review@example.test',
            'role' => 'applicant',
            'applicant_status' => 'historical_claim_pending_onsite',
        ]);
        $claim = $this->createClaim($user);

        $this->actingAs($admin)
            ->get(route('admin.historical-alumni.index'))
            ->assertOk()
            ->assertSee('Alumni claims')
            ->assertSee('legacy.review@example.test')
            ->assertSee('Batch 2018-A')
            ->assertSee(route('admin.historical-alumni.show', $claim), false);

        $this->actingAs($admin)
            ->get(route('admin.historical-alumni.index', ['status' => HistoricalAlumniClaim::STATUS_PENDING_ONSITE]))
            ->assertOk()
            ->assertSee('legacy.review@example.test');

        $this->actingAs($admin)
            ->get(route('admin.historical-alumni.show', $claim))
            ->assertOk()
            ->assertSee('Legacy Graduate')
            ->assertSee('Iriga City')
            ->assertSee('COTC-2018-114')
            ->assertSee('Verify and activate alumni');
    }

    public function test_admin_can_open_claim_evidence(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);
        $claimant = User::factory()->create(['role' => 'applicant']);
        $path = 'historical-alumni/'.$claimant->id.'/cotc.pdf';
        Storage::disk('local')->put($path, '%PDF-1.4 evidence');
        $claim = $this->createClaim($claimant);
        $claim->forceFill(['evidence_document_path' => $path])->save();

        $this->actingAs($admin)
            ->get(route('admin.historical-alumni.evidence', $claim))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_admin_can_promote_a_verified_alumni_and_a_graduate_to_senior(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $claimant = User::factory()->create([
            'name' => 'Legacy Graduate',
            'email' => 'senior.claim@example.test',
            'email_verified_at' => now(),
            'role' => 'applicant',
        ]);
        $claim = $this->createClaim($claimant, HistoricalAlumniClaim::STATUS_APPROVED);
        AlumniProfile::query()->create([
            'user_id' => $claimant->id,
            'rank' => AlumniProfile::RANK_JUNIOR,
            'is_available_for_duty' => false,
        ]);

        $graduateUser = User::factory()->create(['role' => 'trainee', 'email' => 'class.graduate@example.test']);
        $graduate = EnrollmentApplication::create([
            'user_id' => $graduateUser->id,
            'email' => $graduateUser->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Class',
            'last_name' => 'Graduate',
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
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'learning_status' => EnrollmentApplication::LEARNING_GRADUATED,
            'is_historical_record' => false,
        ]);
        AlumniProfile::query()->create([
            'user_id' => $graduateUser->id,
            'rank' => AlumniProfile::RANK_JUNIOR,
            'is_available_for_duty' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.historical-alumni.index'))
            ->assertOk()
            ->assertDontSee('Promote to senior')
            ->assertDontSee('Class Graduate');

        $this->actingAs($admin)
            ->get(route('admin.alumni-standing.index'))
            ->assertOk()
            ->assertSee('Junior and senior alumni')
            ->assertSee('Alumni claim')
            ->assertSee('Training graduate')
            ->assertSee('Legacy Graduate')
            ->assertSee('Class Graduate')
            ->assertSee('Promote to senior');

        $this->actingAs($admin)
            ->patch(route('admin.historical-alumni.promote', $claim))
            ->assertRedirect()
            ->assertSessionHas('saved', 'Legacy Graduate is now a senior alumni.');

        $this->assertDatabaseHas('alumni_profiles', [
            'user_id' => $claimant->id,
            'rank' => AlumniProfile::RANK_SENIOR,
            'rank_promoted_by_id' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.alumni-standing.promote', $graduate))
            ->assertRedirect()
            ->assertSessionHas('saved', 'Class Graduate is now a senior alumni.');

        $this->assertDatabaseHas('alumni_profiles', [
            'user_id' => $graduateUser->id,
            'rank' => AlumniProfile::RANK_SENIOR,
        ]);

        $pending = $this->createClaim(User::factory()->create(['role' => 'applicant']));
        $this->actingAs($admin)
            ->patch(route('admin.historical-alumni.promote', $pending))
            ->assertStatus(422);
    }

    public function test_non_admin_cannot_open_alumni_claims(): void
    {
        $trainee = User::factory()->create(['role' => 'trainee']);

        $this->actingAs($trainee)
            ->get(route('admin.historical-alumni.index'))
            ->assertForbidden();
    }

    private function claimPayload(): array
    {
        return [
            'first_name' => 'Legacy',
            'middle_name' => 'M',
            'last_name' => 'Graduate',
            'email' => 'legacy.graduate@example.test',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'birth_date' => '1990-05-10',
            'gender' => 'Female',
            'contact_number' => '09171234567',
            'street' => '14 Archive Street',
            'barangay' => 'Central',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'region' => 'Bicol Region',
            'zip_code' => '4431',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'Iriga National High School',
            'education_year_graduated' => 2007,
            'training_completion_year' => 2018,
            'historical_batch_name' => 'Batch 2018-A',
            'training_schedule' => 'AM',
            'evidence_type' => 'both',
            'certificate_number' => 'COTC-2018-114',
            'tor_reference' => 'TOR-2018-114',
            'privacy_consent' => '1',
        ];
    }

    private function createClaim(User $user, string $status = HistoricalAlumniClaim::STATUS_PENDING_ONSITE): HistoricalAlumniClaim
    {
        return HistoricalAlumniClaim::create([
            'user_id' => $user->id,
            'first_name' => 'Legacy',
            'middle_name' => 'M',
            'last_name' => 'Graduate',
            'birth_date' => '1990-05-10',
            'gender' => 'Female',
            'contact_number' => '09171234567',
            'street' => '14 Archive Street',
            'barangay' => 'Central',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'region' => 'Bicol Region',
            'zip_code' => '4431',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'Iriga National High School',
            'education_year_graduated' => 2007,
            'training_completion_year' => 2018,
            'historical_batch_name' => 'Batch 2018-A',
            'training_schedule' => 'AM',
            'evidence_type' => 'both',
            'certificate_number' => 'COTC-2018-114',
            'tor_reference' => 'TOR-2018-114',
            'status' => $status,
            'privacy_consent_at' => now(),
        ]);
    }
}
