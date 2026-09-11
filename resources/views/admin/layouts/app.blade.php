<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#faf9f7]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'MCARE Admin' }}</title>
    <x-dashboard-theme-head />
    <script>
        try {
            if (window.localStorage.getItem('mcare-admin-sidebar-collapsed') === '1') {
                document.documentElement.classList.add('is-admin-sidebar-collapsed');
            }
        } catch (error) {
            // Keep the sidebar expanded when storage is unavailable.
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-shell universal-dashboard" data-dashboard-role="admin">
    <div class="dashboard-navigation-progress" aria-hidden="true"></div>
    @php
        $adminName = auth()->user()?->name ?? 'Admin User';
        $navClass = 'dashboard-nav-link';
        $navIdle = '';
        $navActive = 'is-active';

        $primaryNav = [
            ['label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Applications', 'icon' => 'fa-clipboard-list', 'href' => route('admin.applications.index'), 'active' => request()->routeIs('admin.applications.*')],
            ['label' => 'Enrollments', 'icon' => 'fa-user-check', 'href' => route('admin.enrollments.index'), 'active' => request()->routeIs('admin.enrollments.*')],
            ['label' => 'Alumni claims', 'icon' => 'fa-user-check', 'href' => route('admin.historical-alumni.index'), 'active' => request()->routeIs('admin.historical-alumni.*')],
            ['label' => 'Payments', 'icon' => 'fa-credit-card', 'href' => route('admin.payment-schedules.index'), 'active' => request()->routeIs('admin.payment-schedules.*')],
            ['label' => 'Announcements', 'icon' => 'fa-bullhorn', 'href' => route('admin.announcements.index'), 'active' => request()->routeIs('admin.announcements.*')],
            ['label' => 'Public Settings', 'icon' => 'fa-gear', 'href' => route('admin.public-settings.index'), 'active' => request()->routeIs('admin.public-settings.*')],
            ['label' => 'Programs', 'icon' => 'fa-file-text', 'href' => route('admin.training-programs.index'), 'active' => request()->routeIs('admin.training-programs.*')],
            ['label' => 'Batches', 'icon' => 'fa-folder-open', 'href' => route('admin.batches.index'), 'active' => request()->routeIs('admin.batches.*')],
            ['label' => 'Schedules', 'icon' => 'fa-calendar-days', 'href' => route('admin.schedules.index'), 'active' => request()->routeIs('admin.schedules.*')],
        ];

        $capstoneNav = [
            ['label' => 'Trainees', 'icon' => 'fa-users', 'href' => route('admin.learning.trainees'), 'active' => request()->routeIs('admin.learning.trainees', 'admin.learning.trainees.show')],
            ['label' => 'Attendance', 'icon' => 'fa-clipboard-user', 'href' => route('admin.learning.attendance'), 'active' => request()->routeIs('admin.learning.attendance*')],
            ['label' => 'LMS Modules', 'icon' => 'fa-book-open', 'href' => route('admin.learning.modules'), 'active' => request()->routeIs('admin.learning.modules')],
            ['label' => 'Training Records', 'icon' => 'fa-award', 'href' => route('admin.learning.certificates'), 'active' => request()->routeIs('admin.learning.certificates', 'admin.learning.documents.*', 'admin.learning.batch-exports.*')],
            ['label' => 'Career Hub', 'icon' => 'fa-briefcase', 'href' => route('admin.learning.alumni-jobs'), 'active' => request()->routeIs('admin.learning.alumni-jobs')],
            ['label' => 'Reports', 'icon' => 'fa-chart-column', 'href' => route('admin.learning.reports'), 'active' => request()->routeIs('admin.learning.reports')],
            ['label' => 'Accounts', 'icon' => 'fa-users', 'href' => route('admin.accounts.index'), 'active' => request()->routeIs('admin.accounts.*')],
        ];
        $adminMobilePrimary = array_slice($primaryNav, 0, 3);
        $adminMobileMore = array_merge(
            array_slice($primaryNav, 3),
            $capstoneNav,
            [
                ['label' => 'Admin logs', 'icon' => 'fa-shield-halved', 'href' => route('admin.logs.index'), 'active' => request()->routeIs('admin.logs.*')],
                ['label' => 'Public site', 'icon' => 'fa-arrow-up-right-from-square', 'href' => route('landing'), 'active' => false],
            ],
        );
    @endphp

    <aside id="admin-dashboard-sidebar" class="dashboard-sidebar" data-dashboard-sidebar>
        <div class="dashboard-sidebar-header">
            <div class="dashboard-brand flex-1 min-w-0">
                <img src="{{ asset('assets/images/logoicon.png') }}" alt="MCARE Hub" class="dashboard-brand-mark">
                <span class="min-w-0">
                    <span class="dashboard-brand-title">MCARE Hub</span>
                    <span class="dashboard-brand-subtitle">Administration</span>
                </span>
            </div>
            <x-dashboard-sidebar-collapse sidebar-id="admin-dashboard-sidebar" />
        </div>

        <nav class="dashboard-nav" aria-label="Admin navigation">
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Operations</p>
                <div class="mt-2 space-y-1">
                    @foreach ($primaryNav as $item)
                        <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="admin-{{ str($item['label'])->slug() }}" class="{{ $navClass }} {{ $item['active'] ? $navActive : $navIdle }}" @if($item['active']) aria-current="page" @endif>
                            <x-dashboard-icon :name="$item['icon']" class="dashboard-nav-icon" />
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Learning system</p>
                <div class="mt-2 space-y-1">
                    @foreach ($capstoneNav as $item)
                        <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="admin-{{ str($item['label'])->slug() }}" class="{{ $navClass }} {{ $item['active'] ? $navActive : $navIdle }}" @if($item['active']) aria-current="page" @endif>
                            <x-dashboard-icon :name="$item['icon']" class="dashboard-nav-icon" />
                            <span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="border-t border-slate-200 pt-4">
                <a href="{{ route('admin.logs.index') }}" data-dashboard-prefetch data-dashboard-nav-key="admin-admin-logs" class="{{ $navClass }} {{ request()->routeIs('admin.logs.*') ? $navActive : $navIdle }}">
                    <x-dashboard-icon name="shield-halved" class="dashboard-nav-icon" />
                    <span>Admin logs</span>
                </a>
                <a href="{{ route('landing') }}" class="{{ $navClass }} {{ $navIdle }}">
                    <x-dashboard-icon name="arrow-up-right-from-square" class="dashboard-nav-icon" />
                    <span>Public site</span>
                </a>
            </div>
        </nav>

        <details class="dashboard-sidebar-footer" data-dashboard-account>
            <summary class="dashboard-account-summary">
                <x-user-avatar :user="auth()->user()" :name="$adminName" class="dashboard-account-avatar" />
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-bold text-slate-950">{{ $adminName }}</span>
                    <span class="block text-xs text-slate-500">Administrator</span>
                </span>
                <x-dashboard-icon name="chevron-up" class="dashboard-chevron text-xs text-slate-400 transition" />
            </summary>
            <div class="dashboard-account-menu">
                <x-dashboard-account-actions :logout-route="route('logout')" role-label="Administrator" :show-admin-logs="true" />
            </div>
        </details>
    </aside>

    <div class="dashboard-layout">
        <div class="admin-masthead">
            <p class="admin-masthead-kicker">TESDA-Accredited Training and Assessment Center</p>
            <p class="admin-masthead-aside">Official administration system · Authorized personnel only</p>
        </div>
        <header class="dashboard-topbar">
            <div class="dashboard-topbar-inner">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="min-w-0">
                        <p class="dashboard-header-kicker">Mission Care Training and Assessment Center</p>
                        <h1 class="dashboard-header-title">{{ $title ?? 'MCARE Administration' }}</h1>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.enrollments.index') }}" class="dashboard-search">
                    <x-dashboard-icon name="magnifying-glass" class="text-sm text-slate-400" />
                    <input name="search" type="search" placeholder="Search enrollments..." class="min-w-0 flex-1 border-0 bg-transparent py-2.5 text-sm outline-none placeholder:text-slate-400">
                    <button type="submit" class="sr-only">Search</button>
                </form>

                <details class="relative shrink-0 justify-self-end" data-dashboard-account>
                        <summary class="dashboard-account-summary">
                            <x-user-avatar :user="auth()->user()" :name="$adminName" class="dashboard-account-avatar h-9 w-9" />
                            <span class="hidden max-w-36 truncate sm:block">{{ $adminName }}</span>
                            <x-dashboard-icon name="chevron-down" class="dashboard-chevron text-xs text-slate-400 transition" />
                        </summary>
                        <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                            <x-dashboard-account-actions :logout-route="route('logout')" role-label="Administrator" :show-admin-logs="true" :career-hub-route="route('admin.learning.alumni-jobs')" />
                        </div>
                </details>
            </div>

            <x-dashboard-mobile-navigation
                :primary-items="$adminMobilePrimary"
                :more-items="$adminMobileMore"
                label="Mobile admin navigation"
                menu-title="Admin destinations"
                role="admin"
            />
        </header>

        <main class="dashboard-main">
            @yield('content')
        </main>
        <footer class="admin-colophon">
            <p>Mission Care Training and Assessment Center · Caregiving NC II</p>
            <p>Official institutional records. Use of this system is restricted to authorized MCARE administrators.</p>
        </footer>
    </div>
    <script>
        (() => {
            // Filter selects and date fields immediately; text search waits
            // briefly so typing does not submit a request for every letter.
            document.querySelectorAll('form[data-auto-filter]').forEach((form) => {
                let searchTimer = null;
                let submitted = false;

                const submitFilters = () => {
                    if (submitted) return;
                    submitted = true;
                    form.classList.add('is-filtering');
                    form.requestSubmit();
                };

                form.querySelectorAll('select, input[type="date"]').forEach((field) => {
                    field.addEventListener('change', submitFilters);
                });

                form.querySelectorAll('input[type="search"], input[name="search"]').forEach((field) => {
                    field.addEventListener('input', () => {
                        window.clearTimeout(searchTimer);
                        const value = field.value.trim();
                        if (value.length > 0 && value.length < 2) return;
                        searchTimer = window.setTimeout(submitFilters, 500);
                    });
                });
            });
        })();
    </script>

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
