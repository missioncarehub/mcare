<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#faf9f7]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title ?? 'MCARE Trainee'); ?></title>
    <?php if (isset($component)) { $__componentOriginalc6a3599bb5002978b92223946fd5c1ba = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc6a3599bb5002978b92223946fd5c1ba = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-theme-head','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-theme-head'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc6a3599bb5002978b92223946fd5c1ba)): ?>
<?php $attributes = $__attributesOriginalc6a3599bb5002978b92223946fd5c1ba; ?>
<?php unset($__attributesOriginalc6a3599bb5002978b92223946fd5c1ba); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc6a3599bb5002978b92223946fd5c1ba)): ?>
<?php $component = $__componentOriginalc6a3599bb5002978b92223946fd5c1ba; ?>
<?php unset($__componentOriginalc6a3599bb5002978b92223946fd5c1ba); ?>
<?php endif; ?>
    <script>
        try {
            if (window.localStorage.getItem('mcare-trainee-sidebar-collapsed') === '1') {
                document.documentElement.classList.add('is-admin-sidebar-collapsed');
            }
        } catch (error) {
            // Keep the sidebar expanded when storage is unavailable.
        }
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="dashboard-shell universal-dashboard" data-dashboard-role="trainee">
    <div class="dashboard-navigation-progress" aria-hidden="true"></div>
    <?php
        $navItem = 'dashboard-nav-link';
        $traineeName = auth()->user()?->name ?? 'Trainee';
        $isGraduate = auth()->user()?->isGraduate() ?? false;
        // Path: resources/views/trainee/layouts/app.blade.php | Label: Hide LMS links until admin approves the enrollment
        $isApprovedTrainee = auth()->user()?->hasApprovedEnrollment() ?? false;
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
            ['label' => 'Applications', 'short' => 'Applications', 'icon' => 'clipboard-list', 'href' => route('trainee.applications'), 'active' => request()->routeIs('trainee.applications')],
            ['label' => 'Career History', 'short' => 'Career History', 'icon' => 'fa-award', 'href' => route('trainee.achievements'), 'active' => request()->routeIs('trainee.achievements')],
            ['label' => 'Grades', 'short' => 'Grades', 'icon' => 'fa-chart-column', 'href' => route('trainee.grades'), 'active' => request()->routeIs('trainee.grades')],
            ['label' => 'Calendar', 'short' => 'Calendar', 'icon' => 'fa-calendar-days', 'href' => route('trainee.schedule'), 'active' => request()->routeIs('trainee.schedule')],
        ] : ($isApprovedTrainee ? [
            ['label' => 'Stream', 'short' => 'Stream', 'icon' => 'fa-bell', 'href' => $traineeStreamHref, 'active' => request()->routeIs('trainee.stream'), 'badge' => $unreadAnnouncementCount],
            ['label' => 'Classwork', 'short' => 'Classwork', 'icon' => 'fa-book-open', 'href' => route('trainee.modules.index'), 'active' => request()->routeIs('trainee.modules.*', 'trainee.quizzes.*', 'trainee.quiz-attempts.*')],
            ['label' => 'Calendar', 'short' => 'Calendar', 'icon' => 'fa-calendar-days', 'href' => route('trainee.schedule'), 'active' => request()->routeIs('trainee.schedule')],
        ] : [
            // Path: resources/views/trainee/layouts/app.blade.php | Label: Pre-approval nav (no LMS / classwork)
            ['label' => 'Calendar', 'short' => 'Calendar', 'icon' => 'fa-calendar-days', 'href' => route('trainee.schedule'), 'active' => request()->routeIs('trainee.schedule')],
        ]);
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
            ? ['Home', 'Career Hub', 'Applications', 'Career History']
            : ($isApprovedTrainee ? ['Home', 'Stream', 'Classwork'] : ['Home', 'Payments', 'Documents']);
        $traineeMobilePrimary = collect($traineeMobileLabels)
            ->map(fn (string $label) => $traineeAllNav->get($label))
            ->filter()
            ->values()
            ->all();
        $traineeMobileMore = $traineeAllNav
            ->reject(fn (array $item, string $label) => in_array($label, $traineeMobileLabels, true))
            ->values()
            ->all();
    ?>

    <aside id="trainee-dashboard-sidebar" class="dashboard-sidebar" data-dashboard-sidebar>
        <div class="dashboard-sidebar-header">
            <div class="dashboard-brand flex-1 min-w-0">
                <img src="<?php echo e(asset('assets/images/logoicon.png')); ?>" alt="MCARE Hub" class="dashboard-brand-mark">
                <span class="min-w-0">
                    <span class="dashboard-brand-title">MCARE Hub</span>
                    <span class="dashboard-brand-subtitle">Trainee Portal</span>
                </span>
            </div>
            <?php if (isset($component)) { $__componentOriginal463eeb60ad0aac5b6969fbbf3579e993 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal463eeb60ad0aac5b6969fbbf3579e993 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-sidebar-collapse','data' => ['sidebarId' => 'trainee-dashboard-sidebar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-sidebar-collapse'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['sidebar-id' => 'trainee-dashboard-sidebar']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal463eeb60ad0aac5b6969fbbf3579e993)): ?>
<?php $attributes = $__attributesOriginal463eeb60ad0aac5b6969fbbf3579e993; ?>
<?php unset($__attributesOriginal463eeb60ad0aac5b6969fbbf3579e993); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal463eeb60ad0aac5b6969fbbf3579e993)): ?>
<?php $component = $__componentOriginal463eeb60ad0aac5b6969fbbf3579e993; ?>
<?php unset($__componentOriginal463eeb60ad0aac5b6969fbbf3579e993); ?>
<?php endif; ?>
        </div>

        <nav class="dashboard-nav" aria-label="Trainee navigation">
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Classroom</p>
                <div class="mt-2 space-y-1">
                    <?php $__currentLoopData = $traineePrimaryNav; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e($item['href']); ?>" data-dashboard-prefetch data-dashboard-nav-key="trainee-<?php echo e(str($item['label'])->slug()); ?>" class="<?php echo e($navItem); ?> <?php echo e($item['active'] ? 'is-active' : ''); ?>" <?php if($item['active']): ?> aria-current="page" <?php endif; ?>>
                            <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => $item['icon'],'class' => 'dashboard-nav-icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['icon']),'class' => 'dashboard-nav-icon']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                            <span><?php echo e($item['label']); ?></span>
                            <?php if(($item['badge'] ?? 0) > 0): ?>
                                <b class="dashboard-nav-badge"><?php echo e($item['badge'] > 99 ? '99+' : $item['badge']); ?></b>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">My account</p>
                <div class="mt-2 space-y-1">
                    <?php $__currentLoopData = $traineeSecondaryNav; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e($item['href']); ?>" data-dashboard-prefetch data-dashboard-nav-key="trainee-<?php echo e(str($item['label'])->slug()); ?>" class="<?php echo e($navItem); ?> <?php echo e($item['active'] ? 'is-active' : ''); ?>" <?php if($item['active']): ?> aria-current="page" <?php endif; ?>>
                            <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => $item['icon'],'class' => 'dashboard-nav-icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($item['icon']),'class' => 'dashboard-nav-icon']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                            <span><?php echo e($item['label']); ?></span>
                            <?php if(($item['badge'] ?? 0) > 0): ?>
                                <b class="dashboard-nav-badge"><?php echo e($item['badge'] > 99 ? '99+' : $item['badge']); ?></b>
                            <?php endif; ?>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>
        </nav>

        <details class="dashboard-sidebar-footer" data-dashboard-account>
            <summary class="dashboard-account-summary">
                <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => auth()->user(),'name' => $traineeName,'class' => 'dashboard-account-avatar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(auth()->user()),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($traineeName),'class' => 'dashboard-account-avatar']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $attributes = $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $component = $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-bold text-slate-950"><?php echo e($traineeName); ?></span>
                    <span class="block text-xs text-slate-500"><?php echo e(\App\Support\AccountPortal::roleLabelFor(auth()->user())); ?></span>
                </span>
                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'chevron-up','class' => 'dashboard-chevron text-xs text-slate-400 transition']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-up','class' => 'dashboard-chevron text-xs text-slate-400 transition']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
            </summary>
            <div class="dashboard-account-menu">
                <?php if (isset($component)) { $__componentOriginal17718dbb3baee5f28bf6086cd74e1e9a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal17718dbb3baee5f28bf6086cd74e1e9a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-account-actions','data' => ['logoutRoute' => route('logout'),'roleLabel' => 'Trainee']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-account-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['logout-route' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('logout')),'role-label' => 'Trainee']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal17718dbb3baee5f28bf6086cd74e1e9a)): ?>
<?php $attributes = $__attributesOriginal17718dbb3baee5f28bf6086cd74e1e9a; ?>
<?php unset($__attributesOriginal17718dbb3baee5f28bf6086cd74e1e9a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal17718dbb3baee5f28bf6086cd74e1e9a)): ?>
<?php $component = $__componentOriginal17718dbb3baee5f28bf6086cd74e1e9a; ?>
<?php unset($__componentOriginal17718dbb3baee5f28bf6086cd74e1e9a); ?>
<?php endif; ?>
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
                            <span class="dashboard-title-desktop"><?php echo e($title ?? 'MCARE Trainee'); ?></span>
                            <span class="dashboard-title-mobile"><?php echo e(str($title ?? 'MCARE Trainee')->before('|')->trim()); ?></span>
                        </h1>
                    </div>
                </div>
                <details class="relative shrink-0 justify-self-end" data-dashboard-account>
                    <summary class="dashboard-account-summary">
                        <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => auth()->user(),'name' => $traineeName,'class' => 'dashboard-account-avatar h-9 w-9']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(auth()->user()),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($traineeName),'class' => 'dashboard-account-avatar h-9 w-9']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $attributes = $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $component = $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
                        <span class="hidden max-w-36 truncate sm:block"><?php echo e($traineeName); ?></span>
                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'chevron-down','class' => 'dashboard-chevron text-xs text-slate-400 transition']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','class' => 'dashboard-chevron text-xs text-slate-400 transition']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                    </summary>
                    <div class="absolute right-0 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                        <?php if (isset($component)) { $__componentOriginal17718dbb3baee5f28bf6086cd74e1e9a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal17718dbb3baee5f28bf6086cd74e1e9a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-account-actions','data' => ['logoutRoute' => route('logout'),'roleLabel' => 'Trainee']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-account-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['logout-route' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('logout')),'role-label' => 'Trainee']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal17718dbb3baee5f28bf6086cd74e1e9a)): ?>
