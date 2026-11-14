<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($enrollmentBatch?->program?->name ?? 'Training Program'); ?> Enrollment | MCARE</title>
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
    <style>
        /* Path: resources/views/enrollment/create.blade.php | Label: Phone-first enrollment density */
        @media (max-width: 767px) {
            .enrollment-header-inner {
                padding: 0.875rem 1rem;
                gap: 0.875rem;
            }

            .enrollment-logo {
                width: 2.75rem;
                height: 2.75rem;
                border-radius: 2px;
            }

            .enrollment-main {
                padding: 1rem;
            }

            .enrollment-form-body {
                padding: 1rem;
            }

            .enrollment-form > * + * {
                margin-top: 1.75rem !important;
            }

            .enrollment-section-heading {
                padding-bottom: 0.875rem;
            }

            .enrollment-section-heading h2,
            .enrollment-consent-card h2 {
                font-size: 1.125rem;
                line-height: 1.55rem;
            }

            .enrollment-fields {
                gap: 0.875rem;
                margin-top: 1rem;
            }

            .enrollment-page label {
                margin-bottom: 0.375rem;
                font-size: 0.8125rem;
            }

            .enrollment-page input:not([type="checkbox"]):not([type="radio"]),
            .enrollment-page select {
                min-height: 2.75rem;
                border-radius: 2px !important;
                padding: 0.7rem 0.875rem;
                font-size: 0.875rem;
            }

            .enrollment-page .enrollment-password-field input {
                padding-right: 2.75rem;
            }

            .enrollment-upload-zone {
                border-radius: 2px;
                padding: 1.25rem 0.875rem;
            }

            .enrollment-signature-switch {
                width: 100%;
                justify-content: center;
            }

            #signature_canvas {
                height: 9.75rem;
            }

            .enrollment-submit-row {
                padding-top: 1rem;
            }
        }

        /* Path: resources/views/enrollment/create.blade.php | Label: Enrollment section jump menu */
        .enrollment-jump-target {
            scroll-margin-top: 12.25rem;
        }

        .enrollment-jump-panel {
            max-height: min(58vh, 26rem);
            overflow-y: auto;
        }

        .enrollment-form input:not([type="checkbox"]):not([type="radio"]):not([type="file"]):not([type="hidden"]),
        .enrollment-form select,
        .enrollment-form textarea {
            min-height: 3rem;
            font-size: 1rem;
            line-height: 1.5rem;
        }
    </style>
