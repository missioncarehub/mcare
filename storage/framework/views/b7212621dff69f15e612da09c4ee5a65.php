<?php $__env->startSection('content'); ?>
    <?php
        $reviewErrors = $errors->getBag('historicalClaimReview');
        $badgeClasses = [
            \App\Models\HistoricalAlumniClaim::STATUS_APPROVED => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            \App\Models\HistoricalAlumniClaim::STATUS_REJECTED => 'bg-red-50 text-red-700 ring-red-100',
            \App\Models\HistoricalAlumniClaim::STATUS_PENDING_EMAIL => 'bg-amber-50 text-amber-700 ring-amber-100',
            \App\Models\HistoricalAlumniClaim::STATUS_PENDING_ONSITE => 'bg-purple-50 text-purple-700 ring-purple-100',
        ];
        $address = collect([
            $claim->street,
            $claim->barangay,
            $claim->city,
            $claim->province,
            $claim->region,
            $claim->zip_code,
        ])->filter()->implode(', ');
    ?>

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="<?php echo e(route('admin.historical-alumni.index')); ?>" class="inline-flex w-fit items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700">Back to alumni claims</a>
        <span class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-bold ring-1 <?php echo e($badgeClasses[$claim->status] ?? 'bg-slate-50 text-slate-700 ring-slate-100'); ?>"><?php echo e($claim->statusLabel()); ?></span>
    </div>

    <?php if($errors->has('historical_alumni')): ?>
        <p class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert"><?php echo e($errors->first('historical_alumni')); ?></p>
    <?php endif; ?>
    <?php if($reviewErrors->any()): ?>
        <p class="mb-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-800" role="alert"><?php echo e($reviewErrors->first()); ?></p>
    <?php endif; ?>

    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
        <div class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex min-w-0 items-center gap-4">
                    <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $claim->user,'class' => 'grid h-14 w-14 shrink-0 place-items-center rounded-full bg-purple-100 text-lg font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($claim->user),'class' => 'grid h-14 w-14 shrink-0 place-items-center rounded-full bg-purple-100 text-lg font-black text-purple-800']); ?>
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
                        <p class="text-xs font-bold uppercase tracking-wider text-purple-700">Historical alumni claim</p>
                        <h2 class="mt-1 truncate text-2xl font-bold text-slate-950"><?php echo e($claim->user->name); ?></h2>
                        <p class="truncate text-sm text-slate-500"><?php echo e($claim->user->email); ?></p>
                    </div>
                </div>
                <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                    <?php $__currentLoopData = [
                        'Email status' => $claim->user->hasVerifiedEmail() ? 'Verified' : 'Pending verification',
                        'Contact number' => $claim->contact_number,
                        'Birth date' => $claim->birth_date?->format('M d, Y'),
                        'Gender' => $claim->gender,
                        'Address' => $address ?: '—',
                        'Educational attainment' => $claim->educational_attainment,
                        'School' => $claim->school_name,
                        'Year graduated' => $claim->education_year_graduated,
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['sm:col-span-2' => $label === 'Address']); ?>">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-500"><?php echo e($label); ?></dt>
                            <dd class="mt-1 font-semibold <?php echo e($label === 'Email status' && ! $claim->user->hasVerifiedEmail() ? 'text-amber-700' : 'text-slate-900'); ?>"><?php echo e($value ?: '—'); ?></dd>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Previous MCARE training</p>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <?php $__currentLoopData = [
                        'Completion year' => $claim->training_completion_year,
                        'Old batch' => $claim->historical_batch_name ?: 'Not known',
                        'Training schedule' => $claim->training_schedule,
                        'Record presented' => str($claim->evidence_type)->headline(),
                        'COTC number' => $claim->certificate_number ?: 'Not provided',
                        'TOR reference' => $claim->tor_reference ?: 'Not provided',
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-500"><?php echo e($label); ?></dt>
                            <dd class="mt-1 font-semibold text-slate-900"><?php echo e($value); ?></dd>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </dl>
                <?php if($claim->evidence_document_path): ?>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <a class="secondary-action text-xs" target="_blank" rel="noopener" href="<?php echo e(route('admin.historical-alumni.evidence', [$claim, 'page' => 1])); ?>">View evidence page 1</a>
                        <a class="secondary-action text-xs" href="<?php echo e(route('admin.historical-alumni.evidence', [$claim, 'page' => 1, 'disposition' => 'attachment'])); ?>">Download</a>
                        <?php if($claim->evidence_document_page_2_path): ?>
                            <a class="secondary-action text-xs" target="_blank" rel="noopener" href="<?php echo e(route('admin.historical-alumni.evidence', [$claim, 'page' => 2])); ?>">View page 2</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="mt-5 text-sm text-slate-500">No optional document preview was uploaded. Originals must still be inspected on-site.</p>
                <?php endif; ?>
            </section>
        </div>

        <aside class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:sticky lg:top-6">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">On-site verification</p>
            <?php if($claim->status !== \App\Models\HistoricalAlumniClaim::STATUS_APPROVED): ?>
                <form method="POST" action="<?php echo e(route('admin.historical-alumni.update', $claim)); ?>" class="mt-4 space-y-4" data-dashboard-dialog-form data-submit-label="Saving alumni review...">
                    <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                    <?php $__currentLoopData = ['identity_verified' => 'Valid government or accepted ID matches the claimant', 'training_evidence_verified' => 'Original COTC and/or TOR was physically inspected', 'archive_record_verified' => 'MCARE archive/paper record confirms graduation']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <label class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 text-xs leading-5 text-slate-700">
                            <input type="checkbox" name="<?php echo e($field); ?>" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-600">
                            <span><?php echo e($label); ?></span>
                        </label>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    <div>
                        <label class="form-label" for="historical-notes">Verification notes</label>
                        <textarea id="historical-notes" name="admin_notes" rows="4" class="form-field" required placeholder="Record the archive reference, documents inspected, or reason for follow-up."><?php echo e(old('admin_notes', $claim->admin_notes)); ?></textarea>
                    </div>
                    <div class="flex flex-col-reverse gap-2">
                        <button type="submit" name="decision" value="reject" class="secondary-action justify-center border-red-200 text-red-700 hover:bg-red-50">Return for follow-up</button>
                        <button type="submit" name="decision" value="approve" class="primary-action justify-center" <?php if(! $claim->user->hasVerifiedEmail()): echo 'disabled'; endif; ?>>Verify and activate alumni</button>
                    </div>
                    <?php if (! ($claim->user->hasVerifiedEmail())): ?>
                        <p class="text-xs font-semibold text-amber-700">Approval unlocks after the claimant verifies their email.</p>
                    <?php endif; ?>
                </form>
            <?php else: ?>
                <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-xs leading-5 text-emerald-800">
                    <strong class="block">Verified by <?php echo e($claim->onsiteVerifier?->name ?? 'MCARE administrator'); ?></strong>
                    <?php echo e($claim->onsite_verified_at?->format('M d, Y g:i A')); ?>

                    <?php if(filled($claim->admin_notes)): ?>
                        <span class="mt-2 block"><?php echo e($claim->admin_notes); ?></span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </aside>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Review alumni claim | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\historical-alumni\show.blade.php ENDPATH**/ ?>