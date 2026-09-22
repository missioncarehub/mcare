<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\AdminContactMessage;
use App\Models\User;
use App\Notifications\AdminOperationsNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminContactMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainee_can_send_a_contact_admin_message(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);

        $this->actingAs($trainee)
            ->from(route('account.help'))
            ->post(route('account.contact.store'), [
                'topic' => AdminContactMessage::TOPIC_PAYMENTS,
                'subject' => 'Receipt not showing',
                'message' => 'I uploaded a receipt but Payments still says unpaid.',
            ])
            ->assertRedirect(route('account.help'))
            ->assertSessionHas('saved', 'Your message was sent to MCARE administration.');

        $this->assertDatabaseHas('admin_contact_messages', [
            'user_id' => $trainee->id,
            'topic' => AdminContactMessage::TOPIC_PAYMENTS,
            'subject' => 'Receipt not showing',
            'status' => AdminContactMessage::STATUS_PENDING,
        ]);
        $this->assertTrue(AdminActivityLog::query()->where('action', 'account.contact.created')->exists());
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'type' => AdminOperationsNotification::class,
        ]);

        $this->actingAs($trainee)
            ->withSession(['saved' => 'Your message was sent to MCARE administration.'])
            ->get(route('account.help'))
            ->assertOk()
            ->assertSee('data-dashboard-toast', false)
            ->assertSee('dashboard-toast-success', false)
            ->assertSee('Your message was sent to MCARE administration.')
            ->assertDontSee('mb-6 rounded-lg border border-emerald-200 bg-emerald-50', false)
            ->assertSee('Receipt not showing')
            ->assertSee('I uploaded a receipt but Payments still says unpaid.')
            ->assertSee('Pending');
    }

    public function test_admin_cannot_submit_a_contact_message(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->post(route('account.contact.store'), [
                'topic' => AdminContactMessage::TOPIC_OTHER,
                'subject' => 'Self message',
                'message' => 'Admins should use the inbox instead.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('admin_contact_messages', 0);
    }

    public function test_admin_can_review_and_reply_to_a_contact_message(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $trainee = User::factory()->create(['role' => 'trainee']);
        $message = AdminContactMessage::query()->create([
            'user_id' => $trainee->id,
            'topic' => AdminContactMessage::TOPIC_DOCUMENTS,
            'subject' => 'PSA was rejected',
            'message' => 'Please tell me which page is unclear.',
            'status' => AdminContactMessage::STATUS_PENDING,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.contact-messages.index'))
            ->assertOk()
            ->assertSee('Contact admin')
            ->assertSee('PSA was rejected')
            ->assertSee($trainee->email);

        $this->actingAs($admin)
            ->from(route('admin.contact-messages.index'))
            ->patch(route('admin.contact-messages.update', $message), [
                'status' => AdminContactMessage::STATUS_REVIEWED,
                'admin_reply' => 'Please upload a clearer copy of page 2.',
            ])
            ->assertRedirect(route('admin.contact-messages.index'))
            ->assertSessionHas('saved', 'Contact message updated.');

        $this->assertDatabaseHas('admin_contact_messages', [
            'id' => $message->id,
            'status' => AdminContactMessage::STATUS_REVIEWED,
            'admin_reply' => 'Please upload a clearer copy of page 2.',
            'reviewed_by_id' => $admin->id,
        ]);
        $this->assertTrue(AdminActivityLog::query()->where('action', 'admin.contact.updated')->exists());
        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $trainee->id,
            'type' => AdminOperationsNotification::class,
        ]);

        $this->actingAs($trainee)
            ->get(route('account.help'))
            ->assertOk()
            ->assertSee('Admin reply')
            ->assertSee('Please upload a clearer copy of page 2.')
            ->assertSee('Reviewed');
    }

    public function test_trainer_cannot_open_the_admin_contact_inbox(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->actingAs($trainer)
            ->get(route('admin.contact-messages.index'))
            ->assertForbidden();
    }

    public function test_guest_cannot_send_a_contact_admin_message(): void
    {
        $this->post(route('account.contact.store'), [
            'topic' => AdminContactMessage::TOPIC_ACCOUNT,
            'subject' => 'Cannot sign in',
            'message' => 'Please reset this account.',
        ])->assertRedirect(route('login'));
    }
}
