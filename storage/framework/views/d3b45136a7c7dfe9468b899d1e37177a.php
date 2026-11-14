<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['module', 'viewerUrl']));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter((['module', 'viewerUrl']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php ($previewKind = $module->previewKind()); ?>

<div <?php echo e($attributes->class(['lms-module-preview-frame overflow-hidden rounded-xl border border-stone-200 bg-stone-950'])); ?> data-module-file-preview data-preview-kind="<?php echo e($previewKind); ?>">
    <?php if($previewKind === 'video'): ?>
        <div class="flex h-full items-center justify-center bg-black p-3">
            <video class="max-h-full max-w-full object-contain" controls controlsList="nodownload noremoteplayback" disablePictureInPicture preload="metadata">
                <source src="<?php echo e($viewerUrl); ?>" type="<?php echo e($module->mime_type); ?>">
                Your browser cannot play this video.
            </video>
        </div>
    <?php elseif($previewKind === 'audio'): ?>
        <div class="grid h-full place-items-center bg-white p-6 sm:p-8">
            <div class="w-full max-w-2xl text-center">
                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'volume-2','class' => 'mx-auto h-10 w-10 text-purple-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'volume-2','class' => 'mx-auto h-10 w-10 text-purple-600']); ?>
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
                <p class="mt-4 break-words font-bold text-stone-950"><?php echo e($module->original_file_name); ?></p>
                <audio class="mt-5 w-full" controls preload="metadata"><source src="<?php echo e($viewerUrl); ?>" type="<?php echo e($module->mime_type); ?>"></audio>
            </div>
        </div>
    <?php elseif($previewKind === 'image'): ?>
        <div class="flex h-full items-center justify-center overflow-auto bg-stone-950 p-4">
            <img src="<?php echo e($viewerUrl); ?>" alt="<?php echo e($module->title); ?>" class="max-h-full max-w-full object-contain">
        </div>
    <?php elseif($previewKind === 'pdf'): ?>
        <div class="flex h-full min-h-0 flex-col bg-slate-900" data-pdf-canvas-viewer data-pdf-url="<?php echo e($viewerUrl); ?>" data-pdf-fit-mode="page">
            <div class="z-20 flex flex-wrap items-center justify-between gap-3 border-b border-slate-700 bg-slate-900 px-3 py-2.5 text-xs text-white sm:px-4">
                <div class="flex items-center gap-2">
                    <button type="button" class="rounded bg-slate-800 px-3 py-1.5 font-bold hover:bg-slate-700 disabled:opacity-40" data-pdf-prev disabled title="Previous page">&larr; Prev</button>
                    <span class="font-medium text-slate-300">Page <span data-pdf-current-page>1</span> of <span data-pdf-total-pages>-</span></span>
                    <button type="button" class="rounded bg-slate-800 px-3 py-1.5 font-bold hover:bg-slate-700 disabled:opacity-40" data-pdf-next disabled title="Next page">Next &rarr;</button>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="rounded bg-slate-800 px-2.5 py-1.5 font-bold hover:bg-slate-700" data-pdf-zoom-out title="Zoom out">&minus;</button>
                    <span class="min-w-12 text-center font-medium text-slate-300" data-pdf-zoom-level>Fit</span>
                    <button type="button" class="rounded bg-slate-800 px-2.5 py-1.5 font-bold hover:bg-slate-700" data-pdf-zoom-in title="Zoom in">+</button>
                    <button type="button" class="rounded bg-slate-800 px-2.5 py-1.5 font-bold text-slate-200 hover:bg-slate-700" data-pdf-fit-width title="Fit the full page">Fit Page</button>
                </div>
            </div>
            <div class="relative min-h-0 flex-1 overflow-auto p-3 sm:p-4" data-pdf-canvas-container>
                <div data-pdf-scroll-sizer>
                    <div class="relative inline-block shadow-2xl" data-pdf-page-wrapper>
                        <canvas class="block bg-white shadow-md" data-pdf-canvas></canvas>
                    </div>
                </div>
                <div class="absolute inset-0 flex items-center justify-center bg-slate-950/85 text-white" data-pdf-loading>
                    <div class="flex items-center gap-3">
                        <span class="h-5 w-5 animate-spin rounded-full border-2 border-purple-400 border-t-transparent"></span>
                        <span class="text-sm font-semibold">Fitting document preview...</span>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="grid h-full place-items-center bg-white p-8 text-center text-stone-700">
            <div>
                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'file-text','class' => 'mx-auto h-10 w-10 text-purple-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'file-text','class' => 'mx-auto h-10 w-10 text-purple-600']); ?>
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
                <p class="mt-4 font-bold"><?php echo e($module->fileTypeLabel()); ?></p>
                <p class="mt-2 text-sm text-stone-500">This file type opens in its native application or a new browser tab.</p>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\components\module-file-preview.blade.php ENDPATH**/ ?>