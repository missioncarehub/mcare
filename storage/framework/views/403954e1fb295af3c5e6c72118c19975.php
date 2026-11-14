

<?php $__env->startSection('auth-content'); ?>
    <div class="auth-card-heading">
        <h2>Forgot password?</h2>
        <p>Enter your MCARE account email and we will send a one-time code.</p>
    </div>
    <?php if($errors->any()): ?><div class="auth-alert auth-alert-error" role="alert"><?php echo e($errors->first()); ?></div><?php endif; ?>
    <form method="POST" action="<?php echo e(route('password.email')); ?>" class="auth-form">
        <?php echo csrf_field(); ?>
        <div class="auth-field-group">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="<?php echo e(old('email', $email)); ?>" autocomplete="email" required autofocus placeholder="Enter your email address" class="auth-input">
            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="auth-field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        </div>
        <button type="submit" class="auth-primary-button"><span>Send OTP</span><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'arrow-right','ariaHidden' => 'true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-right','aria-hidden' => 'true']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></button>
    </form>
    <div class="auth-secondary-links auth-secondary-links-single"><a href="<?php echo e(route('login')); ?>">Back to sign in</a></div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('auth.password-layout', ['title' => 'Forgot password'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\auth\forgot-password.blade.php ENDPATH**/ ?>