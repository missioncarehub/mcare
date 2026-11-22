<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_sends_otp_email_to_user(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'trainee@example.test',
            'name' => 'Maria Santos',
            'password' => 'OldPassword123!',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'trainee@example.test',
        ]);

        $response->assertRedirect(route('password.otp'));
        $response->assertSessionHas('password_reset_notice');
        $this->assertSame('trainee@example.test', session('password_reset.email'));

        Mail::assertSent(PasswordResetOtpMail::class, function (PasswordResetOtpMail $mail) use ($user) {
            return $mail->hasTo('trainee@example.test')
                && $mail->recipientName === 'Maria Santos'
                && strlen($mail->code) === 6;
        });
    }

    public function test_forgot_password_completion_sends_password_changed_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'applicant@example.test',
            'name' => 'Juan Dela Cruz',
            'password' => 'OldPassword123!',
        ]);

        $response = $this->withSession([
            'password_reset.email' => 'applicant@example.test',
            'password_reset.verified' => true,
            'password_reset.challenge' => [
                'user_id' => $user->id,
                'hash' => Hash::make('123456'),
                'expires_at' => now()->addMinutes(10)->timestamp,
                'attempts' => 0,
            ],
        ])->post(route('password.update'), [
            'password' => 'NewSecurePassword123!',
            'password_confirmation' => 'NewSecurePassword123!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('password_reset');
        $this->assertTrue(Hash::check('NewSecurePassword123!', $user->fresh()->password));

        Notification::assertSentTo($user, PasswordChangedNotification::class, function (PasswordChangedNotification $notification) {
            return $notification->source === 'Password Reset';
        });
    }

    public function test_account_settings_password_change_sends_password_changed_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'role' => 'trainer',
            'email' => 'trainer@example.test',
            'name' => 'Trainer Sarah',
            'password' => 'CurrentPassword123!',
        ]);

        $response = $this->actingAs($user)
            ->patch(route('account.password.update'), [
                'current_password' => 'CurrentPassword123!',
                'password' => 'BrandNewPassword456!',
                'password_confirmation' => 'BrandNewPassword456!',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('saved');
        $this->assertTrue(Hash::check('BrandNewPassword456!', $user->fresh()->password));

        Notification::assertSentTo($user, PasswordChangedNotification::class, function (PasswordChangedNotification $notification) {
            return $notification->source === 'Account Settings';
        });
    }

    public function test_password_changed_notification_renders_proper_mail_content(): void
    {
        $user = User::factory()->create([
            'email' => 'student@example.test',
            'name' => 'Elena Reyes',
        ]);

        $notification = new PasswordChangedNotification(
            changedAt: 'November 22, 2026 at 6:00 PM PST',
            ipAddress: '192.168.1.50',
            source: 'Account Settings',
        );

        $mail = $notification->toMail($user);
        $rendered = $mail->render();

        $this->assertSame('Your MCARE password was changed', $mail->subject);
        $this->assertStringContainsString('Elena Reyes', $rendered);
        $this->assertStringContainsString('Account Settings', $rendered);
        $this->assertStringContainsString('192.168.1.50', $rendered);
        $this->assertStringContainsString('November 22, 2026 at 6:00 PM PST', $rendered);
        $this->assertStringContainsString(route('password.request'), $rendered);

        $dbData = $notification->toDatabase($user);
        $this->assertSame('Password changed', $dbData['title']);
        $this->assertStringContainsString('Account Settings', $dbData['message']);
        $this->assertSame('lock', $dbData['icon']);
    }
}
