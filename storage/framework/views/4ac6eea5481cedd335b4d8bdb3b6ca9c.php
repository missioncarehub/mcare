<?php
    $layout = \App\Support\AccountPortal::dashboardLayoutFor($user);
    $isAdmin = $user->role === 'admin';
    $siteSettings = $siteSettings ?? null;
    $registrarHasErrors = $errors->registrar->any();
    $registrarName = $registrarHasErrors ? old('registrar_name') : ($siteSettings?->registrarNameForForm() ?? '');
    $registrarSignatureType = $registrarHasErrors ? old('registrar_signature_type', 'draw') : ($siteSettings?->registrar_signature_type ?: 'draw');
    $hasSavedRegistrarSignature = (bool) $siteSettings?->hasRegistrarSignature();
    $expiryModes = \App\Models\PublicSiteSetting::unusedApprovedExpiryModes();
    $expiryOff = \App\Models\PublicSiteSetting::UNUSED_APPROVED_EXPIRY_OFF;
    $expiryDays = \App\Models\PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DAYS;
    $expiryMonths = \App\Models\PublicSiteSetting::UNUSED_APPROVED_EXPIRY_MONTHS;
    $expiryDateMode = \App\Models\PublicSiteSetting::UNUSED_APPROVED_EXPIRY_DATE;
?>



