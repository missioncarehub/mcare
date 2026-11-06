<!DOCTYPE html>
<html lang="en" class="bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCARE | Mission Care Training and Assessment Center</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <x-site-favicon />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* Path: resources/views/landing/home.blade.php | Label: Main landing background */
        .landing-grid-bg {
            background-image:
                linear-gradient(to right, #f0f0f0 1px, transparent 1px),
                linear-gradient(to bottom, #f0f0f0 1px, transparent 1px),
                radial-gradient(circle 600px at 0% 200px, #d5c5ff, transparent),
                radial-gradient(circle 600px at 100% 200px, #d5c5ff, transparent);
            background-size: 20px 20px, 20px 20px, 100% 100%, 100% 100%;
        }

        /* Path: resources/views/landing/home.blade.php | Label: 6.7-inch phone header layout */
        @media (max-width: 767px) {
            .site-header {
                background: #ffffff;
            }

            /* Path: resources/views/landing/home.blade.php | Label: Phone-first landing density */
            .phone-hero-copy {
                max-width: 24rem;
                margin-inline: auto;
                text-align: center;
            }

            .phone-section-heading {
                max-width: 24rem;
            }

            .phone-hero-card {
                max-width: 20.5rem;
            }

            .phone-hero-frame {
                border-radius: 1.5rem;
                padding: 0.625rem;
            }

            .phone-hero-media {
                border-radius: 1.125rem;
            }

            .phone-soft-card {
                border-radius: 1rem;
                box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
            }
        }

        /* Path: resources/views/landing/home.blade.php | Label: Mobile sidebar motion */
        .mobile-sidebar-shell {
            opacity: 0;
            pointer-events: none;
            transition: opacity 180ms ease;
        }

        .mobile-sidebar-panel {
            transform: translateX(100%);
            transition: transform 220ms ease;
        }

        .mobile-sidebar-shell.is-open {
            opacity: 1;
            pointer-events: auto;
        }

        .mobile-sidebar-shell.is-open .mobile-sidebar-panel {
            transform: translateX(0);
        }

        /* Path: resources/views/landing/home.blade.php | Label: Hero carousel */
        .hero-slide {
            opacity: 0;
            pointer-events: none;
            transition: opacity 400ms ease;
        }

        .hero-slide.is-active {
            opacity: 1;
            pointer-events: auto;
            z-index: 1;
        }

        .hero-dot {
            width: 0.5rem;
            height: 0.5rem;
            padding: 0;
            border: 0;
            border-radius: 999px;
            background: rgb(216 180 254);
            cursor: pointer;
            transition: width 200ms ease, background-color 200ms ease;
        }

        .hero-dot.is-active {
            width: 1.75rem;
            background: rgb(147 51 234);
        }

        @media (prefers-reduced-motion: reduce) {
            .hero-slide,
            .hero-dot {
                transition: none;
            }
        }

        /* Path: resources/views/landing/home.blade.php | Label: Footer-only radial background */
        .footer-radial-bg {
            background: radial-gradient(125% 125% at 50% 10%, #fff 40%, #7c3aed 100%);
        }

        /* Path: resources/views/landing/home.blade.php | Label: Discover section bottom radial background */
        .discover-radial-bg {
            background: radial-gradient(125% 125% at 50% 90%, #fff 40%, #7c3aed 100%);
        }

        /* Path: resources/views/landing/home.blade.php | Label: Discover text fade animation */
        .discover-fade {
            opacity: 0;
            transform: translate3d(0, 14px, 0);
            transition: opacity 420ms ease, transform 420ms ease;
        }

        .discover-fade.is-visible {
            opacity: 1;
            transform: translate3d(0, 0, 0);
        }

        @media (max-width: 1023px), (prefers-reduced-motion: reduce) {
            .discover-fade {
                opacity: 1;
                transform: none;
                transition: none;
            }
        }
    </style>
</head>
<body class="landing-site relative overflow-x-hidden bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    @php
        $socialLinks = $socialLinks ?? [
            'facebook' => null,
            'instagram' => null,
            'youtube' => null,
        ];

        $publicUpdates = $publicUpdates ?? collect();

        $currentUser = auth()->user();
        $accountCtaUrl = $currentUser ? \App\Support\AccountPortal::urlFor($currentUser) : route('applications.create');
        $accountCtaLabel = $currentUser ? \App\Support\AccountPortal::ctaLabelFor($currentUser) : 'Apply now';
        $accountRoleLabel = \App\Support\AccountPortal::roleLabelFor($currentUser);
    @endphp

    <!-- Path: resources/views/landing/home.blade.php | Label: Main landing background layer -->
    <div class="landing-grid-bg pointer-events-none fixed inset-0 z-0"></div>

    <!-- Path: resources/views/landing/home.blade.php | Label: Clean single-row header -->
    <header id="site-header" class="site-header fixed inset-x-0 top-0 z-50 bg-white" data-header-scroll-border>
        <div class="landing-masthead">
            <p class="landing-masthead-kicker">TESDA-Accredited Training and Assessment Center</p>
            <p class="landing-masthead-aside">Caregiving NC II · Official public information</p>
        </div>
        <nav class="mx-auto flex min-h-[5rem] max-w-7xl items-center justify-between gap-4 px-4 py-2.5 sm:min-h-[5.5rem] sm:px-6 lg:px-8" aria-label="Main navigation">
            <a href="{{ route('landing') }}" class="flex min-w-0 items-center gap-3">
                <img src="{{ asset('assets/images/logoicon.png') }}" alt="MCARE Hub" class="landing-brand-mark">
                <span class="mcare-brand">
                    <span class="mcare-mark">MCARE</span>
                    <p class="mcare-brand-name">Mission Care</p>
                </span>
            </a>

            <div class="hidden items-center gap-6 lg:flex">
                <a href="#programs" class="text-sm font-semibold text-slate-600 hover:text-purple-700">Programs</a>
                <a href="#admissions" class="text-sm font-semibold text-slate-600 hover:text-purple-700">Admissions</a>
                <a href="#why" class="text-sm font-semibold text-slate-600 hover:text-purple-700">Why MCARE</a>
                <a href="#discover" class="text-sm font-semibold text-slate-600 hover:text-purple-700">Discover</a>
                <a href="#contact" class="text-sm font-semibold text-slate-600 hover:text-purple-700">Contact</a>
                <a href="{{ route('payments.show') }}" class="text-sm font-semibold text-slate-600 hover:text-purple-700">Payments</a>
            </div>

            <div class="flex shrink-0 items-center gap-2">
                @auth
                    <!-- Path: resources/views/landing/home.blade.php | Label: Compact active account menu -->
                    <details class="relative hidden lg:block">
                        <summary class="flex cursor-pointer list-none items-center gap-2 rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-left hover:bg-slate-50">
                            <x-user-avatar :user="$currentUser" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-purple-100 text-sm font-bold text-purple-700" />
                            <span class="hidden min-w-0 max-w-32 lg:block">
                                <span class="block truncate text-sm font-bold text-slate-900">{{ $currentUser->name }}</span>
                                <span class="block truncate text-xs text-slate-500">{{ $accountRoleLabel }}</span>
                            </span>
                            <x-dashboard-icon name="chevron-down" class="mx-1 text-xs text-slate-400" />
                        </summary>
                        <div class="absolute right-0 mt-2 w-64 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                            <div class="border-b border-slate-100 px-3 py-2.5">
                                <p class="truncate text-sm font-bold text-slate-900">{{ $currentUser->name }}</p>
                                <p class="mt-0.5 truncate text-xs text-slate-500">{{ $currentUser->email }}</p>
                            </div>
                            <a href="{{ $accountCtaUrl }}" class="mt-1 flex items-center justify-between rounded-lg px-3 py-2.5 text-sm font-semibold text-purple-700 hover:bg-purple-50">
                                <span>{{ $accountCtaLabel }}</span>
                                <x-dashboard-icon name="arrow-right" class="text-xs" />
                            </a>
                            <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-1">
                                @csrf
                                <button type="submit" class="w-full rounded-lg px-3 py-2.5 text-left text-sm font-semibold text-red-600 hover:bg-red-50">Sign out</button>
                            </form>
                        </div>
                    </details>
                @else
                    <a href="{{ route('login') }}" class="hidden rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-purple-200 hover:text-purple-700 lg:inline-flex">Sign in</a>
                    <a href="{{ route('alumni.claim.create') }}" class="hidden rounded-lg border border-purple-200 bg-purple-50 px-4 py-2.5 text-sm font-semibold text-purple-700 hover:bg-purple-100 xl:inline-flex">Alumni claim</a>
                    <a href="{{ route('applications.status') }}" class="hidden rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-purple-200 hover:text-purple-700 lg:inline-flex">Check status</a>
                    <a href="{{ route('applications.create') }}" class="hidden rounded-lg bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white hover:bg-purple-800 lg:inline-flex">Apply now</a>
                @endauth

                <button id="mobile-menu-open" type="button" aria-controls="mobile-sidebar" aria-expanded="false" class="flex h-16 w-16 items-center justify-center border-0 bg-transparent p-0 lg:hidden">
                    <span class="sr-only">Open navigation menu</span>
                    <img src="{{ asset('assets/images/menu/burger-menu.png') }}" alt="" class="h-14 w-14 object-contain" width="56" height="56">
                </button>
            </div>
        </nav>
    </header>

    <!-- Path: resources/views/landing/home.blade.php | Label: Mobile sidebar navigation -->
    <div id="mobile-sidebar" class="mobile-sidebar-shell fixed inset-0 z-[60] lg:hidden" aria-hidden="true">
        <button id="mobile-menu-overlay" type="button" class="absolute inset-0 bg-slate-950/35" aria-label="Close navigation menu"></button>
        <aside class="mobile-sidebar-panel absolute right-0 top-0 flex h-full w-[min(86vw,340px)] flex-col border-l border-purple-100 bg-white shadow-2xl shadow-purple-950/20">
            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-4">
                <a href="{{ route('landing') }}" class="flex items-center gap-3">
                    <img src="{{ asset('assets/images/logoicon.png') }}" alt="MCARE Hub" class="landing-brand-mark">
                    <span class="mcare-brand">
                        <span class="mcare-mark">MCARE</span>
                        <p class="mcare-brand-name">Mission Care</p>
                    </span>
                </a>
                <button id="mobile-menu-close" type="button" class="flex h-10 w-10 items-center justify-center border-0 bg-transparent p-0">
                    <span class="sr-only">Close navigation menu</span>
                    <img src="{{ asset('assets/images/menu/x-burger.png') }}" alt="" class="h-8 w-8 object-contain" width="32" height="32">
                </button>
            </div>

            <nav class="flex-1 space-y-1.5 overflow-y-auto px-4 py-4" aria-label="Mobile navigation">
                @auth
                    <!-- Path: resources/views/landing/home.blade.php | Label: Mobile active account identity -->
                    <div class="mb-3 rounded-2xl border border-purple-100 bg-purple-50/80 p-4">
                        <div class="flex items-start gap-3">
                            <x-user-avatar :user="$currentUser" class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-purple-600 text-sm font-black text-white" />
                            <div class="min-w-0">
                                <p class="text-[10px] font-black uppercase tracking-wide text-purple-700">Active account</p>
                                <p class="truncate text-sm font-bold text-slate-900" title="{{ $currentUser->name }}">{{ $currentUser->name }}</p>
                                <p class="mt-0.5 truncate text-xs font-semibold text-slate-500" title="{{ $currentUser->email }}">{{ $currentUser->email }}</p>
                                <p class="mt-1 inline-flex rounded-full bg-white px-2.5 py-1 text-[11px] font-black text-purple-700">{{ $accountRoleLabel }}</p>
                            </div>
                        </div>
                    </div>
                @endauth
                <a href="#programs" class="mobile-sidebar-link block rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Programs</a>
                <a href="#admissions" class="mobile-sidebar-link block rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Admissions</a>
                <a href="#why" class="mobile-sidebar-link block rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Why MCARE</a>
                <a href="#discover" class="mobile-sidebar-link block rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Discover</a>
                <a href="#contact" class="mobile-sidebar-link block rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Contact</a>
                <a href="{{ route('payments.show') }}" class="mobile-sidebar-link block rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Payments</a>
                <a href="{{ route('applications.status') }}" class="mobile-sidebar-link block rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Check status</a>
                @guest
                    <a href="{{ route('alumni.claim.create') }}" class="mobile-sidebar-link block rounded-xl px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-purple-50 hover:text-purple-700">Alumni claim</a>
                @endguest
            </nav>

            <div class="border-t border-slate-100 p-4">
                @auth
                    <a href="{{ $accountCtaUrl }}" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">{{ $accountCtaLabel }}</a>
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="inline-flex w-full items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:border-purple-200 hover:text-purple-700">
                            Sign out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="mobile-sidebar-link mb-2.5 inline-flex w-full items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:border-purple-200 hover:text-purple-700">Sign in</a>
                    <a href="{{ route('applications.status') }}" class="mobile-sidebar-link mb-2.5 inline-flex w-full items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-3 text-sm font-bold text-slate-700 hover:border-purple-200 hover:text-purple-700">Check status</a>
                    <a href="{{ route('applications.create') }}" class="mobile-sidebar-link inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-5 py-3 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700">Apply now</a>
                @endauth
            </div>
        </aside>
    </div>


    @php
        $paymentCleared = $applicationProgress?->hasEnrollmentPaymentClearance() ?? false;
        $accountApproved = $applicationProgress?->status === \App\Models\EnrollmentApplication::STATUS_APPROVED;
        $accountDenied = $applicationProgress?->status === \App\Models\EnrollmentApplication::STATUS_DENIED;
    @endphp

    @if (session('payment_error'))
        <section class="relative z-20 border-b border-red-200 bg-red-50" role="alert">
            <div class="mx-auto max-w-7xl px-4 py-4 text-sm font-semibold text-red-800 sm:px-6 lg:px-8">
                {{ session('payment_error') }}
            </div>
        </section>
    @endif

    @if (session('account_denied') || $accountDenied)
        <section id="applicant-review-status" data-account-terminal="true" data-payment-status-url="{{ route('payment.status') }}" class="relative z-20 border-b border-red-200 bg-red-50" role="alert" aria-live="assertive">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-5 sm:px-6 lg:px-8">
                <div>
                    <p class="font-black text-red-950">Enrollment application not approved</p>
                    <p class="mt-1 text-sm leading-6 text-red-800">Your verified payment remains recorded, but administration did not approve your MCARE account. Please review the reason below and contact MCARE regarding correction, resubmission, or other next steps.</p>
                    @if (filled($applicationProgress?->admin_notes))
                        <p class="mt-3 rounded-xl border border-red-200 bg-white px-4 py-3 text-sm font-semibold leading-6 text-red-900"><span class="font-black">Administrator note:</span> {{ $applicationProgress->admin_notes }}</p>
                    @endif
                    <a href="{{ route('login') }}" class="mt-4 inline-flex items-center justify-center rounded-full bg-red-700 px-5 py-2.5 text-sm font-bold text-white hover:bg-red-800">Sign in to correct and resubmit</a>
                </div>
            </div>
        </section>
    @elseif (session('account_approved') || $accountApproved)
        <section id="applicant-review-status" data-account-terminal="true" data-payment-status-url="{{ route('payment.status') }}" class="relative z-20 border-b border-emerald-200 bg-emerald-50" role="status" aria-live="polite">
            <div class="mx-auto flex max-w-7xl flex-col gap-3 px-4 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                <div>
                    <p class="font-black text-emerald-900">Your MCARE account is approved</p>
                    <p class="mt-1 text-sm leading-6 text-emerald-800">Administration verified your account. You can now log in and open the trainee portal.</p>
                </div>
                <a href="{{ route('login') }}" class="inline-flex shrink-0 items-center justify-center rounded-full bg-emerald-700 px-6 py-3 text-sm font-bold text-white hover:bg-emerald-800">Log in to MCARE</a>
            </div>
        </section>
    @elseif (session('payment_verified') || session('account_pending') || $paymentCleared)
        <section id="applicant-review-status" data-account-terminal="false" data-payment-status-url="{{ route('payment.status') }}" class="relative z-20 border-b border-purple-200 bg-purple-50" role="status" aria-live="polite">
            <div class="mx-auto flex max-w-7xl items-start gap-3 px-4 py-5 sm:px-6 lg:px-8">
                <span class="mcare-spinner mt-0.5" aria-hidden="true"></span>
                <div>
                    <p class="font-black text-purple-950">Payment verified successfully</p>
                    <p class="mt-1 text-sm leading-6 text-purple-800">Please wait while the administrator completes your account verification. MCARE will email you when your account is approved and ready to log in.</p>
                </div>
            </div>
        </section>
    @endif

    <main class="relative z-10">
        <section class="relative overflow-hidden bg-white">
            <div class="mx-auto grid max-w-7xl grid-cols-1 items-start gap-8 px-4 pt-5 pb-8 sm:gap-10 sm:px-6 sm:pt-6 sm:pb-14 lg:grid-cols-2 lg:gap-14 lg:px-8 lg:pt-8 lg:pb-16">
                <div class="phone-hero-copy sm:text-left">
                    <h1 class="mt-0 max-w-3xl text-[1.85rem] font-bold leading-[2.2rem] text-slate-900 sm:text-5xl sm:leading-tight">
                        <span class="tesda-mark">TESDA</span>-accredited Caregiving NC II training and assessment.
                    </h1>

                    <p class="mt-4 max-w-2xl text-sm leading-6 text-slate-600 sm:mt-7 sm:text-lg sm:leading-8">
                        Mission Care Training and Assessment Center publishes official enrollment for TESDA-oriented caregiving programs, class schedules, training records, and graduate career follow-through.
                    </p>

                    <div class="mt-6 grid grid-cols-1 gap-2.5 sm:mt-10 sm:flex sm:flex-row sm:flex-wrap sm:gap-3">
                        @auth
                            <a href="{{ $accountCtaUrl }}" class="inline-flex h-11 items-center justify-center rounded-full bg-purple-600 px-6 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 sm:h-auto sm:px-7 sm:py-3.5">{{ $accountCtaLabel }}</a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex h-11 items-center justify-center rounded-full border border-purple-200 bg-purple-50 px-6 text-sm font-bold text-purple-700 hover:bg-purple-100 sm:h-auto sm:px-7 sm:py-3.5">Sign In</a>
                            <a href="{{ route('applications.create') }}" class="inline-flex h-11 items-center justify-center rounded-full bg-purple-600 px-6 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 sm:h-auto sm:px-7 sm:py-3.5">Apply now</a>
                        @endauth
                        <a href="{{ route('applications.status') }}" class="inline-flex h-11 items-center justify-center rounded-full border border-slate-200 bg-white px-6 text-sm font-semibold text-slate-700 hover:border-purple-200 hover:text-purple-700 sm:h-auto sm:px-7 sm:py-3.5">Check status</a>
                        <a href="#programs" class="inline-flex h-11 items-center justify-center rounded-full border border-slate-200 bg-white px-6 text-sm font-semibold text-slate-700 hover:border-purple-200 hover:text-purple-700 sm:h-auto sm:px-7 sm:py-3.5">View Programs</a>
                    </div>

                    <div class="mt-7 grid max-w-xl grid-cols-3 gap-3 border-t border-slate-200 pt-5 text-left sm:mt-10 sm:gap-5 sm:pt-8">
                        <div>
                            <p class="text-lg font-bold text-slate-900 sm:text-2xl">NC II</p>
                            <p class="mt-1 text-[11px] leading-4 text-slate-500 sm:text-sm sm:leading-6">TESDA qualification</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-slate-900 sm:text-2xl">Assess</p>
                            <p class="mt-1 text-[11px] leading-4 text-slate-500 sm:text-sm sm:leading-6">Skills demonstration</p>
                        </div>
                        <div>
                            <p class="text-lg font-bold text-slate-900 sm:text-2xl">Records</p>
                            <p class="mt-1 text-[11px] leading-4 text-slate-500 sm:text-sm sm:leading-6">Certificates and TOR</p>
                        </div>
                    </div>

                    @if (session('google_config_missing'))
                        <div class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-medium leading-6 text-amber-800">
                            {{ session('google_config_missing') }}
                        </div>
                    @endif

                    @if (session('auth_error'))
                        <div class="mt-6 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-medium leading-6 text-red-700">
                            {{ session('auth_error') }}
                            @if (session('enrollment_required'))
                                <a href="{{ route('applications.create') }}" class="ml-1 font-black text-purple-700 underline decoration-purple-300 underline-offset-2 hover:text-purple-900">Start an application</a>
                            @endif
                        </div>
                    @endif

                    @if (session('signed_in'))
                        <div class="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-medium leading-6 text-emerald-700">
                            {{ session('signed_in') }}
                        </div>
                    @endif
                </div>

                <div class="phone-hero-card relative mx-auto w-full sm:max-w-[560px] lg:mx-0">
                    <div class="phone-hero-frame relative rounded-[2rem] border border-purple-100 bg-white p-4">
                        <div class="phone-hero-media relative aspect-[4/5] overflow-hidden rounded-[1.5rem] bg-slate-100" data-hero-carousel>
                            <div class="hero-slide is-active absolute inset-0" data-hero-slide>
                                <picture>
                                    <source srcset="{{ asset('assets/landing/caregiving-training-hero.webp') }}" type="image/webp">
                                    <img src="{{ asset('assets/landing/caregiving-training-hero.jpg') }}" alt="MCARE caregiving training preview" class="h-full w-full object-cover" width="960" height="512" decoding="async" fetchpriority="high">
                                </picture>
                                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/75 via-slate-950/20 to-transparent p-4 text-white sm:p-7">
                                    <p class="text-xs font-bold uppercase tracking-wide text-purple-100">Hands-on learning</p>
                                    <p class="mt-2 max-w-xs text-lg font-bold leading-tight sm:text-2xl">Training built around real caregiving routines.</p>
                                </div>
                            </div>

                            <div class="hero-slide absolute inset-0 bg-gradient-to-br from-purple-50 via-white to-slate-100 p-5 sm:p-7" data-hero-slide>
                                <div class="flex h-full flex-col justify-between">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-purple-600">Applicant tracking</p>
                                        <h2 class="mt-3 max-w-sm text-xl font-bold leading-tight text-slate-900 sm:text-3xl">Submit your profile, documents, and signature in one flow.</h2>
                                    </div>
                                    <div class="space-y-3">
                                        <div class="rounded-xl border border-purple-100 bg-white p-3 shadow-sm sm:rounded-2xl sm:p-4">
                                            <div class="flex items-center justify-between gap-4">
                                                <div>
                                                    <p class="text-sm font-bold text-slate-900">Learner profile</p>
                                                    <p class="mt-1 text-xs text-slate-500">Personal, address, education</p>
                                                </div>
                                                <span class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Ready</span>
                                            </div>
                                        </div>
                                        <div class="rounded-xl border border-purple-100 bg-white p-3 shadow-sm sm:rounded-2xl sm:p-4">
                                            <div class="flex items-center justify-between gap-4">
                                                <div>
                                                    <p class="text-sm font-bold text-slate-900">Uploads</p>
                                                    <p class="mt-1 text-xs text-slate-500">Birth certificate, ID photo, diploma</p>
                                                </div>
                                                <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700">Secure</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="hero-slide absolute inset-0 bg-gradient-to-br from-slate-900 via-purple-950 to-slate-800 p-5 text-white sm:p-7" data-hero-slide>
                                <div class="flex h-full flex-col justify-between">
                                    <div>
                                        <p class="text-xs font-bold uppercase tracking-wide text-purple-200">Admin review</p>
                                        <h2 class="mt-3 max-w-sm text-xl font-bold leading-tight sm:text-3xl">Move applicants from submitted to pre-enlistment.</h2>
                                    </div>
                                    <div class="grid grid-cols-1 gap-3">
                                        <div class="rounded-xl border border-white/10 bg-white/10 p-3 sm:rounded-2xl sm:p-4">
                                            <p class="text-xs font-semibold text-purple-100">Current status</p>
                                            <p class="mt-1 text-lg font-bold sm:text-xl">Pre-enlistment</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div class="rounded-xl border border-white/10 bg-white/10 p-3 sm:rounded-2xl sm:p-4">
                                                <p class="text-xl font-bold sm:text-2xl">3</p>
                                                <p class="mt-1 text-xs text-purple-100">Review decisions</p>
                                            </div>
                                            <div class="rounded-xl border border-white/10 bg-white/10 p-3 sm:rounded-2xl sm:p-4">
                                                <p class="text-xl font-bold sm:text-2xl">5</p>
                                                <p class="mt-1 text-xs text-purple-100">Required files</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="absolute right-4 top-4 z-10 flex gap-2 rounded-full bg-white px-3 py-2 sm:right-5 sm:top-5">
                                <button type="button" class="hero-dot is-active" data-hero-dot aria-label="Show training slide" aria-current="true"></button>
                                <button type="button" class="hero-dot" data-hero-dot aria-label="Show applicant tracking slide"></button>
                                <button type="button" class="hero-dot" data-hero-dot aria-label="Show admin review slide"></button>
                            </div>
                        </div>
                    </div>
                    <div class="phone-soft-card relative mx-2 mt-4 rounded-2xl border border-slate-100 bg-white p-4 shadow-xl shadow-slate-200 sm:absolute sm:-bottom-7 sm:left-6 sm:right-6 sm:mx-0 sm:mt-0 sm:rounded-3xl sm:p-5">
                        <div class="flex items-center gap-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 sm:h-12 sm:w-12 sm:rounded-2xl">
                                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true">
                                    <path d="M8 12.5l2.5 2.5L16.5 9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 21a9 9 0 100-18 9 9 0 000 18z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-900">Applicant profile ready</p>
                                <p class="mt-1 text-xs text-slate-500 sm:text-sm">Create your account during enrollment.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y border-slate-100 bg-slate-50">
            <div class="mx-auto max-w-7xl px-4 py-7 sm:px-6 sm:py-10 lg:px-8">
                <p class="text-center text-xs font-semibold leading-5 text-slate-600 sm:text-left sm:text-sm">TESDA-accredited caregiving NC II training, assessment, and institutional records</p>
                <div class="mt-5 grid grid-cols-2 gap-3 sm:mt-6 sm:grid-cols-4 sm:gap-4">
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white px-4 py-3 text-center text-xs font-bold text-slate-700 sm:rounded-2xl sm:px-5 sm:py-4 sm:text-sm">Admissions</div>
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white px-4 py-3 text-center text-xs font-bold text-slate-700 sm:rounded-2xl sm:px-5 sm:py-4 sm:text-sm">Programs</div>
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white px-4 py-3 text-center text-xs font-bold text-slate-700 sm:rounded-2xl sm:px-5 sm:py-4 sm:text-sm">Schedules</div>
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white px-4 py-3 text-center text-xs font-bold text-slate-700 sm:rounded-2xl sm:px-5 sm:py-4 sm:text-sm">Career Hub</div>
                </div>
            </div>
        </section>

        <section id="programs" class="bg-white py-14 sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="phone-section-heading max-w-3xl">
                    <p class="text-sm font-bold uppercase tracking-[0.14em] text-purple-800">Programs</p>
                    <h2 class="mt-3 text-2xl font-bold leading-tight text-slate-900 sm:mt-4 sm:text-4xl">Official training programs from the MCARE catalog.</h2>
                    <p class="mt-4 text-sm leading-6 text-slate-600 sm:mt-6 sm:text-lg sm:leading-8">These are the published programs currently open for application, including the official fee and required downpayment.</p>
                </div>

                @php
                    $programCards = collect($programs ?? [])->values();
                    $supportCards = collect([
                        [
                            'title' => 'Admissions path',
                            'description' => 'Apply first, keep your application number, then enroll after MCARE approval.',
                            'href' => '#admissions',
                            'cta' => 'View training path',
                            'icon' => 'heart',
                        ],
                        [
                            'title' => 'Alumni Career Hub',
                            'description' => 'Graduate tracking, career support, and partner opportunities after TESDA assessment.',
                            'href' => route('alumni.claim.create'),
                            'cta' => 'See career support',
                            'icon' => 'briefcase',
                        ],
                    ]);
                    $fillerCards = $programCards->count() >= 3
                        ? collect()
                        : $supportCards->take(3 - $programCards->count());
                @endphp
                <div class="landing-program-grid mt-8 grid grid-cols-1 gap-4 sm:mt-14 sm:gap-6 lg:grid-cols-3">
                    @foreach ($programCards as $program)
                        <article class="landing-program-card phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg sm:p-8">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 sm:h-12 sm:w-12 sm:rounded-2xl">
                                <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true">
                                    <path d="M5 20V7a2 2 0 012-2h10a2 2 0 012 2v13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <path d="M8 9h8M8 13h6M5 20h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </div>
                            <h3 class="mt-5 text-xl font-bold text-slate-900 sm:mt-7 sm:text-2xl">{{ $program->name }}</h3>
                            <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-purple-700">{{ $program->code }}</p>
                            <p class="mt-3 text-sm leading-6 text-slate-600 sm:mt-4 sm:text-base sm:leading-7">{{ $program->description ?: 'TESDA-aligned training administered by Mission Care.' }}</p>
                            <p class="mt-4 text-sm font-semibold text-slate-800">₱{{ number_format((float) $program->total_program_fee, 2) }} <span class="font-medium text-slate-500">total</span><span class="mx-2 text-slate-300">·</span>₱{{ number_format((float) $program->downpayment_amount, 2) }} <span class="font-medium text-slate-500">downpayment</span></p>
                            <a href="{{ route('applications.create', ['training_program_id' => $program->id]) }}" class="mt-5 inline-flex text-sm font-semibold text-purple-600 hover:text-purple-700 sm:mt-auto sm:pt-8">Apply for this program</a>
                        </article>
                    @endforeach

                    @foreach ($fillerCards as $support)
                        <article class="landing-program-card phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm transition-all hover:-translate-y-1 hover:shadow-lg sm:p-8">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 sm:h-12 sm:w-12 sm:rounded-2xl">
                                @if ($support['icon'] === 'briefcase')
                                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true">
                                        <path d="M4 7h16v11a2 2 0 01-2 2H6a2 2 0 01-2-2V7z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                        <path d="M8 7V5a2 2 0 012-2h4a2 2 0 012 2v2M9 13h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                @else
                                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true">
                                        <path d="M12 21s-7-4.35-7-10a4 4 0 017-2.65A4 4 0 0119 11c0 5.65-7 10-7 10z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                        <path d="M9 12h2l1-2 1.5 4 1-2H16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                @endif
                            </div>
                            <h3 class="mt-5 text-xl font-bold text-slate-900 sm:mt-7 sm:text-2xl">{{ $support['title'] }}</h3>
                            <p class="mt-3 text-sm leading-6 text-slate-600 sm:mt-4 sm:text-base sm:leading-7">{{ $support['description'] }}</p>
                            <a href="{{ $support['href'] }}" class="mt-5 inline-flex text-sm font-semibold text-purple-600 hover:text-purple-700 sm:mt-auto sm:pt-8">{{ $support['cta'] }}</a>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        <section id="admissions" class="bg-slate-50 py-14 sm:py-32">
            <div class="mx-auto grid max-w-7xl grid-cols-1 gap-8 px-4 sm:gap-12 sm:px-6 lg:grid-cols-2 lg:px-8">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.14em] text-purple-800">Admissions</p>
                    <h2 class="mt-3 text-2xl font-bold leading-tight text-slate-900 sm:mt-4 sm:text-4xl">Apply first. Enroll after your application number is approved.</h2>
                    <p class="mt-4 text-sm leading-6 text-slate-600 sm:mt-6 sm:text-lg sm:leading-8">Submit a short application, keep the issued number, and wait for MCARE review. Approved applicants then complete the TESDA enrollment form and payment.</p>
                    <div class="mt-6 flex flex-col gap-3 sm:mt-10 sm:flex-row sm:flex-wrap">
                        @auth
                            <a href="{{ $accountCtaUrl }}" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 sm:w-auto">{{ $accountCtaLabel }}</a>
                        @else
                            <a href="{{ route('applications.create') }}" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-7 py-3.5 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 sm:w-auto">Apply now</a>
                            <a href="{{ route('payments.show') }}" class="inline-flex w-full items-center justify-center rounded-full border border-slate-200 bg-white px-7 py-3.5 text-sm font-semibold text-slate-700 hover:border-purple-200 hover:text-purple-700 sm:w-auto">Pay with enrollment number</a>
                        @endauth
                        <a href="{{ route('applications.status') }}" class="inline-flex w-full items-center justify-center rounded-full border border-slate-200 bg-white px-7 py-3.5 text-sm font-semibold text-slate-700 hover:border-purple-200 hover:text-purple-700 sm:w-auto">Check status</a>
                    </div>
                </div>

                <div class="space-y-3 sm:space-y-4">
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-4 shadow-sm sm:rounded-2xl sm:p-6">
                        <div class="flex gap-3 sm:gap-5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-50 text-sm font-bold text-purple-600 sm:h-10 sm:w-10">1</span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Submit an application</h3>
                                <p class="mt-1.5 text-sm leading-6 text-slate-600 sm:mt-2 sm:text-base sm:leading-7">MCARE issues an application number so you can check status at any time.</p>
                            </div>
                        </div>
                    </div>
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-4 shadow-sm sm:rounded-2xl sm:p-6">
                        <div class="flex gap-3 sm:gap-5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-50 text-sm font-bold text-purple-600 sm:h-10 sm:w-10">2</span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Enroll after approval</h3>
                                <p class="mt-1.5 text-sm leading-6 text-slate-600 sm:mt-2 sm:text-base sm:leading-7">Enter the approved application number to open the TESDA enrollment form and create your MCARE account.</p>
                            </div>
                        </div>
                    </div>
                    <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-4 shadow-sm sm:rounded-2xl sm:p-6">
                        <div class="flex gap-3 sm:gap-5">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-purple-50 text-sm font-bold text-purple-600 sm:h-10 sm:w-10">3</span>
                            <div>
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Verify payment, then admin review</h3>
                                <p class="mt-1.5 text-sm leading-6 text-slate-600 sm:mt-2 sm:text-base sm:leading-7">The application reaches the admin review queue only after MCARE validates the required payment.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="why" class="bg-white py-14 sm:py-32">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-8 sm:gap-12 lg:grid-cols-3">
                    <div>
                        <p class="text-sm font-bold uppercase tracking-[0.14em] text-purple-800">Why MCARE</p>
                        <h2 class="mt-3 text-2xl font-bold leading-tight text-slate-900 sm:mt-4 sm:text-3xl">Training, assessment, and official learner records.</h2>
                    </div>
                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 sm:gap-5">
                            <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm sm:p-7">
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Clear schedules</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600 sm:mt-3 sm:text-base sm:leading-7">Support for weekday, weekend, and special training batches.</p>
                            </div>
                            <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm sm:p-7">
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Document readiness</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600 sm:mt-3 sm:text-base sm:leading-7">Prepared for applicant requirements, verification status, and admin notes.</p>
                            </div>
                            <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm sm:p-7">
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Learning records</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600 sm:mt-3 sm:text-base sm:leading-7">A foundation for modules, progress tracking, certificates, and digital records.</p>
                            </div>
                            <div class="phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm sm:p-7">
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">Career continuity</h3>
                                <p class="mt-2 text-sm leading-6 text-slate-600 sm:mt-3 sm:text-base sm:leading-7">Alumni profiles can continue after training for employment and job placement updates.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="discover" class="relative overflow-hidden border-y border-purple-100 py-14 sm:py-20">
            <!-- Path: resources/views/landing/home.blade.php | Label: Discover bottom radial background layer -->
            <div class="discover-radial-bg absolute inset-0 z-0"></div>
            <div class="relative z-10 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <p class="discover-fade text-sm font-bold uppercase tracking-[0.14em] text-purple-800">Public updates</p>
                        <h2 class="discover-fade mt-3 max-w-3xl text-2xl font-bold leading-tight text-slate-900 sm:mt-4 sm:text-3xl">Training notices and official social channels.</h2>
                        <p class="discover-fade mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:mt-4 sm:text-base sm:leading-7">Follow Mission Care for program notices, class activities, and enrollment announcements.</p>
                    </div>

                    <x-landing-social-links :links="$socialLinks" class="discover-fade" />
                </div>

                <div class="mt-8 grid grid-cols-1 gap-4 sm:mt-12 sm:gap-6 lg:grid-cols-3">
                    @forelse ($publicUpdates as $update)
                        <article class="discover-fade phone-soft-card overflow-hidden rounded-xl border border-slate-100 bg-white shadow-sm sm:rounded-2xl">
                            <div class="aspect-video bg-slate-100">
                                <iframe
                                    src="{{ $update->embedSrc() }}"
                                    class="h-full w-full border-0"
                                    loading="lazy"
                                    referrerpolicy="strict-origin-when-cross-origin"
                                    allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                                    allowfullscreen
                                    title="{{ $update->title }}">
                                </iframe>
                            </div>
                            <div class="p-4 sm:p-5">
                                <h3 class="text-base font-bold text-slate-900 sm:text-lg">{{ $update->title }}</h3>
                                @if ($update->description)
                                    <p class="mt-2 text-sm leading-6 text-slate-500">{{ $update->description }}</p>
                                @endif
                                <a href="{{ $update->facebook_url }}" target="_blank" rel="noopener noreferrer" class="mt-4 inline-flex text-sm font-bold text-purple-700 hover:text-purple-800">Open on Facebook</a>
                            </div>
                        </article>
                    @empty
                        <article class="discover-fade phone-soft-card rounded-xl border border-slate-100 bg-white p-5 shadow-sm sm:col-span-3 sm:p-8">
                            <h3 class="text-base font-bold text-slate-900 sm:text-lg">No public updates yet</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-500">Mission Care has not published a Facebook update on this page. Follow the official channels above for the latest notices.</p>
                        </article>
                    @endforelse
                </div>
            </div>
        </section>

        <section id="contact" class="border-y border-slate-100 bg-slate-50 py-14 sm:py-20">
            <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-6 px-4 sm:gap-8 sm:px-6 lg:flex-row lg:items-center lg:px-8">
                <div>
                    <p class="text-sm font-bold uppercase tracking-[0.14em] text-purple-800">Mission Care Training and Assessment Center</p>
                    <h2 class="mt-3 text-2xl font-bold leading-tight text-slate-900 sm:mt-4 sm:text-3xl">Begin with an official training application.</h2>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 sm:mt-4 sm:text-base sm:leading-7">Submit your application, keep the number, and enroll after MCARE approval. Payment verification then precedes enrollment review.</p>
                </div>
                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                    @auth
                        <a href="{{ $accountCtaUrl }}" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-8 py-4 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 sm:w-auto">{{ $accountCtaLabel }}</a>
                    @else
                        <a href="{{ route('applications.create') }}" class="inline-flex w-full items-center justify-center rounded-full bg-purple-600 px-8 py-4 text-sm font-semibold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 sm:w-auto">Apply now</a>
                    @endauth
                    <a href="{{ route('applications.status') }}" class="inline-flex w-full items-center justify-center rounded-full border border-slate-200 bg-white px-8 py-4 text-sm font-semibold text-slate-700 hover:border-purple-200 hover:text-purple-700 sm:w-auto">Check status</a>
                </div>
            </div>
        </section>
    </main>

    <!-- Path: resources/views/landing/home.blade.php | Label: Footer radial gradient area -->
    <footer class="relative z-10 overflow-hidden border-t border-purple-100">
        <div class="footer-radial-bg absolute inset-0 z-0"></div>
        <div class="relative z-10 mx-auto flex max-w-7xl flex-col gap-5 px-4 py-10 text-center text-sm text-slate-700 sm:flex-row sm:items-center sm:justify-between sm:px-6 sm:py-12 sm:text-left lg:px-8">
            <div>
                <p class="font-bold text-slate-900">&copy; {{ date('Y') }} Mission Care Training and Assessment Center.</p>
                <p class="mt-1 text-slate-600">TESDA-accredited Caregiving NC II training and assessment.</p>
                <p class="landing-colophon-note">Official public information. Applicant records are processed for training, scholarship, employment, and related institutional purposes.</p>
            </div>
            <x-landing-social-links :links="$socialLinks" variant="footer" class="sm:justify-end" />
        </div>
    </footer>

    <x-landing-chat :enabled="filled(config('services.groq.key'))" />

    <!-- Path: resources/views/landing/home.blade.php | Label: Hero carousel, mobile sidebar, and status polling -->
    <script>
        const mobileSidebar = document.getElementById('mobile-sidebar');
        const mobileMenuOpen = document.getElementById('mobile-menu-open');
        const mobileMenuClose = document.getElementById('mobile-menu-close');
        const mobileMenuOverlay = document.getElementById('mobile-menu-overlay');
        const mobileSidebarLinks = document.querySelectorAll('.mobile-sidebar-link');
        const discoverFadeItems = document.querySelectorAll('.discover-fade');
        const applicantReviewStatus = document.getElementById('applicant-review-status');

        function attachHeroCarousel() {
            const root = document.querySelector('[data-hero-carousel]');
            if (!root) return;

            const slides = Array.from(root.querySelectorAll('[data-hero-slide]'));
            const dots = Array.from(root.querySelectorAll('[data-hero-dot]'));
            if (slides.length < 2) return;

            const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            let index = 0;
            let timer = null;
            let inView = true;
            let startX = 0;

            const show = (next) => {
                index = (next + slides.length) % slides.length;
                slides.forEach((slide, i) => slide.classList.toggle('is-active', i === index));
                dots.forEach((dot, i) => {
                    const active = i === index;
                    dot.classList.toggle('is-active', active);
                    dot.setAttribute('aria-current', active ? 'true' : 'false');
                });
            };

            const stop = () => {
                if (timer) {
                    window.clearInterval(timer);
                    timer = null;
                }
            };

            const start = () => {
                stop();
                if (reduceMotion || !inView || document.hidden) return;
                timer = window.setInterval(() => show(index + 1), 5000);
            };

            dots.forEach((dot, i) => {
                dot.addEventListener('click', () => {
                    show(i);
                    start();
                });
            });

            root.addEventListener('touchstart', (event) => {
                startX = event.changedTouches[0]?.clientX ?? 0;
            }, { passive: true });

            root.addEventListener('touchend', (event) => {
                const distance = (event.changedTouches[0]?.clientX ?? 0) - startX;
                if (Math.abs(distance) < 40) return;
                show(index + (distance < 0 ? 1 : -1));
                start();
            }, { passive: true });

            root.addEventListener('mouseenter', stop);
            root.addEventListener('mouseleave', start);

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    inView = entries.some((entry) => entry.isIntersecting);
                    if (inView) start();
                    else stop();
                }, { threshold: 0.2 });
                observer.observe(root);
            }

            document.addEventListener('visibilitychange', () => {
                if (document.hidden) stop();
                else start();
            });

            show(0);
            start();
        }

        function openMobileSidebar() {
            mobileSidebar?.classList.add('is-open');
            mobileSidebar?.setAttribute('aria-hidden', 'false');
            mobileMenuOpen?.setAttribute('aria-expanded', 'true');
            document.body.classList.add('overflow-hidden');
        }

        function closeMobileSidebar() {
            mobileSidebar?.classList.remove('is-open');
            mobileSidebar?.setAttribute('aria-hidden', 'true');
            mobileMenuOpen?.setAttribute('aria-expanded', 'false');
            document.body.classList.remove('overflow-hidden');
        }

        function syncResponsiveNavigation() {
            if (window.matchMedia('(min-width: 1024px)').matches) {
                closeMobileSidebar();
            }
        }

        function attachDiscoverFadeObserver() {
            if (!discoverFadeItems.length) return;

            if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                discoverFadeItems.forEach((item) => item.classList.add('is-visible'));
                return;
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    entry.target.classList.toggle('is-visible', entry.isIntersecting);
                });
            }, {
                root: null,
                threshold: 0.18,
                rootMargin: '0px 0px -8% 0px',
            });

            discoverFadeItems.forEach((item) => observer.observe(item));
        }

        function attachApplicantReviewPolling() {
            if (!applicantReviewStatus || applicantReviewStatus.dataset.accountTerminal === 'true') return;

            const statusUrl = applicantReviewStatus.dataset.paymentStatusUrl;
            if (!statusUrl) return;
            const checkStatus = async () => {
                if (document.hidden) return;

                try {
                    const response = await fetch(statusUrl, {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        cache: 'no-store',
                    });
                    if (!response.ok) return;

                    const status = await response.json();
                    if (status.account_approved === true || status.account_denied === true) window.location.reload();
                } catch (error) {
                    // Email remains the authoritative fallback when a phone
                    // briefly loses data connectivity.
                }
            };

            window.setInterval(checkStatus, 15000);
            document.addEventListener('visibilitychange', checkStatus);
        }

        attachHeroCarousel();
        attachDiscoverFadeObserver();
        attachApplicantReviewPolling();
        window.addEventListener('resize', syncResponsiveNavigation);
        mobileMenuOpen?.addEventListener('click', openMobileSidebar);
        mobileMenuClose?.addEventListener('click', closeMobileSidebar);
        mobileMenuOverlay?.addEventListener('click', closeMobileSidebar);
        mobileSidebarLinks.forEach((link) => link.addEventListener('click', closeMobileSidebar));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') closeMobileSidebar();
        });
    </script>
</body>
</html>
