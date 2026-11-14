<?php $__env->startSection('content'); ?>
<?php
    $viewerUrl = route('admin.learning.modules.content', $module);
    $downloadUrl = route('admin.learning.modules.download', $module);
?>

<div class="mx-auto max-w-7xl space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <p class="min-w-0 text-sm text-slate-600">
            <?php echo e($module->original_file_name); ?> · <?php echo e($module->fileTypeLabel()); ?> · <?php echo e($module->batch ? $module->batch->name.' '.$module->batch->year : 'General module'); ?>

        </p>
        <div class="flex flex-wrap gap-2">
            <a href="<?php echo e(route('admin.learning.modules')); ?>" class="secondary-action">Back to modules</a>
            <a href="<?php echo e($viewerUrl); ?>" target="_blank" rel="noopener" class="secondary-action">Open in new tab</a>
            <a href="<?php echo e($downloadUrl); ?>" class="primary-action">Download file</a>
        </div>
    </header>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" aria-label="Module file preview">
        <?php if (isset($component)) { $__componentOriginal63d8f2fdcf624a967dc47b4d6ec2784d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal63d8f2fdcf624a967dc47b4d6ec2784d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.module-file-preview','data' => ['module' => $module,'viewerUrl' => $viewerUrl]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('module-file-preview'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['module' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module),'viewer-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($viewerUrl)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal63d8f2fdcf624a967dc47b4d6ec2784d)): ?>
<?php $attributes = $__attributesOriginal63d8f2fdcf624a967dc47b4d6ec2784d; ?>
<?php unset($__attributesOriginal63d8f2fdcf624a967dc47b4d6ec2784d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal63d8f2fdcf624a967dc47b4d6ec2784d)): ?>
<?php $component = $__componentOriginal63d8f2fdcf624a967dc47b4d6ec2784d; ?>
<?php unset($__componentOriginal63d8f2fdcf624a967dc47b4d6ec2784d); ?>
<?php endif; ?>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => $module->title.' Preview | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\learning\module-preview.blade.php ENDPATH**/ ?>