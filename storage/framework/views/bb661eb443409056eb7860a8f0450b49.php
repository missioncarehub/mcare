<?php $__env->startSection('content'); ?>
<div class="w-full space-y-7">
    <header class="border-b border-stone-200 pb-6">
        <p class="dashboard-section-kicker">Trainees</p>
        <h1 class="dashboard-section-title mt-2 text-3xl">Approved learner roster</h1>
        <p class="mt-2 text-stone-600">Your roster is limited to the current batch assigned by the administrator. Review LMS module progress and training state, and export summaries.</p>
    </header>

    <form method="GET" action="<?php echo e(route('trainer.trainees')); ?>" class="dashboard-panel grid gap-4 md:grid-cols-4">
        <div class="md:col-span-2">
            <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Search</label>
            <input name="search" value="<?php echo e($search); ?>" class="form-field" placeholder="Search name or email">
        </div>
        <div>
            <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Assigned batch</label>
            <select name="batch_id" class="form-field">
                <option value="">Current assignment</option>
                <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($batch->id); ?>" <?php if($batchId === $batch->id): echo 'selected'; endif; ?>><?php echo e($batch->name); ?> <?php echo e($batch->year); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Schedule</label>
            <select name="schedule" class="form-field">
                <option value="">AM and PM</option>
                <option value="AM" <?php if($schedule === 'AM'): echo 'selected'; endif; ?>>AM</option>
                <option value="PM" <?php if($schedule === 'PM'): echo 'selected'; endif; ?>>PM</option>
            </select>
        </div>
        <div class="flex flex-wrap gap-2 md:col-span-4">
            <button class="primary-action">Apply filters</button>
            <a href="<?php echo e(route('trainer.trainees')); ?>" class="secondary-action">Reset</a>
            <a href="<?php echo e(route('trainer.trainees.export', request()->query())); ?>" class="secondary-action">Export trainee summary</a>
        </div>
    </form>

    <div class="dashboard-table-wrap overflow-x-auto">
        <table class="dashboard-table w-full min-w-[52rem]">
            <thead>
                <tr>
                    <th>Trainee</th>
                    <th>Batch / Schedule</th>
                    <th>Module progress</th>
                    <th>Training state</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $trainees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trainee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $isGraduated = $trainee->learning_status === \App\Models\EnrollmentApplication::LEARNING_GRADUATED;
                    ?>
                    <tr class="align-top">
                        <td>
                            <div class="flex items-center gap-3">
                                <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $trainee->user,'name' => trim($trainee->first_name.' '.$trainee->last_name),'class' => 'grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trainee->user),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(trim($trainee->first_name.' '.$trainee->last_name)),'class' => 'grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800']); ?>
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
                                <div class="min-w-0">
                                    <p class="font-bold text-stone-950"><?php echo e($trainee->last_name); ?>, <?php echo e($trainee->first_name); ?></p>
                                    <p class="truncate text-xs text-stone-500"><?php echo e($trainee->email); ?></p>
                                    <?php if (isset($component)) { $__componentOriginal1cb871248b084f1659af43a69ab30a65 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cb871248b084f1659af43a69ab30a65 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.graduate-batch-badge','data' => ['application' => $trainee,'class' => 'mt-1.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('graduate-batch-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['application' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trainee),'class' => 'mt-1.5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cb871248b084f1659af43a69ab30a65)): ?>
<?php $attributes = $__attributesOriginal1cb871248b084f1659af43a69ab30a65; ?>
<?php unset($__attributesOriginal1cb871248b084f1659af43a69ab30a65); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cb871248b084f1659af43a69ab30a65)): ?>
<?php $component = $__componentOriginal1cb871248b084f1659af43a69ab30a65; ?>
<?php unset($__componentOriginal1cb871248b084f1659af43a69ab30a65); ?>
<?php endif; ?>
                                    <p class="mt-1 text-[11px] text-stone-400">Tel: <?php echo e($trainee->contact_number); ?></p>
                                </div>
                            </div>
                        </td>
                        <td>
                            <p class="font-bold text-stone-900"><?php echo e($trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : 'Unassigned'); ?></p>
                            <p class="mt-1 text-xs text-stone-600"><?php echo e($trainee->schedule_preference); ?> · <?php echo e($trainee->batch?->scheduleLabelFor($trainee->schedule_preference) ?? 'Schedule pending'); ?></p>
                        </td>
                        <td>
                            <span class="font-bold text-emerald-700"><?php echo e($trainee->moduleProgress->where('status', 'completed')->count()); ?> complete</span>
                            <p class="text-xs text-amber-700"><?php echo e($trainee->moduleProgress->where('status', 'awaiting_evaluation')->count()); ?> awaiting evaluation</p>
                            <p class="text-xs text-stone-500"><?php echo e($trainee->moduleProgress->whereIn('status', ['not_started', 'in_progress', 'needs_remediation'])->count()); ?> active or for remediation</p>
                        </td>
                        <td>
                            <?php if($isGraduated): ?>
                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800 ring-1 ring-emerald-200">Graduated</span>
                            <?php else: ?>
                                <span class="inline-flex items-center rounded-full bg-amber-50 px-3 py-1 text-xs font-bold text-amber-800 ring-1 ring-amber-200">In progress</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="4" class="px-5 py-10 text-center text-stone-600">No approved trainees found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php echo e($trainees->links()); ?>

</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => 'Trainees | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\trainees.blade.php ENDPATH**/ ?>