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
        <div class="auth-topnav-links">
            <a href="{{ $homeUrl }}" class="auth-topnav-link">Home</a>
        </div>
    </nav>
</header>
