<?php $__env->startSection('content'); ?>
<?php
    $batchLabel = $application->batch
        ? $application->batch->name.' '.$application->batch->year
        : 'Caregiving NC II';
?>

<div class="lms-page" data-lms-stream data-lms-role="trainee">
    <header class="lms-class-banner">
        <div>
            <p class="lms-eyebrow">MCARE Classroom</p>
            <h1><?php echo e($batchLabel); ?></h1>
            <p>Class announcements, reminders, and the next activities your trainer has shared.</p>
        </div>
    </header>

    <nav class="lms-context-tabs" aria-label="Trainee classroom sections">
        <a href="<?php echo e(route('trainee.stream')); ?>" class="is-active" aria-current="page">Stream</a>
        <a href="<?php echo e(route('trainee.modules.index')); ?>">Classwork</a>
        <a href="<?php echo e(route('trainee.schedule')); ?>">Calendar</a>
    </nav>

    <div class="lms-stream-layout">
        <main class="lms-stream-feed">
            <?php if(isset($adminAnnouncements) && $adminAnnouncements->isNotEmpty()): ?>
                <section class="space-y-4 mb-6" aria-label="Administrative notices">
                    <?php $__currentLoopData = $adminAnnouncements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $adminNotice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <article class="rounded-2xl border border-purple-200 bg-gradient-to-r from-purple-50 via-white to-sky-50 p-5 shadow-xs">
                            <div class="flex items-start gap-3.5">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-700 text-white">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => $adminNotice->kind === 'reminder' ? 'credit-card' : 'bullhorn','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($adminNotice->kind === 'reminder' ? 'credit-card' : 'bullhorn'),'class' => 'h-5 w-5']); ?>
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
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="rounded-md bg-purple-100 px-2 py-0.5 text-[11px] font-bold uppercase text-purple-800">
                                                <?php echo e($adminNotice->kind === 'reminder' ? 'Payment / Due Date Reminder' : 'Administration Notice'); ?>

                                            </span>
                                            <?php if($adminNotice->due_date): ?>
                                                <span class="rounded-md bg-rose-100 px-2 py-0.5 text-[11px] font-bold text-rose-800">
                                                    Due: <?php echo e($adminNotice->due_date->format('M d, Y')); ?>

                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="text-xs text-slate-500"><?php echo e($adminNotice->posted_at?->diffForHumans() ?? 'Recently'); ?></span>
                                    </div>
                                    <h2 class="text-base font-bold text-slate-950"><?php echo e($adminNotice->title); ?></h2>
                                    <p class="text-sm text-slate-700 leading-relaxed"><?php echo e($adminNotice->message); ?></p>
                                    <?php if($adminNotice->kind === 'reminder'): ?>
                                        <div class="pt-2">
                                            <a href="<?php echo e(route('trainee.payments')); ?>" class="inline-flex items-center gap-1.5 text-xs font-bold text-purple-700 hover:text-purple-900 underline">
                                                <span>Open Billing & Payment Dashboard</span>
                                                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'arrow-right','class' => 'h-3 w-3']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-right','class' => 'h-3 w-3']); ?>
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
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </section>
            <?php endif; ?>

            <section class="lms-post-list" aria-label="Class announcements">
                <?php $__empty_1 = true; $__currentLoopData = $announcements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $announcement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $postedAt = $announcement->posted_at ?? $announcement->created_at;
                        $postKind = str($announcement->kind ?? 'announcement')->headline();
                    ?>
                    <article class="lms-post-card <?php echo e($announcement->is_pinned ? 'is-pinned' : ''); ?>" data-announcement-card>
                        <header class="lms-post-header">
                            <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $announcement->trainer,'name' => $announcement->trainer?->name ?? 'MCARE Trainer','class' => 'lms-avatar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($announcement->trainer),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($announcement->trainer?->name ?? 'MCARE Trainer'),'class' => 'lms-avatar']); ?>
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
                            <div class="min-w-0 flex-1">
                                <div class="lms-post-author-line">
                                    <strong><?php echo e($announcement->trainer?->name ?? 'MCARE Trainer'); ?></strong>
                                    <?php if($announcement->is_pinned): ?>
                                        <span class="lms-status-chip is-purple">Pinned</span>
                                    <?php endif; ?>
                                </div>
                                <p><?php echo e($postKind); ?> - <?php echo e($postedAt?->diffForHumans() ?? 'Recently'); ?></p>
                            </div>
                        </header>
                        <div class="lms-post-body">
                            <h2><?php echo e($announcement->title); ?></h2>
                            <p><?php echo e($announcement->message); ?></p>
                        </div>
                        <?php if($announcement->expires_at): ?>
                            <footer class="lms-card-footer">
                                <span class="lms-muted-note">Visible until <?php echo e($announcement->expires_at->format('M d, Y g:i A')); ?></span>
                            </footer>
                        <?php endif; ?>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="lms-empty-state">
                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'bell']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'bell']); ?>
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
                        <h2>No announcements yet</h2>
                        <p>Your trainer's class updates will appear in this stream.</p>
                    </div>
                <?php endif; ?>
            </section>

            <?php if(method_exists($announcements, 'hasPages') && $announcements->hasPages()): ?>
                <div class="lms-pagination"><?php echo e($announcements->links()); ?></div>
            <?php endif; ?>
        </main>

        <aside class="lms-stream-sidebar" aria-label="Upcoming classwork">
            <section class="lms-side-card">
                <div class="lms-side-card-heading">
                    <h2>Upcoming</h2>
                    <a href="<?php echo e(route('trainee.schedule')); ?>">View calendar</a>
                </div>
                <div class="lms-upcoming-list">
                    <?php $__empty_1 = true; $__currentLoopData = $upcomingQuizzes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quiz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <a href="<?php echo e($quiz->training_module_id ? route('trainee.modules.show', $quiz->training_module_id).'#assessments' : route('trainee.quizzes.show', $quiz)); ?>" class="lms-upcoming-item">
                            <span class="lms-upcoming-icon is-quiz"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'square-check']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'square-check']); ?>
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
                            <span><strong><?php echo e($quiz->title); ?></strong><small>Assessment - <?php echo e($quiz->due_at?->format('M d, g:i A') ?? 'No due date'); ?></small></span>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php endif; ?>
                    <?php $__empty_1 = true; $__currentLoopData = $upcomingModules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <a href="<?php echo e(route('trainee.modules.show', $module)); ?>" class="lms-upcoming-item">
                            <span class="lms-upcoming-icon"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
                            <span><strong><?php echo e($module->title); ?></strong><small>Material - <?php echo e($module->due_at?->format('M d, g:i A') ?? 'No due date'); ?></small></span>
                        </a>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <?php endif; ?>

                    <?php if(collect($upcomingQuizzes)->isEmpty() && collect($upcomingModules)->isEmpty()): ?>
                        <p class="lms-empty-copy">Nothing is due soon. You're all caught up.</p>
                    <?php endif; ?>
                </div>
            </section>

            <section class="lms-side-card">
                <h2>Quick links</h2>
                <div class="lms-quick-links">
                    <a href="<?php echo e(route('trainee.modules.index')); ?>"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
<?php endif; ?> Review classwork</a>
                </div>
            </section>
        </aside>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainee.layouts.app', ['title' => 'Class Stream | MCARE Trainee'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainee\stream.blade.php ENDPATH**/ ?>