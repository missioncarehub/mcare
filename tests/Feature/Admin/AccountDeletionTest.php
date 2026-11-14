<?php

namespace Tests\Feature\Admin;

use App\Models\EnrollmentApplication;
use App\Models\DeletedAccountEmail;
use App\Models\PaymentAttempt;
use App\Models\PaymentTransaction;
use App\Models\TrainingBatch;
use App\Models\TrainingProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesLmsTestData;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use CreatesLmsTestData;
    use RefreshDatabase;

    private function adminUser(): User
    {
        return $this->lmsUser('admin');
    }

    public function test_admin_can_view_applicants_in_accounts_tab(): void
    {
        $admin = $this->adminUser();
        $batch = $this->lmsBatch();

        $applicantUser = User::factory()->create([
            'role' => 'applicant',
            'name' => 'Applicant TestUser',
            'email' => 'applicant.tester@gmail.com',
        ]);

        EnrollmentApplication::create([
            'user_id' => $applicantUser->id,
            'email' => $applicantUser->email,
            'first_name' => 'Applicant',
            'last_name' => 'TestUser',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000001',
            'schedule_preference' => 'AM',
            'street' => 'Street 1',
            'barangay' => 'Barangay 1',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'University',
            'year_graduated' => 2022,
            'program' => 'Caregiving NC II',
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'payment_status' => EnrollmentApplication::PAYMENT_PARTIALLY_PAID,
            'downpayment_amount' => 2000,
            'total_paid_amount' => 2000,
            'payment_verified_at' => now(),
            'review_released_at' => now(),
            'training_batch_id' => $batch->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.accounts.index', ['role' => 'applicant']));

        $response->assertOk();
        $response->assertSee('applicant.tester@gmail.com');
        $response->assertSee('Applicant TestUser');
        $response->assertSee('Pre-enlistment');
    }

    public function test_admin_can_delete_applicant_and_all_records_and_files_are_purged(): void
    {
        Storage::fake('local');
        Storage::fake('public');
        $admin = $this->adminUser();
        $batch = $this->lmsBatch();

        $photoPath = 'enrollments/test-photo.jpg';
        $receiptPath = 'payments/receipt-proof.png';
        Storage::disk('local')->put($photoPath, 'fake-photo-content');
        Storage::disk('local')->put($receiptPath, 'fake-receipt-content');

        $applicantUser = User::factory()->create([
            'role' => 'applicant',
            'name' => 'Juan Dela Cruz',
            'email' => 'juan.delacruz@gmail.com',
        ]);
        $avatarPath = 'avatars/'.$applicantUser->id.'/face.jpg';
        Storage::disk('public')->put($avatarPath, 'avatar-bytes');
        $applicantUser->forceFill([
            'profile_photo_path' => $avatarPath,
        ])->save();

        $application = EnrollmentApplication::create([
            'user_id' => $applicantUser->id,
            'email' => $applicantUser->email,
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'birth_date' => '1998-05-15',
            'gender' => 'Male',
            'contact_number' => '09171234567',
            'schedule_preference' => 'AM',
            'street' => '123 Main St',
            'barangay' => 'San Roque',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'High School',
            'year_graduated' => 2018,
            'program' => 'Caregiving NC II',
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'payment_status' => EnrollmentApplication::PAYMENT_ONSITE_PENDING,
            'id_photo_path' => $photoPath,
            'training_batch_id' => $batch->id,
        ]);

        PaymentAttempt::create([
            'enrollment_application_id' => $application->id,
            'idempotency_key' => 'TEST-IDEMP-001',
            'provider' => 'onsite',
            'merchant_reference' => 'TEST-REF-001',
            'status' => PaymentAttempt::STATUS_PENDING,
            'amount' => 2000.00,
            'amount_minor' => 200000,
            'currency' => 'PHP',
            'livemode' => false,
        ]);

        PaymentTransaction::create([
            'enrollment_application_id' => $application->id,
            'user_id' => $applicantUser->id,
            'transaction_type' => 'downpayment',
            'payment_channel' => 'onsite',
            'amount' => 2000.00,
            'receipt_proof_path' => $receiptPath,
            'status' => 'pending',
        ]);

        $this->assertTrue(Storage::disk('local')->exists($photoPath));
        $this->assertTrue(Storage::disk('local')->exists($receiptPath));

        $response = $this->actingAs($admin)
            ->from(route('admin.accounts.photo', $applicantUser))
            ->delete(route('admin.accounts.destroy', $applicantUser));

        $response->assertRedirect(route('admin.accounts.index'));
        $response->assertSessionHas('saved');

        // Database records must be removed
        $this->assertDatabaseMissing('users', ['id' => $applicantUser->id]);
        $this->assertDatabaseMissing('enrollment_applications', ['id' => $application->id]);
        $this->assertDatabaseMissing('payment_attempts', ['enrollment_application_id' => $application->id]);
        $this->assertDatabaseMissing('payment_transactions', ['enrollment_application_id' => $application->id]);

        // Physical files must be deleted from storage
        $this->assertFalse(Storage::disk('local')->exists($photoPath));
        $this->assertFalse(Storage::disk('local')->exists($receiptPath));
        $this->assertFalse(Storage::disk('public')->exists($avatarPath));
    }

    public function test_same_email_cannot_re_enroll_after_account_deletion(): void
    {
        Notification::fake();
        Storage::fake('local');
        Storage::fake('public');
        $admin = $this->adminUser();
        $program = TrainingProgram::create([
            'name' => 'Caregiving NC II',
            'code' => 'CG-NC-II',
            'total_program_fee' => 22000,
            'downpayment_amount' => 2000,
            'is_active' => true,
        ]);
        $batch = TrainingBatch::create([
            'training_program_id' => $program->id,
            'name' => 'Batch 2026',
            'year' => 2026,
            'is_active' => true,
            'show_on_enrollment_page' => true,
            'enrollment_starts_at' => now()->subDay(),
            'enrollment_ends_at' => now()->addWeek(),
        ]);

        $testEmail = 'reenroll.applicant@gmail.com';

        // 1. Initial creation
        $applicantUser = User::factory()->create([
            'role' => 'applicant',
            'name' => 'Maria Reenroll',
            'email' => $testEmail,
        ]);

        $application = EnrollmentApplication::create([
            'user_id' => $applicantUser->id,
            'email' => $testEmail,
            'first_name' => 'Maria',
            'last_name' => 'Reenroll',
            'birth_date' => '2001-03-20',
            'gender' => 'Female',
            'contact_number' => '09179876543',
            'schedule_preference' => 'PM',
            'street' => '456 Oak Avenue',
            'barangay' => 'San Juan',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'College',
            'school_name' => 'College Univ',
            'year_graduated' => 2023,
            'program' => 'Caregiving NC II',
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'payment_status' => EnrollmentApplication::PAYMENT_NOT_SELECTED,
            'training_batch_id' => $batch->id,
        ]);

        // 2. Admin deletes the account
        $this->actingAs($admin)
            ->delete(route('admin.accounts.destroy', $applicantUser))
            ->assertRedirect(route('admin.accounts.index'));

        $this->assertDatabaseMissing('users', ['email' => $testEmail]);
        $this->assertDatabaseMissing('enrollment_applications', ['email' => $testEmail]);
        $this->assertDatabaseHas('deleted_account_email_blocks', [
            'email_hash' => DeletedAccountEmail::hashFor($testEmail),
        ]);

        // 3. Applicant submits fresh enrollment with the exact same email
        auth()->logout();
        $signature = 'data:image/png;base64,'.base64_encode('fake-signature-bytes');

        $enrollmentResponse = $this->post(route('enrollment.store'), [
            'application_number' => $this->makeApprovedAdmission(['email' => $testEmail])->application_number,
            'training_batch_id' => $batch->id,
            'email' => $testEmail,
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'first_name' => 'Maria',
            'last_name' => 'Reenroll',
            'birth_date' => '2001-03-20',
            'gender' => 'Female',
            'civil_status' => 'Single',
            'employment_status' => 'Unemployed',
            'contact_number' => '09179876543',
            'nationality' => 'Filipino',
            'schedule_preference' => 'PM',
            'street' => '456 Oak Avenue',
            'barangay' => 'San Juan',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'region' => 'Region V',
            'zip_code' => '4431',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'College Univ',
            'year_graduated' => 2023,
            'guardian_name' => 'Ana Reenroll',
            'guardian_address' => '456 Oak Avenue',
            'privacy_consent' => '1',
            'signature_name' => 'Maria Reenroll',
            'signature_type' => 'draw',
            'signature_data' => $signature,
            'birth_certificate' => UploadedFile::fake()->create('birth_cert.pdf', 100, 'application/pdf'),
            'education_document' => UploadedFile::fake()->create('diploma.pdf', 100, 'application/pdf'),
            'good_moral_certificate' => UploadedFile::fake()->create('good_moral.pdf', 100, 'application/pdf'),
            'id_photo' => UploadedFile::fake()->create('id_photo.jpg', 100, 'image/jpeg'),
        ]);

        $enrollmentResponse->assertRedirect(route('enrollment.create'))
            ->assertSessionHasErrors(['email']);
        $this->assertDatabaseMissing('users', ['email' => $testEmail]);
        $this->assertDatabaseMissing('enrollment_applications', ['email' => $testEmail]);
    }

    public function test_verified_historical_alumni_accounts_can_be_deleted(): void
    {
        $admin = $this->adminUser();
        $alumni = User::factory()->create([
            'role' => 'alumni',
            'name' => 'Historical Alumni',
            'email' => 'historical.alumni@gmail.com',
        ]);
        EnrollmentApplication::create([
            'user_id' => $alumni->id,
            'email' => $alumni->email,
            'first_name' => 'Historical',
            'last_name' => 'Alumni',
            'birth_date' => '1990-01-01',
            'gender' => 'Male',
            'contact_number' => '09170000002',
            'schedule_preference' => 'AM',
            'street' => 'Street 2',
            'barangay' => 'Barangay 2',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'College Graduate',
            'school_name' => 'University',
            'year_graduated' => 2015,
            'program' => 'Caregiving NC II',
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'is_historical_record' => true,
        ]);

        $this->actingAs($admin)
            ->from(route('admin.accounts.index'))
            ->delete(route('admin.accounts.destroy', $alumni))
            ->assertRedirect(route('admin.accounts.index'))
            ->assertSessionHas('saved');

        $this->assertDatabaseMissing('users', ['id' => $alumni->id]);
    }

    public function test_admin_cannot_delete_admin_account(): void
    {
        $admin = $this->adminUser();
        $otherAdmin = $this->lmsUser('admin');

        // Cannot delete self
        $this->actingAs($admin)
            ->delete(route('admin.accounts.destroy', $admin))
            ->assertNotFound();

        // Cannot delete another admin
        $this->actingAs($admin)
            ->delete(route('admin.accounts.destroy', $otherAdmin))
            ->assertNotFound();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id]);
    }

    public function test_accounts_search_and_role_filters_work(): void
    {
        $admin = $this->adminUser();

        User::factory()->create(['role' => 'trainer', 'name' => 'Special Trainer', 'email' => 'special.trainer@gmail.com']);
        User::factory()->create(['role' => 'trainee', 'name' => 'Special Trainee', 'email' => 'special.trainee@gmail.com']);
        $applicant = User::factory()->create(['role' => 'applicant', 'name' => 'Special Applicant', 'email' => 'special.applicant@gmail.com']);
        EnrollmentApplication::create([
            'user_id' => $applicant->id,
            'email' => $applicant->email,
            'first_name' => 'Special',
            'last_name' => 'Applicant',
            'birth_date' => '2000-01-01',
            'gender' => 'Female',
            'contact_number' => '09170000000',
            'schedule_preference' => 'AM',
            'street' => 'Test Street',
            'barangay' => 'Test Barangay',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'Test School',
            'year_graduated' => 2020,
            'program' => 'Caregiving NC II',
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
            'review_released_at' => now(),
        ]);

        // Search
        $response = $this->actingAs($admin)->get(route('admin.accounts.index', ['search' => 'Special Trainer']));
        $response->assertOk();
        $response->assertSee('special.trainer@gmail.com');
        $response->assertDontSee('special.trainee@gmail.com');

        // Role Filter: trainer
        $trainerResponse = $this->actingAs($admin)->get(route('admin.accounts.index', ['role' => 'trainer']));
        $trainerResponse->assertOk();
        $trainerResponse->assertSee('special.trainer@gmail.com');
        $trainerResponse->assertDontSee('special.trainee@gmail.com');
        $trainerResponse->assertDontSee('special.applicant@gmail.com');

        // Role Filter: applicant
        $applicantResponse = $this->actingAs($admin)->get(route('admin.accounts.index', ['role' => 'applicant']));
        $applicantResponse->assertOk();
        $applicantResponse->assertSee('special.applicant@gmail.com');
        $applicantResponse->assertDontSee('special.trainer@gmail.com');
    }
}
