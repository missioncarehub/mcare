<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Application | MCARE</title>
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
<body class="enrollment-page application-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    <?php if (isset($component)) { $__componentOriginald5c36e5eb93f3b111375117a4289e4f9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5c36e5eb93f3b111375117a4289e4f9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.public-official-header','data' => ['mastheadAside' => 'Caregiving NC II · Official application','navLabel' => 'Application','secondaryHref' => route('applications.status'),'secondaryLabel' => 'Check status','primaryHref' => route('login'),'primaryLabel' => 'Sign in']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('public-official-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['masthead-aside' => 'Caregiving NC II · Official application','nav-label' => 'Application','secondary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('applications.status')),'secondary-label' => 'Check status','primary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('login')),'primary-label' => 'Sign in']); ?>
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
                <p class="enrollment-kicker">Before enrollment</p>
                <h1>Submit a training application</h1>
                <p class="enrollment-lede">This is not the enrollment form. MCARE reviews this application first, then issues an application number. After approval, use that number on the enrollment page to open the TESDA applicant profile.</p>
                <ol class="alumni-process">
                    <li><span>1</span><span><strong>Apply.</strong> Submit your name, Gmail, and contact details.</span></li>
                    <li><span>2</span><span><strong>Keep your number.</strong> Check status anytime with the application number.</span></li>
                    <li><span>3</span><span><strong>Admin review.</strong> MCARE approves or denies the application.</span></li>
                    <li><span>4</span><span><strong>Enroll.</strong> Enter the approved number to fill out the enrollment form.</span></li>
                </ol>
            </header>

            <div class="enrollment-form-body">
                
                <?php if(session('application_error')): ?>
                    <p class="enrollment-notice enrollment-notice-error" role="alert" data-auto-dismiss="8000">
                        <?php echo e(session('application_error')); ?>

                    </p>
                <?php endif; ?>

                <?php if($programs->isEmpty()): ?>
                    <p class="enrollment-notice enrollment-notice-amber" role="alert">
                        MCARE has not published any active training program right now. Applications will reopen once the administrator activates a program.
                        You can <a href="<?php echo e(route('applications.status')); ?>" class="underline underline-offset-2">check the status of a previous application</a> in the meantime.
                    </p>
                <?php endif; ?>

                <?php if($errors->first('email') === \App\Models\AdmissionApplication::EMAIL_IN_USE_MESSAGE): ?>
                    <p class="enrollment-notice enrollment-notice-error" role="alert">
                        This Gmail has already been used for a pending or approved MCARE application.
                        <a href="<?php echo e(route('applications.status')); ?>" class="underline underline-offset-2">Check status</a>
                        with the application number sent to this email.
                    </p>
                <?php elseif($errors->any()): ?>
                    <div class="enrollment-notice enrollment-notice-error" role="alert">
                        <p class="font-bold">We couldn't submit your application yet.</p>
                        <p class="mt-1">Please review the highlighted fields below and try again. Common issues:</p>
                        <ul class="mt-2 list-disc pl-5 text-sm">
                            <li>Use a valid Gmail address ending in <span class="font-mono">@gmail.com</span>.</li>
                            <li>Enter a working PH contact number (digits, spaces, and <span class="font-mono">+-()</span> only).</li>
                            <li>Do not include special characters like <span class="font-mono">&lt; &gt; " ' ` ; { } | \</span> in your name or notes.</li>
                            <li>Tick the privacy consent checkbox before submitting.</li>
                        </ul>
                        <?php if($errors->hasBag('default')): ?>
                            <details class="mt-3 text-xs">
                                <summary class="cursor-pointer font-bold">Show detailed error list</summary>
                                <ul class="mt-2 space-y-1">
                                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li>· <?php echo e($error); ?></li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            </details>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php
                    $applicationCanSubmit = $programs->isNotEmpty()
                        && filled(old('first_name'))
                        && filled(old('last_name'))
                        && filled(old('email'))
                        && filled(old('contact_number'))
                        && filled(old('educational_attainment'))
                        && (string) old('privacy_consent') === '1';
                ?>

                <form method="POST" action="<?php echo e(route('applications.store')); ?>" class="enrollment-form space-y-10" data-application-form data-form-draft="applications.create" <?php if($errors->any()): ?> data-form-draft-server-old="1" <?php endif; ?>>
                    <?php echo csrf_field(); ?>
                    <section>
                        <div class="enrollment-section-heading border-b border-slate-200 pb-3">
                            <p>Applicant details</p>
                            <h2>Identity and contact</h2>
                        </div>
                        <div class="mt-5 grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="form-label" for="first_name">First name</label>
                                <input class="form-field" id="first_name" name="first_name" value="<?php echo e(old('first_name')); ?>" required>
                                <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div>
                                <label class="form-label" for="middle_name">Middle name</label>
                                <input class="form-field" id="middle_name" name="middle_name" value="<?php echo e(old('middle_name')); ?>">
                                <?php $__errorArgs = ['middle_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div>
                                <label class="form-label" for="last_name">Last name</label>
                                <input class="form-field" id="last_name" name="last_name" value="<?php echo e(old('last_name')); ?>" required>
                                <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div>
                                <label class="form-label" for="email">Gmail address</label>
                                <input class="form-field" id="email" name="email" type="email" value="<?php echo e(old('email')); ?>" required>
                                <p class="mt-1 text-xs text-slate-500">Use the same Gmail later for enrollment and Google sign-in.</p>
                                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div>
                                <label class="form-label" for="contact_number">Contact number</label>
                                <input class="form-field" id="contact_number" name="contact_number" value="<?php echo e(old('contact_number')); ?>" required>
                                <?php $__errorArgs = ['contact_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label" for="training_program_id">Program</label>
                                <?php if($programs->isNotEmpty()): ?>
                                    <select class="form-field" id="training_program_id" name="training_program_id" required>
                                        <?php $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $program): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <option value="<?php echo e($program->id); ?>" <?php if((int) old('training_program_id', $selectedProgramId) === $program->id): echo 'selected'; endif; ?>><?php echo e($program->name); ?></option>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </select>
                                    <p class="mt-1 text-xs text-slate-500">Choose the TESDA program you are applying for.</p>
                                <?php else: ?>
                                    <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">No training programs are open for application right now. Please contact MCARE or try again later.</p>
                                <?php endif; ?>
                                <?php $__errorArgs = ['training_program_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div>
                                <label class="form-label" for="schedule_preference">Preferred schedule</label>
                                <select class="form-field" id="schedule_preference" name="schedule_preference">
                                    <option value="">No preference yet</option>
                                    <option value="AM" <?php if(old('schedule_preference') === 'AM'): echo 'selected'; endif; ?>>AM</option>
                                    <option value="PM" <?php if(old('schedule_preference') === 'PM'): echo 'selected'; endif; ?>>PM</option>
                                    <option value="Weekend" <?php if(old('schedule_preference') === 'Weekend'): echo 'selected'; endif; ?>>Weekend</option>
                                </select>
                                <?php $__errorArgs = ['schedule_preference'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label" for="educational_attainment">Highest educational attainment</label>
                                <select class="form-field" id="educational_attainment" name="educational_attainment" required>
                                    <option value="">Select</option>
                                    <?php $__currentLoopData = \App\Models\AdmissionApplication::educationalAttainmentOptions(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($option); ?>" <?php if(old('educational_attainment') === $option): echo 'selected'; endif; ?>><?php echo e($option); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = ['educational_attainment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="md:col-span-3">
                                <label class="form-label" for="notes">Additional note (optional)</label>
                                <textarea class="form-field min-h-24" id="notes" name="notes" maxlength="500"><?php echo e(old('notes')); ?></textarea>
                                <?php $__errorArgs = ['notes'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    </section>

                    <section>
                        <label class="flex items-start gap-3 text-sm leading-6 text-slate-700">
                            <input class="mt-1" type="checkbox" name="privacy_consent" value="1" <?php if(old('privacy_consent')): echo 'checked'; endif; ?> required>
                            <span>I confirm that these details are mine and that MCARE may use them to review this application and contact me about enrollment.</span>
                        </label>
                        <?php $__errorArgs = ['privacy_consent'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </section>

                    <div class="enrollment-submit-row">
                        <button type="submit" class="primary-action" data-application-submit <?php if(! $applicationCanSubmit || $programs->isEmpty()): echo 'disabled'; endif; ?>>Submit application</button>
                        <a href="<?php echo e(route('applications.status')); ?>" class="secondary-action application-number-link-in-card">I already have an application number</a>
                    </div>
                </form>
            </div>
        </article>
        <p class="application-number-link-after-card">
            <a href="<?php echo e(route('applications.status')); ?>" class="secondary-action">I already have an application number</a>
        </p>
    </main>

    <?php if (isset($component)) { $__componentOriginalb54bedc4af9c9c96c0755ad537184e90 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb54bedc4af9c9c96c0755ad537184e90 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.public-official-footer','data' => ['note' => 'Application numbers are issued after this form is submitted. Enrollment opens only after administrator approval.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('public-official-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['note' => 'Application numbers are issued after this form is submitted. Enrollment opens only after administrator approval.']); ?>
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
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\applications\create.blade.php ENDPATH**/ ?>