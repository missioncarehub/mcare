<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'links' => [],
    'variant' => 'discover',
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
    'links' => [],
    'variant' => 'discover',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $visible = collect(['facebook', 'instagram', 'youtube'])
        ->filter(fn (string $network) => filled($links[$network] ?? null))
        ->values();
    $linkClass = $variant === 'footer'
        ? 'inline-flex h-10 w-10 items-center justify-center rounded-lg border border-white/50 bg-white/85 text-purple-700 shadow-sm hover:bg-white'
        : 'inline-flex h-11 w-11 items-center justify-center rounded-lg border border-purple-100 bg-white text-purple-700 shadow-sm transition hover:-translate-y-0.5 hover:border-purple-200 hover:bg-purple-50 hover:text-purple-800 sm:h-12 sm:w-12';
    $labels = [
        'facebook' => $variant === 'footer' ? 'Open MCARE Facebook page from footer' : 'Open MCARE Facebook page',
        'instagram' => $variant === 'footer' ? 'Open MCARE Instagram page from footer' : 'Open MCARE Instagram page',
        'youtube' => $variant === 'footer' ? 'Open MCARE YouTube channel from footer' : 'Open MCARE YouTube channel',
    ];
?>

<?php if($visible->isNotEmpty()): ?>
    <div <?php echo e($attributes->class('flex flex-wrap items-center justify-center gap-2.5 sm:gap-3')); ?>>
        <?php $__currentLoopData = $visible; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $network): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e($links[$network]); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php echo e($labels[$network]); ?>" class="<?php echo e($linkClass); ?>">
                <?php if($network === 'facebook'): ?>
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 sm:h-5 sm:w-5" aria-hidden="true">
                        <path d="M14 8.5h2V5.2c-.35-.05-1.55-.15-2.95-.15-2.9 0-4.9 1.82-4.9 5.18v2.92H5v3.7h3.15V24h3.88v-7.15h3.03l.48-3.7h-3.51v-2.55c0-1.07.29-2.1 1.97-2.1Z"/>
                    </svg>
                <?php elseif($network === 'instagram'): ?>
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4 sm:h-5 sm:w-5" aria-hidden="true">
                        <rect x="4" y="4" width="16" height="16" rx="5" stroke="currentColor" stroke-width="2"/>
                        <circle cx="12" cy="12" r="3.5" stroke="currentColor" stroke-width="2"/>
                        <circle cx="16.8" cy="7.2" r="1" fill="currentColor"/>
                    </svg>
                <?php else: ?>
                    <svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 sm:h-6 sm:w-6" aria-hidden="true">
                        <path d="M21.6 7.2a3 3 0 0 0-2.1-2.1C17.65 4.6 12 4.6 12 4.6s-5.65 0-7.5.5a3 3 0 0 0-2.1 2.1A31.2 31.2 0 0 0 1.9 12c0 1.6.16 3.2.5 4.8a3 3 0 0 0 2.1 2.1c1.85.5 7.5.5 7.5.5s5.65 0 7.5-.5a3 3 0 0 0 2.1-2.1c.34-1.6.5-3.2.5-4.8 0-1.6-.16-3.2-.5-4.8ZM10 15.4V8.6l5.8 3.4L10 15.4Z"/>
                    </svg>
                <?php endif; ?>
            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\components\landing-social-links.blade.php ENDPATH**/ ?>