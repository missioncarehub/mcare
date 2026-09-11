<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#faf9f7]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'MCARE Trainee' }}</title>
    <x-dashboard-theme-head />
    <script>
        try {
            if (window.localStorage.getItem('mcare-trainee-sidebar-collapsed') === '1') {
                document.documentElement.classList.add('is-admin-sidebar-collapsed');
            }
        } catch (error) {
            // Keep the sidebar expanded when storage is unavailable.
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="dashboard-shell universal-dashboard" data-dashboard-role="trainee">
    <div class="dashboard-navigation-progress" aria-hidden="true"></div>
    @php
        $navItem = 'dashboard-nav-link';
        $traineeName = auth()->user()?->name ?? 'Trainee';
        $isGraduate = auth()->user()?->isGraduate() ?? false;
        $unreadAnnouncementCount = auth()->user()?->unreadNotifications()
            ->whereIn('type', [
                \App\Notifications\AdminAnnouncementNotification::class,
                \App\Notifications\LmsAnnouncementPublished::class,
            ])
            ->count() ?? 0;
        $traineeStreamHref = \Illuminate\Support\Facades\Route::has('trainee.stream')
            ? route('trainee.stream')
            : route('trainee.dashboard');
        $traineePrimaryNav = $isGraduate ? [
            ['label' => 'Career Hub', 'short' => 'Career Hub', 'icon' => 'fa-briefcase', 'href' => route('trainee.career-hub'), 'active' => request()->routeIs('trainee.career-hub')],
            ['label' => 'Grades', 'short' => 'Grades', 'icon' => 'fa-chart-column', 'href' => route('trainee.grades'), 'active' => request()->routeIs('trainee.grades')],
            ['label' => 'Calendar', 'short' => 'Calendar', 'icon' => 'fa-calendar-days', 'href' => route('trainee.schedule'), 'active' => request()->routeIs('trainee.schedule')],
        ] : [
            ['label' => 'Stream', 'short' => 'Stream', 'icon' => 'fa-bell', 'href' => $traineeStreamHref, 'active' => request()->routeIs('trainee.stream'), 'badge' => $unreadAnnouncementCount],
            ['label' => 'Classwork', 'short' => 'Classwork', 'icon' => 'fa-book-open', 'href' => route('trainee.modules.index'), 'active' => request()->routeIs('trainee.modules.*', 'trainee.quizzes.*', 'trainee.quiz-attempts.*')],
            ['label' => 'Calendar', 'short' => 'Calendar', 'icon' => 'fa-calendar-days', 'href' => route('trainee.schedule'), 'active' => request()->routeIs('trainee.schedule')],
        ];
        $traineeSecondaryNav = $isGraduate ? [
            ['label' => 'Home', 'icon' => 'fa-house', 'href' => route('trainee.dashboard'), 'active' => request()->routeIs('trainee.dashboard')],
            ['label' => 'Documents', 'icon' => 'fa-folder-open', 'href' => route('trainee.documents'), 'active' => request()->routeIs('trainee.documents')],
        ] : [
            ['label' => 'Home', 'icon' => 'fa-house', 'href' => route('trainee.dashboard'), 'active' => request()->routeIs('trainee.dashboard')],
            ['label' => 'Payments', 'icon' => 'fa-credit-card', 'href' => route('trainee.payments'), 'active' => request()->routeIs('trainee.payments')],
            ['label' => 'Documents', 'icon' => 'fa-folder-open', 'href' => route('trainee.documents'), 'active' => request()->routeIs('trainee.documents')],
        ];
        $traineeAllNav = collect(array_merge($traineePrimaryNav, $traineeSecondaryNav))->keyBy('label');
        $traineeMobileLabels = $isGraduate
            ? ['Home', 'Career Hub', 'Grades', 'Documents']
            : ['Home', 'Stream', 'Classwork'];
        $traineeMobilePrimary = collect($traineeMobileLabels)
            ->map(fn (string $label) => $traineeAllNav->get($label))
            ->filter()
            ->values()
            ->all();
        $traineeMobileMore = $traineeAllNav
            ->reject(fn (array $item, string $label) => in_array($label, $traineeMobileLabels, true))
            ->values()
            ->all();
    @endphp

    <aside id="trainee-dashboard-sidebar" class="dashboard-sidebar" data-dashboard-sidebar>
        <div class="dashboard-sidebar-header">
            <div class="dashboard-brand flex-1 min-w-0">
                <img src="{{ asset('assets/images/logoicon.png') }}" alt="MCARE Hub" class="dashboard-brand-mark">
                <span class="min-w-0">
                    <span class="dashboard-brand-title">MCARE Hub</span>
                    <span class="dashboard-brand-subtitle">Trainee Portal</span>
                </span>
            </div>
            <x-dashboard-sidebar-collapse sidebar-id="trainee-dashboard-sidebar" />
        </div>

        <nav class="dashboard-nav" aria-label="Trainee navigation">
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Classroom</p>
                <div class="mt-2 space-y-1">
                    @foreach ($traineePrimaryNav as $item)
                        <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="trainee-{{ str($item['label'])->slug() }}" class="{{ $navItem }} {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>
                            <x-dashboard-icon :name="$item['icon']" class="dashboard-nav-icon" />
                            <span>{{ $item['label'] }}</span>
                            @if(($item['badge'] ?? 0) > 0)
                                <b class="dashboard-nav-badge">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</b>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">My account</p>
                <div class="mt-2 space-y-1">
                    @foreach ($traineeSecondaryNav as $item)
                        <a href="{{ $item['href'] }}" data-dashboard-prefetch data-dashboard-nav-key="trainee-{{ str($item['label'])->slug() }}" class="{{ $navItem }} {{ $item['active'] ? 'is-active' : '' }}" @if($item['active']) aria-current="page" @endif>
                            <x-dashboard-icon :name="$item['icon']" class="dashboard-nav-icon" />
                            <span>{{ $item['label'] }}</span>
                            @if(($item['badge'] ?? 0) > 0)
                                <b class="dashboard-nav-badge">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</b>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>

        <details class="dashboard-sidebar-footer" data-dashboard-account>
            <summary class="dashboard-account-summary">
                <x-user-avatar :user="auth()->user()" :name="$traineeName" class="dashboard-account-avatar" />
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-bold text-slate-950">{{ $traineeName }}</span>
                    <span class="block text-xs text-slate-500">{{ \App\Support\AccountPortal::roleLabelFor(auth()->user()) }}</span>
                </span>
                <x-dashboard-icon name="chevron-up" class="dashboard-chevron text-xs text-slate-400 transition" />
            </summary>
            <div class="dashboard-account-menu">
                <x-dashboard-account-actions :logout-route="route('logout')" role-label="Trainee" />
            </div>
        </details>
    </aside>

    <div class="dashboard-layout">
        <div class="admin-masthead">
            <p class="admin-masthead-kicker">TESDA-Accredited Training and Assessment Center</p>
            <p class="admin-masthead-aside">Official trainee learning system · Enrolled learners only</p>
        </div>
        <header class="dashboard-topbar">
            <div class="dashboard-topbar-inner">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="min-w-0">
                        <p class="dashboard-header-kicker">Mission Care Training and Assessment Center</p>
                        <h1 class="dashboard-header-title">
                            <span class="dashboard-title-desktop">{{ $title ?? 'MCARE Trainee' }}</span>
                            <span class="dashboard-title-mobile">{{ str($title ?? 'MCARE Trainee')->before('|')->trim() }}</span>
                        </h1>
                    </div>
                </div>
                <details class="relative shrink-0 justify-self-end" data-dashboard-account>
                    <summary class="dashboard-account-summary">
                        <x-user-avatar :user="auth()->user()" :name="$traineeName" class="dashboard-account-avatar h-9 w-9" />
                        <span class="hidden max-w-36 truncate sm:block">{{ $traineeName }}</span>
                        <x-dashboard-icon name="chevron-down" class="dashboard-chevron text-xs text-slate-400 transition" />
                    </summary>
                    <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                        <x-dashboard-account-actions :logout-route="route('logout')" role-label="Trainee" />
                    </div>
                </details>
            </div>
            <x-dashboard-mobile-navigation
                :primary-items="$traineeMobilePrimary"
                :more-items="$traineeMobileMore"
                label="Mobile trainee navigation"
                menu-title="Trainee destinations"
                role="trainee"
            />
        </header>

        <main class="dashboard-main">
            @if (session('saved'))
                <div class="mb-6 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status" aria-live="polite" data-auto-dismiss="5000"@if (session('saved_icon')) data-flash-icon="{{ session('saved_icon') }}"@endif>{{ session('saved') }}</div>
            @endif

            @if (session('error') || session('alert'))
                <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert">{{ session('error') ?? session('alert') }}</div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-semibold text-rose-800" role="alert">
                    @if($errors->count() === 1)
                        <p>{{ $errors->first() }}</p>
                    @else
                        <ul class="list-disc pl-4 space-y-1">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @yield('content')
        </main>
        <footer class="admin-colophon">
            <p>Mission Care Training and Assessment Center · Caregiving NC II</p>
            <p>Official institutional records. Use of this system is restricted to enrolled MCARE trainees.</p>
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
</body>
</html>
