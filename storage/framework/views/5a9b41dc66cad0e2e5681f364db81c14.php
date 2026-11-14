<?php $__env->startSection('content'); ?>
<section class="space-y-6">
    <?php if($isAdminPreview ?? false): ?>
        <div class="flex flex-col gap-3 rounded-lg border border-purple-200 bg-purple-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-semibold text-purple-900">Admin preview of the alumni experience</p>
            <a href="<?php echo e(route('admin.learning.alumni-jobs')); ?>" class="text-sm font-bold text-purple-800 hover:text-purple-950">Back to Job Board management</a>
        </div>
    <?php endif; ?>

    <header class="border-b border-slate-200 pb-6">
        <?php if (! ($isAdminPreview ?? false)): ?>
            <p class="dashboard-section-kicker">MCARE Alumni</p>
        <?php endif; ?>
        <div class="<?php echo e(($isAdminPreview ?? false) ? '' : 'mt-3 '); ?>flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <?php if (! ($isAdminPreview ?? false)): ?>
                    <h1 class="text-3xl font-black text-slate-950 sm:text-4xl">Alumni Job Board</h1>
                <?php endif; ?>
                <p class="<?php echo e(($isAdminPreview ?? false) ? '' : 'mt-2 '); ?>text-sm text-slate-600">Privacy-reviewed caregiving duties coordinated by MCARE.</p>
            </div>
            <a href="<?php echo e(route('notifications.index')); ?>" class="secondary-action inline-flex items-center gap-3 self-start lg:self-auto"><span class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-50 text-purple-700"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'bell','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'bell','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></span><span><span class="block text-xs font-bold uppercase text-slate-500">Unread updates</span><span class="block text-lg font-black text-slate-950"><?php echo e($unreadNotifications); ?></span></span></a>
        </div>
    </header>

    <?php if (! ($isAdminPreview ?? false)): ?>
        <section class="dashboard-panel flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between" aria-labelledby="availability-title" data-availability-state="<?php echo e($alumniProfile->is_available_for_duty ? 'available' : 'unavailable'); ?>">
            <div class="flex items-start gap-4">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg <?php echo e($alumniProfile->is_available_for_duty ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'); ?>"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => $alumniProfile->is_available_for_duty ? 'circle-check' : 'circle-minus','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($alumniProfile->is_available_for_duty ? 'circle-check' : 'circle-minus'),'class' => 'h-5 w-5']); ?>
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
                <div><p class="dashboard-section-kicker">Caregiver availability</p><h2 id="availability-title" class="mt-1 text-xl font-black text-slate-950"><?php echo e($alumniProfile->is_available_for_duty ? 'Available for Duty' : 'Currently unavailable'); ?></h2><p class="mt-1 text-sm text-slate-500"><?php echo e($alumniProfile->availability_updated_at ? 'Updated '.$alumniProfile->availability_updated_at->diffForHumans() : 'Set your current duty status.'); ?></p></div>
            </div>
            <?php echo $__env->make('trainee.partials.career-availability-toggle', [
                'action' => route('alumni.availability.update'),
                'alumniProfile' => $alumniProfile,
                'confirm' => false,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </section>
    <?php endif; ?>

    <section aria-labelledby="career-board-title">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between"><div><p class="dashboard-section-kicker">Open careers</p><h2 id="career-board-title" class="mt-1 text-2xl font-black text-slate-950">Career opportunities</h2></div><span class="text-sm font-semibold text-slate-500"><?php echo e($jobs->total()); ?> available</span></div>

        <div class="mt-5 grid gap-4 lg:grid-cols-2">
            <?php $__empty_1 = true; $__currentLoopData = $jobs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $job): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php echo $__env->make('trainee.partials.career-posting-card', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="dashboard-panel py-16 text-center lg:col-span-2"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'briefcase','class' => 'mx-auto h-9 w-9 text-slate-300']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'briefcase','class' => 'mx-auto h-9 w-9 text-slate-300']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?><h3 class="mt-4 text-lg font-bold text-slate-900">No open careers yet</h3><p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">MCARE will publish privacy-reviewed careers here.</p></div>
            <?php endif; ?>
        </div>

        <?php if($jobs->hasPages()): ?><div class="mt-6"><?php echo e($jobs->links()); ?></div><?php endif; ?>
    </section>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make(($isAdminPreview ?? false) ? 'admin.layouts.app' : 'alumni.layouts.app', ['title' => 'Alumni Job Board | MCARE'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\alumni\dashboard.blade.php ENDPATH**/ ?>