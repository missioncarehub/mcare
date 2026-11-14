<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments | MCARE</title>
    <?php if (isset($component)) { $__componentOriginald9e77967a5438b63fd29d241808e49d9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald9e77967a5438b63fd29d241808e49d9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-favicon','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-favicon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald9e77967a5438b63fd29d241808e49d9)): ?>
<?php $attributes = $__attributesOriginald9e77967a5438b63fd29d241808e49d9; ?>
<?php unset($__attributesOriginald9e77967a5438b63fd29d241808e49d9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald9e77967a5438b63fd29d241808e49d9)): ?>
<?php $component = $__componentOriginald9e77967a5438b63fd29d241808e49d9; ?>
<?php unset($__componentOriginald9e77967a5438b63fd29d241808e49d9); ?>
<?php endif; ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="enrollment-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    <div id="action-toast" class="fixed right-5 top-5 z-50 hidden max-w-sm rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold leading-6 text-amber-800">
        Too many actions. Please wait for the current request to finish.
    </div>

    <?php if (isset($component)) { $__componentOriginald5c36e5eb93f3b111375117a4289e4f9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5c36e5eb93f3b111375117a4289e4f9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.public-official-header','data' => ['mastheadAside' => 'Caregiving NC II · Official payment','navLabel' => 'Payments','secondaryHref' => route('enrollment.create'),'secondaryLabel' => 'Enrollment','secondaryCompactHide' => false,'primaryHref' => route('login'),'primaryLabel' => 'Sign in']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('public-official-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['masthead-aside' => 'Caregiving NC II · Official payment','nav-label' => 'Payments','secondary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('enrollment.create')),'secondary-label' => 'Enrollment','secondary-compact-hide' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'primary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('login')),'primary-label' => 'Sign in']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald5c36e5eb93f3b111375117a4289e4f9)): ?>
<?php $attributes = $__attributesOriginald5c36e5eb93f3b111375117a4289e4f9; ?>
<?php unset($__attributesOriginald5c36e5eb93f3b111375117a4289e4f9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald5c36e5eb93f3b111375117a4289e4f9)): ?>
<?php $component = $__componentOriginald5c36e5eb93f3b111375117a4289e4f9; ?>
<?php unset($__componentOriginald5c36e5eb93f3b111375117a4289e4f9); ?>
<?php endif; ?>

    <main class="enrollment-main mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <article class="enrollment-sheet">
            <header class="enrollment-intro">
                <p class="enrollment-kicker">Enrollment payment</p>
                <h1>Continue with your enrollment number</h1>
                <p class="enrollment-lede">Enter the enrollment number issued after you submitted the enrollment form. If it is already paid, this page will show that status. If it is not paid yet, the payment methods will appear.</p>
            </header>

            <div class="enrollment-form-body space-y-6">
                <form method="POST" action="<?php echo e(route('payments.lookup')); ?>" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label class="form-label" for="enrollment_number">Enrollment number</label>
                        <input class="form-field" id="enrollment_number" name="enrollment_number" value="<?php echo e(old('enrollment_number', $submittedNumber)); ?>" placeholder="MCE-2026-XXXXXX" required>
                        <?php $__errorArgs = ['enrollment_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <button type="submit" class="primary-action">Look up payment</button>
                </form>

                <?php if(session('payment_notice')): ?>
                    <p class="enrollment-notice enrollment-notice-ok"><?php echo e(session('payment_notice')); ?></p>
                <?php endif; ?>

                <?php if($errors->any()): ?>
                    <p class="enrollment-notice enrollment-notice-error"><?php echo e($errors->first('payment') ?: $errors->first()); ?></p>
                <?php endif; ?>

                <?php if($lookedUp && ! $application): ?>
                    <p class="enrollment-notice enrollment-notice-error" role="alert">That enrollment number was not found. Check the number from the payment page or your confirmation email and try again.</p>
                <?php endif; ?>

                <?php if($application && $paymentCleared): ?>
                    <?php echo $__env->make('enrollment.partials.enrollment-number', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('enrollment.partials.enrollment-summary', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <p class="enrollment-notice enrollment-notice-ok">This enrollment is already paid. MCARE has the required payment on record.</p>
                <?php elseif($application): ?>
                    <?php echo $__env->make('enrollment.partials.enrollment-number', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('enrollment.partials.enrollment-summary', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php echo $__env->make('enrollment.partials.payment-methods', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php endif; ?>
            </div>
        </article>
    </main>

    <?php if (isset($component)) { $__componentOriginalb54bedc4af9c9c96c0755ad537184e90 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb54bedc4af9c9c96c0755ad537184e90 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.public-official-footer','data' => ['note' => 'Use the enrollment number to continue payment later.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('public-official-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['note' => 'Use the enrollment number to continue payment later.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalb54bedc4af9c9c96c0755ad537184e90)): ?>
<?php $attributes = $__attributesOriginalb54bedc4af9c9c96c0755ad537184e90; ?>
<?php unset($__attributesOriginalb54bedc4af9c9c96c0755ad537184e90); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalb54bedc4af9c9c96c0755ad537184e90)): ?>
<?php $component = $__componentOriginalb54bedc4af9c9c96c0755ad537184e90; ?>
<?php unset($__componentOriginalb54bedc4af9c9c96c0755ad537184e90); ?>
<?php endif; ?>

    <?php echo $__env->make('enrollment.partials.payment-scripts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\enrollment\payments.blade.php ENDPATH**/ ?>