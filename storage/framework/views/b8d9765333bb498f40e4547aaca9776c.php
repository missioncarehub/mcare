<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'src',
    'size' => 'viewer',
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
    'src',
    'size' => 'viewer',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $frame = $size === 'page'
        ? 'h-[72%] w-[72%] max-h-[42rem] max-w-[42rem]'
        : 'h-[72%] w-[72%] max-h-[36rem] max-w-[36rem]';
?>

<div <?php echo e($attributes->class(['learning-watermark', $frame])); ?> aria-hidden="true">
    <img src="<?php echo e($src); ?>" alt="" class="learning-watermark__logo" draggable="false">
</div>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\components\learning-watermark.blade.php ENDPATH**/ ?>