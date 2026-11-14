<?php $__env->startSection('content'); ?>
<?php
    $scheduleLabel = $batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule to be confirmed';
    $roomLabel = $batch?->roomFor($application->schedule_preference) ?: 'Room TBA';
    $deadline = $application->effectivePaymentDeadline() ?: $batch?->enrollment_ends_at;
?>

<section class="space-y-6">
    <?php if($isGraduate ?? false): ?>
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div><p class="dashboard-section-kicker">Graduate calendar</p><h1 class="dashboard-section-title mt-2 text-2xl sm:text-3xl">Career opportunities calendar</h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">This calendar shows estimated start dates for privacy-reviewed caregiving duties.</p></div>
            <a href="<?php echo e(route('trainee.career-hub')); ?>" class="secondary-action inline-flex w-full items-center justify-center gap-2 sm:w-auto"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'briefcase','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'briefcase','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Career Hub</a>
        </header>
        <?php if (isset($component)) { $__componentOriginalbe6a3b5a8a13afac890ee49a30b72876 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbe6a3b5a8a13afac890ee49a30b72876 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.training-calendar','data' => ['month' => $calendarMonth,'sessions' => $calendarSessions,'selectedDate' => $calendarSelectedDate,'monthRoute' => route('trainee.schedule'),'showBatch' => false,'eyebrow' => 'Read-only career calendar','heading' => $calendarMonth->format('F Y').' opportunities','description' => 'Career dates are estimates. Open the Career Hub for the privacy-reviewed duty details.','emptyMessage' => 'No career opportunity starts on this date.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('training-calendar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['month' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarMonth),'sessions' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarSessions),'selected-date' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarSelectedDate),'month-route' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('trainee.schedule')),'show-batch' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'eyebrow' => 'Read-only career calendar','heading' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarMonth->format('F Y').' opportunities'),'description' => 'Career dates are estimates. Open the Career Hub for the privacy-reviewed duty details.','empty-message' => 'No career opportunity starts on this date.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbe6a3b5a8a13afac890ee49a30b72876)): ?>
<?php $attributes = $__attributesOriginalbe6a3b5a8a13afac890ee49a30b72876; ?>
<?php unset($__attributesOriginalbe6a3b5a8a13afac890ee49a30b72876); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbe6a3b5a8a13afac890ee49a30b72876)): ?>
<?php $component = $__componentOriginalbe6a3b5a8a13afac890ee49a30b72876; ?>
<?php unset($__componentOriginalbe6a3b5a8a13afac890ee49a30b72876); ?>
<?php endif; ?>
    <?php else: ?>
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="dashboard-section-kicker">My schedule</p>
            <h1 class="dashboard-section-title mt-2 text-2xl sm:text-3xl">Class calendar</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Your calendar only includes the <?php echo e($application->schedule_preference); ?> sessions assigned to your approved batch.</p>
        </div>
        <div class="flex flex-wrap gap-2 text-xs font-bold">
            <span class="rounded-lg bg-purple-50 px-3 py-2 text-purple-700 ring-1 ring-purple-100"><?php echo e($application->schedule_preference); ?> class</span>
            <span class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700"><?php echo e($batch ? $batch->name.' '.$batch->year : 'Batch pending'); ?></span>
        </div>
    </header>

    <?php if($batch): ?>
        <?php if (isset($component)) { $__componentOriginalbe6a3b5a8a13afac890ee49a30b72876 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbe6a3b5a8a13afac890ee49a30b72876 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.training-calendar','data' => ['month' => $calendarMonth,'sessions' => $calendarSessions,'selectedDate' => $calendarSelectedDate,'monthRoute' => route('trainee.schedule'),'showBatch' => false,'eyebrow' => 'Read-only class calendar','heading' => $calendarMonth->format('F Y').' schedule','description' => 'Choose a date to review the complete class time and room. Schedule changes made by the administrator appear here on your next visit.','emptyMessage' => 'You have no class scheduled on this date.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('training-calendar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['month' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarMonth),'sessions' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarSessions),'selected-date' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarSelectedDate),'month-route' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('trainee.schedule')),'show-batch' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'eyebrow' => 'Read-only class calendar','heading' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarMonth->format('F Y').' schedule'),'description' => 'Choose a date to review the complete class time and room. Schedule changes made by the administrator appear here on your next visit.','empty-message' => 'You have no class scheduled on this date.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbe6a3b5a8a13afac890ee49a30b72876)): ?>
<?php $attributes = $__attributesOriginalbe6a3b5a8a13afac890ee49a30b72876; ?>
<?php unset($__attributesOriginalbe6a3b5a8a13afac890ee49a30b72876); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbe6a3b5a8a13afac890ee49a30b72876)): ?>
<?php $component = $__componentOriginalbe6a3b5a8a13afac890ee49a30b72876; ?>
<?php unset($__componentOriginalbe6a3b5a8a13afac890ee49a30b72876); ?>
<?php endif; ?>
    <?php else: ?>
        <section class="dashboard-panel text-center">
            <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'calendar-days','class' => 'mx-auto h-9 w-9 text-slate-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'calendar-days','class' => 'mx-auto h-9 w-9 text-slate-400']); ?>
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
            <h2 class="mt-4 text-xl font-bold text-slate-900">Batch schedule pending</h2>
            <p class="mt-2 text-sm text-slate-600">The calendar will appear after the administrator assigns your approved enrollment to a batch.</p>
        </section>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="dashboard-panel">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Regular schedule</p>
                    <p class="mt-2 text-lg font-black text-slate-900"><?php echo e($scheduleLabel); ?></p>
                </div>
                <div class="rounded-xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Classroom</p>
                    <p class="mt-2 text-lg font-black text-slate-900"><?php echo e($roomLabel); ?></p>
                </div>
                <div class="rounded-xl bg-purple-50 p-5 ring-1 ring-purple-100">
                    <p class="text-xs font-black uppercase tracking-wide text-purple-700">Enrollment/payment deadline</p>
                    <p class="mt-2 text-lg font-black text-slate-900"><?php echo e($deadline?->format('M d, Y g:i A') ?? 'Deadline TBA'); ?></p>
                </div>
            </div>
        </section>

        <aside class="dashboard-panel">
            <p class="text-xs font-black uppercase tracking-wide text-amber-600">Announcements</p>
            <h2 class="mt-2 font-display text-xl font-black text-slate-900">Trainer notices</h2>
            <div class="mt-5 space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $announcements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $announcement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <article class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="font-bold text-slate-900"><?php echo e($announcement->title); ?></p>
                        <p class="mt-2 text-sm leading-6 text-slate-600"><?php echo e($announcement->message); ?></p>
                        <p class="mt-2 text-xs font-bold text-amber-700"><?php echo e($announcement->posted_at?->format('M d, Y g:i A') ?? 'Recently posted'); ?></p>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-sm text-slate-600">No class announcements yet.</p>
                <?php endif; ?>
            </div>
        </aside>
    </div>
    <?php endif; ?>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainee.layouts.app', ['title' => 'Schedule | MCARE Trainee'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainee\schedule.blade.php ENDPATH**/ ?>