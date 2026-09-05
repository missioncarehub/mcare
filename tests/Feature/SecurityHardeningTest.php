<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_responses_include_global_security_headers(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-XSS-Protection', '0')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin-allow-popups')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_private_routes_are_marked_noindex_and_no_store(): void
    {
        $response = $this->get('/login');

        $response
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow, noarchive, nosnippet')
            ->assertHeader('Referrer-Policy', 'no-referrer');

        // Cache-Control directive ordering can vary, so test the important value.
        $this->assertStringContainsString(
            'no-store',
            (string) $response->headers->get('Cache-Control')
        );
    }

    public function test_production_response_has_a_restrictive_content_security_policy(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $policy = (string) $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("default-src 'self'", $policy);
        $this->assertStringContainsString("form-action 'self'", $policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString("object-src 'none'", $policy);
        $this->assertStringContainsString("worker-src 'self' blob:", $policy);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' blob:", $policy);
    }

    public function test_admin_login_is_throttled_after_repeated_failures(): void
    {
        $credentials = [
            'email' => 'attacker@example.com',
            'password' => 'definitely-wrong',
        ];

        // The strict limiter allows five attempts per minute for email + IP.
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post('/login', $credentials)
                ->assertSessionHasErrors('email');
        }

        // The sixth request should be stopped before another password check.
        $this->post('/login', $credentials)
            ->assertStatus(429);
    }

    public function test_oversized_admin_search_input_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->from('/admin/logs')
            ->get('/admin/logs?search='.str_repeat('a', 101))
            ->assertRedirect('/admin/logs')
            ->assertSessionHasErrors('search');
    }

    public function test_role_column_is_synchronized_with_spatie_permissions(): void
    {
        $user = User::factory()->create(['role' => 'admin']);

        $this->assertTrue($user->hasRole('admin'));
        $this->assertTrue($user->can('logs.view'));
        $this->assertFalse($user->can('modules.view'));

        $user->update(['role' => 'trainer']);
        $user->refresh();

        $this->assertTrue($user->hasRole('trainer'));
        $this->assertFalse($user->hasRole('admin'));
        $this->assertTrue($user->can('modules.publish'));
        $this->assertFalse($user->can('logs.view'));

        $user->update(['role' => 'unsupported-role']);
        $user->refresh();

        $this->assertTrue($user->roles->isEmpty());
        $this->assertFalse($user->can('trainer.access'));
    }

    public function test_named_permission_is_required_for_sensitive_admin_route(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $admin->roles()->firstOrFail()->revokePermissionTo('logs.view');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($admin)
            ->get('/admin/logs')
            ->assertForbidden();

        // The unrelated admin dashboard permission remains available.
        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }

    public function test_theme_uses_light_as_default_and_shared_persistent_storage_key(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee("window.localStorage.getItem('mcare-dashboard-theme') === 'dark' ? 'dark' : 'light'", false)
            ->assertSee('One sign-in page for applicants, trainees, trainers, alumni, and administrators.');
    }
}
