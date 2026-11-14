<?php
    $activeUser = $activeUser ?? auth()->user();
?>

<?php if($activeUser): ?>
    
    <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-4 text-sm leading-6 text-amber-900">
        <div class="flex items-center gap-3">
            <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $activeUser,'class' => 'grid h-10 w-10 place-items-center rounded-full bg-amber-100 font-bold text-amber-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($activeUser),'class' => 'grid h-10 w-10 place-items-center rounded-full bg-amber-100 font-bold text-amber-800']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $attributes = $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $component = $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
            <div class="min-w-0">
                <p class="font-bold">Currently signed in</p>
                <p class="truncate"><?php echo e($activeUser->name); ?> · <?php echo e(\App\Support\AccountPortal::roleLabelFor($activeUser)); ?> · <?php echo e($activeUser->email); ?></p>
            </div>
        </div>
        <div class="mt-4 flex flex-col gap-2 sm:flex-row">
            <a href="<?php echo e(\App\Support\AccountPortal::urlFor($activeUser)); ?>" class="inline-flex items-center justify-center rounded-lg bg-amber-600 px-4 py-2 text-xs font-bold text-white hover:bg-amber-700">
                <?php echo e(\App\Support\AccountPortal::ctaLabelFor($activeUser)); ?>

            </a>
            <form method="POST" action="<?php echo e(route('logout')); ?>">
                <?php echo csrf_field(); ?>
                <button type="submit" class="inline-flex w-full items-center justify-center rounded-lg border border-amber-200 bg-white px-4 py-2 text-xs font-bold text-amber-800 hover:bg-amber-100">
                    Sign out to switch account
                </button>
            </form>
        </div>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\auth\partials\current-account.blade.php ENDPATH**/ ?>