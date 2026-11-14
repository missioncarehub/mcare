<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'mastheadAside' => 'Caregiving NC II · Official public information',
    'navLabel' => 'Site navigation',
    'secondaryHref' => null,
    'secondaryLabel' => 'Public site',
    'secondaryCompactHide' => false,
    'primaryHref' => null,
    'primaryLabel' => null,
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
    'mastheadAside' => 'Caregiving NC II · Official public information',
    'navLabel' => 'Site navigation',
    'secondaryHref' => null,
    'secondaryLabel' => 'Public site',
    'secondaryCompactHide' => false,
    'primaryHref' => null,
    'primaryLabel' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $homeUrl = route('landing');
?>


<header <?php echo e($attributes->class('auth-site-header')); ?> data-header-scroll-border>
    <div class="landing-masthead">
        <p class="landing-masthead-kicker">TESDA-Accredited Training and Assessment Center</p>
        <p class="landing-masthead-aside"><?php echo e($mastheadAside); ?></p>
    </div>
    <nav class="auth-topnav" aria-label="<?php echo e($navLabel); ?>">
        <a href="<?php echo e($homeUrl); ?>" class="flex min-w-0 items-center gap-3">
            <img src="<?php echo e(asset('assets/images/logoicon.png')); ?>" alt="MCARE Hub" class="landing-brand-mark">
            <span class="mcare-brand">
                <span class="mcare-mark">MCARE</span>
                <p class="mcare-brand-name">Mission Care</p>
            </span>
        </a>
        <div class="auth-topnav-links">
            <a href="<?php echo e($homeUrl); ?>" class="auth-topnav-link">Home</a>
        </div>
    </nav>
</header>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\components\public-official-header.blade.php ENDPATH**/ ?>