<?php $__env->startSection('content'); ?>
<section class="space-y-6">
    <?php if(session('saved') && $user->role === 'admin'): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800" role="status" aria-live="polite" data-auto-dismiss="5000"><?php echo e(session('saved')); ?></div>
    <?php endif; ?>

    <header class="border-b border-slate-200 pb-6">
        <p class="dashboard-section-kicker">Account preferences</p>
        <h1 class="dashboard-section-title mt-2 text-3xl">Settings</h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Manage the signed-in <?php echo e(strtolower($roleLabel)); ?> account, profile photo, display preference, and password.<?php echo e($isAdmin ? ' Admins can also save unused approved-application expiry and the TESDA form registrar here.' : ''); ?></p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.35fr)]">
        <div class="space-y-6">
            <section class="dashboard-panel space-y-4" data-profile-photo-form>
                <p class="dashboard-section-kicker">Signed-in account</p>
                <div class="flex items-center gap-4">
                    <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $user,'class' => 'grid h-14 w-14 place-items-center rounded-full bg-purple-100 text-lg font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user),'class' => 'grid h-14 w-14 place-items-center rounded-full bg-purple-100 text-lg font-black text-purple-800']); ?>
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
                        <h2 class="truncate text-xl font-bold text-slate-950"><?php echo e($user->name); ?></h2>
                        <p class="mt-1 truncate text-sm text-slate-600"><?php echo e($user->email); ?></p>
                    </div>
                </div>
                <span class="inline-flex rounded-lg bg-purple-50 px-3 py-1.5 text-xs font-bold text-purple-800"><?php echo e($roleLabel); ?></span>

                <form method="POST" action="<?php echo e(route('account.avatar.update')); ?>" enctype="multipart/form-data" class="space-y-3 border-t border-slate-200 pt-4">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <div>
                        <label for="avatar" class="block text-sm font-bold text-slate-700">Profile photo</label>
                        <p class="mt-1 text-xs leading-5 text-slate-500">JPG, PNG, or WEBP. Maximum 5MB. The photo is stored in public storage and used across MCARE dashboards.</p>
                        <input
                            id="avatar"
                            name="avatar"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            required
                            class="form-field mt-3 text-sm"
                            data-profile-photo-input
                        >
                        <?php $__errorArgs = ['avatar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="mt-2 block text-sm font-semibold text-red-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <button type="submit" class="secondary-action w-full sm:w-auto">
                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'cloud-arrow-up','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cloud-arrow-up','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                        <span>Save photo</span>
                    </button>
                </form>
            </section>

            <a href="<?php echo e(route('account.help')); ?>" class="dashboard-panel block space-y-2 transition hover:border-purple-300">
                <p class="dashboard-section-kicker">Need assistance?</p>
                <h2 class="text-lg font-bold text-slate-950">Open help center</h2>
                <p class="text-sm leading-6 text-slate-600">View guidance tailored to the <?php echo e(strtolower($roleLabel)); ?> portal.</p>
            </a>
        </div>

        <section id="change-password" class="dashboard-panel scroll-mt-8 space-y-4">
            <p class="dashboard-section-kicker">Security</p>
            <h2 class="text-lg font-bold text-slate-950">Change password</h2>
            <p class="text-sm leading-6 text-slate-600">Choose a strong password that meets all of these requirements:</p>
            <form method="POST" action="<?php echo e(route('account.password.update')); ?>" class="space-y-5">
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <label class="block text-sm font-bold text-slate-700">Current password
                    <div class="relative mt-2">
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="form-field pr-12 text-base">
                        <button type="button" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-slate-500 hover:text-purple-700" data-password-toggle="current_password" aria-label="Show current password" title="Show current password">
                            <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'eye','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'eye','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                        </button>
                    </div>
                    <?php $__errorArgs = ['current_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="mt-2 block text-sm font-semibold text-red-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </label>
                <label class="block text-sm font-bold text-slate-700">New password
                    <div class="relative mt-2">
                        <input id="password" name="password" type="password" autocomplete="new-password" required aria-describedby="password-requirements" class="form-field pr-12 text-base">
                        <button type="button" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-slate-500 hover:text-purple-700" data-password-toggle="password" aria-label="Show new password" title="Show new password">
                            <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'eye','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'eye','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                        </button>
                    </div>
                    <span id="password-requirements" class="mt-2 block text-xs font-semibold leading-5 text-slate-500">At least 10 characters, including an uppercase letter (A-Z), a lowercase letter (a-z), and a number (0-9).</span>
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="mt-2 block text-sm font-semibold text-red-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </label>
                <label class="block text-sm font-bold text-slate-700">Confirm new password
                    <div class="relative mt-2">
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="form-field pr-12 text-base">
                        <button type="button" class="absolute inset-y-0 right-0 grid w-12 place-items-center text-slate-500 hover:text-purple-700" data-password-toggle="password_confirmation" aria-label="Show password confirmation" title="Show password confirmation">
                            <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'eye','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'eye','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                        </button>
                    </div>
                </label>
                <button type="submit" class="primary-action">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'key','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'key','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                    <span>Update password</span>
                </button>
            </form>
        </section>
    </div>

    <?php if($isAdmin): ?>
        <?php
            $expiryHasErrors = $errors->unusedApprovedExpiry->any();
            $expiryMode = $expiryHasErrors
                ? old('unused_approved_expiry_mode', $expiryOff)
                : ($siteSettings?->unusedApprovedExpiryMode() ?? $expiryOff);
            $expiryAmount = $expiryHasErrors
                ? old('unused_approved_expiry_amount')
                : ($siteSettings?->unused_approved_expiry_amount ?? '');
            $expiryDate = $expiryHasErrors
                ? old('unused_approved_expiry_date')
                : optional($siteSettings?->unused_approved_expiry_date)->format('Y-m-d');
            $expirySummary = $siteSettings?->unusedApprovedExpirySummary();
        ?>
        <section id="unused-approved-expiry" class="dashboard-panel scroll-mt-8 space-y-4">
            <p class="dashboard-section-kicker">Applications</p>
            <h2 class="text-lg font-bold text-slate-950">Unused approved applications</h2>
            <p class="text-sm leading-6 text-slate-600">If an application is approved but the applicant never continues to enrollment, MCARE can delete that application automatically. Submitted enrollments are never removed by this setting.</p>
            <?php if($expirySummary && ! $expiryHasErrors): ?>
                <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium leading-6 text-amber-900"><?php echo e($expirySummary); ?></p>
            <?php endif; ?>
            <form method="POST" action="<?php echo e(route('account.unused-approved-expiry.update')); ?>" class="space-y-5" data-unused-approved-expiry-form>
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <fieldset class="space-y-3">
                    <legend class="text-sm font-bold text-slate-700">Delete unused approved applications</legend>
                    <?php $__currentLoopData = $expiryModes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mode => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="flex items-start gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-800">
                            <input type="radio" name="unused_approved_expiry_mode" value="<?php echo e($mode); ?>" class="mt-1" <?php if($expiryMode === $mode): echo 'checked'; endif; ?>>
                            <span><?php echo e($label); ?></span>
                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <?php $__errorArgs = ['unused_approved_expiry_mode', 'unusedApprovedExpiry'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="block text-sm font-semibold text-red-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </fieldset>

                <div data-expiry-amount-fields class="<?php echo e(in_array($expiryMode, [$expiryDays, $expiryMonths], true) ? '' : 'hidden'); ?>">
                    <label for="unused_approved_expiry_amount" class="block text-sm font-bold text-slate-700">Keep unused approved applications for</label>
                    <input id="unused_approved_expiry_amount" name="unused_approved_expiry_amount" type="number" min="1" max="365" value="<?php echo e($expiryAmount); ?>" class="form-field mt-2 max-w-xs text-base" placeholder="14">
                    <p class="mt-2 text-xs leading-5 text-slate-500">Counted from the day the application was approved. Days accept 1–365. Months accept 1–36.</p>
                    <?php $__errorArgs = ['unused_approved_expiry_amount', 'unusedApprovedExpiry'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="mt-2 block text-sm font-semibold text-red-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div data-expiry-date-fields class="<?php echo e($expiryMode === $expiryDateMode ? '' : 'hidden'); ?>">
                    <label for="unused_approved_expiry_date" class="block text-sm font-bold text-slate-700">Delete unused approved applications starting</label>
                    <input id="unused_approved_expiry_date" name="unused_approved_expiry_date" type="date" value="<?php echo e($expiryDate); ?>" class="form-field mt-2 max-w-xs text-base">
                    <p class="mt-2 text-xs leading-5 text-slate-500">Applications approved on or before this date are removed if the applicant still has not enrolled. Applications approved after this date stay until you set a new deadline.</p>
                    <?php $__errorArgs = ['unused_approved_expiry_date', 'unusedApprovedExpiry'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="mt-2 block text-sm font-semibold text-red-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <button type="submit" class="primary-action">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'save','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'save','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                    <span>Save application expiry</span>
                </button>
            </form>
        </section>

        <section id="tesda-registrar" class="dashboard-panel scroll-mt-8 space-y-4">
            <p class="dashboard-section-kicker">TESDA form</p>
            <h2 class="text-lg font-bold text-slate-950">TESDA form registrar</h2>
            <p class="text-sm leading-6 text-slate-600">This name and signature are placed on the <span class="font-semibold">Noted by: Registrar / School Administrator</span> line when you preview or download Registration Form MIS 03-01.</p>
            <form
                method="POST"
                action="<?php echo e(route('account.registrar.update')); ?>"
                enctype="multipart/form-data"
                class="space-y-5"
                data-single-action
                data-registrar-signature-form
                data-signature-saved="<?php echo e($hasSavedRegistrarSignature ? '1' : '0'); ?>"
            >
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <div>
                    <label for="registrar_name" class="block text-sm font-bold text-slate-700">Registrar / school administrator name</label>
                    <input id="registrar_name" name="registrar_name" type="text" value="<?php echo e($registrarName); ?>" required maxlength="180" placeholder="Salvacion A. Collao" class="form-field mt-2 max-w-xl text-base">
                    <p class="mt-2 text-xs leading-5 text-slate-500">Use the same printed name that should appear under the signature.</p>
                    <?php $__errorArgs = ['registrar_name', 'registrar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="mt-2 block text-sm font-semibold text-red-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div>
                    <label for="tor_title" class="block text-sm font-bold text-slate-700">Transcript title</label>
                    <input id="tor_title" name="tor_title" type="text" value="<?php echo e(old('tor_title', \App\Models\PublicSiteSetting::current()->tor_title)); ?>" maxlength="180" placeholder="OFFICIAL TRANSCRIPT OF RECORD" class="form-field mt-2 max-w-xl text-base">
                    <p class="mt-2 text-xs leading-5 text-slate-500">Leave this blank to keep OFFICIAL TRANSCRIPT OF RECORD. The next transcript uses this title.</p>
                    <?php $__errorArgs = ['tor_title', 'registrar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="mt-2 block text-sm font-semibold text-red-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-purple-700">Signature method</p>
                            <p class="mt-1 text-xs leading-5 text-slate-500">Draw on the pad or upload a clear JPG or PNG. Maximum 5MB.</p>
                        </div>
                        <div class="flex rounded-full border border-purple-100 bg-white p-1">
                            <label class="cursor-pointer rounded-full px-4 py-2 text-sm font-bold text-slate-700">
                                <input type="radio" name="registrar_signature_type" value="draw" class="mr-1" <?php if($registrarSignatureType === 'draw'): echo 'checked'; endif; ?>>
                                Draw
                            </label>
                            <label class="cursor-pointer rounded-full px-4 py-2 text-sm font-bold text-slate-700">
                                <input type="radio" name="registrar_signature_type" value="upload" class="mr-1" <?php if($registrarSignatureType === 'upload'): echo 'checked'; endif; ?>>
                                Upload
                            </label>
                        </div>
                    </div>

                    <div id="registrar-draw-signature-panel" class="mt-4">
                        <canvas id="registrar_signature_canvas" width="900" height="220" class="block h-44 w-full touch-none rounded-lg border border-slate-200 bg-white" aria-label="Draw the registrar signature"></canvas>
                        <input id="registrar_signature_data" name="registrar_signature_data" type="hidden" value="<?php echo e(old('registrar_signature_data')); ?>">
                        <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <p id="registrar_signature_draw_status" class="text-xs font-semibold text-slate-500">
                                <?php echo e($hasSavedRegistrarSignature ? 'Previously saved. Draw again to replace it.' : 'No signature drawn yet.'); ?>

                            </p>
                            <button type="button" id="clear_registrar_signature" class="inline-flex items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-bold text-slate-700 hover:border-purple-200 hover:text-purple-700">
                                Clear signature
                            </button>
                        </div>
                        <?php $__errorArgs = ['registrar_signature_data', 'registrar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="mt-2 block text-sm font-semibold text-red-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div id="registrar-upload-signature-panel" class="mt-4 hidden">
                        <div data-registrar-upload-zone class="relative rounded-xl border-2 border-dashed border-purple-200 bg-white px-5 py-7 text-center transition hover:border-purple-400 hover:bg-purple-50/50">
                            <input id="registrar_signature_upload" name="registrar_signature_upload" type="file" accept=".jpg,.jpeg,.png" class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0">
                            <p class="text-sm font-bold text-purple-700">Click to upload signature</p>
                            <p data-registrar-upload-name class="mt-1 text-xs font-semibold text-slate-500">
                                <?php echo e($hasSavedRegistrarSignature ? 'Previously saved. Upload a new image to replace it.' : 'No file selected'); ?>

                            </p>
                        </div>
                        <?php $__errorArgs = ['registrar_signature_upload', 'registrar'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="mt-2 block text-sm font-semibold text-red-600"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <?php if($hasSavedRegistrarSignature): ?>
                        <div class="mt-4 overflow-hidden rounded-xl border border-emerald-200 bg-white p-3">
                            <p class="text-[11px] font-black uppercase tracking-wide text-emerald-700">Saved signature</p>
                            <img src="<?php echo e(route('account.registrar.signature')); ?>" alt="Saved registrar signature" class="mt-2 max-h-24 w-full bg-white object-contain">
                        </div>
                    <?php endif; ?>
                </div>

                <button type="submit" data-action-button class="primary-action">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'save','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'save','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                    <span>Save registrar signature</span>
                </button>
            </form>
        </section>
    <?php endif; ?>
</section>

<?php if($isAdmin): ?>
    <script>
        (() => {
            const form = document.querySelector('[data-unused-approved-expiry-form]');
            if (!form) return;

            const amountFields = form.querySelector('[data-expiry-amount-fields]');
            const dateFields = form.querySelector('[data-expiry-date-fields]');
            const radios = form.querySelectorAll('input[name="unused_approved_expiry_mode"]');

            const syncExpiryFields = () => {
                const mode = form.querySelector('input[name="unused_approved_expiry_mode"]:checked')?.value || 'off';
                amountFields?.classList.toggle('hidden', mode !== 'days' && mode !== 'months');
                dateFields?.classList.toggle('hidden', mode !== 'date');
            };

            radios.forEach((radio) => radio.addEventListener('change', syncExpiryFields));
            syncExpiryFields();
        })();
    </script>
<?php endif; ?>

<?php if($isAdmin): ?>
    <script>
        (() => {
            const form = document.querySelector('[data-registrar-signature-form]');
            const canvas = document.getElementById('registrar_signature_canvas');
            const signatureData = document.getElementById('registrar_signature_data');
            const clearButton = document.getElementById('clear_registrar_signature');
            const status = document.getElementById('registrar_signature_draw_status');
            const drawPanel = document.getElementById('registrar-draw-signature-panel');
            const uploadPanel = document.getElementById('registrar-upload-signature-panel');
            const uploadInput = document.getElementById('registrar_signature_upload');
            const uploadName = document.querySelector('[data-registrar-upload-name]');
            const radios = document.querySelectorAll('input[name="registrar_signature_type"]');
            if (!form || !canvas || !signatureData) return;

            const existingSignatureSaved = form.dataset.signatureSaved === '1';
            const restoredSignatureData = signatureData.value;
            const context = canvas.getContext('2d');
            let drawing = false;
            let signatureDrawn = false;

            function resizeCanvas() {
                const image = signatureDrawn ? canvas.toDataURL('image/png') : null;
                const rect = canvas.getBoundingClientRect();
                const ratio = window.devicePixelRatio || 1;
                canvas.width = Math.max(Math.floor(rect.width * ratio), 1);
                canvas.height = Math.max(Math.floor(rect.height * ratio), 1);
                context.setTransform(ratio, 0, 0, ratio, 0, 0);
                context.lineWidth = 2.5;
                context.lineCap = 'round';
                context.lineJoin = 'round';
                context.strokeStyle = '#1e293b';
                if (image) {
                    const restored = new Image();
                    restored.onload = () => context.drawImage(restored, 0, 0, rect.width, rect.height);
                    restored.src = image;
                }
            }

            function pointFromEvent(event) {
                const rect = canvas.getBoundingClientRect();
                return { x: event.clientX - rect.left, y: event.clientY - rect.top };
            }

            function startDrawing(event) {
                event.preventDefault();
                drawing = true;
                const point = pointFromEvent(event);
                context.beginPath();
                context.moveTo(point.x, point.y);
            }

            function draw(event) {
                if (!drawing) return;
                event.preventDefault();
                const point = pointFromEvent(event);
                context.lineTo(point.x, point.y);
                context.stroke();
                signatureDrawn = true;
                signatureData.value = canvas.toDataURL('image/png');
                if (status) {
                    status.textContent = 'Signature captured.';
                    status.classList.remove('text-red-600');
                    status.classList.add('text-slate-500');
                }
            }

            function stopDrawing() {
                drawing = false;
                context.beginPath();
            }

            function syncSignatureMode() {
                const mode = document.querySelector('input[name="registrar_signature_type"]:checked')?.value || 'draw';
                drawPanel?.classList.toggle('hidden', mode !== 'draw');
                uploadPanel?.classList.toggle('hidden', mode !== 'upload');
            }

            function restoreSignature(data) {
                if (!data || !data.startsWith('data:image/png;base64,')) return;
                const restored = new Image();
                restored.onload = () => {
                    const rect = canvas.getBoundingClientRect();
                    context.drawImage(restored, 0, 0, rect.width, rect.height);
                    signatureDrawn = true;
                    signatureData.value = data;
                    if (status) status.textContent = 'Signature restored. Draw again to replace it.';
                };
                restored.src = data;
            }

            canvas.addEventListener('pointerdown', startDrawing);
            canvas.addEventListener('pointermove', draw);
            canvas.addEventListener('pointerup', stopDrawing);
            canvas.addEventListener('pointerleave', stopDrawing);
            clearButton?.addEventListener('click', () => {
                context.clearRect(0, 0, canvas.width, canvas.height);
                signatureDrawn = false;
                signatureData.value = '';
                if (status) {
                    status.textContent = existingSignatureSaved
                        ? 'Previously saved. Draw again to replace it.'
                        : 'No signature drawn yet.';
                    status.classList.remove('text-red-600');
                    status.classList.add('text-slate-500');
                }
            });
            radios.forEach((radio) => radio.addEventListener('change', syncSignatureMode));
            uploadInput?.addEventListener('change', () => {
                const file = uploadInput.files?.[0];
                if (uploadName) uploadName.textContent = file ? `Selected: ${file.name}` : (existingSignatureSaved ? 'Previously saved. Upload a new image to replace it.' : 'No file selected');
            });
            window.addEventListener('resize', resizeCanvas);
            form.addEventListener('submit', (event) => {
                const mode = document.querySelector('input[name="registrar_signature_type"]:checked')?.value || 'draw';
                if (mode === 'draw' && !signatureDrawn && !existingSignatureSaved) {
                    event.preventDefault();
                    if (status) {
                        status.textContent = 'Draw the registrar signature before saving.';
                        status.classList.remove('text-slate-500');
                        status.classList.add('text-red-600');
                    }
                    return;
                }
                if (mode === 'upload' && !uploadInput?.files?.length && !existingSignatureSaved) {
                    event.preventDefault();
                    if (uploadName) uploadName.textContent = 'Upload a registrar signature image before saving.';
                }
            });
            resizeCanvas();
            restoreSignature(restoredSignatureData);
            syncSignatureMode();
        })();
    </script>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make($layout, ['title' => 'Account Settings | MCARE '.$roleLabel], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\account\settings.blade.php ENDPATH**/ ?>