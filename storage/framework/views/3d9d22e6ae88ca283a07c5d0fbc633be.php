<?php $__env->startSection('content'); ?>
    <section class="space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <p class="dashboard-section-kicker">Career record</p>
            <h1 class="text-2xl font-black text-slate-950 sm:text-3xl">Career history</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Approved careers appear here first as a pending record. Upload your placement certificate for MCARE review. Once the certificate is approved, the career is fully awarded in your history.</p>
        </header>

        <div class="grid gap-4 lg:grid-cols-2">
            <?php $__empty_1 = true; $__currentLoopData = $achievements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $achievement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php $job = $achievement->opportunity; ?>
                <article class="dashboard-panel <?php echo e($achievement->isCareerAwarded() ? 'ring-1 ring-emerald-100' : ''); ?>">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-purple-700">
                                <?php echo e($achievement->isCareerAwarded() ? 'Career awarded' : 'Career record'); ?>

                            </p>
                            <h2 class="mt-1 text-xl font-black text-slate-950"><?php echo e($job?->listingTitle() ?? 'Career opportunity'); ?></h2>
                        </div>
                        <?php if($achievement->isCareerAwarded()): ?>
                            <span class="dashboard-pill bg-emerald-50 text-emerald-800 ring-emerald-200">Awarded</span>
                        <?php elseif($achievement->certificateIsPendingReview()): ?>
                            <span class="dashboard-pill bg-amber-50 text-amber-800 ring-amber-200">Certificate under review</span>
                        <?php elseif($achievement->certificate_status === \App\Models\CareerInquiry::CERT_REJECTED): ?>
                            <span class="dashboard-pill bg-red-50 text-red-800 ring-red-200">Resubmit certificate</span>
                        <?php else: ?>
                            <span class="dashboard-pill bg-sky-50 text-sky-800 ring-sky-200">Certificate required</span>
                        <?php endif; ?>
                    </div>

                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Salary</dt>
                            <dd class="mt-1 font-semibold text-slate-900"><?php echo e($job?->estimated_salary ?: 'See Career Hub'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Start</dt>
                            <dd class="mt-1 font-semibold text-slate-900"><?php echo e($job?->estimated_start_date?->format('M d, Y') ?: 'To be announced'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Placement</dt>
                            <dd class="mt-1 font-semibold text-slate-900"><?php echo e($job?->listingEmployer() ?? 'MCARE-Coordinated Placement'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500"><?php echo e($achievement->isCareerAwarded() ? 'Awarded' : 'Approved'); ?></dt>
                            <dd class="mt-1 font-semibold text-slate-900"><?php echo e(($achievement->isCareerAwarded() ? $achievement->certificate_reviewed_at : $achievement->reviewed_at)?->format('M d, Y') ?: $achievement->created_at?->format('M d, Y')); ?></dd>
                        </div>
                    </dl>

                    <?php if($achievement->needsCertificateUpload()): ?>
                        <div class="mt-4 rounded-lg border border-sky-100 bg-sky-50 p-4">
                            <p class="text-sm font-bold text-sky-950">Upload your placement certificate</p>
                            <p class="mt-1 text-sm leading-6 text-sky-900/80">Submit proof that you completed this career placement. MCARE will review it before this record is fully awarded.</p>
                            <?php if($achievement->certificate_status === \App\Models\CareerInquiry::CERT_REJECTED && $achievement->certificate_admin_notes): ?>
                                <div class="mt-3 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm text-red-800">
                                    <span class="font-bold">Admin remarks:</span> <?php echo e($achievement->certificate_admin_notes); ?>

                                </div>
                            <?php endif; ?>
                            <form method="POST" action="<?php echo e(route('trainee.achievements.upload-certificate', $achievement)); ?>" enctype="multipart/form-data" class="mt-4 space-y-3">
                                <?php echo csrf_field(); ?>
                                <div>
                                    <label for="placement-certificate-<?php echo e($achievement->id); ?>" class="form-label">Placement certificate</label>
                                    <input id="placement-certificate-<?php echo e($achievement->id); ?>" name="placement_certificate" type="file" accept=".pdf,.jpg,.jpeg,.png" required class="form-field">
                                    <p class="mt-1 text-xs text-slate-500">PDF, JPG, or PNG. Maximum 5 MB.</p>
                                    <?php $__errorArgs = ['placement_certificate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="form-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                                <button type="submit" data-action-button class="primary-action inline-flex items-center gap-2 text-sm">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'file-text','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'file-text','class' => 'h-4 w-4']); ?>
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
                                    Submit certificate
                                </button>
                            </form>
                        </div>
                    <?php elseif($achievement->certificateIsPendingReview()): ?>
                        <div class="mt-4 rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950">
                            Your placement certificate was submitted and is waiting for MCARE review.
                            <?php if($achievement->placement_certificate): ?>
                                <a href="<?php echo e(route('trainee.achievements.placement-certificate', $achievement)); ?>" target="_blank" class="mt-2 inline-flex items-center gap-1 font-bold text-amber-900 underline decoration-amber-300 hover:text-amber-950">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'file-text','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'file-text','class' => 'h-4 w-4']); ?>
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
                                    View submitted certificate
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php elseif($achievement->isCareerAwarded()): ?>
                        <div class="mt-4 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-950">
                            This career is fully awarded in your MCARE career history.
                            <?php if($achievement->placement_certificate): ?>
                                <a href="<?php echo e(route('trainee.achievements.placement-certificate', $achievement)); ?>" target="_blank" class="mt-2 inline-flex items-center gap-1 font-bold text-emerald-900 underline decoration-emerald-300 hover:text-emerald-950">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'file-text','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'file-text','class' => 'h-4 w-4']); ?>
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
                                    View awarded certificate
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if($job?->postingSummary()): ?>
                        <p class="mt-4 text-sm leading-6 text-slate-600"><?php echo e($job->postingSummary()); ?></p>
                    <?php endif; ?>
                </article>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="dashboard-panel py-16 text-center lg:col-span-2">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'award','class' => 'mx-auto h-9 w-9 text-slate-300']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'award','class' => 'mx-auto h-9 w-9 text-slate-300']); ?>
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
                    <h2 class="mt-4 text-lg font-bold text-slate-900">No career history yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Apply for a career from the Career Hub. Once MCARE approves your application, it will appear here so you can submit your placement certificate.</p>
                    <a href="<?php echo e(route('trainee.career-hub')); ?>" class="primary-action mt-5 inline-flex">Open Career Hub</a>
                </div>
            <?php endif; ?>
        </div>

        <?php if($achievements->hasPages()): ?>
            <div><?php echo e($achievements->links()); ?></div>
        <?php endif; ?>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainee.layouts.app', ['title' => 'Career History | MCARE Graduate'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainee\achievements.blade.php ENDPATH**/ ?>