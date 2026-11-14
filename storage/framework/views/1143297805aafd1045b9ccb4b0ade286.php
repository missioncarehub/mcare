<?php $__env->startSection('content'); ?>
<?php
    $classworkAccess = $classworkAccess ?? collect();

    // Path: resources/views/trainee/modules/index.blade.php | Label: Open (accessible) modules first, closed after
    $orderedModules = collect($modules)->values()->sortBy(function ($module) use ($classworkAccess) {
        $access = ($classworkAccess ?? collect())[$module->id] ?? ['accessible' => true];
        return $access['accessible'] ?? true ? 0 : 1;
    }, SORT_REGULAR, false)->values();

    $categories = $orderedModules->groupBy(function ($module) {
        return match($module->competency_category) {
            'core' => '1. Core Competencies',
            'common' => '2. Common Competencies',
            'basic' => '3. Basic Competencies',
            default => '4. Learning Materials',
        };
    })->sortKeys();

    $completedCount = collect($modules)->filter(
        fn ($module) => ($progressByModule->get($module->id)?->status === 'completed' || $progressByModule->get($module->id)?->competency_outcome === 'competent')
    )->count();
?>

<div class="lms-page" data-lms-classwork data-lms-role="trainee">
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Classwork</p>
            <h1>Modules</h1>
            <p>Complete units in order.</p>
        </div>
        <div class="lms-compact-progress" aria-label="<?php echo e($completedCount); ?> of <?php echo e(collect($modules)->count()); ?> modules completed">
            <strong><?php echo e($completedCount); ?>/<?php echo e(collect($modules)->count()); ?></strong>
            <span>completed</span>
        </div>
    </header>

    <div class="lms-topic-list">
        <?php $__empty_1 = true; $__currentLoopData = $categories; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $categoryName => $catModules): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <section class="lms-topic-section">
                <header class="lms-topic-heading">
                    <h2><?php echo e($categoryName); ?></h2>
                    <span><?php echo e($catModules->count()); ?> <?php echo e(str('unit')->plural($catModules->count())); ?></span>
                </header>
                <div class="lms-trainee-classwork-list">
                    <?php $__currentLoopData = $catModules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php
                            $moduleProgress = $progressByModule->get($module->id);
                            $access = $classworkAccess[$module->id] ?? ['accessible' => true, 'blocker' => null];
                            $isLocked = ! ($access['accessible'] ?? true);
                            $blocker = $access['blocker'] ?? null;
                            $progressValue = $isLocked ? 0 : (int) ($moduleProgress?->displayProgressPercent() ?? 0);
                            $isCompetent = $moduleProgress?->competency_outcome === 'competent';
                            $isCompleted = $moduleProgress?->status === 'completed' || $isCompetent;
                            $isMaterialOnly = !$module->requiresEvaluation();
                            $isAwaitingEval = ($moduleProgress?->status ?? null) === \App\Models\ModuleProgress::STATUS_AWAITING_EVALUATION;
                            $isTrainerValidated = $moduleProgress?->isTrainerValidated() ?? false;
                            $canMarkAsDone = ! $isLocked
                                && $module->requiresEvaluation()
                                && ! $isTrainerValidated
                                && ! $isAwaitingEval
                                && ! $isCompleted;
                            $isDeferred = (bool) ($moduleProgress?->is_deferred ?? false);
                            $blockerLabel = $blocker ? ($blocker->module_code ?: $blocker->title) : null;
                            $blockerNeedsRemediation = $blocker
                                ? (bool) ($progressByModule->get($blocker->id)?->needsRemediation())
                                : false;
                            if ($isDeferred && $isLocked) {
                                $lockLabel = $blocker
                                    ? ($blockerNeedsRemediation
                                        ? 'Locked — finish '.$blockerLabel
                                        : 'Locked — '.$blockerLabel)
                                    : 'Locked — finish current modules';
                            } elseif ($blockerNeedsRemediation) {
                                $lockLabel = 'Locked — finish '.$blockerLabel;
                            } else {
                                $lockLabel = $blocker
                                    ? 'Locked — '.$blockerLabel
                                    : ($moduleProgress?->workflowStatusLabel() ?: 'Locked');
                            }
                        ?>
                        <article class="lms-trainee-classwork-card<?php echo e($isLocked ? ' is-locked' : ''); ?><?php echo e($isDeferred ? ' is-deferred' : ''); ?>" <?php if($isDeferred): ?> data-classwork-deferred="1" <?php endif; ?>>
                            <span class="lms-classwork-icon"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
