<?php

namespace Tests\Feature;

use App\Models\AdminAnnouncement;
use App\Models\EnrollmentApplication;
use App\Models\TrainingBatch;
use App\Models\User;
use App\Notifications\AdminAnnouncementNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AdminAnnouncementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_announcement_to_all_trainees_with_email(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $trainee1 = User::factory()->create(['role' => 'trainee']);
        $trainee2 = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();

        $this->createApprovedApplication($trainee1, $batch);
        $this->createApprovedApplication($trainee2, $batch);

        $this->actingAs($admin)
            ->post(route('admin.announcements.store'), [
                'title' => 'Orientation Schedule Update',
                'message' => 'Please be at Skills Lab A at 8:00 AM on Monday.',
                'kind' => 'announcement',
                'target_type' => 'all',
                'send_email' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('admin_announcements', [
            'title' => 'Orientation Schedule Update',
            'target_type' => 'all',
            'kind' => 'announcement',
            'send_email' => true,
        ]);

        $announcement = AdminAnnouncement::firstOrFail();

        Notification::assertSentTo(
            [$trainee1, $trainee2],
            AdminAnnouncementNotification::class,
            function ($notification) use ($announcement) {
                return $notification->announcement->id === $announcement->id;
            }
        );
    }

    public function test_admin_can_send_monthly_payment_reminder_to_specific_batch(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $trainee1 = User::factory()->create(['role' => 'trainee']);
        $trainee2 = User::factory()->create(['role' => 'trainee']);
        $batch1 = $this->batch('Batch 1');
        $batch2 = $this->batch('Batch 2');

        $this->createApprovedApplication($trainee1, $batch1);
        $this->createApprovedApplication($trainee2, $batch2);

        $this->actingAs($admin)
            ->post(route('admin.announcements.store'), [
                'title' => 'Monthly Tuition Installment Due: Sept 30',
                'message' => 'Second installment of ₱5,000 is due at the cashier.',
                'kind' => 'reminder',
                'target_type' => 'batch',
                'training_batch_id' => $batch1->id,
                'due_date' => '2026-09-30',
                'send_email' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('admin_announcements', [
            'training_batch_id' => $batch1->id,
            'kind' => 'reminder',
        ]);

        $announcement = AdminAnnouncement::query()->where('training_batch_id', $batch1->id)->firstOrFail();
        $this->assertEquals('2026-09-30', $announcement->due_date?->toDateString());

        Notification::assertSentTo($trainee1, AdminAnnouncementNotification::class);
        Notification::assertNotSentTo($trainee2, AdminAnnouncementNotification::class);
    }

    public function test_admin_notice_appears_in_trainee_stream(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $this->createApprovedApplication($trainee, $batch);

        AdminAnnouncement::create([
            'author_id' => $admin->id,
            'title' => 'System Wide Training Notice',
            'message' => 'Welcome to the second semester modules.',
            'kind' => 'announcement',
            'target_type' => 'all',
            'is_published' => true,
            'posted_at' => now(),
        ]);

        $this->actingAs($trainee)
            ->get(route('trainee.stream'))
            ->assertOk()
            ->assertSee('System Wide Training Notice')
            ->assertSee('Welcome to the second semester modules.');
    }

    public function test_publishing_writes_the_inbox_notification_immediately(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $this->createApprovedApplication($trainee, $batch);

        $this->actingAs($admin)
            ->post(route('admin.announcements.store'), [
                'title' => 'Lab opening hours',
                'message' => 'Skills lab opens at 7:30 AM.',
                'kind' => 'announcement',
                'target_type' => 'all',
                'send_email' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $trainee->id,
            'notifiable_type' => $trainee->getMorphClass(),
            'type' => AdminAnnouncementNotification::class,
        ]);
        $this->assertSame(1, $trainee->unreadNotifications()->count());
        $this->actingAs($trainee)
            ->get(route('trainee.dashboard'))
            ->assertOk()
            ->assertSee('dashboard-nav-badge', false);
    }

    public function test_trainee_visit_repairs_a_stuck_announcement_delivery(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $batch = $this->batch();
        $this->createApprovedApplication($trainee, $batch);

        $announcement = AdminAnnouncement::create([
            'author_id' => $admin->id,
            'title' => 'Missed inbox notice',
            'message' => 'This should appear after the next visit.',
            'kind' => AdminAnnouncement::KIND_ANNOUNCEMENT,
            'target_type' => AdminAnnouncement::TARGET_ALL,
            'is_published' => true,
            'posted_at' => now(),
        ]);

        DB::table('announcement_deliveries')->insert([
            'user_id' => $trainee->id,
            'announcement_type' => 'admin',
            'announcement_id' => $announcement->id,
            'delivery_reason' => 'publication',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(0, $trainee->notifications()->count());

        $this->actingAs($trainee)
            ->get(route('trainee.stream'))
            ->assertOk();

        $notification = $trainee->fresh()->unreadNotifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame($announcement->id, $notification->data['announcement_id']);
    }

    private function batch(string $name = 'Batch 1'): TrainingBatch
    {
        return TrainingBatch::create([
            'name' => $name,
            'year' => 2026,
            'is_active' => true,
            'enrollment_ends_at' => now()->addMonth(),
        ]);
    }

    private function createApprovedApplication(User $user, TrainingBatch $batch): EnrollmentApplication
    {
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
            'status' => EnrollmentApplication::STATUS_APPROVED,
            'total_program_fee' => 22000.00,
            'downpayment_amount' => 2000.00,
            'total_paid_amount' => 0.00,
            'payment_status' => EnrollmentApplication::PAYMENT_ONSITE_PENDING,
            'payment_method' => 'onsite',
        ]);
    }
}
