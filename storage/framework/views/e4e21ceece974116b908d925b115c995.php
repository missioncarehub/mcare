<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alumni claim received | MCARE</title>
    <?php if (isset($component)) { $__componentOriginalc6a3599bb5002978b92223946fd5c1ba = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalc6a3599bb5002978b92223946fd5c1ba = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-theme-head','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-theme-head'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalc6a3599bb5002978b92223946fd5c1ba)): ?>
<?php $attributes = $__attributesOriginalc6a3599bb5002978b92223946fd5c1ba; ?>
<?php unset($__attributesOriginalc6a3599bb5002978b92223946fd5c1ba); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalc6a3599bb5002978b92223946fd5c1ba)): ?>
<?php $component = $__componentOriginalc6a3599bb5002978b92223946fd5c1ba; ?>
<?php unset($__componentOriginalc6a3599bb5002978b92223946fd5c1ba); ?>
<?php endif; ?>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body class="enrollment-page alumni-claim-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    <?php if (isset($component)) { $__componentOriginald5c36e5eb93f3b111375117a4289e4f9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5c36e5eb93f3b111375117a4289e4f9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.public-official-header','data' => ['mastheadAside' => 'Caregiving NC II · Official alumni claim','navLabel' => 'Alumni claim received','secondaryHref' => route('landing'),'secondaryLabel' => 'Public site','primaryHref' => route('login'),'primaryLabel' => 'Sign in']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('public-official-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['masthead-aside' => 'Caregiving NC II · Official alumni claim','nav-label' => 'Alumni claim received','secondary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('landing')),'secondary-label' => 'Public site','primary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('login')),'primary-label' => 'Sign in']); ?>
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
                <p class="enrollment-kicker">Claim submitted</p>
                <h1>Claim received</h1>
                <p class="enrollment-lede">Your alumni claim was recorded. Verify your email, then visit MCARE with a valid ID and your original COTC or TOR.</p>
            </header>
            <div class="enrollment-form-body space-y-6">
                <p class="enrollment-notice enrollment-notice-ok" role="status">
                    <?php if(filled($name)): ?>
                        <strong><?php echo e($name); ?></strong>, your alumni claim was recorded.
                    <?php else: ?>
                        <strong>Claim received.</strong> Your alumni claim was recorded.
                    <?php endif; ?>
                    <?php if($verificationSent && filled($email)): ?>
                        A verification link was sent to <strong><?php echo e($email); ?></strong>.
                    <?php elseif(! $verificationSent): ?>
                        The claim was recorded, but the verification email could not be sent. Contact MCARE administration.
                    <?php endif; ?>
                </p>

                <ol class="alumni-process">
                    <li><span>1</span><span><strong>Verify your email.</strong> Open the secure link sent to your inbox before visiting the center.</span></li>
                    <li><span>2</span><span><strong>Visit MCARE.</strong> Bring a valid ID and your original COTC or TOR.</span></li>
                    <li><span>3</span><span><strong>Record verification.</strong> An administrator checks the physical documents and the MCARE archive.</span></li>
                    <li><span>4</span><span><strong>Alumni access.</strong> Once approved, your graduate account becomes available.</span></li>
                </ol>

                <p class="enrollment-notice enrollment-notice-amber">Email verification confirms mailbox ownership only. Alumni access is activated only after physical identity, COTC/TOR, and archive verification.</p>

                <div class="flex flex-wrap gap-3">
                    <a href="<?php echo e(route('login')); ?>" class="primary-action">Go to sign in</a>
                    <a href="<?php echo e(route('landing')); ?>" class="secondary-action">Return to public site</a>
                </div>
            </div>
        </article>
    </main>

    <?php if (isset($component)) { $__componentOriginalb54bedc4af9c9c96c0755ad537184e90 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb54bedc4af9c9c96c0755ad537184e90 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.public-official-footer','data' => ['note' => 'Official alumni claim for TESDA-accredited Caregiving NC II. On-site identity and archive verification precede graduate account access.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('public-official-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['note' => 'Official alumni claim for TESDA-accredited Caregiving NC II. On-site identity and archive verification precede graduate account access.']); ?>
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
</body>
</html>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\alumni\claim-received.blade.php ENDPATH**/ ?>