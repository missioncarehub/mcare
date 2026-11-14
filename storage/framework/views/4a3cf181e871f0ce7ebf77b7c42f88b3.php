<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#faf9f7]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo e($title ?? 'MCARE Admin'); ?></title>
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
            if (window.localStorage.getItem('mcare-admin-sidebar-collapsed') === '1') {
                document.documentElement.classList.add('is-admin-sidebar-collapsed');
            }
        } catch (error) {
            // Keep the sidebar expanded when storage is unavailable.
        }
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="dashboard-shell universal-dashboard" data-dashboard-role="admin">
    <div class="dashboard-navigation-progress" aria-hidden="true"></div>
    <?php
        $adminName = auth()->user()?->name ?? 'Admin User';
        $navClass = 'dashboard-nav-link';
        $navIdle = '';
        $navActive = 'is-active';

        $primaryNav = [
            ['label' => 'Dashboard', 'icon' => 'fa-gauge-high', 'href' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')],
            ['label' => 'Applications', 'icon' => 'fa-clipboard-list', 'href' => route('admin.applications.index'), 'active' => request()->routeIs('admin.applications.*')],
            ['label' => 'Enrollments', 'icon' => 'fa-user-check', 'href' => route('admin.enrollments.index'), 'active' => request()->routeIs('admin.enrollments.*')],
            ['label' => 'Payments', 'icon' => 'fa-credit-card', 'href' => route('admin.payment-schedules.index'), 'active' => request()->routeIs('admin.payment-schedules.*')],
            ['label' => 'Announcements', 'icon' => 'fa-bullhorn', 'href' => route('admin.announcements.index'), 'active' => request()->routeIs('admin.announcements.*')],
            ['label' => 'Contact admin', 'icon' => 'fa-message-circle', 'href' => route('admin.contact-messages.index'), 'active' => request()->routeIs('admin.contact-messages.*')],
        ];

        $alumniNav = [
            ['label' => 'Alumni list', 'icon' => 'fa-users', 'href' => route('admin.alumni.index'), 'active' => request()->routeIs('admin.alumni.*')],
            ['label' => 'Alumni claims', 'icon' => 'fa-user-check', 'href' => route('admin.historical-alumni.index'), 'active' => request()->routeIs('admin.historical-alumni.*')],
            ['label' => 'Alumni standing', 'icon' => 'fa-award', 'href' => route('admin.alumni-standing.index'), 'active' => request()->routeIs('admin.alumni-standing.*')],
        ];

        $capstoneNav = [
            ['label' => 'Trainees', 'icon' => 'fa-users', 'href' => route('admin.learning.trainees'), 'active' => request()->routeIs('admin.learning.trainees', 'admin.learning.trainees.show', 'admin.learning.trainees.archive')],
            ['label' => 'LMS Modules', 'icon' => 'fa-book-open', 'href' => route('admin.learning.modules'), 'active' => request()->routeIs('admin.learning.modules', 'admin.learning.modules.quizzes', 'admin.learning.quizzes.*')],
            ['label' => 'Training Records', 'icon' => 'fa-award', 'href' => route('admin.learning.certificates'), 'active' => request()->routeIs('admin.learning.certificates', 'admin.learning.certificate-templates', 'admin.learning.certificate-templates.*', 'admin.learning.documents.*', 'admin.learning.batch-exports.*')],
            ['label' => 'Career Hub', 'icon' => 'fa-briefcase', 'href' => route('admin.learning.alumni-jobs'), 'active' => request()->routeIs('admin.learning.alumni-jobs')],
            ['label' => 'Reports', 'icon' => 'fa-chart-column', 'href' => route('admin.learning.reports'), 'active' => request()->routeIs('admin.learning.reports')],
        ];

        $configNav = [
            ['label' => 'Programs', 'icon' => 'fa-file-text', 'href' => route('admin.training-programs.index'), 'active' => request()->routeIs('admin.training-programs.*')],
            ['label' => 'Batches', 'icon' => 'fa-folder-open', 'href' => route('admin.batches.index'), 'active' => request()->routeIs('admin.batches.*')],
            ['label' => 'Schedules', 'icon' => 'fa-calendar-days', 'href' => route('admin.schedules.index'), 'active' => request()->routeIs('admin.schedules.*')],
            ['label' => 'Public Settings', 'icon' => 'fa-gear', 'href' => route('admin.public-settings.index'), 'active' => request()->routeIs('admin.public-settings.*')],
            ['label' => 'Accounts', 'icon' => 'fa-users', 'href' => route('admin.accounts.index'), 'active' => request()->routeIs('admin.accounts.*')],
        ];

        $configDrawerOpen = collect($configNav)->contains('active', true);
        $learningDrawerOpen = collect($capstoneNav)->contains('active', true);

        $adminMobilePrimary = array_slice($primaryNav, 0, 3);
        $adminMobileMore = array_merge(
            array_slice($primaryNav, 3),
            $alumniNav,
            $capstoneNav,
            $configNav,
            [
                ['label' => 'Admin logs', 'icon' => 'fa-shield-halved', 'href' => route('admin.logs.index'), 'active' => request()->routeIs('admin.logs.*')],
                ['label' => 'Public site', 'icon' => 'fa-arrow-up-right-from-square', 'href' => route('landing'), 'active' => false],
            ],
        );
    ?>

    <aside id="admin-dashboard-sidebar" class="dashboard-sidebar" data-dashboard-sidebar>
        <div class="dashboard-sidebar-header">
            <div class="dashboard-brand flex-1 min-w-0">
                <img src="<?php echo e(asset('assets/images/logoicon.png')); ?>" alt="MCARE Hub" class="dashboard-brand-mark">
                <span class="min-w-0">
                    <span class="dashboard-brand-title">MCARE Hub</span>
                    <span class="dashboard-brand-subtitle">Administration</span>
                </span>
            </div>
            <?php if (isset($component)) { $__componentOriginal463eeb60ad0aac5b6969fbbf3579e993 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal463eeb60ad0aac5b6969fbbf3579e993 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-sidebar-collapse','data' => ['sidebarId' => 'admin-dashboard-sidebar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-sidebar-collapse'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['sidebar-id' => 'admin-dashboard-sidebar']); ?>
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

        <nav class="dashboard-nav" aria-label="Admin navigation">
            <div>
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Operations</p>
                <div class="mt-2 space-y-1">
                    <?php $__currentLoopData = $primaryNav; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e($item['href']); ?>" data-dashboard-prefetch data-dashboard-nav-key="admin-<?php echo e(str($item['label'])->slug()); ?>" class="<?php echo e($navClass); ?> <?php echo e($item['active'] ? $navActive : $navIdle); ?>" <?php if($item['active']): ?> aria-current="page" <?php endif; ?>>
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
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-3">
                <p class="px-3 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Alumni</p>
                <div class="mt-2 space-y-1">
                    <?php $__currentLoopData = $alumniNav; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <a href="<?php echo e($item['href']); ?>" data-dashboard-prefetch data-dashboard-nav-key="admin-<?php echo e(str($item['label'])->slug()); ?>" class="<?php echo e($navClass); ?> <?php echo e($item['active'] ? $navActive : $navIdle); ?>" <?php if($item['active']): ?> aria-current="page" <?php endif; ?>>
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
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div>

            <div class="border-t border-slate-200 pt-3">
                <details class="group" <?php if($learningDrawerOpen): ?> open <?php endif; ?>>
                    <summary class="flex cursor-pointer select-none items-center gap-2 rounded-lg px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 hover:bg-slate-50 hover:text-slate-600">
                        <span class="flex-1">Learning system</span>
                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'chevron-down','class' => 'h-3 w-3 transition group-open:rotate-180']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','class' => 'h-3 w-3 transition group-open:rotate-180']); ?>
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
                    <div class="mt-1 space-y-1">
                        <?php $__currentLoopData = $capstoneNav; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e($item['href']); ?>" data-dashboard-prefetch data-dashboard-nav-key="admin-<?php echo e(str($item['label'])->slug()); ?>" class="<?php echo e($navClass); ?> <?php echo e($item['active'] ? $navActive : $navIdle); ?>" <?php if($item['active']): ?> aria-current="page" <?php endif; ?>>
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
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </details>
            </div>

            <div class="border-t border-slate-200 pt-3">
                <details class="group" <?php if($configDrawerOpen): ?> open <?php endif; ?>>
                    <summary class="flex cursor-pointer select-none items-center gap-2 rounded-lg px-3 py-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400 hover:bg-slate-50 hover:text-slate-600">
                        <span class="flex-1">Configuration</span>
                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'chevron-down','class' => 'h-3 w-3 transition group-open:rotate-180']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','class' => 'h-3 w-3 transition group-open:rotate-180']); ?>
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
                    <div class="mt-1 space-y-1">
                        <?php $__currentLoopData = $configNav; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <a href="<?php echo e($item['href']); ?>" data-dashboard-prefetch data-dashboard-nav-key="admin-<?php echo e(str($item['label'])->slug()); ?>" class="<?php echo e($navClass); ?> <?php echo e($item['active'] ? $navActive : $navIdle); ?>" <?php if($item['active']): ?> aria-current="page" <?php endif; ?>>
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
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </details>
            </div>

            <div class="border-t border-slate-200 pt-3">
                <a href="<?php echo e(route('admin.logs.index')); ?>" data-dashboard-prefetch data-dashboard-nav-key="admin-admin-logs" class="<?php echo e($navClass); ?> <?php echo e(request()->routeIs('admin.logs.*') ? $navActive : $navIdle); ?>">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'shield-halved','class' => 'dashboard-nav-icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'shield-halved','class' => 'dashboard-nav-icon']); ?>
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
                    <span>Admin logs</span>
                </a>
                <a href="<?php echo e(route('landing')); ?>" class="<?php echo e($navClass); ?> <?php echo e($navIdle); ?>">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'arrow-up-right-from-square','class' => 'dashboard-nav-icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-up-right-from-square','class' => 'dashboard-nav-icon']); ?>
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
                    <span>Public site</span>
                </a>
            </div>
        </nav>

        <details class="dashboard-sidebar-footer" data-dashboard-account>
            <summary class="dashboard-account-summary">
                <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => auth()->user(),'name' => $adminName,'class' => 'dashboard-account-avatar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(auth()->user()),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($adminName),'class' => 'dashboard-account-avatar']); ?>
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
                    <span class="block truncate text-sm font-bold text-slate-950"><?php echo e($adminName); ?></span>
                    <span class="block text-xs text-slate-500">Administrator</span>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-account-actions','data' => ['logoutRoute' => route('logout'),'roleLabel' => 'Administrator','showAdminLogs' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-account-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['logout-route' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('logout')),'role-label' => 'Administrator','show-admin-logs' => true]); ?>
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
            <p class="admin-masthead-aside">Official administration system · Authorized personnel only</p>
        </div>
        <header class="dashboard-topbar">
            <div class="dashboard-topbar-inner">
                <div class="flex min-w-0 items-center gap-3">
                    <div class="min-w-0">
                        <p class="dashboard-header-kicker">Mission Care Training and Assessment Center</p>
                        <h1 class="dashboard-header-title"><?php echo e($title ?? 'MCARE Administration'); ?></h1>
                    </div>
                </div>

                <form method="GET" action="<?php echo e(route('admin.enrollments.index')); ?>" class="dashboard-search">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'magnifying-glass','class' => 'text-sm text-slate-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'magnifying-glass','class' => 'text-sm text-slate-400']); ?>
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
                    <input name="search" type="search" placeholder="Search enrollments..." class="min-w-0 flex-1 border-0 bg-transparent py-2.5 text-sm outline-none placeholder:text-slate-400">
                    <button type="submit" class="sr-only">Search</button>
                </form>

                <details class="relative shrink-0 justify-self-end" data-dashboard-account>
                        <summary class="dashboard-account-summary">
                            <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => auth()->user(),'name' => $adminName,'class' => 'dashboard-account-avatar h-9 w-9']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(auth()->user()),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($adminName),'class' => 'dashboard-account-avatar h-9 w-9']); ?>
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
                            <span class="hidden max-w-36 truncate sm:block"><?php echo e($adminName); ?></span>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-account-actions','data' => ['logoutRoute' => route('logout'),'roleLabel' => 'Administrator','showAdminLogs' => true,'careerHubRoute' => route('admin.learning.alumni-jobs')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-account-actions'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['logout-route' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('logout')),'role-label' => 'Administrator','show-admin-logs' => true,'career-hub-route' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('admin.learning.alumni-jobs'))]); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-mobile-navigation','data' => ['primaryItems' => $adminMobilePrimary,'moreItems' => $adminMobileMore,'label' => 'Mobile admin navigation','menuTitle' => 'Admin destinations','role' => 'admin']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-mobile-navigation'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['primary-items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($adminMobilePrimary),'more-items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($adminMobileMore),'label' => 'Mobile admin navigation','menu-title' => 'Admin destinations','role' => 'admin']); ?>
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

        <main class="dashboard-main">
            <?php echo $__env->yieldContent('content'); ?>
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
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\layouts\app.blade.php ENDPATH**/ ?>