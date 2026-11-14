<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['application']));

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

foreach (array_filter((['application']), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php if($application?->learning_status === \App\Models\EnrollmentApplication::LEARNING_GRADUATED): ?>
    <?php
        $graduatedBatch = trim(($application->batch->name ?? '').' '.($application->batch->year ?? ''));
    ?>
    <span <?php echo e($attributes->class(['inline-flex w-fit items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-200'])); ?>>
        <?php echo e($graduatedBatch !== '' ? 'Graduated in '.$graduatedBatch : 'Verified graduate'); ?>

    </span>
<?php endif; ?>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\components\graduate-batch-badge.blade.php ENDPATH**/ ?>