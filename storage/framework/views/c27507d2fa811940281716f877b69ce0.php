<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'units',
    'id',
    'placeholder' => '-- Choose from Caregiving NC II course modules or enter custom --',
]));

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

foreach (array_filter(([
    'units',
    'id',
    'placeholder' => '-- Choose from Caregiving NC II course modules or enter custom --',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<?php
    $units = collect($units);
    $labels = \App\Models\CompetencyUnit::categoryLabels();
?>
<select id="<?php echo e($id); ?>" <?php echo e($attributes->class(['form-field'])); ?>>
    <option value=""><?php echo e($placeholder); ?></option>
    <?php $__currentLoopData = $labels; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $group = $units->where('category', $category); ?>
        <?php if($group->isNotEmpty()): ?>
            <optgroup label="<?php echo e($label); ?>">
                <?php $__currentLoopData = $group; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option
                        value="<?php echo e($unit->code); ?>"
                        data-code="<?php echo e($unit->code); ?>"
                        data-title="<?php echo e($unit->title); ?>"
                        data-category="<?php echo e($unit->category); ?>"
                        data-hours="<?php echo e($unit->suggestedHours()); ?>"
                        data-outcomes="<?php echo e(json_encode($unit->outcomeTitles())); ?>"
                    >
                        [<?php echo e($unit->code); ?>] <?php echo e($unit->title); ?>

                    </option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </optgroup>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</select>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\components\module-preset-select.blade.php ENDPATH**/ ?>