</head>
<body class="enrollment-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased" <?php if($canCompleteEnrollment ?? false): ?> data-form-draft-clear="enrollment.unlock" <?php endif; ?>>
    <div class="enrollment-page-glow pointer-events-none fixed inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-purple-100 via-purple-50/70 to-white"></div>
    <div class="enrollment-page-glow pointer-events-none fixed inset-x-0 bottom-0 -z-10 h-72 bg-gradient-to-t from-purple-100 via-purple-50/60 to-white"></div>

    <div id="action-toast" class="fixed right-5 top-5 z-50 hidden max-w-sm rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold leading-6 text-amber-800">
        Too many actions. Please wait for the current request to finish.
    </div>

    <?php if (isset($component)) { $__componentOriginald5c36e5eb93f3b111375117a4289e4f9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5c36e5eb93f3b111375117a4289e4f9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.public-official-header','data' => ['mastheadAside' => 'Caregiving NC II · Official enrollment','navLabel' => 'Enrollment','secondaryHref' => route('landing'),'secondaryLabel' => 'Public site','primaryHref' => route('login'),'primaryLabel' => 'Sign in']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('public-official-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['masthead-aside' => 'Caregiving NC II · Official enrollment','nav-label' => 'Enrollment','secondary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('landing')),'secondary-label' => 'Public site','primary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('login')),'primary-label' => 'Sign in']); ?>
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
                <p class="enrollment-kicker">Official TESDA NC II applicant profile</p>
                <h1>
                    <?php echo e($selectedProgramName ?? $enrollmentBatch?->program?->name ?? 'Training Program'); ?> Enrollment
                </h1>
                <p class="enrollment-lede">
                    <?php if($canCompleteEnrollment): ?>
                        Your program is already selected from the application. Choose a batch for that program, then complete the official applicant profile. After the application is saved, you may use the same registered email with Google sign-in.
                    <?php else: ?>
                        Enter the approved application number from the applications page. The enrollment form opens only after MCARE approves that application.
                    <?php endif; ?>
                </p>
                <dl class="enrollment-status-row">
                    <div>
                        <dt>Program</dt>
                        <dd><?php echo e($selectedProgramName ?? $enrollmentBatch?->program?->name ?? 'From your application'); ?></dd>
                    </div>
                    <div>
                        <dt>Current step</dt>
                        <dd><?php echo e($canCompleteEnrollment ? 'Learner profile' : 'Application number'); ?></dd>
                    </div>
                    <div>
                        <dt>Enrollment batch</dt>
                        <dd>
                            <?php echo e($enrollmentBatch ? $enrollmentBatch->name.' '.$enrollmentBatch->year : 'Not selected'); ?>

                            <?php if($enrollmentBatch): ?>
                                <span><?php echo e($enrollmentBatch->enrollmentStateLabel()); ?> · <?php echo e($enrollmentBatch->trainingStateLabel()); ?></span>
                            <?php endif; ?>
                        </dd>
                    </div>
                </dl>
            </header>

            <?php if (! ($canCompleteEnrollment)): ?>
                <section class="enrollment-form-body" aria-labelledby="application-number-title">
                    <?php if($errors->any()): ?>
                        <p class="enrollment-notice enrollment-notice-error"><?php echo e($errors->first('application_number') ?: 'Please review the application number.'); ?></p>
                    <?php endif; ?>
                    <form method="POST" action="<?php echo e(route('enrollment.unlock')); ?>" class="space-y-5" data-form-draft="enrollment.unlock" <?php if($errors->any()): ?> data-form-draft-server-old="1" <?php endif; ?>>
                        <?php echo csrf_field(); ?>
                        <div class="enrollment-section-heading">
                            <p>Required before enrollment</p>
                            <h2 id="application-number-title">Enter your approved application number</h2>
                            <p>Submit an application first. After an administrator approves it, enter the number here to open the enrollment form.</p>
                        </div>
                        <div>
                            <label class="form-label" for="application_number">Application number</label>
                            <input class="form-field" id="application_number" name="application_number" value="<?php echo e(old('application_number', request('application_number'))); ?>" placeholder="MCA-2026-XXXXXX" required>
                            <?php $__errorArgs = ['application_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            <button type="submit" class="primary-action">Continue to enrollment</button>
                            <a href="<?php echo e(route('applications.create')); ?>" class="secondary-action">I still need to apply</a>
                            <a href="<?php echo e(route('applications.status')); ?>" class="secondary-action">Check application status</a>
                        </div>
                    </form>
                </section>
            <?php endif; ?>

            <?php if($canCompleteEnrollment): ?>
            <section class="enrollment-batches" aria-labelledby="available-batches-title">
                <div class="enrollment-section-heading">
                    <p>Admin-published enrollment</p>
                    <h2 id="available-batches-title">Choose a batch</h2>
                    <p><?php echo e($availableBatches->count()); ?> <?php echo e(str('batch')->plural($availableBatches->count())); ?> for <?php echo e($selectedProgramName ?? 'your selected program'); ?>. The program comes from your application and cannot be changed here.</p>
                </div>

                <?php if($application && $enrollmentBatch): ?>
                    <div class="enrollment-batch-option is-selected">
                        <p class="enrollment-kicker">Your saved batch</p>
                        <p class="enrollment-batch-title"><?php echo e($enrollmentBatch->name); ?> <?php echo e($enrollmentBatch->year); ?></p>
                        <p class="enrollment-batch-meta">
                            <strong class="text-purple-700">Enrollment deadline:</strong>
                            <?php echo e($enrollmentBatch->is_continuous_enrollment ? 'Continuous enrollment · no deadline' : ($enrollmentBatch->enrollment_ends_at?->format('M d, Y g:i A') ?? 'To be announced')); ?>

                        </p>
                    </div>
                <?php elseif($availableBatches->isNotEmpty()): ?>
                    <div class="enrollment-batch-list">
                        <?php $__currentLoopData = $availableBatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $availableBatch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $isSelectedBatch = (int) optional($enrollmentBatch)->id === (int) $availableBatch->id;
                            ?>
                            <a href="<?php echo e(route('enrollment.create', ['batch' => $availableBatch->id]).'#enrollment-form'); ?>" class="enrollment-batch-option<?php echo e($isSelectedBatch ? ' is-selected' : ''); ?>" <?php if($isSelectedBatch): ?> aria-current="true" <?php endif; ?>>
                                <div class="enrollment-batch-option-top">
                                    <div>
                                        <p class="enrollment-batch-title"><?php echo e($availableBatch->name); ?> <?php echo e($availableBatch->year); ?></p>
                                        <p class="enrollment-batch-meta"><?php echo e($availableBatch->scheduleLabelFor('AM')); ?> · <?php echo e($availableBatch->scheduleLabelFor('PM')); ?></p>
                                    </div>
                                    <span class="enrollment-batch-flag"><?php echo e($isSelectedBatch ? 'Selected' : 'Choose'); ?></span>
                                </div>
                                <p class="enrollment-batch-meta">AM: <?php echo e($availableBatch->scheduleLabelFor('AM')); ?></p>
                                <p class="enrollment-batch-meta">PM: <?php echo e($availableBatch->scheduleLabelFor('PM')); ?></p>
                                <p class="enrollment-batch-meta">
                                    <strong class="text-purple-700">Enrollment deadline:</strong>
                                    <?php echo e($availableBatch->is_continuous_enrollment ? 'Continuous enrollment · no deadline' : ($availableBatch->enrollment_ends_at?->format('M d, Y g:i A') ?? 'To be announced')); ?>

                                </p>
                                <p class="enrollment-batch-fee">Required downpayment: ₱<?php echo e(number_format((float) $availableBatch->program?->downpayment_amount, 2)); ?></p>
                            </a>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php else: ?>
                    <p class="enrollment-notice enrollment-notice-amber">No active batch is currently published for <?php echo e($selectedProgramName ?? 'this program'); ?>. Please wait for MCARE to open the next batch.</p>
                <?php endif; ?>
            </section>

            <section class="enrollment-jump-nav sticky z-40" aria-label="Enrollment form sections">
                <details id="enrollment-jump-details" class="group">
                    <summary class="enrollment-jump-summary">
                        <span>
                            <span class="block font-bold leading-5">Jump to section</span>
                            <span class="block text-xs font-semibold text-slate-500">Tap a part instead of scrolling the whole form.</span>
                        </span>
                        <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5 shrink-0 text-slate-400 transition group-open:rotate-180" aria-hidden="true">
                            <path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </summary>
                    <nav class="enrollment-jump-panel grid grid-cols-2 gap-x-4 gap-y-1 text-sm sm:grid-cols-4" aria-label="Enrollment form sections">
                        <a href="#enrollment-account">Account</a>
                        <a href="#enrollment-profile">Profile</a>
                        <a href="#enrollment-address">Address</a>
                        <a href="#enrollment-personal">Personal</a>
                        <a href="#enrollment-education">Education</a>
                        <a href="#enrollment-classification">Classification</a>
                        <a href="#enrollment-documents">Documents</a>
                        <a href="#enrollment-signature">Signature</a>
                        <a href="#enrollment-submit" class="enrollment-jump-submit">Submit</a>
                    </nav>
                </details>
            </section>

            <div class="enrollment-form-body">
            <?php if(! $application && ! $enrollmentBatch): ?>
                <p class="enrollment-notice enrollment-notice-amber">Choose an active batch from the window above. Only batches that are active, inside their enrollment window, and explicitly shown by an administrator can be selected.</p>
            <?php endif; ?>
            <?php if(session('saved')): ?>
                <p class="enrollment-notice enrollment-notice-ok" role="status" aria-live="polite" data-auto-dismiss="5000"><?php echo e(session('saved')); ?></p>
            <?php endif; ?>

            <?php if(session('reapply_notice')): ?>
                <p class="enrollment-notice enrollment-notice-info" role="status" aria-live="polite"><?php echo e(session('reapply_notice')); ?></p>
            <?php endif; ?>

            <?php if($errors->any()): ?>
                <p class="enrollment-notice enrollment-notice-error">Please review the highlighted fields and complete the required information.</p>
            <?php endif; ?>

            <?php if($application && ($application->admin_notes || $documentFeedback->isNotEmpty())): ?>
                <section class="enrollment-notice enrollment-notice-amber">
                    <p class="enrollment-kicker">Admin review feedback</p>
                    <?php if($application->admin_notes): ?>
                        <p class="mt-2 text-sm font-semibold leading-6"><?php echo e($application->admin_notes); ?></p>
                    <?php endif; ?>
                    <div class="mt-3 space-y-2">
                        <?php $__currentLoopData = $documentFeedback; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $feedback): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div>
                                <span class="font-bold"><?php echo e($documentLabels[$key] ?? 'Enrollment document'); ?>:</span>
                                <?php echo e(data_get($feedback, 'status') === 'missing' ? 'Missing.' : 'Needs replacement.'); ?>

                                <?php echo e(data_get($feedback, 'note')); ?>

                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <p class="mt-3 text-xs font-semibold text-amber-800">Upload corrected files below and submit the form again. Replaced documents return to admin review automatically.</p>
                </section>
            <?php endif; ?>

            <form
                id="enrollment-form"
                method="POST"
                action="<?php echo e(route('enrollment.store', absolute: false)); ?>"
                enctype="multipart/form-data"
                class="enrollment-form space-y-10"
                data-payment-status-url="<?php echo e(route('payment.status', absolute: false)); ?>"
                data-payment-url="<?php echo e(route('payment.show', absolute: false)); ?>"
                data-signature-saved="<?php echo e(($application->signature_path ?? false) ? '1' : '0'); ?>"
                data-requires-batch="<?php echo e((! $application && ! $enrollmentBatch) ? '1' : '0'); ?>"
                data-server-errors="<?php echo e(json_encode(array_values($errors->keys()))); ?>"
                data-address-regions-url="<?php echo e(route('enrollment.address.regions', absolute: false)); ?>"
                data-address-provinces-url="<?php echo e(route('enrollment.address.provinces', absolute: false)); ?>"
                data-address-cities-url="<?php echo e(route('enrollment.address.cities', absolute: false)); ?>"
                data-address-barangays-url="<?php echo e(route('enrollment.address.barangays', absolute: false)); ?>"
                data-address-region="<?php echo e(old('region', $application->region ?? '')); ?>"
                data-address-province="<?php echo e(old('province', $application->province ?? '')); ?>"
                data-address-city="<?php echo e(old('city', $application->city ?? '')); ?>"
                data-address-barangay="<?php echo e(old('barangay', $application->barangay ?? '')); ?>"
                data-form-draft="enrollment.create.<?php echo e($unlockedAdmission?->application_number ?? ($application?->admissionApplication?->application_number ?? 'open')); ?>"
                <?php if($errors->any()): ?> data-form-draft-server-old="1" <?php endif; ?>
            >
                <?php echo csrf_field(); ?>
                <?php if($unlockedAdmission): ?>
                    <input type="hidden" name="application_number" value="<?php echo e($unlockedAdmission->application_number); ?>">
                    <p class="enrollment-notice enrollment-notice-ok">Approved application number <?php echo e($unlockedAdmission->application_number); ?>. Complete the TESDA enrollment form below.</p>
                <?php endif; ?>
                <input type="hidden" name="training_batch_id" value="<?php echo e($enrollmentBatch?->id); ?>">
                <?php $__errorArgs = ['training_batch_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold leading-6 text-red-700"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

                <div id="enrollment-account" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Account</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Applicant account</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Google sign-in verifies the applicant identity. Browser autofill and saved MCARE details reduce repeat typing without requesting private Google profile data.</p>
                        <?php if($isGoogleApplicant): ?>
                            <div class="mt-4 inline-flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 px-3 py-2.5">
                                <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $user,'class' => 'grid h-10 w-10 place-items-center rounded-full bg-emerald-100 font-black text-emerald-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($user),'class' => 'grid h-10 w-10 place-items-center rounded-full bg-emerald-100 font-black text-emerald-800']); ?>
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
                                <span class="text-left">
                                    <strong class="block text-sm text-emerald-900"><?php echo e($user->name); ?></strong>
                                    <span class="block text-xs font-semibold text-emerald-700">Google profile connected</span>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <label for="email" class="mb-2 block text-sm font-semibold text-slate-800">Email address</label>
                            <input id="email" name="email" type="email" inputmode="email" autocomplete="section-applicant email" pattern="^[A-Za-z0-9._%+\-]+@gmail\.com$" value="<?php echo e(old('email', $application->email ?? $user?->email ?? $unlockedAdmission?->email ?? '')); ?>" required <?php if($user || $unlockedAdmission): echo 'readonly'; endif; ?> class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100 read-only:cursor-not-allowed read-only:bg-slate-100 read-only:text-slate-600">
                            <?php if($isGoogleApplicant): ?>
                                <p class="mt-2 text-xs font-semibold leading-5 text-emerald-700">Verified by Google and locked to this signed-in account.</p>
                            <?php elseif($user): ?>
                                <p class="mt-2 text-xs leading-5 text-slate-500">This email is locked to your signed-in MCARE account.</p>
                            <?php elseif($unlockedAdmission): ?>
                                <p class="mt-2 text-xs leading-5 text-slate-500">This Gmail is locked to approved application <?php echo e($unlockedAdmission->application_number); ?>.</p>
                            <?php else: ?>
                                <p class="mt-2 text-xs leading-5 text-slate-500">Use a Gmail address only. After MCARE saves your enrollment decision, a verification link will be emailed so you can sign in.</p>
                            <?php endif; ?>
                            <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="schedule_preference" class="mb-2 block text-sm font-semibold text-slate-800">Preferred schedule</label>
                            <select id="schedule_preference" name="schedule_preference" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Select</option>
                                <?php $__currentLoopData = ['AM' => 'AM class', 'PM' => 'PM class', 'Weekend' => 'Weekend class']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($value); ?>" <?php if(old('schedule_preference', $application->schedule_preference ?? $unlockedAdmission?->schedule_preference ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['schedule_preference'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <?php if($isGoogleApplicant): ?>
                            <div class="md:col-span-2 rounded-2xl border border-emerald-200 bg-emerald-50 p-4">
                                <p class="text-sm font-bold text-emerald-900">Google account connected</p>
                                <p class="mt-1 text-xs leading-5 text-emerald-800">No separate MCARE password is required. Use Continue with Google whenever you return.</p>
                            </div>
                        <?php else: ?>
                            <div>
                                <label for="password" class="mb-2 block text-sm font-semibold text-slate-800"><?php echo e($user ? 'New password (optional)' : 'Password'); ?></label>
                                <div class="enrollment-password-field">
                                    <input id="password" name="password" type="password" autocomplete="new-password" <?php if(! $user): echo 'required'; endif; ?> class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                    <button type="button" class="enrollment-password-toggle" data-password-toggle="password" aria-label="Show password" title="Show password">
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
                                <div class="mt-3 space-y-1.5 text-xs font-semibold">
                                    <p id="pw-length-check" class="flex items-center gap-2 text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> At least 10 characters</p>
                                    <p id="pw-letter-number-check" class="flex items-center gap-2 text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> Contains a number</p>
                                    <p id="pw-case-check" class="flex items-center gap-2 text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> Contains upper and lowercase letters</p>
                                </div>
                                <p class="mt-2 text-xs leading-5 text-slate-500"><?php echo e($user ? 'Leave blank to keep your current password.' : 'Use a unique passphrase you do not reuse on another website.'); ?></p>
                                <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div>
                                <label for="password_confirmation" class="mb-2 block text-sm font-semibold text-slate-800">Confirm password</label>
                                <div class="enrollment-password-field">
                                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" <?php if(! $user): echo 'required'; endif; ?> class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 pr-12 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                    <button type="button" class="enrollment-password-toggle" data-password-toggle="password_confirmation" aria-label="Show password confirmation" title="Show password">
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
                                <p id="pw-match-check" class="mt-3 flex items-center gap-2 text-xs font-semibold text-slate-500"><span class="grid h-5 w-5 place-items-center rounded-full border border-slate-300 text-[10px]"> </span> Passwords match</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div id="enrollment-profile" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Learner profile</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Name and contact details</h2>
                    </div>
                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-4">
                        <div>
                            <label for="last_name" class="mb-2 block text-sm font-semibold text-slate-800">Last name</label>
                            <input id="last_name" name="last_name" type="text" autocomplete="section-applicant family-name" value="<?php echo e(old('last_name', $application->last_name ?? $googleIdentity['last_name'] ?: ($unlockedAdmission?->last_name ?? ''))); ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="first_name" class="mb-2 block text-sm font-semibold text-slate-800">First name</label>
                            <input id="first_name" name="first_name" type="text" autocomplete="section-applicant given-name" value="<?php echo e(old('first_name', $application->first_name ?? $googleIdentity['first_name'] ?: ($unlockedAdmission?->first_name ?? ''))); ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="middle_name" class="mb-2 block text-sm font-semibold text-slate-800">Middle name</label>
                            <input id="middle_name" name="middle_name" type="text" autocomplete="section-applicant additional-name" value="<?php echo e(old('middle_name', $application->middle_name ?? $googleIdentity['middle_name'] ?: ($unlockedAdmission?->middle_name ?? ''))); ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['middle_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="extension_name" class="mb-2 block text-sm font-semibold text-slate-800">Extension</label>
                            <input id="extension_name" name="extension_name" type="text" autocomplete="section-applicant honorific-suffix" value="<?php echo e(old('extension_name', $application->extension_name ?? '')); ?>" placeholder="Jr., Sr." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['extension_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="contact_number" class="mb-2 block text-sm font-semibold text-slate-800">Contact number</label>
                            <input id="contact_number" name="contact_number" type="tel" inputmode="tel" autocomplete="section-applicant tel" value="<?php echo e(old('contact_number', $application->contact_number ?? $unlockedAdmission?->contact_number ?? '')); ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['contact_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="nationality" class="mb-2 block text-sm font-semibold text-slate-800">Nationality</label>
                            <input id="nationality" name="nationality" type="text" autocomplete="section-applicant country-name" value="<?php echo e(old('nationality', $application->nationality ?? 'Filipino')); ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['nationality'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <div id="enrollment-address" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Permanent mailing address</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Address information</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Choose region first. Province, city/municipality, and barangay then load from the official geographic list.</p>
                    </div>
                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-6">
                        <div>
                            <label for="region" class="mb-2 block text-sm font-semibold text-slate-800">Region</label>
                            <select id="region" name="region" autocomplete="off" required data-address-field="region" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Select region</option>
                            </select>
                            <?php $__errorArgs = ['region'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="province" class="mb-2 block text-sm font-semibold text-slate-800">Province</label>
                            <select id="province" name="province" autocomplete="section-address address-level1" required data-address-field="province" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Select region first</option>
                            </select>
                            <?php $__errorArgs = ['province'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="city" class="mb-2 block text-sm font-semibold text-slate-800">City/Municipality</label>
                            <select id="city" name="city" autocomplete="section-address address-level2" required data-address-field="city" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Select province first</option>
                            </select>
                            <?php $__errorArgs = ['city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="barangay" class="mb-2 block text-sm font-semibold text-slate-800">Barangay</label>
                            <select id="barangay" name="barangay" autocomplete="section-address address-level3" required data-address-field="barangay" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Select city first</option>
                            </select>
                            <?php $__errorArgs = ['barangay'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="md:col-span-2">
                            <label for="street" class="mb-2 block text-sm font-semibold text-slate-800">Number, street</label>
                            <input id="street" name="street" type="text" autocomplete="section-address address-line1" maxlength="100" value="<?php echo e(old('street', $application->street ?? '')); ?>" placeholder="e.g. 24 E. Corporal Street, Zone 1" required data-address-field="street" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-base outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <p class="mt-2 text-xs leading-5 text-slate-500">Enter only the house/building number, street, and zone. Barangay and city belong in their own fields.</p>
                            <?php $__errorArgs = ['street'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="zip_code" class="mb-2 block text-sm font-semibold text-slate-800">ZIP code</label>
                            <input id="zip_code" name="zip_code" type="text" inputmode="numeric" autocomplete="section-address postal-code" value="<?php echo e(old('zip_code', $application->zip_code ?? '')); ?>" required data-address-field="zip_code" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['zip_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <p id="address-lookup-status" class="md:col-span-6 text-sm font-semibold text-slate-500" role="status" aria-live="polite" data-address-lookup-status></p>
                    </div>
                </div>

                <div id="enrollment-personal" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Personal information</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Birth, status, and employment</h2>
                    </div>
                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-4">
                        <div>
                            <label for="gender" class="mb-2 block text-sm font-semibold text-slate-800">Sex</label>
                            <select id="gender" name="gender" autocomplete="section-applicant sex" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Select</option>
                                <?php $__currentLoopData = ['Female', 'Male']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($option); ?>" <?php if(old('gender', $application->gender ?? '') === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="civil_status" class="mb-2 block text-sm font-semibold text-slate-800">Civil status</label>
                            <select id="civil_status" name="civil_status" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Select</option>
                                <?php $__currentLoopData = ['Single', 'Married', 'Separated/Divorced/Annulled', 'Widow/er', 'Common Law/Live-in']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($option); ?>" <?php if(old('civil_status', $application->civil_status ?? '') === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['civil_status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="employment_status" class="mb-2 block text-sm font-semibold text-slate-800">Employment status</label>
                            <select id="employment_status" name="employment_status" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Select</option>
                                <?php $__currentLoopData = ['Wage-Employed', 'Underemployed', 'Self-Employed', 'Unemployed']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($option); ?>" <?php if(old('employment_status', $application->employment_status ?? '') === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['employment_status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="employment_type" class="mb-2 block text-sm font-semibold text-slate-800">Employment type</label>
                            <select id="employment_type" name="employment_type" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">None / not applicable</option>
                                <?php $__currentLoopData = ['Regular', 'Casual', 'Job Order', 'Probationary', 'Permanent', 'Contractual', 'Temporary']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($option); ?>" <?php if(old('employment_type', $application->employment_type ?? '') === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['employment_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="birth_date" class="mb-2 block text-sm font-semibold text-slate-800">Birthdate</label>
                            <input id="birth_date" name="birth_date" type="date" autocomplete="section-applicant bday" value="<?php echo e(old('birth_date', optional($application?->birth_date)->format('Y-m-d'))); ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['birth_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="md:col-span-4">
                            <label class="flex items-start gap-3 text-sm font-semibold leading-6 text-slate-700">
                                <input id="use-permanent-address-as-birthplace" type="checkbox" value="1" data-copy-address-to-birthplace aria-controls="birthplace_city birthplace_province birthplace_region" class="mt-1 h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                                <span>Use my permanent address as my birthplace</span>
                            </label>
                            <p class="mt-2 text-xs leading-5 text-slate-500">Copies region, province, and city/municipality from Address information into the birthplace fields.</p>
                        </div>
                        <div>
                            <label for="birthplace_city" class="mb-2 block text-sm font-semibold text-slate-800">Birthplace city</label>
                            <input id="birthplace_city" name="birthplace_city" type="text" value="<?php echo e(old('birthplace_city', $application->birthplace_city ?? '')); ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['birthplace_city'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="birthplace_province" class="mb-2 block text-sm font-semibold text-slate-800">Birthplace province</label>
                            <input id="birthplace_province" name="birthplace_province" type="text" value="<?php echo e(old('birthplace_province', $application->birthplace_province ?? '')); ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['birthplace_province'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="birthplace_region" class="mb-2 block text-sm font-semibold text-slate-800">Birthplace region</label>
                            <input id="birthplace_region" name="birthplace_region" type="text" value="<?php echo e(old('birthplace_region', $application->birthplace_region ?? '')); ?>" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['birthplace_region'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <div id="enrollment-education" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Education and guardian</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Training eligibility details</h2>
                    </div>
                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-3">
                        <div>
                            <label for="educational_attainment" class="mb-2 block text-sm font-semibold text-slate-800">Highest educational attainment</label>
                            <?php if($lockedEducationalAttainment): ?>
                                <input id="educational_attainment" type="text" value="<?php echo e($lockedEducationalAttainment); ?>" readonly data-locked-educational-attainment class="w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-600 outline-none read-only:cursor-not-allowed">
                                <input type="hidden" name="educational_attainment" value="<?php echo e($lockedEducationalAttainment); ?>">
                                <p class="mt-2 text-xs leading-5 text-slate-500">Copied from your approved application and cannot be changed.</p>
                            <?php else: ?>
                                <select id="educational_attainment" name="educational_attainment" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                    <option value="">Select</option>
                                    <?php $__currentLoopData = \App\Models\AdmissionApplication::educationalAttainmentOptions(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($option); ?>" <?php if(old('educational_attainment', $application->educational_attainment ?? '') === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            <?php endif; ?>
                            <?php $__errorArgs = ['educational_attainment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="school_name" class="mb-2 block text-sm font-semibold text-slate-800">School name</label>
                            <input id="school_name" name="school_name" type="text" value="<?php echo e(old('school_name', $application->school_name ?? '')); ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['school_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div data-year-graduated-wrap>
                            <label for="year_graduated" class="mb-2 block text-sm font-semibold text-slate-800">Year graduated</label>
                            <?php
                                $attainmentForYear = $lockedEducationalAttainment
                                    ?? old('educational_attainment', $application->educational_attainment ?? '');
                                $requiresGraduationYear = \App\Models\AdmissionApplication::requiresGraduationYear($attainmentForYear);
                            ?>
                            <div data-year-graduated-year class="<?php echo e($requiresGraduationYear ? '' : 'hidden'); ?>">
                                <input id="year_graduated" name="year_graduated" type="number" min="1950" max="<?php echo e(now()->year); ?>" value="<?php echo e(old('year_graduated', $requiresGraduationYear ? ($application->year_graduated ?? '') : '')); ?>" <?php if($requiresGraduationYear): echo 'required'; endif; ?> <?php if(! $requiresGraduationYear): echo 'disabled'; endif; ?> class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            </div>
                            <div data-year-graduated-na class="<?php echo e($requiresGraduationYear ? 'hidden' : ''); ?>">
                                <input type="text" value="N/A" readonly data-year-graduated-na-display class="w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm text-slate-600 outline-none read-only:cursor-not-allowed">
                                <input type="hidden" name="year_graduated" value="N/A" <?php if($requiresGraduationYear): echo 'disabled'; endif; ?> data-year-graduated-na-value>
                            </div>
                            <p class="mt-2 text-xs leading-5 text-slate-500" data-year-graduated-hint>
                                <?php echo e($requiresGraduationYear ? 'Enter the year you completed this level.' : 'N/A because the approved educational attainment is not a graduate level.'); ?>

                            </p>
                            <?php $__errorArgs = ['year_graduated'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="guardian_name" class="mb-2 block text-sm font-semibold text-slate-800">Parent/Guardian name</label>
                            <input id="guardian_name" name="guardian_name" type="text" autocomplete="section-guardian name" value="<?php echo e(old('guardian_name', $application->guardian_name ?? '')); ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['guardian_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="md:col-span-2">
                            <label for="guardian_address" class="mb-2 block text-sm font-semibold text-slate-800">Parent/Guardian permanent address</label>
                            <input id="guardian_address" name="guardian_address" type="text" autocomplete="section-guardian street-address" value="<?php echo e(old('guardian_address', $application->guardian_address ?? '')); ?>" required class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['guardian_address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <div id="enrollment-classification" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">TESDA classification</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Optional classification details</h2>
                    </div>
                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-4">
                        <div>
                            <label for="classification" class="mb-2 block text-sm font-semibold text-slate-800">Client classification</label>
                            <select id="classification" name="classification" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">None / not specified</option>
                                <?php $__currentLoopData = ['4Ps Beneficiary', 'Displaced Worker', 'Industry Worker', 'Out-of-School Youth', 'Overseas Filipino Worker', 'Returning/Repatriated OFW', 'Student', 'TESDA Alumni', 'TVET Trainer', 'Victim of Natural Disaster/Calamity', 'Others']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($option); ?>" <?php if(old('classification', $application->classification ?? '') === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['classification'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="disability_type" class="mb-2 block text-sm font-semibold text-slate-800">Disability type</label>
                            <select id="disability_type" name="disability_type" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Not applicable</option>
                                <?php $__currentLoopData = ['Mental/Intellectual', 'Visual Disability', 'Orthopedic Disability', 'Hearing Disability', 'Speech Impairment', 'Multiple Disabilities', 'Psychosocial Disability', 'Disability Due to Chronic Illness', 'Learning Disability']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($option); ?>" <?php if(old('disability_type', $application->disability_type ?? '') === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['disability_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="disability_cause" class="mb-2 block text-sm font-semibold text-slate-800">Cause of disability</label>
                            <select id="disability_cause" name="disability_cause" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                                <option value="">Not applicable</option>
                                <?php $__currentLoopData = ['Congenital/Inborn', 'Illness', 'Injury']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($option); ?>" <?php if(old('disability_cause', $application->disability_cause ?? '') === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['disability_cause'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="scholarship_type" class="mb-2 block text-sm font-semibold text-slate-800">Scholarship package</label>
                            <input id="scholarship_type" name="scholarship_type" type="text" value="<?php echo e(old('scholarship_type', $application->scholarship_type ?? '')); ?>" placeholder="TWSP, PESFA, STEP, etc." class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-300 focus:bg-white focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['scholarship_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <div id="enrollment-documents" class="enrollment-jump-target">
                    <div class="enrollment-section-heading border-b border-slate-100 pb-5">
                        <p class="text-sm font-bold uppercase text-purple-600">Document upload</p>
                        <h2 class="mt-2 text-2xl font-bold text-slate-900">Supporting requirements</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Accepted formats are PDF, JPG, JPEG, and PNG. ID photo accepts JPG or PNG only. Maximum size is 5MB per file.</p>
                    </div>

                    <?php
                        $uploadFields = [
                            'birth_certificate' => [
                                'label' => 'Birth Certificate (Photocopy)',
                                'description' => 'Clear PSA/NSO or local civil registrar copy.',
                                'accept' => '.pdf,.jpg,.jpeg,.png',
                                'path' => $application->birth_certificate_path ?? null,
                            ],
                            'education_document' => [
                                'label' => 'Form 137/138 or Diploma',
                                'description' => 'Upload the document that verifies your educational background.',
                                'accept' => '.pdf,.jpg,.jpeg,.png',
                                'path' => $application->education_document_path ?? null,
                            ],
                            'good_moral_certificate' => [
                                'label' => 'Certificate of Good Moral',
                                'description' => 'Upload a readable copy from your school or issuing office.',
                                'accept' => '.pdf,.jpg,.jpeg,.png',
                                'path' => $application->good_moral_certificate_path ?? null,
                            ],
                            'id_photo' => [
                                'label' => '1x1 or 2x2 ID Photo',
                                'description' => 'Use a recent formal ID photo in JPG or PNG format.',
                                'accept' => '.jpg,.jpeg,.png',
                                'path' => $application->id_photo_path ?? null,
                            ],
                        ];

                        // Browsers cannot repopulate a file input after the server
                        // redirects back with validation errors. The controller
                        // stores valid files in a short-lived, session-bound draft;
                        // include that draft in the field state so it is not asked
                        // for again and can be previewed immediately.
                        foreach ($uploadFields as $field => &$details) {
                            $details['draft'] = data_get($draftUploads, $field);
                            $details['has_file'] = filled($details['path']) || filled(data_get($details['draft'], 'path'));
                        }
                        unset($details);
                    ?>

                    <div class="enrollment-fields mt-6 grid grid-cols-1 gap-5 md:grid-cols-2">
                        <?php $__currentLoopData = $uploadFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $details): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="enrollment-upload-card">
                                <label for="<?php echo e($field); ?>" class="block text-sm font-bold text-slate-900"><?php echo e($details['label']); ?></label>
                                <p class="mt-1 text-xs leading-5 text-slate-500"><?php echo e($details['description']); ?></p>
                                <div data-upload-zone class="enrollment-upload-zone relative mt-4 rounded-2xl border-2 border-dashed border-purple-200 bg-white px-5 py-7 text-center transition hover:border-purple-400 hover:bg-purple-50/50">
                                    <input id="<?php echo e($field); ?>" name="<?php echo e($field); ?>" type="file" accept="<?php echo e($details['accept']); ?>" <?php if(! $details['has_file']): echo 'required'; endif; ?> class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0">
                                    <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-purple-50 text-purple-600">
                                        <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                                            <path d="M12 16V4m0 0L8 8m4-4 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </div>
                                    <p class="mt-3 text-sm font-bold text-purple-700">Click to upload or drag and drop</p>
                                    <p data-upload-name class="mt-1 text-xs font-semibold text-slate-500">
                                        <?php if($details['draft']): ?>
                                            Preserved: <?php echo e(data_get($details['draft'], 'name', 'uploaded file')); ?>. Upload a new file to replace it.
                                        <?php elseif($details['path']): ?>
                                            Previously uploaded. Upload a new file to replace it.
                                        <?php else: ?>
                                            No file selected
                                        <?php endif; ?>
                                    </p>
                                    <div data-upload-preview class="mt-4 hidden overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-2 text-left"></div>
                                    <?php if($details['draft']): ?>
                                        <div data-draft-preview class="mt-4 overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50/40 p-2 text-left">
                                            <p class="mb-2 text-[11px] font-black uppercase tracking-wide text-emerald-700">Preserved upload preview</p>
                                            <?php if(str_starts_with((string) data_get($details['draft'], 'mime'), 'image/')): ?>
                                                <img src="<?php echo e(route('enrollment.drafts.content', ['field' => $field])); ?>" alt="<?php echo e($details['label']); ?> preview" class="max-h-52 w-full rounded-lg object-contain" loading="lazy">
                                            <?php elseif(data_get($details['draft'], 'mime') === 'application/pdf'): ?>
                                                <iframe src="<?php echo e(route('enrollment.drafts.content', ['field' => $field])); ?>" title="<?php echo e($details['label']); ?> preview" class="h-56 w-full rounded-lg bg-white" loading="lazy"></iframe>
                                            <?php else: ?>
                                                <a href="<?php echo e(route('enrollment.drafts.content', ['field' => $field])); ?>" target="_blank" rel="noopener" class="text-sm font-bold text-purple-700 underline">Open preserved file preview</a>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php $__errorArgs = [$field];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>

                <div id="enrollment-signature" class="enrollment-consent-card enrollment-jump-target">
                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
                        <div class="md:col-span-2">
                            <h2 class="text-2xl font-bold text-slate-900">Privacy consent and signature</h2>
                            <p class="mt-3 text-sm leading-6 text-slate-600">
                                I attest that I have read and understood the TESDA privacy notice and consent to the processing of my personal information for training, scholarship, employment, survey, and related programs.
                            </p>
                            <label class="mt-5 flex items-start gap-3 text-sm font-semibold leading-6 text-slate-700">
                                <input id="privacy_consent" name="privacy_consent" type="checkbox" value="1" <?php if(old('privacy_consent', $application?->privacy_consent ?? false)): echo 'checked'; endif; ?> required class="mt-1 h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                                <span>I agree and certify that the information stated above is true and correct.</span>
                            </label>
                            <?php $__errorArgs = ['privacy_consent'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="signature_name" class="mb-2 block text-sm font-semibold text-slate-800">Applicant signature over printed name</label>
                            <input id="signature_name" name="signature_name" type="text" autocomplete="section-applicant name" value="<?php echo e(old('signature_name', $application->signature_name ?? $googleIdentity['full_name'])); ?>" required class="w-full rounded-2xl border border-purple-200 bg-white px-4 py-3 text-sm outline-none focus:border-purple-300 focus:ring-4 focus:ring-purple-100">
                            <?php $__errorArgs = ['signature_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            <p class="mt-3 text-xs leading-5 text-slate-500">Use the same name shown in your drawn or uploaded signature.</p>
                        </div>
                    </div>

                    <div class="enrollment-signature-card mt-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm font-bold uppercase text-purple-600">E-signature method</p>
                                <p class="mt-1 text-sm leading-6 text-slate-500">Draw directly on the pad or upload a clear signature image.</p>
                            </div>
                            <div class="enrollment-signature-switch flex rounded-full border border-purple-100 bg-purple-50 p-1">
                                <label class="cursor-pointer rounded-full px-4 py-2 text-sm font-bold text-slate-700">
                                    <input type="radio" name="signature_type" value="draw" class="mr-1" <?php if(old('signature_type', $application->signature_type ?? 'draw') === 'draw'): echo 'checked'; endif; ?>>
                                    Draw
                                </label>
                                <label class="cursor-pointer rounded-full px-4 py-2 text-sm font-bold text-slate-700">
                                    <input type="radio" name="signature_type" value="upload" class="mr-1" <?php if(old('signature_type', $application->signature_type ?? 'draw') === 'upload'): echo 'checked'; endif; ?>>
                                    Upload
                                </label>
                            </div>
                        </div>

                        <div id="draw-signature-panel" class="mt-5">
                            <div class="enrollment-signature-pad">
                                <canvas id="signature_canvas" width="900" height="260" class="block h-52 w-full touch-none border border-slate-200 bg-white" aria-label="Draw your signature"></canvas>
                            </div>
                            <input id="signature_data" name="signature_data" type="hidden" value="<?php echo e(old('signature_data')); ?>">
                            <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <p id="signature_draw_status" class="text-xs font-semibold text-slate-500"><?php echo e(($application->signature_path ?? null) ? 'Previously saved. Draw again to replace it.' : 'No signature drawn yet.'); ?></p>
                                <button type="button" id="clear_signature" class="inline-flex items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 hover:border-purple-200 hover:text-purple-700">
                                    Clear signature
                                </button>
                            </div>
                            <?php $__errorArgs = ['signature_data'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <div id="upload-signature-panel" class="mt-5 hidden">
                            <div data-upload-zone class="relative rounded-2xl border-2 border-dashed border-purple-200 bg-slate-50 px-5 py-7 text-center transition hover:border-purple-400 hover:bg-purple-50/50">
                                <input id="signature_upload" name="signature_upload" type="file" accept=".jpg,.jpeg,.png" class="absolute inset-0 z-10 h-full w-full cursor-pointer opacity-0">
                                <div class="mx-auto flex h-11 w-11 items-center justify-center rounded-2xl bg-purple-50 text-purple-600">
                                    <svg viewBox="0 0 24 24" fill="none" class="h-6 w-6" aria-hidden="true">
                                        <path d="M12 16V4m0 0L8 8m4-4 4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </div>
                                <p class="mt-3 text-sm font-bold text-purple-700">Click to upload signature</p>
                                <p data-upload-name class="mt-1 text-xs font-semibold text-slate-500">
                                    <?php if(data_get($draftUploads, 'signature_upload')): ?>
                                        Preserved: <?php echo e(data_get($draftUploads, 'signature_upload.name', 'signature image')); ?>. Upload a new image to replace it.
                                    <?php elseif($application->signature_path ?? null): ?>
                                        Previously uploaded/saved. Upload a new image to replace it.
                                    <?php else: ?>
                                        No file selected
                                    <?php endif; ?>
                                </p>
                                <div data-upload-preview class="mt-4 hidden overflow-hidden rounded-xl border border-slate-200 bg-slate-50 p-2 text-left"></div>
                                <?php if(data_get($draftUploads, 'signature_upload')): ?>
                                    <div data-draft-preview class="mt-4 overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50/40 p-2 text-left">
                                        <p class="mb-2 text-[11px] font-black uppercase tracking-wide text-emerald-700">Preserved signature preview</p>
                                        <img src="<?php echo e(route('enrollment.drafts.content', ['field' => 'signature_upload'])); ?>" alt="Preserved signature preview" class="max-h-32 w-full rounded-lg object-contain" loading="lazy">
                                    </div>
                                <?php endif; ?>
                            </div>
                            <?php $__errorArgs = ['signature_upload'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-2 text-sm font-semibold text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <div id="enrollment-submit" class="enrollment-submit-row enrollment-jump-target border-t border-slate-100 pt-6">
                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-sm leading-6 text-slate-500">Date accomplished will be recorded automatically when the form is submitted.</p>
                        <button type="submit" formaction="<?php echo e(route('enrollment.store', absolute: false)); ?>" formmethod="post" data-enrollment-submit data-default-label="<?php echo e($application?->status === \App\Models\EnrollmentApplication::STATUS_DENIED ? 'Resubmit corrected enrollment' : 'Submit enrollment'); ?>" <?php if((! $application && ! $enrollmentBatch) || ! old('privacy_consent', $application?->privacy_consent ?? false)): echo 'disabled'; endif; ?> class="inline-flex h-12 items-center justify-center rounded-full bg-purple-600 px-8 text-sm font-bold text-white shadow-lg shadow-purple-100 hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50 sm:h-auto sm:py-4">
                            <?php echo e($application?->status === \App\Models\EnrollmentApplication::STATUS_DENIED ? 'Resubmit corrected enrollment' : 'Submit enrollment'); ?>

                        </button>
                    </div>

                    <div id="enrollment-submit-progress" class="mt-4 hidden rounded-2xl border border-purple-200 bg-purple-50 p-4" role="status" aria-live="polite">
                        <div class="flex items-start gap-3">
                            <span class="mcare-spinner mt-0.5" aria-hidden="true"></span>
                            <div class="min-w-0 flex-1">
                                <p id="enrollment-submit-title" class="text-sm font-black text-purple-900">Submitting your enrollment…</p>
                                <p id="enrollment-submit-detail" class="mt-1 text-xs font-semibold leading-5 text-purple-700">Keep this page open. The next screen opens when the application is saved.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            </div>
            <?php endif; ?>
        </article>
    </main>

    <?php if (isset($component)) { $__componentOriginalb54bedc4af9c9c96c0755ad537184e90 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb54bedc4af9c9c96c0755ad537184e90 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.public-official-footer','data' => ['note' => 'Official enrollment for TESDA-accredited Caregiving NC II. Payment verification precedes administrative review.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('public-official-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['note' => 'Official enrollment for TESDA-accredited Caregiving NC II. Payment verification precedes administrative review.']); ?>
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

    <script>
        const blockedCharacters = /[<>"`;{}|\\]/;
        const passwordInput = document.getElementById('password');
        const passwordConfirmationInput = document.getElementById('password_confirmation');
        const emailInput = document.getElementById('email');
        const enrollmentForm = document.getElementById('enrollment-form');
        const actionToast = document.getElementById('action-toast');
        const enrollmentJumpDetails = document.getElementById('enrollment-jump-details');
        const enrollmentSubmitButton = enrollmentForm?.querySelector('[data-enrollment-submit]');
        const privacyConsentInput = enrollmentForm?.querySelector('#privacy_consent');
        const enrollmentSubmitProgress = document.getElementById('enrollment-submit-progress');
        const existingSignatureSaved = enrollmentForm?.dataset.signatureSaved === '1';
        const serverErrorFields = JSON.parse(enrollmentForm?.dataset.serverErrors || '[]');
        let signatureDrawn = false;

        function showActionToast(message) {
            if (!actionToast) return;
            actionToast.textContent = message;
            actionToast.classList.remove('hidden');
            window.clearTimeout(window.mcareEnrollmentToastTimer);
            window.mcareEnrollmentToastTimer = window.setTimeout(() => actionToast.classList.add('hidden'), 2800);
        }

        function formatFileSize(bytes) {
            if (!bytes) return '0 MB';

            return `${(bytes / (1024 * 1024)).toFixed(bytes >= 1024 * 1024 ? 1 : 2)} MB`;
        }

        function setCheckState(elementId, isValid) {
            const row = document.getElementById(elementId);
            if (!row) return;

            const icon = row.querySelector('span');
            row.classList.toggle('text-emerald-700', isValid);
            row.classList.toggle('text-slate-500', !isValid);
            icon.classList.toggle('border-emerald-500', isValid);
            icon.classList.toggle('bg-emerald-500', isValid);
            icon.classList.toggle('text-white', isValid);
            icon.classList.toggle('border-slate-300', !isValid);
            icon.classList.toggle('bg-transparent', !isValid);
            icon.textContent = isValid ? '✓' : '';
        }

        function updatePasswordChecks() {
            const password = passwordInput?.value || '';
            const confirmation = passwordConfirmationInput?.value || '';

            setCheckState('pw-length-check', password.length >= 10);
            setCheckState('pw-letter-number-check', /\d/.test(password));
            setCheckState('pw-case-check', /[a-z]/.test(password) && /[A-Z]/.test(password));
            setCheckState('pw-match-check', password.length > 0 && password === confirmation);

            // Passwords may use punctuation; server-side Password rules handle
            // strength while the client only mirrors the match indicator.
            passwordInput?.setCustomValidity('');

            if (passwordConfirmationInput) {
                passwordConfirmationInput.setCustomValidity(
                    confirmation.length > 0 && password !== confirmation
                        ? 'Passwords do not match.'
                        : ''
                );
            }
        }

        function requiresGraduationYear(value) {
            const attainment = String(value || '').trim();
            if (!attainment) return false;
            if (attainment === 'Masteral' || attainment === 'Doctorate') return true;
            return attainment.includes('Graduate') && !attainment.includes('Undergraduate');
        }

        function currentEducationalAttainment() {
            const locked = document.querySelector('[data-locked-educational-attainment]');
            if (locked) return locked.value;
            const select = document.getElementById('educational_attainment');
            if (select && select.tagName === 'SELECT') return select.value;
            const hidden = enrollmentForm?.querySelector('input[type="hidden"][name="educational_attainment"]');
            return hidden?.value || '';
        }

        function attachYearGraduatedField() {
            const wrap = document.querySelector('[data-year-graduated-wrap]');
            if (!wrap) return;

            const yearWrap = wrap.querySelector('[data-year-graduated-year]');
            const naWrap = wrap.querySelector('[data-year-graduated-na]');
            const yearInput = yearWrap?.querySelector('input[name="year_graduated"]');
            const naValue = wrap.querySelector('[data-year-graduated-na-value]');
            const hint = wrap.querySelector('[data-year-graduated-hint]');
            const select = document.getElementById('educational_attainment');

            const syncYearGraduatedField = () => {
                const graduate = requiresGraduationYear(currentEducationalAttainment());
                yearWrap?.classList.toggle('hidden', !graduate);
                naWrap?.classList.toggle('hidden', graduate);
                if (yearInput) {
                    yearInput.disabled = !graduate;
                    yearInput.required = graduate;
                }
                if (naValue) {
                    naValue.disabled = graduate;
                }
                if (hint) {
                    hint.textContent = graduate
                        ? 'Enter the year you completed this level.'
                        : 'N/A because the approved educational attainment is not a graduate level.';
                }
            };

            if (select && select.tagName === 'SELECT') {
                select.addEventListener('change', syncYearGraduatedField);
            }
            syncYearGraduatedField();
        }

        function attachInputHardening() {
            document.querySelectorAll('input[type="text"]').forEach((input) => {
                input.addEventListener('input', () => {
                    input.setCustomValidity(blockedCharacters.test(input.value)
                        ? 'This field contains a blocked security character.'
                        : ''
                    );
                });
            });

            emailInput?.addEventListener('input', () => {
                const email = emailInput.value.trim().toLowerCase();
                emailInput.value = email;
                emailInput.setCustomValidity(email && !/^[a-z0-9._%+-]+@gmail\.com$/.test(email)
                    ? 'Please use a Gmail address ending in @gmail.com.'
                    : ''
                );
            });

            passwordInput?.addEventListener('input', updatePasswordChecks);
            passwordConfirmationInput?.addEventListener('input', updatePasswordChecks);
            updatePasswordChecks();
        }

        function attachBirthplaceAddressCopy() {
            const form = document.getElementById('enrollment-form');
            const checkbox = document.querySelector('[data-copy-address-to-birthplace]');
            const mappings = [
                ['city', 'birthplace_city'],
                ['province', 'birthplace_province'],
                ['region', 'birthplace_region'],
            ].map(([sourceId, targetId]) => ({
                source: document.getElementById(sourceId),
                target: document.getElementById(targetId),
            }));

            if (!form || !checkbox || mappings.some(({ source, target }) => !source || !target)) {
                return;
            }

            function selectedAddressValue(select) {
                const option = select.options[select.selectedIndex];
                return option && option.value ? option.value.trim() : '';
            }

            function syncFromPermanentAddress() {
                if (!checkbox.checked) {
                    return;
                }

                mappings.forEach(({ source, target }) => {
                    target.value = selectedAddressValue(source);
                    target.dispatchEvent(new Event('input', { bubbles: true }));
                });
            }

            function applyLockedState() {
                mappings.forEach(({ target }) => {
                    target.readOnly = checkbox.checked;
                    target.classList.toggle('cursor-not-allowed', checkbox.checked);
                });
                syncFromPermanentAddress();
            }

            checkbox.addEventListener('change', applyLockedState);
            mappings.forEach(({ source }) => source.addEventListener('change', syncFromPermanentAddress));
            form.addEventListener('address-lookup-updated', syncFromPermanentAddress);
        }

        function attachAddressAutofillGuard() {
            const street = document.getElementById('street');
            const addressParts = ['region', 'province', 'city', 'barangay', 'zip_code']
                .map((id) => document.getElementById(id))
                .filter((field) => field);

            if (!street || !addressParts.length) return;

            const normalize = (value) => value.trim().replace(/\s+/g, ' ').toLocaleLowerCase();

            function keepStreetOnly() {
                const value = street.value.trim();
                if (!value.includes(',')) return;

                const knownAddressParts = addressParts
                    .map((field) => normalize(field.value))
                    .filter((part) => part.length > 1);
                if (!knownAddressParts.length) return;

                const segments = value.split(/\s*,\s*/).map((segment) => segment.trim()).filter(Boolean);
                const filtered = segments.filter((segment) => !knownAddressParts.includes(normalize(segment)));

                // Keep a useful value if a browser profile happens to contain
                // one shared string for every address field.
                if (filtered.length && filtered.length !== segments.length) {
                    street.value = filtered.join(', ');
                }
            }

            [...addressParts, street].forEach((field) => {
                ['input', 'change', 'blur'].forEach((eventName) => field.addEventListener(eventName, () => {
                    // Autofill events are dispatched one field at a time. Run
                    // again after the profile has populated the remaining
                    // address fields so Number, street stays self-contained.
                    keepStreetOnly();
                    window.setTimeout(keepStreetOnly, 200);
                    window.setTimeout(keepStreetOnly, 900);
                }));
            });

            window.setTimeout(keepStreetOnly, 900);
        }

        function attachUploadFeedback() {
            document.querySelectorAll('[data-upload-zone] input[type="file"]').forEach((input) => {
                const zone = input.closest('[data-upload-zone]');
                const preview = zone?.querySelector('[data-upload-preview]');
                const draftPreview = zone?.querySelector('[data-draft-preview]');

                function clearPreview() {
                    if (!preview) return;
                    if (preview.dataset.objectUrl) {
                        URL.revokeObjectURL(preview.dataset.objectUrl);
                        delete preview.dataset.objectUrl;
                    }
                    preview.replaceChildren();
                    preview.classList.add('hidden');
                }

                function showPreview(file) {
                    if (!preview || !file) return;

                    clearPreview();
                    const objectUrl = URL.createObjectURL(file);
                    preview.dataset.objectUrl = objectUrl;
                    preview.classList.remove('hidden');

                    const heading = document.createElement('p');
                    heading.className = 'mb-2 text-[11px] font-black uppercase tracking-wide text-purple-700';
                    heading.textContent = 'Attached document preview';
                    preview.appendChild(heading);

                    if (file.type === 'application/pdf') {
                        const frame = document.createElement('iframe');
                        frame.className = 'h-56 w-full rounded-lg bg-white';
                        frame.title = `${file.name} preview`;
                        frame.src = objectUrl;
                        preview.appendChild(frame);
                    } else if (file.type.startsWith('image/')) {
                        const image = document.createElement('img');
                        image.className = 'max-h-64 w-full rounded-lg object-contain';
                        image.alt = `${file.name} preview`;
                        image.src = objectUrl;
                        preview.appendChild(image);
                    } else {
                        const link = document.createElement('a');
                        link.className = 'text-sm font-bold text-purple-700 underline';
                        link.href = objectUrl;
                        link.target = '_blank';
                        link.rel = 'noopener';
                        link.textContent = `Open ${file.name} preview`;
                        preview.appendChild(link);
                    }
                }

                input.addEventListener('change', () => {
                    const name = zone?.querySelector('[data-upload-name]');
                    const file = input.files?.[0];

                    if (!file) {
                        if (name) name.textContent = 'No file selected';
                        zone?.classList.remove('border-emerald-300', 'bg-emerald-50');
                        clearPreview();
                        draftPreview?.classList.remove('hidden');
                        return;
                    }

                    if (file.size > 5 * 1024 * 1024) {
                        input.value = '';
                        input.setCustomValidity('Each uploaded file must not exceed 5MB.');
                        input.reportValidity();
                        if (name) name.textContent = 'No file selected';
                        zone?.classList.remove('border-emerald-300', 'bg-emerald-50');
                        clearPreview();
                        return;
                    }

                    input.setCustomValidity('');
                    if (name) name.textContent = `Selected: ${file.name} (${formatFileSize(file.size)})`;
                    zone?.classList.add('border-emerald-300', 'bg-emerald-50');
                    draftPreview?.classList.add('hidden');
                    showPreview(file);
                });
            });
        }

        function attachSignaturePad() {
            const canvas = document.getElementById('signature_canvas');
            const signatureData = document.getElementById('signature_data');
            const clearButton = document.getElementById('clear_signature');
            const status = document.getElementById('signature_draw_status');
            const drawPanel = document.getElementById('draw-signature-panel');
            const uploadPanel = document.getElementById('upload-signature-panel');
            const radios = document.querySelectorAll('input[name="signature_type"]');
            const restoredSignatureData = signatureData.value;

            if (!canvas || !signatureData) return;

            const context = canvas.getContext('2d');
            let drawing = false;

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

                return {
                    x: event.clientX - rect.left,
                    y: event.clientY - rect.top,
                };
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
                const mode = document.querySelector('input[name="signature_type"]:checked')?.value || 'draw';
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
                }
            });
            radios.forEach((radio) => radio.addEventListener('change', syncSignatureMode));
            window.addEventListener('resize', resizeCanvas);

            resizeCanvas();
            // A failed validation redirects back to this page. Restore the
            // serialized canvas from flashed old input so the applicant does
            // not have to sign a second time.
            restoreSignature(restoredSignatureData);
            syncSignatureMode();
        }

        function enrollmentSubmitIsReady() {
            if (!enrollmentForm || enrollmentForm.dataset.submitted === 'true') {
                return false;
            }

            if (enrollmentForm.dataset.requiresBatch === '1') {
                return false;
            }

            return Boolean(privacyConsentInput?.checked);
        }

        function syncEnrollmentSubmitButton() {
            if (!enrollmentSubmitButton || enrollmentForm?.dataset.submitted === 'true') {
                return;
            }

            const ready = enrollmentSubmitIsReady();
            enrollmentSubmitButton.disabled = !ready;
            enrollmentSubmitButton.setAttribute('aria-disabled', ready ? 'false' : 'true');
        }

        function unlockEnrollmentSubmission() {
            if (!enrollmentForm) return;

            enrollmentForm.dataset.submitted = 'false';
            enrollmentSubmitProgress?.classList.add('hidden');

            if (enrollmentSubmitButton) {
                enrollmentSubmitButton.classList.remove('cursor-not-allowed', 'opacity-70');
                enrollmentSubmitButton.textContent = enrollmentSubmitButton.dataset.defaultLabel || 'Submit enrollment';
            }

            syncEnrollmentSubmitButton();
        }

        function showFieldToast(field) {
            if (!(field instanceof HTMLElement)) return;

            const label = field.labels?.[0]?.textContent?.trim()
                || field.getAttribute('aria-label')
                || 'the highlighted field';

            showActionToast(`Please check ${label} before submitting.`);
            field.scrollIntoView({ behavior: 'smooth', block: 'center' });
            field.focus?.({ preventScroll: true });
        }

        function attachSubmitValidation() {
            enrollmentForm?.addEventListener('invalid', (event) => {
                const invalidField = event.target;
                if (!(invalidField instanceof HTMLElement)) return;

                window.clearTimeout(window.mcareEnrollmentInvalidTimer);
                window.mcareEnrollmentInvalidTimer = window.setTimeout(() => {
                    showFieldToast(enrollmentForm.querySelector(':invalid'));
                }, 0);
            }, true);

            privacyConsentInput?.addEventListener('change', syncEnrollmentSubmitButton);
            syncEnrollmentSubmitButton();

            enrollmentForm?.addEventListener('submit', (event) => {
                if (enrollmentForm.dataset.submitted === 'true') {
                    event.preventDefault();
                    return;
                }

                if (!privacyConsentInput?.checked) {
                    event.preventDefault();
                    syncEnrollmentSubmitButton();
                    privacyConsentInput?.focus({ preventScroll: true });
                    privacyConsentInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    showActionToast('Confirm that the information is true and correct before submitting.');
                    return;
                }

                if (typeof enrollmentForm.reportValidity === 'function' && !enrollmentForm.reportValidity()) {
                    event.preventDefault();
                    showFieldToast(enrollmentForm.querySelector(':invalid'));
                    return;
                }

                const signatureMode = document.querySelector('input[name="signature_type"]:checked')?.value || 'draw';
                const signatureData = document.getElementById('signature_data');
                const signatureUpload = document.getElementById('signature_upload');

                if (signatureMode === 'draw' && !signatureDrawn && !existingSignatureSaved) {
                    event.preventDefault();
                    const status = document.getElementById('signature_draw_status');
                    if (status) {
                        status.textContent = 'Draw your signature before submitting.';
                        status.classList.remove('text-slate-500');
                        status.classList.add('text-red-600');
                    }
                    showActionToast('Draw your signature before submitting.');
                    document.getElementById('signature_canvas')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                if (signatureMode === 'draw' && signatureDrawn && signatureData) {
                    signatureData.value = document.getElementById('signature_canvas').toDataURL('image/png');
                }

                if (signatureMode === 'upload' && !signatureUpload?.files?.length && !existingSignatureSaved) {
                    event.preventDefault();
                    signatureUpload?.setCustomValidity('Upload a signature image or choose Draw Signature.');
                    signatureUpload?.reportValidity();
                    showActionToast('Upload a signature image or choose Draw Signature.');
                    document.getElementById('upload-signature-panel')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    return;
                }

                signatureUpload?.setCustomValidity('');
                enrollmentForm.dataset.submitted = 'true';
                enrollmentSubmitProgress?.classList.remove('hidden');

                // Disable after the browser has accepted the native POST.
                // Disabling the button in this same tick can cancel submit on phones.
                window.setTimeout(() => {
                    if (enrollmentSubmitButton) {
                        enrollmentSubmitButton.disabled = true;
                        enrollmentSubmitButton.classList.add('cursor-not-allowed', 'opacity-70');
                        enrollmentSubmitButton.textContent = 'Submitting…';
                    }
                }, 0);
            });

            window.addEventListener('pageshow', (event) => {
                if (event.persisted) unlockEnrollmentSubmission();
            });
        }

        function attachEnrollmentJumpMenu() {
            enrollmentJumpDetails?.querySelectorAll('a[href^="#"]').forEach((link) => {
                link.addEventListener('click', () => {
                    enrollmentJumpDetails.open = false;
                });
            });
        }

        function focusFirstServerError() {
            if (!serverErrorFields.length) return;

            const aliases = {
                signature_data: 'signature_canvas',
            };
            const firstField = serverErrorFields
                .map((field) => document.getElementById(aliases[field] || field))
                .find((field) => field);

            if (!firstField) return;

            firstField.setAttribute('aria-invalid', 'true');
            window.setTimeout(() => {
                firstField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (typeof firstField.focus === 'function') {
                    firstField.focus({ preventScroll: true });
                }
            }, 80);
        }

        attachYearGraduatedField();
        attachInputHardening();
        attachBirthplaceAddressCopy();
        attachAddressAutofillGuard();
        attachUploadFeedback();
        attachSignaturePad();
        attachEnrollmentJumpMenu();
        attachSubmitValidation();
        focusFirstServerError();
    </script>
</body>
</html>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\enrollment\create.blade.php ENDPATH**/ ?>