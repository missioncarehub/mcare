@props([
    'primaryItems' => [],
    'moreItems' => [],
    'label' => 'Mobile navigation',
    'menuTitle' => 'More destinations',
    'role' => 'account',
])

@php
    $menuId = 'dashboard-mobile-menu-'.str($role)->slug();
    $hasMore = count($moreItems) > 0;
    $moreIsActive = collect($moreItems)->contains(fn (array $item): bool => (bool) ($item['active'] ?? false));
    $columnCount = max(1, min(4, count($primaryItems) + ($hasMore ? 1 : 0)));
@endphp

<nav class="dashboard-mobile-bar" style="grid-template-columns: repeat({{ $columnCount }}, minmax(0, 1fr));" aria-label="{{ $label }}">
    @foreach ($primaryItems as $item)
        <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="{{ $role }}-mobile-{{ str($item['label'])->slug() }}" class="dashboard-mobile-link {{ ($item['active'] ?? false) ? 'is-active' : '' }}" @if($item['active'] ?? false) aria-current="page" @endif>
            <x-dashboard-icon :name="$item['icon']" />
            <span class="truncate">{{ $item['short'] ?? $item['label'] }}</span>
            @if(($item['badge'] ?? 0) > 0)
                <b class="dashboard-nav-badge is-mobile">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</b>
            @endif
        </a>
    @endforeach

    @if ($hasMore)
        <button type="button" class="dashboard-mobile-link {{ $moreIsActive ? 'is-active' : '' }}" data-dashboard-mobile-menu-open="{{ $menuId }}" aria-haspopup="dialog" aria-controls="{{ $menuId }}">
            <x-dashboard-icon name="bars" />
            <span>More</span>
        </button>
    @endif
</nav>

@if ($hasMore)
    <dialog id="{{ $menuId }}" class="dashboard-mobile-menu" data-dashboard-mobile-menu aria-labelledby="{{ $menuId }}-title">
        <section class="dashboard-mobile-menu-card">
            <header class="dashboard-mobile-menu-header">
                <div>
                    <p class="dashboard-section-kicker">{{ str($role)->headline() }} portal</p>
                    <h2 id="{{ $menuId }}-title" class="dashboard-mobile-menu-title">{{ $menuTitle }}</h2>
                </div>
                <button type="button" class="dashboard-mobile-menu-close" data-dashboard-mobile-menu-close aria-label="Close navigation menu">
                    <x-dashboard-icon name="xmark" />
                </button>
            </header>

            <nav class="dashboard-mobile-menu-list" aria-label="{{ $menuTitle }}">
                @foreach ($moreItems as $item)
                    <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="{{ $role }}-more-{{ str($item['label'])->slug() }}" class="dashboard-mobile-menu-link {{ ($item['active'] ?? false) ? 'is-active' : '' }}" @if($item['active'] ?? false) aria-current="page" @endif>
                        <span class="dashboard-mobile-menu-icon"><x-dashboard-icon :name="$item['icon']" /></span>
                        <span>{{ $item['label'] }}</span>
                        <x-dashboard-icon name="chevron-right" class="ml-auto text-slate-400" />
                    </a>
                @endforeach
            </nav>
        </section>
    </dialog>
@endif
