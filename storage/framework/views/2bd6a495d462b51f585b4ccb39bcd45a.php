<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'note' => 'Official public information. Applicant records are processed for training, scholarship, employment, and related institutional purposes.',
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
    'note' => 'Official public information. Applicant records are processed for training, scholarship, employment, and related institutional purposes.',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<footer <?php echo e($attributes->class('auth-footer')); ?>>
    <div class="auth-footer-inner">
        <p>&copy; <?php echo e(date('Y')); ?> Mission Care Training and Assessment Center.</p>
        <p>TESDA-accredited Caregiving NC II training and assessment.</p>
        <p class="landing-colophon-note"><?php echo e($note); ?></p>
    </div>
</footer>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\components\public-official-footer.blade.php ENDPATH**/ ?>