<?php endif; ?></span>
                            <div class="lms-classwork-main">
                                <div class="lms-classwork-title-line">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <?php if($module->module_code): ?>
                                            <span class="rounded bg-purple-100 px-2 py-0.5 text-xs font-mono font-bold text-purple-900 ring-1 ring-purple-300">
                                                <?php echo e($module->module_code); ?>

                                            </span>
                                        <?php endif; ?>
                                        <?php if($isDeferred && ! $isCompleted): ?>
                                            <span class="rounded bg-amber-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-amber-900 ring-1 ring-amber-300">
                                                Catch-up
                                            </span>
                                        <?php endif; ?>
                                        <h3>
                                            <?php if($isLocked): ?>
                                                <?php echo e($module->title); ?>

                                            <?php else: ?>
                                                <a href="<?php echo e(route('trainee.modules.show', $module)); ?>" class="hover:text-purple-700 transition">
                                                    <?php echo e($module->title); ?>

                                                </a>
                                            <?php endif; ?>
                                        </h3>
                                    </div>
                                    <span class="lms-status-chip <?php echo e($isLocked ? 'is-red' : ($isCompleted ? 'is-green' : ($isMaterialOnly ? 'is-purple' : ($moduleProgress ? 'is-amber' : 'is-neutral')))); ?>">
                                        <?php echo e($isLocked ? $lockLabel : ($isCompleted ? 'Completed' : ($isMaterialOnly ? 'Material' : ($moduleProgress ? $moduleProgress->workflowStatusLabel() : 'Not started')))); ?>

                                    </span>
                                </div>

                                <?php if($module->topic): ?>
                                    <p class="lms-classwork-topic"><?php echo e($module->topic); ?></p>
                                <?php endif; ?>

                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                    <span class="inline-flex items-center gap-1.5">
                                        <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $module->trainer,'name' => $module->trainer?->name ?? 'MCARE Trainer','class' => 'grid h-6 w-6 place-items-center rounded-full bg-purple-100 text-[9px] font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module->trainer),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module->trainer?->name ?? 'MCARE Trainer'),'class' => 'grid h-6 w-6 place-items-center rounded-full bg-purple-100 text-[9px] font-black text-purple-800']); ?>
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
                                        <?php echo e($module->trainer?->name ?? 'MCARE Trainer'); ?>

                                    </span>
                                    <?php if($module->due_at): ?><span>Due <?php echo e($module->due_at->format('M d')); ?></span><?php endif; ?>
                                </div>

                                <div class="lms-progress-track" role="progressbar" aria-label="<?php echo e($progressValue); ?> percent complete" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo e($progressValue); ?>">
                                    <?php if($progressValue > 0): ?>
                                        <span style="width: <?php echo e($progressValue); ?>%"></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="lms-classwork-actions">
                                <?php if($isLocked): ?>
                                    <span class="secondary-action text-xs py-2 px-4" aria-disabled="true">Locked</span>
                                <?php else: ?>
                                    <a href="<?php echo e(route('trainee.modules.show', $module)); ?>" class="<?php echo e($isCompleted ? 'secondary-action' : 'primary-action'); ?> text-xs py-2 px-4">
                                        <?php echo e($isCompleted ? 'Review' : 'Open'); ?>

                                    </a>
                                    <?php if($canMarkAsDone): ?>
                                        <form method="POST" action="<?php echo e(route('trainee.modules.progress', $module)); ?>" data-confirm="Mark this module as done and send it to your trainer for evaluation? You will not be able to edit it after this.">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>
                                            <input type="hidden" name="action" value="submit">
                                            <button type="submit" class="secondary-action text-xs py-2 px-4">Mark as done</button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </section>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="lms-empty-state">
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
                <h2>No classwork yet</h2>
                <p>Published learning materials will appear here when your trainer shares them.</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainee.layouts.app', ['title' => 'Classwork | MCARE Trainee'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainee\modules\index.blade.php ENDPATH**/ ?>