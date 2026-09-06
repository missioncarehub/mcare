<?php

namespace App\Providers;

use App\Contracts\OfficialDocumentRenderer;
use App\Services\BrowsershotOfficialDocumentRenderer;
use App\Services\FpdfOfficialDocumentRenderer;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Keep PDF generation replaceable so queue jobs and tests do not depend on a web controller.
        $this->app->bind(OfficialDocumentRenderer::class, function ($app) {
            return match (strtolower((string) config('official_documents.pdf_engine', 'auto'))) {
                'browsershot' => $app->make(BrowsershotOfficialDocumentRenderer::class),
                'fpdf' => $app->make(FpdfOfficialDocumentRenderer::class),
                default => BrowsershotOfficialDocumentRenderer::environmentIsReady()
                    ? $app->make(BrowsershotOfficialDocumentRenderer::class)
                    : $app->make(FpdfOfficialDocumentRenderer::class),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->isProduction()) {
            URL::forceScheme('https');
        }

        /*
         * Helper key used by several limiters.
         * Logged-in users are limited by account ID; guests fall back to IP.
         */
        $actorKey = static fn (Request $request): string => $request->user()
            ? 'user:'.$request->user()->getAuthIdentifier()
            : 'ip:'.$request->ip();

        /*
         * Coarse application-wide protection.
         * This is intentionally generous so normal browsing still works.
         * Sensitive endpoints receive stricter named limiters below.
         */
        RateLimiter::for('global-web', function (Request $request) use ($actorKey) {
            return Limit::perMinute(120)
                ->by('global-web:'.$actorKey($request));
        });

        /*
         * Admin login is protected against credential stuffing and brute force.
         * We combine normalized email + IP so one attacker cannot hammer a
         * single account, then add a broader IP ceiling as a second barrier.
         */
        RateLimiter::for('admin-login', function (Request $request) {
            $email = Str::lower(trim((string) $request->input('email', 'unknown')));
            $ip = $request->ip();

            return [
                Limit::perMinute(5)
                    ->by('admin-login:'.hash('sha256', $email.'|'.$ip)),
                Limit::perMinute(20)
                    ->by('admin-login-ip:'.$ip),
            ];
        });

        // OAuth endpoints should not be used as an unlimited redirect/callback loop.
        RateLimiter::for('oauth', function (Request $request) use ($actorKey) {
            return Limit::perMinute(10)
                ->by('oauth:'.$actorKey($request));
        });

        // Search endpoints can create expensive LIKE queries if spammed repeatedly.
        RateLimiter::for('search', function (Request $request) use ($actorKey) {
            return Limit::perMinute(30)
                ->by('search:'.$actorKey($request));
        });

        // Cascading address dropdowns call this several times per form fill.
        RateLimiter::for('address-lookup', function (Request $request) use ($actorKey) {
            return Limit::perMinute(60)
                ->by('address-lookup:'.$actorKey($request));
        });

        // Public Groq chat is metered; keep a tight IP ceiling.
        RateLimiter::for('landing-chat', function (Request $request) use ($actorKey) {
            return Limit::perMinute(8)
                ->by('landing-chat:'.$actorKey($request));
        });

        // Protect high-impact write operations such as review decisions and CRUD.
        RateLimiter::for('sensitive-mutation', function (Request $request) use ($actorKey) {
            return Limit::perMinute(20)
                ->by('sensitive-mutation:'.$actorKey($request));
        });

        // Limit bulk extraction attempts against protected applicant documents.
        RateLimiter::for('document-downloads', function (Request $request) use ($actorKey) {
            return Limit::perMinute(15)
                ->by('document-downloads:'.$actorKey($request));
        });

    }
}
