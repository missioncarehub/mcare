<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Method | MCARE</title>
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
<body class="enrollment-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased" data-form-draft-clear="enrollment.create">
    <?php
        $fullName = preg_replace('/\s+/', ' ', trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name)) ?: 'Applicant';
        $amount = 'PHP '.number_format((float) ($application->downpayment_amount ?: $application->payment_amount ?: 0), 2);
        $batch = $application->batch;
        $activeUser = auth()->user();
        $backUrl = match($activeUser?->role) {
            'trainee' => route('trainee.payments'),
            'trainer' => route('trainer.dashboard'),
            'admin' => route('admin.enrollments.index'),
            default => route('enrollment.create'),
        };
        $backLabel = match($activeUser?->role) {
            'trainee' => 'Trainee portal',
            'trainer' => 'Trainer portal',
            'admin' => 'Admin portal',
            default => 'Enrollment',
        };
    ?>

    <div id="action-toast" class="fixed right-5 top-5 z-50 hidden max-w-sm rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold leading-6 text-amber-800">
        Too many actions. Please wait for the current request to finish.
    </div>

    <?php if (isset($component)) { $__componentOriginald5c36e5eb93f3b111375117a4289e4f9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5c36e5eb93f3b111375117a4289e4f9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.public-official-header','data' => ['mastheadAside' => 'Caregiving NC II · Official payment','navLabel' => 'Enrollment payment','secondaryHref' => route('payments.show'),'secondaryLabel' => 'Payments','secondaryCompactHide' => false,'primaryHref' => route('login'),'primaryLabel' => 'Sign in']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('public-official-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['masthead-aside' => 'Caregiving NC II · Official payment','nav-label' => 'Enrollment payment','secondary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('payments.show')),'secondary-label' => 'Payments','secondary-compact-hide' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(false),'primary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('login')),'primary-label' => 'Sign in']); ?>
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
                <p class="enrollment-kicker">Step 2 of official enrollment</p>
                <h1>Choose your payment method</h1>
                <p class="enrollment-lede">Copy your enrollment number first if you are not ready to pay. You can return later on the payments page with that number. MCARE records the result when you return from checkout or when the cashier verifies the receipt.</p>
                <dl class="enrollment-status-row">
                    <div>
                        <dt>Applicant</dt>
                        <dd><?php echo e($fullName); ?><span><?php echo e($application->email); ?></span></dd>
                    </div>
                    <div>
                        <dt>Program</dt>
                        <dd><?php echo e($application->program); ?><span><?php echo e($batch ? $batch->name.' '.$batch->year : 'Batch to be assigned'); ?></span></dd>
                    </div>
                    <div>
                        <dt>Downpayment</dt>
                        <dd><?php echo e($amount); ?><span><?php echo e($application->paymentStatusLabel()); ?></span></dd>
                    </div>
                </dl>
            </header>

            <div class="enrollment-form-body space-y-6">
                <?php if(session('payment_notice')): ?>
                    <p class="enrollment-notice enrollment-notice-ok"><?php echo e(session('payment_notice')); ?></p>
                <?php endif; ?>

                <?php if($errors->any()): ?>
                    <p class="enrollment-notice enrollment-notice-error"><?php echo e($errors->first('payment') ?: 'Please choose a valid payment method.'); ?></p>
                <?php endif; ?>

                <?php echo $__env->make('enrollment.partials.enrollment-number', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                <?php echo $__env->make('enrollment.partials.payment-methods', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </article>
    </main>

    <?php if (isset($component)) { $__componentOriginalb54bedc4af9c9c96c0755ad537184e90 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb54bedc4af9c9c96c0755ad537184e90 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.public-official-footer','data' => ['note' => 'Official enrollment payment for TESDA-accredited Caregiving NC II.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('public-official-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['note' => 'Official enrollment payment for TESDA-accredited Caregiving NC II.']); ?>
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
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\enrollment\payment.blade.php ENDPATH**/ ?>