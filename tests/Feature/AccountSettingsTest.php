<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\PublicSiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_open_account_settings(): void
    {
        $this->get(route('account.settings'))->assertRedirect(route('login'));
    }

    public function test_shared_admin_login_lands_on_the_dashboard(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'password' => 'Password123!',
        ]);

        $this->withSession(['url.intended' => route('admin.enrollments.index')])
            ->post(route('login.store'), [
                'email' => $admin->email,
                'password' => 'Password123!',
            ])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_trainer_and_trainee_can_open_role_aware_settings_and_help(): void
    {
        foreach (['admin', 'trainer', 'trainee'] as $role) {
            $avatarUrl = "https://example.test/{$role}-avatar.jpg";
            $user = User::factory()->create([
                'role' => $role,
                'avatar_url' => $avatarUrl,
            ]);

            $settings = $this->actingAs($user)
                ->get(route('account.settings'))
                ->assertOk()
                ->assertDontSee('Night mode')
                ->assertSee('Change password')
                ->assertSee('Profile photo')
                ->assertSee('data-dashboard-sidebar', false)
                ->assertSee('data-dashboard-role="'.$role.'"', false)
                ->assertDontSee('Back to dashboard')
                ->assertSee($avatarUrl, false);

            if ($role === 'admin') {
                $settings->assertSee('TESDA form registrar')
                    ->assertSee('Unused approved applications');
            } else {
                $settings->assertDontSee('TESDA form registrar')
                    ->assertDontSee('Unused approved applications');
            }

            $help = $this->actingAs($user)
                ->get(route('account.help'))
                ->assertOk()
                ->assertSee('Help for')
                ->assertDontSee('Account settings')
                ->assertSee('data-dashboard-sidebar', false)
                ->assertSee('data-dashboard-role="'.$role.'"', false);

            if ($role === 'admin') {
                $help->assertSee('Review messages')
                    ->assertSee(route('admin.contact-messages.index'), false);
            } else {
                $help->assertSee('Contact admin')
                    ->assertSee('data-dashboard-dialog-open="contact-admin-dialog"', false);
            }
        }
    }

    public function test_user_can_change_password_and_the_action_is_logged(): void
    {
        $trainer = User::factory()->create([
            'role' => 'trainer',
            'password' => 'OldPassword123',
        ]);

        $this->actingAs($trainer)
            ->patch(route('account.password.update'), [
                'current_password' => 'OldPassword123',
                'password' => 'NewPassword456',
                'password_confirmation' => 'NewPassword456',
            ])
            ->assertRedirect()
            ->assertSessionHas('saved');

        $this->assertTrue(Hash::check('NewPassword456', $trainer->fresh()->password));
        $this->assertTrue(AdminActivityLog::query()->where('action', 'account.password.updated')->exists());
    }

    public function test_user_can_store_a_profile_photo_on_the_public_disk(): void
    {
        $disk = Storage::fake('public');
        $trainee = User::factory()->create([
            'role' => 'trainee',
            'avatar_url' => 'https://example.test/google-face.jpg',
        ]);
        $photo = UploadedFile::fake()->image('profile.jpg', 120, 120);

        $this->actingAs($trainee)
            ->from(route('account.settings'))
            ->patch(route('account.avatar.update'), [
                'avatar' => $photo,
            ])
            ->assertRedirect(route('account.settings'))
            ->assertSessionHas('saved');

        $trainee->refresh();
        $this->assertNotNull($trainee->profile_photo_path);
        $this->assertTrue($disk->exists($trainee->profile_photo_path));
        $this->assertSame('/storage/'.$trainee->profile_photo_path, $trainee->profilePhotoUrl());
        $this->assertStringStartsWith('avatars/'.$trainee->id.'/', (string) $trainee->profile_photo_path);
        $this->assertDatabaseHas('users', [
            'id' => $trainee->id,
            'profile_photo_path' => $trainee->profile_photo_path,
        ]);
        $this->assertTrue(AdminActivityLog::query()->where('action', 'account.avatar.updated')->exists());

        $this->actingAs($trainee)
            ->get(route('account.settings'))
            ->assertOk()
            ->assertSee($trainee->profilePhotoUrl(), false);
    }

    public function test_profile_photo_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('account.settings'))
            ->patch(route('account.avatar.update'), [
                'avatar' => UploadedFile::fake()->create('notes.pdf', 40, 'application/pdf'),
            ])
            ->assertRedirect(route('account.settings'))
            ->assertSessionHasErrors('avatar');

        $this->assertNull($admin->fresh()->avatar_url);
        $this->assertNull($admin->fresh()->profile_photo_path);
        $this->assertFalse(AdminActivityLog::query()->where('action', 'account.avatar.updated')->exists());
    }

    public function test_dashboard_account_dropdowns_include_the_new_actions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertDontSee('Night mode')
            ->assertSee('Settings')
            ->assertSee('Help')
            ->assertDontSee(route('account.settings').'#change-password', false)
            ->assertDontSee('data-dashboard-notifications', false);

        $trainer = User::factory()->create(['role' => 'trainer']);
        $this->actingAs($trainer)->get(route('trainer.dashboard'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertDontSee('Night mode')
            ->assertSee('Settings')
            ->assertSee('Help')
            ->assertDontSee(route('account.settings').'#change-password', false)
            ->assertDontSee('data-dashboard-notifications', false);

        $trainee = User::factory()->create(['role' => 'trainee']);
        EnrollmentApplication::query()->create([
            'user_id' => $trainee->id,
            'email' => $trainee->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Trainee',
            'last_name' => 'Sample',
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
        ]);
        $this->actingAs($trainee)->get(route('trainee.dashboard'))
            ->assertOk()
            ->assertSee('Notifications')
            ->assertDontSee('Night mode')
            ->assertSee('Settings')
            ->assertSee('Help')
            ->assertDontSee(route('account.settings').'#change-password', false)
            ->assertDontSee('data-dashboard-notifications', false);
    }

    public function test_admin_can_save_a_drawn_registrar_signature_for_the_tesda_form(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('account.settings'))
            ->patch(route('account.registrar.update'), [
                'registrar_name' => 'Salvacion A. Collao',
                'registrar_signature_type' => 'draw',
                'registrar_signature_data' => $this->pngDataUrl(),
            ])
            ->assertRedirect(route('account.settings').'#tesda-registrar')
            ->assertSessionHas('saved', 'TESDA form registrar name and signature saved.');

        $settings = PublicSiteSetting::query()->firstOrFail();
        $this->assertSame('Salvacion A. Collao', $settings->registrar_name);
        $this->assertSame('draw', $settings->registrar_signature_type);
        $this->assertTrue($settings->hasRegistrarSignature());

        $this->actingAs($admin)
            ->get(route('account.registrar.signature'))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
    }

    public function test_admin_can_save_an_uploaded_registrar_signature(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->patch(route('account.registrar.update'), [
                'registrar_name' => 'Maria Santos',
                'registrar_signature_type' => 'upload',
                'registrar_signature_upload' => UploadedFile::fake()->image('registrar.png', 240, 80),
            ])
            ->assertRedirect(route('account.settings').'#tesda-registrar')
            ->assertSessionHas('saved');

        $settings = PublicSiteSetting::query()->firstOrFail();
        $this->assertSame('Maria Santos', $settings->registrar_name);
        $this->assertSame('upload', $settings->registrar_signature_type);
        $this->assertTrue($settings->hasRegistrarSignature());
    }

    public function test_non_admin_cannot_save_the_tesda_registrar_signature(): void
    {
        $trainer = User::factory()->create(['role' => 'trainer']);

        $this->actingAs($trainer)
            ->patch(route('account.registrar.update'), [
                'registrar_name' => 'Salvacion A. Collao',
                'registrar_signature_type' => 'draw',
                'registrar_signature_data' => $this->pngDataUrl(),
            ])
            ->assertForbidden();
    }

    public function test_admin_cannot_save_a_registrar_without_a_signature(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from(route('account.settings'))
            ->patch(route('account.registrar.update'), [
                'registrar_name' => 'Salvacion A. Collao',
                'registrar_signature_type' => 'draw',
            ])
            ->assertRedirect(route('account.settings'))
            ->assertSessionHasErrorsIn('registrar', 'registrar_signature_data');

        $this->assertDatabaseCount('public_site_settings', 0);
    }

    private function pngDataUrl(): string
    {
        $image = imagecreatetruecolor(120, 40);
        imagefilledrectangle($image, 0, 0, 120, 40, imagecolorallocate($image, 255, 255, 255));
        imageline($image, 8, 28, 110, 12, imagecolorallocate($image, 20, 20, 20));
        ob_start();
        imagepng($image);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/png;base64,'.base64_encode($bytes);
    }
}