<?php $attributes = $__attributesOriginal17718dbb3baee5f28bf6086cd74e1e9a; ?>
<?php unset($__attributesOriginal17718dbb3baee5f28bf6086cd74e1e9a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal17718dbb3baee5f28bf6086cd74e1e9a)): ?>
<?php $component = $__componentOriginal17718dbb3baee5f28bf6086cd74e1e9a; ?>
<?php unset($__componentOriginal17718dbb3baee5f28bf6086cd74e1e9a); ?>
<?php endif; ?>
                    </div>
                </details>
            </div>
            <?php if (isset($component)) { $__componentOriginal8b20011e26f59cba8e3d18527f598ea3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal8b20011e26f59cba8e3d18527f598ea3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-mobile-navigation','data' => ['primaryItems' => $traineeMobilePrimary,'moreItems' => $traineeMobileMore,'label' => 'Mobile trainee navigation','menuTitle' => 'Trainee destinations','role' => 'trainee']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-mobile-navigation'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['primary-items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($traineeMobilePrimary),'more-items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($traineeMobileMore),'label' => 'Mobile trainee navigation','menu-title' => 'Trainee destinations','role' => 'trainee']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal8b20011e26f59cba8e3d18527f598ea3)): ?>
<?php $attributes = $__attributesOriginal8b20011e26f59cba8e3d18527f598ea3; ?>
<?php unset($__attributesOriginal8b20011e26f59cba8e3d18527f598ea3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal8b20011e26f59cba8e3d18527f598ea3)): ?>
<?php $component = $__componentOriginal8b20011e26f59cba8e3d18527f598ea3; ?>
<?php unset($__componentOriginal8b20011e26f59cba8e3d18527f598ea3); ?>
<?php endif; ?>
        </header>

        <main class="dashboard-main" data-trainee-page>
            <?php echo $__env->yieldContent('content'); ?>
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
    <?php if (isset($component)) { $__componentOriginald09371189ec3f48f490f7c83af7b6da2 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald09371189ec3f48f490f7c83af7b6da2 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-toasts','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-toasts'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald09371189ec3f48f490f7c83af7b6da2)): ?>
<?php $attributes = $__attributesOriginald09371189ec3f48f490f7c83af7b6da2; ?>
<?php unset($__attributesOriginald09371189ec3f48f490f7c83af7b6da2); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald09371189ec3f48f490f7c83af7b6da2)): ?>
<?php $component = $__componentOriginald09371189ec3f48f490f7c83af7b6da2; ?>
<?php unset($__componentOriginald09371189ec3f48f490f7c83af7b6da2); ?>
<?php endif; ?>
</body>
</html>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views/trainee/layouts/app.blade.php ENDPATH**/ ?>