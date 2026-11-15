<?php $__env->startSection('content'); ?>
<?php
    $trainerDisplayName = trim(auth()->user()?->name ?? 'Trainer');
    $followUpCount = $learnerFollowUps->where('needs_action', true)->count();
?>

<div class="space-y-7">
    <header class="flex flex-col gap-5 border-b border-stone-200 pb-7 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="dashboard-section-kicker">Teaching day</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950 sm:text-4xl">Welcome back, <?php echo e($trainerDisplayName); ?></h1>
            <p class="mt-2 text-base text-stone-600">Here is your complete teaching day, organized around the live administrator schedule.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <span class="inline-flex items-center gap-2 font-medium text-stone-700">
                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'calendar','class' => 'text-violet-700']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'calendar','class' => 'text-violet-700']); ?>
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
                <?php echo e(now()->format('l, F j, Y')); ?>

            </span>
            <a href="<?php echo e(route('trainer.sessions')); ?>" class="secondary-action inline-flex items-center justify-center gap-2 text-sm font-bold">
                Full calendar
            </a>
        </div>
    </header>

    <section class="dashboard-panel" aria-labelledby="delivery-snapshot-title">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="dashboard-section-kicker">Learning delivery</p>
                <h2 id="delivery-snapshot-title" class="mt-2 text-xl font-bold text-stone-950">Delivery snapshot</h2>
                <p class="mt-1 text-sm text-stone-600">Manage learning files and audiences from Resources.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="<?php echo e(route('trainer.resources')); ?>" class="primary-action inline-flex items-center justify-center text-sm font-bold">Manage resources</a>
                <a href="<?php echo e(route('trainer.trainees')); ?>" class="secondary-action inline-flex items-center justify-center text-sm font-bold">View trainees</a>
            </div>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Published modules</p>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100">
                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'book-open']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'book-open']); ?>
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
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-stone-950"><?php echo e($moduleCount); ?></p>
                <a href="<?php echo e(route('trainer.resources')); ?>" class="mt-2 block text-xs font-bold text-purple-700 hover:text-purple-900">View modules →</a>
            </div>
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Quizzes & Assessments</p>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-100">
                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'clipboard-list']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard-list']); ?>
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
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-amber-700"><?php echo e($quizCount ?? 0); ?></p>
                <a href="<?php echo e(route('trainer.resources')); ?>#assessments-hub" class="mt-2 block text-xs font-bold text-amber-800 hover:text-amber-950">View quizzes →</a>
            </div>
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Assigned learners</p>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700 ring-1 ring-sky-100">
                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'users']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'users']); ?>
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
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-stone-950"><?php echo e($stats['total_trainees'] ?? 0); ?></p>
                <a href="<?php echo e(route('trainer.trainees')); ?>" class="mt-2 block text-xs font-bold text-stone-700 hover:text-stone-900">View roster →</a>
            </div>
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Sessions today</p>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'calendar-days']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'calendar-days']); ?>
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
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-stone-950"><?php echo e($stats['sessions_today'] ?? 0); ?></p>
                <a href="<?php echo e(route('trainer.sessions')); ?>" class="mt-2 block text-xs font-bold text-stone-700 hover:text-stone-900">Open calendar →</a>
            </div>
        </div>
    </section>

    <section class="grid items-start gap-7 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0">
            <div class="mb-5 flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-stone-950">Today's schedule</h2>
                    <p class="mt-1 text-sm text-stone-600">Every session stays visible. Only the status rail animates when a class is currently in progress.</p>
                </div>
                <span class="text-sm font-medium text-stone-500"><?php echo e($teachingTimeline->count()); ?> <?php echo e(\Illuminate\Support\Str::plural('session', $teachingTimeline->count())); ?></span>
            </div>

            <ol class="trainer-day-agenda">
                <?php $__empty_1 = true; $__currentLoopData = $teachingTimeline; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <li class="trainer-day-session is-<?php echo e($item['state']); ?>">
                        <span class="trainer-day-status-dot" aria-hidden="true"></span>
                        <div class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-3">
                                    <time class="text-sm font-bold text-stone-950"><?php echo e($item['time']); ?></time>
                                    <span class="trainer-day-state is-<?php echo e($item['state']); ?>"><?php echo e($item['label']); ?></span>
                                </div>
                                <h3 class="mt-2 font-bold text-stone-950"><?php echo e($item['title']); ?></h3>
                                <p class="mt-1 text-sm text-stone-600"><?php echo e($item['duration']); ?> &middot; <?php echo e($item['training']); ?></p>
                            </div>
                            <div class="min-w-0 sm:max-w-52 sm:text-right">
                                <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Room</p>
                                <p class="mt-1 text-sm font-semibold text-stone-900"><?php echo e($item['room']); ?></p>
                            </div>
                        </div>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <li class="trainer-day-session is-empty">
                        <span class="trainer-day-status-dot" aria-hidden="true"></span>
                        <div>
                            <p class="font-bold text-stone-950">No session scheduled today.</p>
                            <p class="mt-2 text-sm text-stone-600">Open the calendar to review the full month generated from the admin schedule.</p>
                        </div>
                    </li>
                <?php endif; ?>
            </ol>
        </div>

        <aside class="dashboard-panel divide-y divide-stone-200 p-0">
            <section class="p-5" id="learner-follow-up">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-stone-950">Learner follow-up</h2>
                        <p class="mt-1 text-sm text-stone-600">Actions that keep delivery moving.</p>
                    </div>
                    <span class="flex h-8 min-w-8 items-center justify-center rounded-lg bg-amber-100 px-2 text-sm font-bold text-amber-900"><?php echo e($followUpCount); ?></span>
                </div>

                <?php if($learnerFollowUps->isNotEmpty()): ?>
                    <ul class="mt-4 divide-y divide-stone-200">
                        <?php $__currentLoopData = $learnerFollowUps->take(5); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $learner): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="py-4 first:pt-0 last:pb-0">
                                <div class="flex gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-stone-900 text-sm font-bold text-white"><?php echo e($learner['initial']); ?></span>
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-stone-950"><?php echo e($learner['name']); ?></p>
                                        <?php if($learner['is_graduate'] ?? false): ?>
                                            <span class="mt-1 inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-200"><?php echo e($learner['graduate_label'] ?? 'Verified graduate'); ?></span>
                                        <?php endif; ?>
                                        <p class="mt-1 text-sm text-stone-600"><?php echo e($learner['action']); ?></p>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php else: ?>
                    <p class="mt-4 text-sm leading-6 text-stone-600">No learners need follow-up yet.</p>
                <?php endif; ?>
            </section>

            <section class="p-5" aria-labelledby="system-notifications-title">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-violet-700">From admin</p>
                        <h2 id="system-notifications-title" class="mt-1 text-lg font-bold text-stone-950">System notifications</h2>
                    </div>
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'bell','class' => 'mt-1 text-violet-700']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'bell','class' => 'mt-1 text-violet-700']); ?>
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
                </div>

                <?php if($systemNotifications->isNotEmpty()): ?>
                    <ul class="mt-4 space-y-3">
                        <?php $__currentLoopData = $systemNotifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li class="border-l-2 border-violet-300 pl-3">
                                <p class="text-sm font-semibold leading-5 text-stone-900"><?php echo e($notification['title']); ?></p>
                                <p class="mt-1 text-xs text-stone-500"><?php echo e($notification['actor']); ?> &middot; <?php echo e($notification['occurred_at']); ?></p>
                            </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                <?php else: ?>
                    <p class="mt-4 text-sm leading-6 text-stone-600">No new schedule, enrollment, or module notices.</p>
                <?php endif; ?>
            </section>
        </aside>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => 'Teaching Day | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views/trainer/dashboard.blade.php ENDPATH**/ ?>