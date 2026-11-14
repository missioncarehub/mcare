<?php $__env->startSection('content'); ?>
    <?php
        $fullName = trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name);
        $batchLabel = $batch ? $batch->name.' '.$batch->year : 'Batch to be assigned';
        $scheduleLabel = $batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule to be confirmed';
        $roomLabel = $batch?->roomFor($application->schedule_preference) ?: 'Room TBA';
        $deadline = $application->effectivePaymentDeadline() ?: $batch?->enrollment_ends_at;
        $progressPercent = max(0, min(100, (int) ($stats['progress'] ?? 0)));
    ?>

    <section id="dashboard" class="space-y-6">
        <?php if(auth()->user()?->verified_banner_dismissed_at === null): ?>
            <div class="flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between" role="status">
                <p class="text-sm font-semibold text-emerald-950">Your account has been verified. You can now access your modules and schedule.</p>
                <form method="POST" action="<?php echo e(route('trainee.dashboard.dismiss-banner')); ?>">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="secondary-action">Dismiss</button>
                </form>
            </div>
        <?php endif; ?>
        <div class="dashboard-hero">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                <div class="min-w-0">
                    <span class="dashboard-pill bg-purple-50 text-purple-700 ring-purple-100">Approved trainee</span>
                    <h1 class="mt-4">Welcome back, <?php echo e($application->first_name); ?></h1>
                    <p>
                        Continue your Caregiving NC II training with your approved batch schedule, modules, payment status, and submitted records in one place.
                    </p>
                </div>
                <div class="min-w-0 rounded-xl bg-purple-50 px-5 py-4 ring-1 ring-purple-100">
                    <p class="text-xs font-bold uppercase tracking-wide text-purple-700">Current batch</p>
                    <p class="mt-1 font-display text-2xl font-extrabold text-slate-950"><?php echo e($batchLabel); ?></p>
                </div>
            </div>
        </div>

        
        <div class="dashboard-stat-rail grid grid-cols-2 gap-3 xl:grid-cols-4" data-trainee-stat-cards>
            <a href="<?php echo e(route('trainee.modules.index')); ?>" class="dashboard-stat dashboard-stat-link" data-trainee-stat-card>
                <div class="min-w-0 flex-1" data-trainee-stat-body>
                    <p class="dashboard-stat-label">Training progress</p>
                    <p class="dashboard-stat-value"><?php echo e($progressPercent); ?>%</p>
                    <div class="dashboard-stat-meter" role="progressbar" aria-label="Training progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo e($progressPercent); ?>">
                        <span style="width: <?php echo e($progressPercent); ?>%"></span>
                    </div>
                    <p class="dashboard-stat-help">Continue in classwork</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100" data-trainee-stat-icon>
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'signal','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'signal','class' => 'h-5 w-5']); ?>
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
            </a>
            <a href="<?php echo e(route('trainee.modules.index')); ?>" class="dashboard-stat dashboard-stat-link" data-trainee-stat-card>
                <div class="min-w-0 flex-1" data-trainee-stat-body>
                    <p class="dashboard-stat-label">Available modules</p>
                    <p class="dashboard-stat-value"><?php echo e($stats['modules']); ?></p>
                    <p class="dashboard-stat-help">Open classwork</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700 ring-1 ring-sky-100" data-trainee-stat-icon>
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'book-open','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'book-open','class' => 'h-5 w-5']); ?>
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
            </a>
            <a href="<?php echo e(route('trainee.documents')); ?>" class="dashboard-stat dashboard-stat-link" data-trainee-stat-card>
                <div class="min-w-0 flex-1" data-trainee-stat-body>
                    <p class="dashboard-stat-label">Documents</p>
                    <p class="dashboard-stat-value"><?php echo e($stats['documents']); ?>/5</p>
                    <p class="dashboard-stat-help">Review TESDA files</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-100" data-trainee-stat-icon>
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'file-text','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'file-text','class' => 'h-5 w-5']); ?>
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
            </a>
            <a href="<?php echo e(route('trainee.payments')); ?>" class="dashboard-stat dashboard-stat-link" data-trainee-stat-card>
                <div class="min-w-0 flex-1" data-trainee-stat-body>
                    <p class="dashboard-stat-label">Payment</p>
                    <p class="dashboard-stat-value text-lg leading-tight"><?php echo e($stats['payment']); ?></p>
                    <p class="dashboard-stat-help"><?php echo e($deadline ? 'Deadline '.$deadline->format('M d, Y g:i A') : 'Deadline TBA'); ?></p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100" data-trainee-stat-icon>
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'credit-card','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'credit-card','class' => 'h-5 w-5']); ?>
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
            </a>
        </div>
    </section>

    <section id="modules" class="mt-8 dashboard-panel">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="dashboard-section-kicker">My modules</p>
                <h2 class="dashboard-section-title">Learning materials</h2>
            </div>
            <span class="dashboard-pill bg-purple-50 text-purple-700 ring-purple-100">Private LMS access</span>
        </div>

        <?php
            // Path: resources/views/trainee/dashboard.blade.php | Label: Open modules first, closed after
            // The service already orders by module code and defer status; re-sort here so accessible
            // (open) modules come before locked ones, preserving the underlying sequence within each group.
            $orderedModules = collect($modules)->values()->sortBy(function ($module) use ($classworkAccess) {
                $access = ($classworkAccess ?? collect())[$module->id] ?? ['accessible' => true];
                return $access['accessible'] ?? true ? 0 : 1;
            }, SORT_REGULAR, false)->values();
        ?>
        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <?php $__empty_1 = true; $__currentLoopData = $orderedModules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $moduleProgress = $progressByModule->get($module->id);
                    $access = ($classworkAccess ?? collect())[$module->id] ?? ['accessible' => true, 'blocker' => null];
                    $isLocked = ! ($access['accessible'] ?? true);
                    $blocker = $access['blocker'] ?? null;
                    $progressValue = $isLocked ? 0 : (int) ($moduleProgress?->displayProgressPercent() ?? 0);
                    $isTrainerValidated = $moduleProgress?->isTrainerValidated() ?? false;
                    $isAwaitingEval = ($moduleProgress?->status ?? null) === \App\Models\ModuleProgress::STATUS_AWAITING_EVALUATION;
                    $canMarkAsDone = ! $isLocked
                        && $module->requiresEvaluation()
                        && ! $isTrainerValidated
                        && ! $isAwaitingEval
                        && ! ($module->submodules()->where('is_required', true)->exists());
                ?>
                <article class="dashboard-card p-5<?php echo e($isLocked ? ' opacity-75' : ''); ?>">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-black uppercase tracking-wide text-purple-600"><?php echo e($module->batch ? $module->batch->name.' '.$module->batch->year : 'General module'); ?></p>
                            <h3 class="mt-2 font-display text-xl font-black leading-tight text-slate-900"><?php echo e($module->title); ?></h3>
                        </div>
                        <span class="dashboard-pill <?php echo e($isLocked ? 'bg-rose-50 text-rose-700 ring-rose-100' : ($moduleProgress?->status === 'completed' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : ($moduleProgress ? 'bg-amber-50 text-amber-700 ring-amber-100' : 'bg-slate-50 text-slate-600 ring-slate-100'))); ?>"><?php echo e($isLocked ? 'Locked' : ($moduleProgress ? str($moduleProgress->status)->headline() : 'Not started')); ?></span>
                    </div>
                    <p class="mt-3 line-clamp-3 text-sm leading-6 text-slate-500"><?php echo e($module->description); ?></p>
                    <div class="mt-3 flex items-center gap-2 text-xs font-semibold text-slate-500">
                        <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $module->trainer,'name' => $module->trainer?->name ?? 'MCARE Trainer','class' => 'grid h-7 w-7 place-items-center rounded-full bg-purple-100 text-[10px] font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module->trainer),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module->trainer?->name ?? 'MCARE Trainer'),'class' => 'grid h-7 w-7 place-items-center rounded-full bg-purple-100 text-[10px] font-black text-purple-800']); ?>
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
                        <span class="truncate">Trainer: <?php echo e($module->trainer?->name ?? 'MCARE Trainer'); ?></span>
                    </div>
                    <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo e($progressValue); ?>">
                        <?php if($progressValue > 0): ?>
                            <div class="h-full bg-purple-600" style="width: <?php echo e($progressValue); ?>%"></div>
                        <?php endif; ?>
                    </div>
                    <p class="mt-2 text-xs font-bold text-slate-500"><?php echo e($isLocked && $blocker ? 'Unlocks after a trainer grade on '.($blocker->module_code ?: $blocker->title) : ($progressValue.'% recorded')); ?></p>
                    <?php if($isLocked): ?>
                        <span class="secondary-action mt-5 w-full" aria-disabled="true">Locked</span>
                    <?php else: ?>
                        <a href="<?php echo e(route('trainee.modules.show', $module)); ?>" class="secondary-action mt-5 w-full">
                            Open protected viewer
                        </a>
                        
                        <?php if($canMarkAsDone): ?>
                            <form method="POST" action="<?php echo e(route('trainee.modules.progress', $module)); ?>" class="mt-2" data-confirm="Mark this module as done and send it to your trainer for evaluation? You will not be able to edit it after this.">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('PATCH'); ?>
                                <input type="hidden" name="action" value="submit">
                                <button type="submit" class="primary-action w-full">Mark as done</button>
                            </form>
                        <?php elseif($isAwaitingEval): ?>
                            <span class="dashboard-pill mt-3 w-full text-center bg-amber-50 text-amber-700 ring-amber-100">Awaiting trainer evaluation</span>
                        <?php elseif($isTrainerValidated): ?>
                            <span class="dashboard-pill mt-3 w-full text-center bg-emerald-50 text-emerald-700 ring-emerald-100">Competent · evaluated by trainer</span>
                        <?php endif; ?>
                    <?php endif; ?>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="dashboard-card p-10 text-center md:col-span-2 xl:col-span-3">
                    <p class="font-display text-xl font-black text-slate-900">No modules yet</p>
                    <p class="mt-2 text-sm leading-6 text-slate-500">Your trainer's published materials will appear here once available.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section id="schedule" class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-[360px_1fr]">
        <aside class="dashboard-panel border-amber-100">
            <p class="text-xs font-black uppercase tracking-wide text-amber-600">Announcements</p>
            <h2 class="mt-2 font-display text-2xl font-black text-slate-900">Trainer notices</h2>
            <div class="mt-5 space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $announcements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $announcement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <article class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="font-bold text-slate-900"><?php echo e($announcement->title); ?></p>
                        <p class="mt-2 text-sm leading-6 text-slate-600"><?php echo e($announcement->message); ?></p>
                        <p class="mt-2 text-xs font-bold text-amber-700"><?php echo e($announcement->posted_at?->format('M d, Y g:i A') ?? 'Recently posted'); ?></p>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <article class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                        <p class="font-bold text-slate-900">No announcements yet</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Class announcements will appear here.</p>
                    </article>
                <?php endif; ?>
            </div>
        </aside>

        <section class="dashboard-panel">
            <p class="dashboard-section-kicker">My schedule</p>
            <h2 class="dashboard-section-title">Class details</h2>
            <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Preferred class</p>
                    <p class="mt-2 font-display text-2xl font-black text-slate-900"><?php echo e($application->schedule_preference); ?></p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5 md:col-span-2">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Schedule</p>
                    <p class="mt-2 font-display text-2xl font-black text-slate-900"><?php echo e($scheduleLabel); ?></p>
                    <p class="mt-2 text-sm text-slate-500"><?php echo e($roomLabel); ?></p>
                </div>
                <div class="rounded-2xl bg-purple-50 p-5 ring-1 ring-purple-100 md:col-span-3">
                    <p class="text-xs font-black uppercase tracking-wide text-purple-700">Enrollment/payment deadline</p>
                    <p class="mt-2 font-display text-2xl font-black text-slate-900"><?php echo e($deadline?->format('M d, Y g:i A') ?? 'Deadline TBA'); ?></p>
                </div>
            </div>
        </section>
    </section>

    <section id="payments" class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-[1fr_360px]">
        <div class="dashboard-panel">
            <p class="dashboard-section-kicker">Billing and payments</p>
            <h2 class="dashboard-section-title">Payment summary</h2>
            <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Payment method</p>
                    <p class="mt-2 text-lg font-black text-slate-900"><?php echo e($application->payment_method ? str($application->payment_method)->headline() : 'Not selected'); ?></p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Amount</p>
                    <p class="mt-2 text-lg font-black text-slate-900"><?php echo e($application->payment_currency); ?> <?php echo e(number_format((float) $application->payment_amount, 2)); ?></p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-5 md:col-span-2">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Reference</p>
                    <p class="mt-2 break-all text-lg font-black text-slate-900"><?php echo e($application->latestPaymentReference() ?: 'Reference pending'); ?></p>
                </div>
            </div>
        </div>

        <aside class="dashboard-panel">
            <p class="text-xs font-black uppercase tracking-wide text-purple-600">Need payment action?</p>
            <p class="mt-2 text-sm leading-6 text-slate-500">Use the payment page to review your current online/on-site payment status or receipt.</p>
            <a href="<?php echo e(route('trainee.payments')); ?>" class="primary-action mt-5 w-full">Open payment page</a>
        </aside>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainee.layouts.app', ['title' => 'Trainee Dashboard'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainee\dashboard.blade.php ENDPATH**/ ?>