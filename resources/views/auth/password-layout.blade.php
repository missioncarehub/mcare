<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} | MCARE Training Center</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page font-sans text-slate-900 antialiased selection:bg-purple-600 selection:text-white">
    <x-public-official-header
        masthead-aside="Caregiving NC II · Account recovery"
        nav-label="Account recovery"
        :secondary-href="route('landing')"
        secondary-label="Public site"
        :primary-href="route('login')"
        primary-label="Sign in"
    />

    <div class="auth-page-layout">
        <main class="auth-shell" data-auth-layout>
            <section class="auth-showcase" aria-labelledby="auth-recovery-title">
                <div class="auth-showcase-copy">
                    <p class="auth-welcome-kicker">Secure account recovery</p>
                    <h1 id="auth-recovery-title" class="auth-welcome-title">
                        MCARE <span>Account Access</span>
                    </h1>
                    <p class="auth-welcome-copy">
                        Verify your email with a one-time code, then create a new password for your MCARE account.
                    </p>
                </div>

                <figure class="auth-dashboard-frame">
                    <img
                        src="{{ asset('assets/login-dashboard-preview.png') }}"
                        alt="Preview of the MCARE trainee dashboard"
                        class="auth-dashboard-image"
                    >
                </figure>
            </section>

            <section class="auth-panel" aria-label="MCARE account recovery">
                <div class="auth-card" data-auth-form-card>
                    @yield('auth-content')
                </div>

                <p class="auth-security-note">
                    <x-dashboard-icon name="shield-halved" aria-hidden="true" />
                    <span>Never share your one-time code. MCARE staff will never ask for your password.</span>
                </p>
            </section>
        </main>
    </div>

    <x-public-official-footer note="Secure account recovery for authorized MCARE accounts." />
</body>
</html>
