<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#faf9f7]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'MCARE Trainer' }}</title>
    <x-dashboard-theme-head />
    <script>
        try {
            if (window.localStorage.getItem('mcare-trainer-sidebar-collapsed') === '1') {
                document.documentElement.classList.add('is-admin-sidebar-collapsed');
            }
        } catch (error) {
            // Keep the sidebar expanded when storage is unavailable.
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-shell universal-dashboard" data-dashboard-role="trainer">
    <div class="dashboard-navigation-progress" aria-hidden="true"></div>
    @php
        $trainerName = auth()->user()?->name ?? 'Trainer User';
        $navClass = 'dashboard-nav-link';
        $navIdle = '';
        $navActive = 'is-active';
        $trainerStreamHref = \Illuminate\Support\Facades\Route::has('trainer.stream')
            ? route('trainer.stream')
            : route('trainer.dashboard');
        $trainerPrimaryNav = [
            ['label' => 'Stream', 'short' => 'Stream', 'icon' => 'fa-bell', 'href' => $trainerStreamHref, 'active' => request()->routeIs('trainer.stream', 'trainer.announcements.*')],
            ['label' => 'Classwork', 'short' => 'Classwork', 'icon' => 'fa-book-open', 'href' => route('trainer.resources'), 'active' => request()->routeIs('trainer.resources', 'trainer.modules.*', 'trainer.quizzes.*', 'trainer.assessments')],
            ['label' => 'Calendar', 'short' => 'Calendar', 'icon' => 'fa-calendar-days', 'href' => route('trainer.sessions'), 'active' => request()->routeIs('trainer.sessions')],
        ];
        $trainerSecondaryNav = [
            ['label' => 'Teaching Day', 'icon' => 'fa-gauge-high', 'href' => route('trainer.dashboard'), 'active' => request()->routeIs('trainer.dashboard')],
            ['label' => 'Classes', 'icon' => 'fa-folder-open', 'href' => route('trainer.trainings'), 'active' => request()->routeIs('trainer.trainings')],
            ['label' => 'Attendance', 'icon' => 'fa-clipboard-user', 'href' => route('trainer.attendance.index'), 'active' => request()->routeIs('trainer.attendance.*')],
            ['label' => 'People', 'icon' => 'fa-users', 'href' => route('trainer.trainees'), 'active' => request()->routeIs('trainer.trainees')],
            ['label' => 'Competency Records', 'icon' => 'fa-clipboard-list', 'href' => route('trainer.competencies.index'), 'active' => request()->routeIs('trainer.competencies.*')],
            ['label' => 'Certificates', 'icon' => 'fa-award', 'href' => route('trainer.certificates'), 'active' => request()->routeIs('trainer.certificates')],
            ['label' => 'Reports', 'icon' => 'fa-chart-column', 'href' => route('trainer.reports'), 'active' => request()->routeIs('trainer.reports')],
        ];
        $trainerAllNav = collect(array_merge($trainerPrimaryNav, $trainerSecondaryNav))->keyBy('label');
        $trainerMobileLabels = ['Teaching Day', 'Stream', 'Classwork'];
        $trainerMobilePrimary = collect($trainerMobileLabels)
            ->map(fn (string $label) => $trainerAllNav->get($label))
            ->filter()
            ->values()
            ->all();
        $trainerMobileMore = $trainerAllNav
            ->reject(fn (array $item, string $label) => in_array($label, $trainerMobileLabels, true))
            ->values()
            ->push(['label' => 'Public site', 'icon' => 'fa-arrow-up-right-from-square', 'href' => route('landing'), 'active' => false])
            ->all();
    @endphp

    <aside id="trainer-dashboard-sidebar" class="dashboard-sidebar" data-dashboard-sidebar>
        <div class="dashboard-sidebar-header">
            <div class="dashboard-brand flex-1 min-w-0">
                <img src="{{ asset('assets/images/logoicon.png') }}" alt="MCARE Hub" class="dashboard-brand-mark">
                <span class="min-w-0">
                    <span class="dashboard-brand-title">MCARE Hub</span>
                    <span class="dashboard-brand-subtitle">Trainer Portal</span>
                </span>
            </div>
            <x-dashboard-sidebar-collapse sidebar-id="trainer-dashboard-sidebar" />
        </div>

        <nav class="dashboard-nav" aria-label="Trainer navigation">
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Classroom</p>
                <div class="mt-2 space-y-1">
                    @foreach ($trainerPrimaryNav as $item)
                        <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="trainer-{{ str($item['label'])->slug() }}" class="{{ $navClass }} {{ $item['active'] ? $navActive : $navIdle }}" @if($item['active']) aria-current="page" @endif>
                            <x-dashboard-icon :name="$item['icon']" class="dashboard-nav-icon" />
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Teaching tools</p>
                <div class="mt-2 space-y-1">
                    @foreach ($trainerSecondaryNav as $item)
                        <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="trainer-{{ str($item['label'])->slug() }}" class="{{ $navClass }} {{ $item['active'] ? $navActive : $navIdle }}" @if($item['active']) aria-current="page" @endif>
                            <x-dashboard-icon :name="$item['icon']" class="dashboard-nav-icon" />
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            <div class="border-t border-slate-200 pt-4">
                <a href="{{ route('landing') }}" class="{{ $navClass }} {{ $navIdle }}">
                    <x-dashboard-icon name="arrow-up-right-from-square" class="dashboard-nav-icon" />
                    <span>Public site</span>
                </a>
            </div>
        </nav>

        <details class="dashboard-sidebar-footer" data-dashboard-account>
            <summary class="dashboard-account-summary">
                <x-user-avatar :user="auth()->user()" :name="$trainerName" class="dashboard-account-avatar" />
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-bold text-slate-950">{{ $trainerName }}</span>
                    <span class="block text-xs text-slate-500">Caregiving NC II Trainer</span>
                </span>
                <x-dashboard-icon name="chevron-up" class="dashboard-chevron text-xs text-slate-400 transition" />
            </summary>
            <div class="dashboard-account-menu">
                <x-dashboard-account-actions :logout-route="route('logout')" role-label="Trainer" />
            </div>
        </details>
    </aside>

    <div class="dashboard-layout">
        <div class="admin-masthead">
            <p class="admin-masthead-kicker">TESDA-Accredited Training and Assessment Center</p>
            <p class="admin-masthead-aside">Official trainer system · Authorized personnel only</p>
        </div>
        <header class="dashboard-topbar">
            <div class="dashboard-topbar-inner">
                <div class="dashboard-topbar-start flex min-w-0 items-center gap-3">
                    <div class="min-w-0">
                        <p class="dashboard-header-kicker">Mission Care Training and Assessment Center</p>
                        <h1 class="dashboard-header-title">
                            <span class="dashboard-title-desktop">{{ $title ?? 'MCARE Trainer' }}</span>
                            <span class="dashboard-title-mobile">{{ str($title ?? 'MCARE Trainer')->before('|')->trim() }}</span>
                        </h1>
                    </div>
                </div>

                <form method="GET" action="{{ route('trainer.search') }}" class="dashboard-search" data-trainer-global-search data-suggest-url="{{ route('trainer.search.suggest') }}">
                    <x-dashboard-icon name="magnifying-glass" class="shrink-0 text-sm text-slate-400" />
                    <input
                        name="q"
                        type="search"
                        value="{{ request('q') }}"
                        placeholder="Search pages, people, modules..."
                        maxlength="100"
                        autocomplete="off"
                        aria-label="Search the trainer portal"
                        aria-autocomplete="list"
                        aria-controls="trainer-search-suggest"
                        aria-expanded="false"
                    >
                    <button type="submit" class="sr-only">Search</button>
                    <div id="trainer-search-suggest" class="trainer-search-suggest" hidden role="listbox"></div>
                </form>

                <details class="dashboard-topbar-account relative shrink-0 justify-self-end" data-dashboard-account>
                    <summary class="dashboard-account-summary">
                        <x-user-avatar :user="auth()->user()" :name="$trainerName" class="dashboard-account-avatar h-9 w-9" />
                        <span class="hidden max-w-36 truncate sm:block">{{ $trainerName }}</span>
                        <x-dashboard-icon name="chevron-down" class="dashboard-chevron text-xs text-slate-400 transition" />
                    </summary>
                    <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                        <x-dashboard-account-actions :logout-route="route('logout')" role-label="Trainer" />
                    </div>
                </details>
            </div>
            <x-dashboard-mobile-navigation
                :primary-items="$trainerMobilePrimary"
                :more-items="$trainerMobileMore"
                label="Mobile trainer navigation"
                menu-title="Trainer destinations"
                role="trainer"
            />
        </header>

        <main class="dashboard-main">
            @yield('content')
        </main>
        <footer class="admin-colophon">
            <p>Mission Care Training and Assessment Center · Caregiving NC II</p>
            <p>Official institutional records. Use of this system is restricted to assigned MCARE trainers.</p>
        </footer>
    </div>

    <dialog class="lms-confirm-dialog" data-lms-confirm-dialog aria-labelledby="lms-confirm-title">
        <form method="dialog" class="lms-confirm-card">
            <span class="lms-confirm-icon" aria-hidden="true">!</span>
            <h2 id="lms-confirm-title">Confirm action</h2>
            <p data-lms-confirm-message>This action cannot be undone.</p>
            <p data-lms-confirm-detail hidden></p>
            <div class="lms-confirm-actions">
                <button value="cancel" class="secondary-action">Cancel</button>
                <button value="confirm" class="lms-danger-action" data-lms-confirm-action>Continue</button>
            </div>
        </form>
    </dialog>
    <x-dashboard-toasts />
</body>
</html>
