<?php

namespace Tests\Feature;

use App\Models\AdmissionApplication;
use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\PublicSiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnusedApprovedApplicationPurgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_unused_approved_application_expiry_in_days(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('account.settings'))
            ->patch(route('account.unused-approved-expiry.update'), [
                'unused_approved_expiry_mode' => PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DAYS,
                'unused_approved_expiry_amount' => 14,
            ])
            ->assertRedirect(route('account.settings').'#unused-approved-expiry')
            ->assertSessionHas('saved', 'Unused approved-application expiry saved.');

        $settings = PublicSiteSetting::query()->firstOrFail();
        $this->assertSame('days', $settings->unused_approved_expiry_mode);
        $this->assertSame(14, $settings->unused_approved_expiry_amount);
        $this->assertNull($settings->unused_approved_expiry_date);

        $this->actingAs($admin)
            ->get(route('admin.applications.index'))
            ->assertOk()
            ->assertSee('deleted after 14 days from approval')
            ->assertSee('Change in Settings');
    }

    public function test_admin_can_save_a_specific_expiry_date_and_turn_the_rule_off(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $date = now()->addDays(10)->toDateString();

        $this->actingAs($admin)
            ->patch(route('account.unused-approved-expiry.update'), [
                'unused_approved_expiry_mode' => PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DATE,
                'unused_approved_expiry_date' => $date,
            ])
            ->assertRedirect(route('account.settings').'#unused-approved-expiry');

        $settings = PublicSiteSetting::query()->firstOrFail();
        $this->assertSame('date', $settings->unused_approved_expiry_mode);
        $this->assertSame($date, $settings->unused_approved_expiry_date?->toDateString());

        $this->actingAs($admin)
            ->patch(route('account.unused-approved-expiry.update'), [
                'unused_approved_expiry_mode' => PublicSiteSetting::UNUSED_APPROVED_EXPIRY_OFF,
            ])
            ->assertRedirect(route('account.settings').'#unused-approved-expiry');

        $settings->refresh();
        $this->assertSame('off', $settings->unused_approved_expiry_mode);
        $this->assertNull($settings->unused_approved_expiry_amount);
        $this->assertNull($settings->unused_approved_expiry_date);
    }

    public function test_non_admin_cannot_change_unused_approved_application_expiry(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->actingAs($trainer)
            ->patch(route('account.unused-approved-expiry.update'), [
                'unused_approved_expiry_mode' => PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DAYS,
                'unused_approved_expiry_amount' => 7,
            ])
            ->assertForbidden();
    }

    public function test_command_deletes_approved_applications_that_never_enrolled_after_the_set_days(): void
    {
        $this->saveExpiry([
            'unused_approved_expiry_mode' => PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DAYS,
            'unused_approved_expiry_amount' => 7,
        ]);

        $expired = $this->makeApprovedAdmission([
            'email' => 'expired.applicant@gmail.com',
            'reviewed_at' => now()->subDays(8),
        ]);
        $fresh = $this->makeApprovedAdmission([
            'email' => 'fresh.applicant@gmail.com',
            'reviewed_at' => now()->subDays(3),
        ]);
        $pending = AdmissionApplication::query()->create([
            'application_number' => AdmissionApplication::generateNumber(),
            'first_name' => 'Pending',
            'last_name' => 'Applicant',
            'email' => 'pending.applicant@gmail.com',
            'contact_number' => '09170000000',
            'educational_attainment' => 'College Graduate',
            'status' => AdmissionApplication::STATUS_PENDING,
            'privacy_consent_at' => now()->subDays(20),
        ]);
        $enrolledAdmission = $this->makeApprovedAdmission([
            'email' => 'enrolled.applicant@gmail.com',
            'reviewed_at' => now()->subDays(20),
        ]);
        $this->linkEnrollment($enrolledAdmission);

        $this->artisan('mcare:purge-unused-approved-applications')
            ->expectsOutput('Deleted 1 unused approved application.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('admission_applications', ['id' => $expired->id]);
        $this->assertDatabaseHas('admission_applications', ['id' => $fresh->id]);
        $this->assertDatabaseHas('admission_applications', ['id' => $pending->id]);
        $this->assertDatabaseHas('admission_applications', ['id' => $enrolledAdmission->id]);
        $this->assertTrue(AdminActivityLog::query()->where('action', 'admission.unused-expired')->exists());
    }

    public function test_command_deletes_unused_approved_applications_on_the_configured_date(): void
    {
        $this->saveExpiry([
            'unused_approved_expiry_mode' => PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DATE,
            'unused_approved_expiry_date' => now()->toDateString(),
        ]);

        $due = $this->makeApprovedAdmission([
            'email' => 'due.applicant@gmail.com',
            'reviewed_at' => now()->subDay(),
        ]);
        $later = $this->makeApprovedAdmission([
            'email' => 'later.applicant@gmail.com',
            'reviewed_at' => now()->addDay(),
        ]);

        $this->artisan('mcare:purge-unused-approved-applications')
            ->expectsOutput('Deleted 1 unused approved application.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('admission_applications', ['id' => $due->id]);
        $this->assertDatabaseHas('admission_applications', ['id' => $later->id]);
    }

    public function test_command_uses_months_from_approval_and_does_nothing_when_disabled(): void
    {
        $this->saveExpiry([
            'unused_approved_expiry_mode' => PublicSiteSetting::UNUSED_APPROVED_EXPIRY_MONTHS,
            'unused_approved_expiry_amount' => 1,
        ]);

        $expired = $this->makeApprovedAdmission([
            'email' => 'month.expired@gmail.com',
            'reviewed_at' => now()->subMonthsNoOverflow(1)->subDay(),
        ]);
        $kept = $this->makeApprovedAdmission([
            'email' => 'month.kept@gmail.com',
            'reviewed_at' => now()->subDays(10),
        ]);

        $this->artisan('mcare:purge-unused-approved-applications')
            ->expectsOutput('Deleted 1 unused approved application.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('admission_applications', ['id' => $expired->id]);
        $this->assertDatabaseHas('admission_applications', ['id' => $kept->id]);

        $this->saveExpiry([
            'unused_approved_expiry_mode' => PublicSiteSetting::UNUSED_APPROVED_EXPIRY_OFF,
        ]);

        $this->artisan('mcare:purge-unused-approved-applications')
            ->expectsOutput('Deleted 0 unused approved applications.')
            ->assertSuccessful();

        $this->assertDatabaseHas('admission_applications', ['id' => $kept->id]);
    }

    public function test_dry_run_does_not_delete_matching_applications(): void
    {
        $this->saveExpiry([
            'unused_approved_expiry_mode' => PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DAYS,
            'unused_approved_expiry_amount' => 1,
        ]);
        $admission = $this->makeApprovedAdmission([
            'email' => 'dry.run@gmail.com',
            'reviewed_at' => now()->subDays(3),
        ]);

        $this->artisan('mcare:purge-unused-approved-applications', ['--dry-run' => true])
            ->expectsOutput('Matched 1 unused approved application.')
            ->assertSuccessful();

        $this->assertDatabaseHas('admission_applications', ['id' => $admission->id]);
    }

    public function test_unused_approved_application_purge_is_scheduled_daily(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('mcare:purge-unused-approved-applications')
            ->assertSuccessful();
    }

    /** @param  array<string, mixed>  $values */
    private function saveExpiry(array $values): void
    {
        PublicSiteSetting::instance()->update([
            'unused_approved_expiry_mode' => $values['unused_approved_expiry_mode'],
            'unused_approved_expiry_amount' => $values['unused_approved_expiry_amount'] ?? null,
            'unused_approved_expiry_date' => $values['unused_approved_expiry_date'] ?? null,
        ]);
    }

    private function linkEnrollment(AdmissionApplication $admission): void
    {
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
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
        ]);
    }
}
