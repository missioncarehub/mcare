@props([
    'mastheadAside' => 'Caregiving NC II · Official public information',
    'navLabel' => 'Site navigation',
    'secondaryHref' => null,
    'secondaryLabel' => 'Public site',
    'secondaryCompactHide' => false,
    'primaryHref' => null,
    'primaryLabel' => null,
])

@php
    $homeUrl = route('landing');
@endphp

{{-- Public inner pages keep a single Home control. Extra destinations stay on the landing page. --}}
<header {{ $attributes->class('auth-site-header') }} data-header-scroll-border>
    <div class="landing-masthead">
        <p class="landing-masthead-kicker">TESDA-Accredited Training and Assessment Center</p>
        <p class="landing-masthead-aside">{{ $mastheadAside }}</p>
    </div>
    <nav class="auth-topnav" aria-label="{{ $navLabel }}">
        <a href="{{ $homeUrl }}" class="flex min-w-0 items-center gap-3">
            <img src="{{ asset('assets/images/logoicon.png') }}" alt="MCARE Hub" class="landing-brand-mark">
            <span class="mcare-brand">
                <span class="mcare-mark">MCARE</span>
                <p class="mcare-brand-name">Mission Care</p>
            </span>
        </a>
        <div class="auth-topnav-links flex items-center gap-2 sm:gap-3">
            <a href="{{ $homeUrl }}" class="auth-topnav-link">Home</a>
            @if (! request()->routeIs('applications.status'))
                <a href="{{ route('applications.status') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-purple-200 bg-purple-50/80 px-3 py-1.5 text-xs sm:text-sm font-bold text-purple-700 hover:bg-purple-100 transition">
                    <x-dashboard-icon name="magnifying-glass" class="w-3.5 h-3.5" />
                    <span>Check status</span>
                </a>
            @endif
            @if ($primaryHref && $primaryLabel && $primaryHref !== route('applications.status'))
                <a href="{{ $primaryHref }}" class="primary-action text-xs sm:text-sm px-3 sm:px-4 py-1.5">{{ $primaryLabel }}</a>
            @endif
            @if ($secondaryHref && $secondaryLabel && $secondaryHref !== $homeUrl && ! $secondaryCompactHide)
                <a href="{{ $secondaryHref }}" class="secondary-action text-xs sm:text-sm px-3 sm:px-4 py-1.5">{{ $secondaryLabel }}</a>
            @endif
        </div>
    </nav>
</header>
