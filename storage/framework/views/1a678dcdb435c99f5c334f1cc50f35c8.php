<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application status | MCARE</title>
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
<body class="enrollment-page application-page application-status-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    <?php if (isset($component)) { $__componentOriginald5c36e5eb93f3b111375117a4289e4f9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald5c36e5eb93f3b111375117a4289e4f9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.public-official-header','data' => ['mastheadAside' => 'Caregiving NC II · Official application','navLabel' => 'Application status','secondaryHref' => route('applications.create'),'secondaryLabel' => 'Apply','primaryHref' => route('login'),'primaryLabel' => 'Sign in']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('public-official-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['masthead-aside' => 'Caregiving NC II · Official application','nav-label' => 'Application status','secondary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('applications.create')),'secondary-label' => 'Apply','primary-href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('login')),'primary-label' => 'Sign in']); ?>
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
                <p class="enrollment-kicker">Applicant self-service</p>
                <h1>Check application status</h1>
                <p class="enrollment-lede">Enter the application number issued after you submitted the applications page. Enrollment opens only when the status is approved.</p>
            </header>
            <div class="enrollment-form-body space-y-6">
                <form method="POST" action="<?php echo e(route('applications.lookup')); ?>" class="space-y-4">
                    <?php echo csrf_field(); ?>
                    <div>
                        <label class="form-label" for="application_number">Application number</label>
                        <input class="form-field" id="application_number" name="application_number" value="<?php echo e(old('application_number', $submittedNumber)); ?>" placeholder="MCA-2026-XXXXXX" required>
                        <?php $__errorArgs = ['application_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <button type="submit" class="primary-action">Look up status</button>
                </form>

                <?php if(session('saved')): ?>
                    <p class="enrollment-notice enrollment-notice-ok" role="status"><?php echo e(session('saved')); ?></p>
                <?php endif; ?>

                <?php if($lookedUp && ! $admission): ?>
                    <p class="enrollment-notice enrollment-notice-error" role="alert">That application number was not found. Check the number from your confirmation email and try again.</p>
                <?php endif; ?>

                <?php if($admission): ?>
                    <?php
                        $stages = $admission->progressStages();
                        $enrollment = $admission->enrollment;
                        $currentStage = $admission->currentStage();
                        $badgeToneByState = [
                            'complete' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                            'in_progress' => 'bg-amber-50 text-amber-700 ring-amber-100',
                            'pending' => 'bg-slate-50 text-slate-500 ring-slate-100',
                            'blocked' => 'bg-red-50 text-red-700 ring-red-100',
                        ];
                        $stateLabels = [
                            'complete' => 'Complete',
                            'in_progress' => 'In progress',
                            'pending' => 'Pending',
                            'blocked' => 'Blocked',
                        ];
                        $documentStatuses = $enrollment?->documentStatuses() ?? [];
                        $canReplaceDocuments = $enrollment && $enrollment->status !== \App\Models\EnrollmentApplication::STATUS_APPROVED;
                        $documentStatusTones = [
                            'accepted' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                            'replace' => 'bg-amber-50 text-amber-800 ring-amber-100',
                            'missing' => 'bg-red-50 text-red-700 ring-red-100',
                            'unreviewed' => 'bg-slate-50 text-slate-600 ring-slate-100',
                            'not_uploaded' => 'bg-slate-50 text-slate-500 ring-slate-100',
                        ];
                    ?>

                    <div class="rounded-xl border border-slate-200 bg-white p-5 space-y-5">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Application number</p>
                                <p class="mt-1 font-display text-2xl font-extrabold text-slate-950"><?php echo e($admission->application_number); ?></p>
                            </div>
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Current stage</p>
                                <p class="mt-1 font-display text-lg font-bold text-slate-950"><?php echo e($currentStage['label']); ?></p>
                                <span class="mt-1 inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 <?php echo e($badgeToneByState[$currentStage['state']] ?? 'bg-slate-50 text-slate-600 ring-slate-100'); ?>"><?php echo e($stateLabels[$currentStage['state']] ?? ucfirst($currentStage['state'])); ?></span>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 text-sm">
                            <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Program</span><br><?php echo e($admission->program); ?></p>
                            <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Preferred schedule</span><br><?php echo e($admission->schedule_preference ?: 'No preference'); ?></p>
                            <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Submitted</span><br><?php echo e($admission->created_at?->format('M d, Y g:i A') ?? '—'); ?></p>
                            <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Overall status</span><br><?php echo e($admission->statusLabel()); ?></p>
                            <?php if($enrollment): ?>
                                <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Enrollment number</span><br><?php echo e($enrollment->enrollment_number); ?></p>
                                <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Batch</span><br><?php echo e($enrollment->batch ? $enrollment->batch->name.' '.$enrollment->batch->year : 'Awaiting assignment'); ?></p>
                                <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Payment</span><br><?php echo e($enrollment->paymentStatusLabel()); ?></p>
                                <?php if($enrollment->batch?->enrollment_ends_at && ! $enrollment->batch->is_continuous_enrollment): ?>
                                    <p><span class="text-xs font-bold uppercase tracking-wider text-slate-500">Enrollment deadline</span><br><?php echo e($enrollment->batch->enrollment_ends_at->format('M d, Y g:i A')); ?></p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>

                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Applicant progress</p>
                            <ol class="mt-3 space-y-3">
                                <?php $__currentLoopData = $stages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $stage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <li class="flex items-start gap-3 rounded-xl border border-slate-100 bg-slate-50/50 p-3">
                                        <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full text-[11px] font-black ring-1 <?php echo e($badgeToneByState[$stage['state']] ?? 'bg-slate-50 text-slate-500 ring-slate-100'); ?>"><?php echo e($index + 1); ?></span>
                                        <div class="min-w-0">
                                            <p class="font-bold text-slate-900"><?php echo e($stage['label']); ?>

                                                <span class="ml-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 <?php echo e($badgeToneByState[$stage['state']] ?? 'bg-slate-50 text-slate-500 ring-slate-100'); ?>"><?php echo e($stateLabels[$stage['state']] ?? ucfirst($stage['state'])); ?></span>
                                            </p>
                                            <p class="mt-1 text-xs text-slate-600"><?php echo e($stage['description']); ?></p>
                                        </div>
                                    </li>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </ol>
                        </div>

                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Document status</p>
                            <?php if($documentStatuses === []): ?>
                                <p class="mt-2 text-sm leading-6 text-slate-600">Document review starts after you submit the enrollment form.</p>
                            <?php else: ?>
                                <?php if($errors->has('verifier') || $errors->has('replacement') || $errors->has('document')): ?>
                                    <p class="mt-2 text-sm font-semibold text-red-700" role="alert"><?php echo e($errors->first('verifier') ?: $errors->first('replacement') ?: $errors->first('document')); ?></p>
                                <?php endif; ?>
                                <ul class="mt-3 space-y-2">
                                    <?php $__currentLoopData = $documentStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li class="rounded-xl border border-slate-100 bg-slate-50/50 px-3 py-2.5">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <p class="text-sm font-bold text-slate-900"><?php echo e($document['label']); ?></p>
                                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 <?php echo e($documentStatusTones[$document['status']] ?? 'bg-slate-50 text-slate-600 ring-slate-100'); ?>"><?php echo e($document['status_label']); ?></span>
                                            </div>
                                            <?php if($document['note']): ?>
                                                <p class="mt-1 text-xs leading-5 text-slate-600"><?php echo e($document['note']); ?></p>
                                            <?php endif; ?>
                                            <?php if($canReplaceDocuments && in_array($document['status'], ['replace', 'missing'], true)): ?>
                                                <form method="POST" action="<?php echo e(route('applications.documents.replace')); ?>" enctype="multipart/form-data" class="mt-3 space-y-2">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="application_number" value="<?php echo e($admission->application_number); ?>">
                                                    <input type="hidden" name="document" value="<?php echo e($document['key']); ?>">
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500" for="verifier-<?php echo e($document['key']); ?>">Email or mobile on the enrollment form</label>
                                                    <input class="form-field" id="verifier-<?php echo e($document['key']); ?>" name="verifier" value="<?php echo e(old('document') === $document['key'] ? old('verifier') : ''); ?>" required autocomplete="username">
                                                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-500" for="replacement-<?php echo e($document['key']); ?>">Upload replacement</label>
                                                    <input id="replacement-<?php echo e($document['key']); ?>" name="replacement" type="file" accept="<?php echo e(in_array($document['key'], ['id-photo', 'signature'], true) ? '.jpg,.jpeg,.png' : '.pdf,.jpg,.jpeg,.png'); ?>" required>
                                                    <button type="submit" class="secondary-action">Submit replacement</button>
                                                </form>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            <?php endif; ?>
                        </div>

                        <?php if($admission->isPending()): ?>
                            <p class="enrollment-notice enrollment-notice-amber">MCARE is still reviewing this application. Return here anytime with the same number.</p>
                        <?php elseif($admission->isApproved()): ?>
                            <p class="enrollment-notice enrollment-notice-ok">This application is approved. Enter this number on the enrollment page to open the TESDA form.</p>
                            <?php if($enrollment): ?>
                                <a href="<?php echo e(route('login')); ?>" class="primary-action">Sign in to continue enrollment</a>
                            <?php else: ?>
                                <a href="<?php echo e($admission->enrollmentUrl()); ?>" class="primary-action">Continue to enrollment</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="enrollment-notice enrollment-notice-error">This application was not approved.<?php if(filled($admission->admin_notes)): ?> <?php echo e($admission->admin_notes); ?><?php endif; ?></p>
                            <a href="<?php echo e(route('applications.create')); ?>" class="secondary-action">Submit a new application</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </main>

    <?php if (isset($component)) { $__componentOriginalb54bedc4af9c9c96c0755ad537184e90 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalb54bedc4af9c9c96c0755ad537184e90 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.public-official-footer','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('public-official-footer'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
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
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\applications\status.blade.php ENDPATH**/ ?>