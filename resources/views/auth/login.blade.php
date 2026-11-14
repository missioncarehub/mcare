<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | MCARE Training Center</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="auth-page font-sans text-slate-900 antialiased selection:bg-purple-600 selection:text-white">
    <x-public-official-header
        masthead-aside="Caregiving NC II · Official sign-in"
        nav-label="Account sign-in"
        :secondary-href="route('landing')"
        secondary-label="Public site"
        :primary-href="route('applications.create')"
        primary-label="Apply now"
    />
    <div class="auth-page-layout">
        <main class="auth-shell" data-auth-layout>
            <section class="auth-showcase" aria-labelledby="auth-welcome-title" data-auth-dashboard-preview>
                <div class="auth-showcase-copy">
                    <p class="auth-welcome-kicker">Official account access</p>
                    <h1 id="auth-welcome-title" class="auth-welcome-title">
                        TESDA-accredited <span>Caregiving NC II</span>
                    </h1>
                    <p class="auth-welcome-copy">
                        Sign in to enrollment, training records, assessment administration, and graduate follow-through at Mission Care Training and Assessment Center.
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

            <section class="auth-panel" aria-label="MCARE account sign in">
                <div class="auth-card" data-auth-form-card>
                    @include('auth.partials.current-account', ['activeUser' => $activeUser])

                    <div class="auth-card-heading">
                        <h2>{{ $mfaPending ? 'Verify your sign-in' : 'Sign in to your account' }}</h2>
                        <p>
                            {{ $mfaPending ? 'Enter the six-digit code sent to your staff email address.' : 'Use your authorized MCARE account.' }}
                            @unless ($mfaPending)
                                <span class="sr-only">One sign-in page for applicants, trainees, trainers, alumni, and administrators.</span>
                            @endunless
                        </p>
                    </div>

                    @if (session('mfa_notice'))
                        <div class="auth-alert auth-alert-notice" role="status">
                            {{ session('mfa_notice') }}
                        </div>
                    @endif

                    @if (session('verified'))
                        <div class="auth-alert auth-alert-success" role="status">
                            {{ session('verified') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="auth-alert auth-alert-error" role="alert">
                            {{ $mfaPending ? 'Please check the verification code and try again.' : 'Please check your account credentials and try again.' }}
                        </div>
                    @endif

                    @if ($mfaPending)
                        <form method="POST" action="{{ route('login.verify-2fa') }}" class="auth-form">
                            @csrf
                            <div class="auth-field-group">
                                <label for="code">Verification code</label>
                                <input
                                    id="code"
                                    name="code"
                                    type="text"
                                    inputmode="numeric"
                                    autocomplete="one-time-code"
                                    pattern="[0-9]{6}"
                                    maxlength="6"
                                    required
                                    autofocus
                                    placeholder="000000"
                                    class="auth-input auth-code-input"
                                >
                                @error('code') <p class="auth-field-error">{{ $message }}</p> @enderror
                            </div>

                            <button type="submit" class="auth-primary-button">
                                <span>Verify and continue</span>
                                <x-dashboard-icon name="arrow-right" aria-hidden="true" />
                            </button>
                        </form>

                        <div class="auth-secondary-links auth-secondary-links-single">
                            <a href="{{ route('login', ['cancel_mfa' => 1]) }}">Use a different account</a>
                        </div>
                    @else
                        <form method="POST" action="{{ route('login.store') }}" class="auth-form" data-auth-login-form>
                            @csrf
                            <div class="auth-field-group">
                                <label for="email">Email</label>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    autocomplete="email"
                                    required
                                    autofocus
                                    placeholder="Enter your email address"
                                    class="auth-input"
                                >
                                @error('email') <p class="auth-field-error">{{ $message }}</p> @enderror
                            </div>

                            <div class="auth-field-group">
                                <label for="password">Password</label>
                                <div class="auth-password-field">
                                    <input
                                        id="password"
                                        name="password"
                                        type="password"
                                        autocomplete="current-password"
                                        required
                                        placeholder="Enter your password"
                                        class="auth-input"
                                    >
                                    <button
                                        type="button"
                                        class="auth-password-toggle"
                                        data-password-toggle="password"
                                        aria-label="Show password"
                                        title="Show password"
                                    >
                                        <x-dashboard-icon name="eye" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>

                            <label class="auth-remember-row">
                                <input name="remember" type="checkbox" value="1">
                                <span>Remember me</span>
                            </label>

                            <div class="auth-secondary-links auth-secondary-links-single">
                                <a href="{{ route('password.request') }}">Forgot password?</a>
                            </div>

                            <button type="submit" class="auth-primary-button disabled:cursor-not-allowed disabled:opacity-80" data-login-submit>
                                <span data-login-submit-label>Sign In</span>
                                <x-dashboard-icon name="arrow-right" aria-hidden="true" />
                            </button>
                        </form>

                        <div class="auth-divider" aria-hidden="true">
                            <span>or</span>
                        </div>

                        <a href="{{ route('auth.google.redirect') }}" class="auth-google-button">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.1c-.22-.66-.35-1.36-.35-2.1s.13-1.44.35-2.1V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                            </svg>
                            <span>Sign in with Google</span>
                        </a>

                        <div class="auth-secondary-links">
                            <p>
                                New applicant?
                                <a href="{{ route('applications.create') }}">Submit an application</a>
                                or
                                <a href="{{ route('enrollment.create') }}">enroll with an approved number</a>
                            </p>
                            <p>
                                Graduated before the MCARE website?
                                <a href="{{ route('alumni.claim.create') }}">Claim your alumni record</a>
                            </p>
                        </div>
                    @endif
                </div>

                <p class="auth-security-note">
                    <x-dashboard-icon name="shield-halved" aria-hidden="true" />
                    <span>Official institutional records. Use is limited to authorized MCARE accounts.</span>
                </p>
            </section>
        </main>
    </div>

    <x-public-official-footer note="Official sign-in for applicants, trainees, trainers, alumni, and administrators." />

    <script>
        (() => {
            const form = document.querySelector('[data-auth-login-form]');
            const submit = form?.querySelector('[data-login-submit]');
            const label = form?.querySelector('[data-login-submit-label]');
            const defaultLabel = label?.textContent || 'Sign In';

            const resetSubmit = () => {
                if (!submit || !label) return;
                submit.disabled = false;
                submit.removeAttribute('aria-busy');
                label.textContent = defaultLabel;
            };

            form?.addEventListener('submit', () => {
                if (!submit || !label) return;
                submit.disabled = true;
                submit.setAttribute('aria-busy', 'true');
                label.textContent = 'signing in';
            });

            window.addEventListener('pageshow', resetSubmit);
        })();
    </script>
</body>
</html>
