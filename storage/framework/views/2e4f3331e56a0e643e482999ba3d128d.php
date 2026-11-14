<?php $__env->startSection('content'); ?>
<div class="w-full space-y-7">
    <header class="border-b border-stone-200 pb-6">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-violet-700">Certificates</p>
        <h1 class="mt-2 text-3xl font-bold text-stone-950">Completion eligibility</h1>
        <p class="mt-2 text-stone-600">Readiness is based on each trainee's trainer-validated required core modules and competency outcomes, not the batch end date. Final document issuance remains controlled by the admin.</p>
    </header>

    <div class="overflow-hidden border border-stone-200 bg-white">
        <?php $__empty_1 = true; $__currentLoopData = $trainees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trainee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $completion = $eligibilityByTrainee->get($trainee->id);
                $eligible = (bool) data_get($completion, 'eligible', false);
                $completedModules = (int) data_get($completion, 'counts.completed_modules', 0);
                $requiredModules = (int) data_get($completion, 'counts.modules', 0);
            ?>
            <div class="flex flex-col gap-4 border-b border-stone-100 p-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex min-w-0 items-center gap-3">
                    <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $trainee->user,'name' => trim($trainee->first_name.' '.$trainee->last_name),'class' => 'grid h-10 w-10 shrink-0 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trainee->user),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(trim($trainee->first_name.' '.$trainee->last_name)),'class' => 'grid h-10 w-10 shrink-0 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800']); ?>
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
                        <p class="truncate font-bold text-stone-950"><?php echo e($trainee->first_name); ?> <?php echo e($trainee->last_name); ?></p>
                        <p class="text-sm text-stone-500"><?php echo e($completedModules); ?> of <?php echo e($requiredModules); ?> required core modules trainer-validated</p>
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
                    </div>
                </div>
                <span class="w-fit px-3 py-1 text-xs font-bold <?php echo e($eligible ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-100 text-stone-600'); ?>">
                    <?php echo e($eligible ? 'Eligible for admin document review' : 'Requirements in progress'); ?>

                </span>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="p-6 text-stone-600">No approved trainees found.</p>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => 'Certificates | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\certificates.blade.php ENDPATH**/ ?>