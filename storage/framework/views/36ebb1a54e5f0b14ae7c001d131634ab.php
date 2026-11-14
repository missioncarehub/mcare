<?php $__env->startSection('content'); ?>
    <?php
        $rank = $profile?->rank ?? \App\Models\AlumniProfile::RANK_JUNIOR;
        $contactNumber = $alumni->contact_number ?: $enrollment?->contact_number;
        $address = collect([
            $enrollment?->street,
            $enrollment?->barangay,
            $enrollment?->city,
            $enrollment?->province,
            $enrollment?->region,
            $enrollment?->zip_code,
        ])->filter()->implode(', ');
        $applicationStatusStyles = [
            \App\Models\CareerInquiry::STATUS_PENDING => 'bg-amber-50 text-amber-800 ring-amber-100',
            \App\Models\CareerInquiry::STATUS_APPROVED => 'bg-emerald-50 text-emerald-800 ring-emerald-100',
            \App\Models\CareerInquiry::STATUS_REJECTED => 'bg-red-50 text-red-800 ring-red-100',
        ];
    ?>

    <section class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $alumni,'application' => $enrollment,'useEnrollmentPhoto' => true,'class' => 'grid h-16 w-16 shrink-0 place-items-center rounded-full bg-purple-100 text-xl font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($alumni),'application' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($enrollment),'use-enrollment-photo' => true,'class' => 'grid h-16 w-16 shrink-0 place-items-center rounded-full bg-purple-100 text-xl font-black text-purple-800']); ?>
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
                    <p class="dashboard-section-kicker">Alumni profile</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold text-slate-950"><?php echo e($alumni->name); ?></h1>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 <?php echo e($rank === \App\Models\AlumniProfile::RANK_SENIOR ? 'bg-purple-50 text-purple-800 ring-purple-100' : 'bg-slate-50 text-slate-700 ring-slate-200'); ?>">
                            <?php echo e(\App\Models\AlumniProfile::ranks()[$rank]); ?>

                        </span>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?php echo e($profile?->is_available_for_duty ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'); ?>">
                            <?php echo e($profile?->is_available_for_duty ? 'Available for Duty' : 'Unavailable'); ?>

                        </span>
                    </div>
                    <p class="mt-1 truncate text-sm text-slate-500"><?php echo e($alumni->email); ?></p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="<?php echo e(route('admin.alumni.index')); ?>" class="secondary-action inline-flex items-center gap-2">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'arrow-left','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-left','class' => 'h-4 w-4']); ?>
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
                    Back to alumni list
                </a>
                <?php if($enrollment && ! $enrollment->is_historical_record): ?>
                    <a href="<?php echo e(route('admin.learning.trainees.show', $enrollment)); ?>" class="secondary-action inline-flex items-center gap-2">
                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'book-open','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'book-open','class' => 'h-4 w-4']); ?>
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
                        Trainee record
                    </a>
                <?php endif; ?>
                <?php if($historicalClaim): ?>
                    <a href="<?php echo e(route('admin.historical-alumni.show', $historicalClaim)); ?>" class="secondary-action inline-flex items-center gap-2">
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
                        Alumni claim
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <article class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Career applications</p>
                    <p class="dashboard-stat-value"><?php echo e($applications->count()); ?></p>
                    <p class="dashboard-stat-help">Submitted through Career Hub</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700 ring-1 ring-sky-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'briefcase','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'briefcase','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></span>
            </article>
            <article class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Awarded careers</p>
                    <p class="dashboard-stat-value"><?php echo e($awardedCareers); ?></p>
                    <p class="dashboard-stat-help">Placement certificate approved</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'award','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'award','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></span>
            </article>
            <article class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Availability updated</p>
                    <p class="dashboard-stat-value text-lg"><?php echo e($profile?->availability_updated_at?->format('M d, Y') ?? '—'); ?></p>
                    <p class="dashboard-stat-help"><?php echo e($profile?->availability_updated_at?->diffForHumans() ?? 'Not updated yet'); ?></p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-violet-50 text-violet-700 ring-1 ring-violet-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'clock','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clock','class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></span>
            </article>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Contact & account</p>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <?php $__currentLoopData = [
                        'Email' => $alumni->email,
                        'Email status' => $alumni->hasVerifiedEmail() ? 'Verified' : 'Pending verification',
                        'Contact number' => $contactNumber,
                        'Address' => $address ?: '—',
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="<?php echo \Illuminate\Support\Arr::toCssClasses(['sm:col-span-2' => in_array($label, ['Address'], true)]); ?>">
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-500"><?php echo e($label); ?></dt>
                            <dd class="mt-1 font-semibold <?php echo e($label === 'Email status' && ! $alumni->hasVerifiedEmail() ? 'text-amber-700' : 'text-slate-900'); ?>"><?php echo e($value ?: '—'); ?></dd>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Personal details</p>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <?php $__currentLoopData = [
                        'Full name' => $enrollment ? trim($enrollment->first_name.' '.$enrollment->middle_name.' '.$enrollment->last_name.' '.$enrollment->extension_name) : $alumni->name,
                        'Birth date' => $enrollment?->birth_date?->format('M d, Y'),
                        'Gender' => $enrollment?->gender,
                        'Educational attainment' => $enrollment?->educational_attainment,
                        'School' => $enrollment?->school_name,
                        'Year graduated' => $enrollment?->yearGraduatedLabel(),
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-500"><?php echo e($label); ?></dt>
                            <dd class="mt-1 font-semibold text-slate-900"><?php echo e($value ?: '—'); ?></dd>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">MCARE training record</p>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <?php $__currentLoopData = [
                        'Program' => $enrollment?->program,
                        'Batch' => $enrollment?->batch ? $enrollment->batch->name.' '.$enrollment->batch->year : ($historicalClaim?->historical_batch_name ?: 'Historical graduate'),
                        'Schedule' => $enrollment?->schedule_preference,
                        'Learning status' => $enrollment?->learningStatusLabel(),
                        'Graduated' => $enrollment?->learning_status_changed_at?->format('M d, Y'),
                        'Source' => $enrollment?->is_historical_record ? 'Historical alumni claim' : 'In-system graduate',
                    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-500"><?php echo e($label); ?></dt>
                            <dd class="mt-1 font-semibold text-slate-900"><?php echo e($value ?: '—'); ?></dd>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </dl>
                <?php if($historicalClaim): ?>
                    <dl class="mt-4 grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2 lg:grid-cols-3">
                        <?php $__currentLoopData = [
                            'Completion year' => $historicalClaim->training_completion_year,
                            'Evidence type' => str($historicalClaim->evidence_type)->headline(),
                            'COTC number' => $historicalClaim->certificate_number,
                        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500"><?php echo e($label); ?></dt>
                                <dd class="mt-1 font-semibold text-slate-900"><?php echo e($value ?: '—'); ?></dd>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </dl>
                <?php endif; ?>
            </section>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-sm font-bold text-slate-950">Career Hub applications</h2>
                <p class="mt-1 text-xs text-slate-500">Applications submitted by this alumni, including certificate review status.</p>
            </div>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Career</th>
                        <th class="px-4 py-3">Application</th>
                        <th class="px-4 py-3">Certificate</th>
                        <th class="px-4 py-3">Submitted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $__empty_1 = true; $__currentLoopData = $applications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $application): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-4 py-4">
                                <p class="font-bold text-slate-900"><?php echo e($application->opportunity?->title ?? 'Removed posting'); ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?php echo e($application->opportunity?->location ?: 'Location not listed'); ?></p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 <?php echo e($applicationStatusStyles[$application->status] ?? 'bg-slate-50 text-slate-700 ring-slate-200'); ?>">
                                    <?php echo e($application->statusLabel()); ?>

                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <?php if($application->certificateStatusLabel()): ?>
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?php echo e($application->isCareerAwarded() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'); ?>">
                                        <?php echo e($application->certificateStatusLabel()); ?>

                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4 text-slate-500"><?php echo e($application->created_at?->format('M d, Y') ?? '—'); ?></td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500">No career applications yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => $alumni->name.' | Alumni Profile'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\alumni\show.blade.php ENDPATH**/ ?>