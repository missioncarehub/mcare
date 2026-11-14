<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-stone-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="dashboard-section-kicker">Sessions</p>
            <h1 class="dashboard-section-title mt-2 text-3xl">Teaching schedule</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-stone-600">The calendar updates from the current batch assigned by the administrator. Select any date to see every class for that day together.</p>
        </div>
        <?php if($activeBatch): ?>
            <div class="rounded-lg border border-violet-200 bg-violet-50 px-4 py-3 text-sm">
                <p class="font-bold text-violet-950"><?php echo e($activeBatch->name); ?> <?php echo e($activeBatch->year); ?></p>
                <p class="mt-1 text-violet-800">Updated <?php echo e($activeBatch->updated_at?->format('M d, Y g:i A')); ?> &middot; <?php echo e($sessions->count()); ?> sessions</p>
            </div>
        <?php endif; ?>
    </header>

    <?php if($activeBatch): ?>
        <?php if (isset($component)) { $__componentOriginalbe6a3b5a8a13afac890ee49a30b72876 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbe6a3b5a8a13afac890ee49a30b72876 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.training-calendar','data' => ['month' => $month,'sessions' => $sessions,'selectedDate' => $calendarSelectedDate,'monthRoute' => route('trainer.sessions'),'eyebrow' => 'Live admin schedule','heading' => $month->format('F Y').' teaching calendar','description' => 'Select a date to open the complete teaching agenda. AM and PM sessions stay visible together—no popups or one-by-one cards.','emptyMessage' => 'No teaching sessions fall on this date.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('training-calendar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['month' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($month),'sessions' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sessions),'selected-date' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarSelectedDate),'month-route' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('trainer.sessions')),'eyebrow' => 'Live admin schedule','heading' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($month->format('F Y').' teaching calendar'),'description' => 'Select a date to open the complete teaching agenda. AM and PM sessions stay visible together—no popups or one-by-one cards.','empty-message' => 'No teaching sessions fall on this date.']); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'calendar-days','class' => 'mx-auto h-9 w-9 text-stone-400']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'calendar-days','class' => 'mx-auto h-9 w-9 text-stone-400']); ?>
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
            <h2 class="mt-4 text-xl font-bold text-stone-950">No assigned training batch</h2>
            <p class="mt-2 text-sm text-stone-600">The calendar will appear after the administrator assigns and schedules a batch for this trainer.</p>
        </section>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => 'Sessions | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\sessions.blade.php ENDPATH**/ ?>