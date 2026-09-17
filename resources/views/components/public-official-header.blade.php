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
    $secondaryHref = $secondaryHref ?? $homeUrl;
    $secondaryIsHome = filled($secondaryHref)
        && rtrim((string) $secondaryHref, '/') === rtrim($homeUrl, '/');
    $showSecondaryInMenu = filled($secondaryHref) && filled($secondaryLabel) && ! $secondaryIsHome;

    // Path: resources/views/components/public-official-header.blade.php | Label: shared public nav links
    // Mirrors the landing page so public pages (applications, status, enrollment, payments, alumni) share the same navigation.
    $publicNavLinks = [
        ['href' => $homeUrl.'#programs', 'label' => 'Programs'],
        ['href' => route('applications.create'), 'label' => 'Apply'],
        ['href' => route('applications.status'), 'label' => 'Check status'],
        ['href' => route('payments.show'), 'label' => 'Payments'],
        ['href' => route('alumni.claim.create'), 'label' => 'Alumni claim'],
    ];
@endphp

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
            @foreach ($publicNavLinks as $link)
                <a href="{{ $link['href'] }}" class="auth-topnav-link is-compact-hide">{{ $link['label'] }}</a>
            @endforeach
            @if ($showSecondaryInMenu)
                <a href="{{ $secondaryHref }}" class="auth-topnav-link{{ $secondaryCompactHide ? ' is-compact-hide' : '' }}">{{ $secondaryLabel }}</a>
            @endif
            @if ($primaryHref && $primaryLabel)
                <a href="{{ $primaryHref }}" class="auth-topnav-enroll">{{ $primaryLabel }}</a>
            @endif
            <button type="button" class="public-menu-open" data-public-menu-open aria-controls="public-mobile-menu" aria-expanded="false">
                <span class="sr-only">Open navigation menu</span>
                <img src="{{ asset('assets/images/menu/burger-menu.png') }}" alt="" width="56" height="56">
            </button>
        </div>
    </nav>
</header>

<div id="public-mobile-menu" class="public-mobile-menu" data-public-menu aria-hidden="true">
    <button type="button" class="public-mobile-menu-overlay" data-public-menu-overlay aria-label="Close navigation menu"></button>
    <aside class="public-mobile-menu-panel" aria-label="{{ $navLabel }}">
        <div class="public-mobile-menu-head">
            <a href="{{ $homeUrl }}" class="public-mobile-menu-brand">
                <img src="{{ asset('assets/images/logoicon.png') }}" alt="MCARE Hub" class="landing-brand-mark">
                <span class="mcare-brand">
                    <span class="mcare-mark">MCARE</span>
                    <p class="mcare-brand-name">Mission Care</p>
                </span>
            </a>
            <button type="button" class="public-menu-close" data-public-menu-close>
                <span class="sr-only">Close navigation menu</span>
                <img src="{{ asset('assets/images/menu/x-burger.png') }}" alt="" width="32" height="32">
            </button>
        </div>
        <nav class="public-mobile-menu-links">
            <a href="{{ $homeUrl }}" class="public-mobile-menu-link" data-public-menu-link>Home</a>
            @foreach ($publicNavLinks as $link)
                <a href="{{ $link['href'] }}" class="public-mobile-menu-link" data-public-menu-link>{{ $link['label'] }}</a>
            @endforeach
            @if ($showSecondaryInMenu)
                <a href="{{ $secondaryHref }}" class="public-mobile-menu-link" data-public-menu-link>{{ $secondaryLabel }}</a>
            @endif
        </nav>
        @if ($primaryHref && $primaryLabel)
            <div class="public-mobile-menu-actions">
                <a href="{{ $primaryHref }}" class="public-mobile-menu-primary" data-public-menu-link>{{ $primaryLabel }}</a>
            </div>
        @endif
    </aside>
</div>
