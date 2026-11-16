<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\EnrollmentApplication;
use App\Models\HistoricalAlumniClaim;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleOauthEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_redirect_is_blocked_when_the_callback_url_is_not_secure_and_exact(): void
    {
        config()->set([
            'app.url' => 'https://mcare-demo.ngrok-free.app',
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.redirect' => 'https://mcare-demo.ngrok-free.app',
        ]);

        Socialite::shouldReceive('driver')->never();

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('landing'))
            ->assertSessionHas('auth_error');
    }

    public function test_google_redirect_allows_local_loopback_callback_aliases(): void
    {
        config()->set([
            'app.url' => 'http://127.0.0.1:8000',
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.redirect' => 'http://localhost:8000/auth/google/callback',
        ]);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')->once()->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get('http://localhost:8000/auth/google')
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_google_redirect_sends_the_configured_callback_from_the_matching_loopback_host(): void
    {
        config()->set([
            'app.url' => 'http://localhost:8000',
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.redirect' => 'http://localhost:8000/auth/google/callback',
        ]);

        $capturedRedirect = null;
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')->once()->andReturnUsing(function () use (&$capturedRedirect) {
            $capturedRedirect = config('services.google.redirect');

            return redirect('https://accounts.google.com/o/oauth2/auth');
        });
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get('http://localhost:8000/auth/google')
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');

        $this->assertSame('http://localhost:8000/auth/google/callback', $capturedRedirect);
    }

    public function test_google_redirect_moves_loopback_hosts_onto_the_configured_callback_host(): void
    {
        config()->set([
            'app.url' => 'http://localhost:8000',
            'services.google.client_id' => 'client-id',
            'services.google.client_secret' => 'client-secret',
            'services.google.redirect' => 'http://localhost:8000/auth/google/callback',
        ]);

        Socialite::shouldReceive('driver')->never();

        $this->get('http://127.0.0.1:8000/auth/google')
            ->assertRedirect('http://localhost:8000/auth/google');
    }

    public function test_google_callback_explains_a_cancelled_sign_in(): void
    {
        Socialite::shouldReceive('driver')->never();

        $this->get(route('auth.google.callback', ['error' => 'access_denied']))
            ->assertRedirect(route('landing'))
            ->assertSessionHas('auth_error', 'Google sign in was cancelled. Please try again when you are ready.');
    }

    public function test_google_callback_explains_an_expired_sign_in_state(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('landing'))
            ->assertSessionHas('auth_error', 'Google sign in expired. Open this site at the same browser address and try again.');
    }

    public function test_unknown_google_email_is_rejected_until_enrollment_exists(): void
    {
        $googleUser = SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Maria Reyes Santos',
            'given_name' => 'Maria',
            'family_name' => 'Santos',
            'email' => 'maria.santos@gmail.com',
            'avatar' => 'https://example.test/maria.jpg',
        ]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('landing'))
            ->assertSessionHas('enrollment_required');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'maria.santos@gmail.com']);
        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'account.login.google.rejected',
        ]);
        $this->assertSame(
            'enrollment_required',
            AdminActivityLog::query()->latest('id')->firstOrFail()->meta['reason'],
        );
    }

    public function test_registered_applicant_can_connect_google_and_continue_payment(): void
    {
        $user = User::factory()->unverified()->create([
            'name' => 'Maria Santos',
            'email' => 'maria.santos@gmail.com',
            'role' => 'applicant',
            'google_id' => null,
        ]);
        EnrollmentApplication::create([
            'user_id' => $user->id,
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
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
        ]);

        $googleUser = SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Different Google Display Name',
            'email' => $user->email,
            'avatar' => 'https://example.test/maria.jpg',
        ]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('payment.show'))
            ->assertSessionHas('signed_in');

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-123', $user->fresh()->google_id);
        $this->assertSame('Maria Santos', $user->fresh()->name);
        $this->assertSame('https://example.test/maria.jpg', $user->fresh()->avatar_url);
        $this->assertNotNull($user->fresh()->email_verified_at);

        $this->get(route('enrollment.create'))
            ->assertOk()
            ->assertSee('Verified by Google')
            ->assertSee('value="maria.santos@gmail.com"', false)
            ->assertSee('https://example.test/maria.jpg', false)
            ->assertSee('autocomplete="section-applicant sex"', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('Google OAuth is paused');
    }

    public function test_denied_google_applicant_can_sign_in_to_correct_and_resubmit(): void
    {
        $user = User::factory()->create([
            'name' => 'Denied Applicant',
            'email' => 'denied.applicant@gmail.com',
            'role' => 'applicant',
            'applicant_status' => EnrollmentApplication::STATUS_DENIED,
            'email_verified_at' => now(),
        ]);
        EnrollmentApplication::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Denied',
            'last_name' => 'Applicant',
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
            'admin_notes' => 'Contact MCARE regarding the submitted document.',
            'total_paid_amount' => 2000,
            'downpayment_amount' => 2000,
            'payment_verified_at' => now(),
        ]);

        $googleUser = SocialiteUser::fake([
            'id' => 'google-denied-123',
            'name' => 'Denied Applicant',
            'email' => $user->email,
        ]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('enrollment.create'))
            ->assertSessionHas('reapply_notice');

        $this->assertAuthenticatedAs($user);
        $this->get(route('enrollment.create'))
            ->assertOk()
            ->assertSee('Resubmit corrected enrollment')
            ->assertSee('Contact MCARE regarding the submitted document.')
            ->assertDontSee('Please wait while the administrator completes your account verification');
    }

    public function test_google_identity_mismatch_does_not_replace_an_existing_link(): void
    {
        $user = User::factory()->create([
            'name' => 'Linked Applicant',
            'email' => 'linked.applicant@gmail.com',
            'role' => 'applicant',
            'google_id' => 'google-original',
            'avatar_url' => 'https://example.test/original.jpg',
        ]);
        $googleUser = SocialiteUser::fake([
            'id' => 'google-different',
            'name' => 'Changed Name',
            'email' => $user->email,
            'avatar' => 'https://example.test/different.jpg',
        ]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('landing'))
            ->assertSessionHas('auth_error');

        $this->assertGuest();
        $this->assertSame('google-original', $user->fresh()->google_id);
        $this->assertSame('Linked Applicant', $user->fresh()->name);
        $this->assertSame('https://example.test/original.jpg', $user->fresh()->avatar_url);
    }

    public function test_google_login_keeps_a_public_storage_profile_photo(): void
    {
        $user = User::factory()->create([
            'name' => 'Photo Applicant',
            'email' => 'photo.applicant@gmail.com',
            'role' => 'applicant',
            'google_id' => null,
            'avatar_url' => '/storage/avatars/placeholder/face.jpg',
        ]);
        $user->forceFill([
            'profile_photo_path' => 'avatars/'.$user->id.'/face.jpg',
        ])->save();
        EnrollmentApplication::create([
            'user_id' => $user->id,
            'email' => $user->email,
            'program' => 'Caregiving NC II',
            'first_name' => 'Photo',
            'last_name' => 'Applicant',
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
            'status' => EnrollmentApplication::STATUS_PRE_ENLISTMENT,
        ]);
        $googleUser = SocialiteUser::fake([
            'id' => 'google-photo-keep',
            'name' => 'Google Display Name',
            'email' => $user->email,
            'avatar' => 'https://example.test/google-replacement.jpg',
        ]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('payment.show'))
            ->assertSessionHas('signed_in');

        $this->assertSame('avatars/'.$user->id.'/face.jpg', $user->fresh()->profile_photo_path);
        $this->assertSame('/storage/avatars/'.$user->id.'/face.jpg', $user->fresh()->profilePhotoUrl());
    }

    public function test_google_verifies_a_historical_claim_without_bypassing_onsite_review(): void
    {
        $user = User::factory()->unverified()->create([
            'name' => 'Legacy Claimant',
            'email' => 'legacy.claimant@gmail.com',
            'role' => 'applicant',
            'applicant_status' => 'historical_claim_pending_email',
        ]);
        HistoricalAlumniClaim::create([
            'user_id' => $user->id,
            'first_name' => 'Legacy',
            'last_name' => 'Claimant',
            'birth_date' => '1990-01-01',
            'gender' => 'Female',
            'contact_number' => '09171234567',
            'street' => '1 Archive Street',
            'barangay' => 'Central',
            'city' => 'Iriga City',
            'province' => 'Camarines Sur',
            'zip_code' => '4431',
            'educational_attainment' => 'High School Graduate',
            'school_name' => 'Archive School',
            'education_year_graduated' => 2007,
            'training_completion_year' => 2018,
            'training_schedule' => 'AM',
            'evidence_type' => 'certificate',
            'status' => HistoricalAlumniClaim::STATUS_PENDING_EMAIL,
            'privacy_consent_at' => now(),
        ]);

        $googleUser = SocialiteUser::fake([
            'id' => 'google-legacy-123',
            'name' => 'Legacy Claimant',
            'email' => 'legacy.claimant@gmail.com',
            'avatar' => 'https://example.test/legacy-avatar.jpg',
        ]);
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('verified');

        $this->assertGuest();
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame('https://example.test/legacy-avatar.jpg', $user->fresh()->avatar_url);
        $this->assertSame(
            HistoricalAlumniClaim::STATUS_PENDING_ONSITE,
            $user->historicalAlumniClaim()->firstOrFail()->status,
        );
        $this->assertDatabaseMissing('enrollment_applications', ['user_id' => $user->id]);
    }
}
