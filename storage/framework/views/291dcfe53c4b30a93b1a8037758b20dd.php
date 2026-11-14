<?php $__env->startSection('content'); ?>
    <?php
        $statusStyles = [
            \App\Models\EnrollmentApplication::LEARNING_ACTIVE => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            \App\Models\EnrollmentApplication::LEARNING_PAUSED => 'bg-amber-50 text-amber-900 ring-amber-200',
            \App\Models\EnrollmentApplication::LEARNING_PENDING_GRADUATE => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
            \App\Models\EnrollmentApplication::LEARNING_GRADUATED => 'bg-purple-50 text-purple-800 ring-purple-200',
            \App\Models\EnrollmentApplication::LEARNING_WITHDRAWN => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];
        $paymentStyles = [
            \App\Models\EnrollmentApplication::PAYMENT_PAID => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            \App\Models\EnrollmentApplication::PAYMENT_ONSITE_PENDING => 'bg-amber-50 text-amber-900 ring-amber-200',
            \App\Models\EnrollmentApplication::PAYMENT_ONLINE_PENDING => 'bg-purple-50 text-purple-800 ring-purple-200',
            \App\Models\EnrollmentApplication::PAYMENT_EXPIRED => 'bg-red-50 text-red-800 ring-red-200',
            \App\Models\EnrollmentApplication::PAYMENT_NOT_SELECTED => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];
        $paymentMethod = match ($trainee->payment_method) {
            'onsite' => 'On-site payment',
            'online' => 'Online payment',
            default => 'Not selected',
        };
        $paymentReference = $trainee->latestPaymentReference()
            ?: $trainee->payment_reference
            ?: $trainee->paymongo_checkout_reference;
        $assessmentLabel = $summary['assessment_ready']
            ? 'Ready for trainer assessment'
            : ($summary['total_modules'] > 0 ? 'Learning requirements pending' : 'Waiting for published modules');
    ?>

    <section class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $trainee->user,'application' => $trainee,'useEnrollmentPhoto' => true,'class' => 'grid h-16 w-16 place-items-center rounded-full bg-purple-100 text-xl font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trainee->user),'application' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trainee),'use-enrollment-photo' => true,'class' => 'grid h-16 w-16 place-items-center rounded-full bg-purple-100 text-xl font-black text-purple-800']); ?>
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
                    <p class="dashboard-section-kicker">Trainee record</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold text-slate-950"><?php echo e($trainee->last_name); ?>, <?php echo e($trainee->first_name); ?></h1>
                        <span class="dashboard-pill <?php echo e($statusStyles[$trainee->learning_status] ?? $statusStyles[\App\Models\EnrollmentApplication::LEARNING_ACTIVE]); ?>"><?php echo e($trainee->learningStatusLabel()); ?></span>
                    </div>
                    <p class="mt-1 truncate text-sm text-slate-500"><?php echo e($trainee->email); ?></p>
                    <p class="mt-2 text-xs font-semibold text-slate-600"><?php echo e($trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : 'Unassigned batch'); ?> · <?php echo e($trainee->schedule_preference ?: 'Schedule pending'); ?></p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="<?php echo e(route('admin.learning.trainees', ['tab' => $rosterTab])); ?>" class="secondary-action inline-flex items-center gap-2">
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
                    Back to roster
                </a>
                <?php if($trainee->isReleasedForReview()): ?>
                    <a href="<?php echo e(route('admin.enrollments.show', $trainee)); ?>" class="primary-action inline-flex items-center justify-center">Open enrollment record</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-3">
            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-purple-50 text-purple-700"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'credit-card','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'credit-card','class' => 'h-5 w-5']); ?>
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
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Payment</p><p class="font-bold text-slate-950"><?php echo e($paymentMethod); ?></p></div>
                </div>
                <dl class="mt-5 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-4"><dt class="text-slate-500">Status</dt><dd><span class="dashboard-pill <?php echo e($paymentStyles[$trainee->payment_status] ?? $paymentStyles[\App\Models\EnrollmentApplication::PAYMENT_NOT_SELECTED]); ?>"><?php echo e($trainee->paymentStatusLabel()); ?></span></dd></div>
                    <div class="flex items-center justify-between gap-4"><dt class="text-slate-500">Amount</dt><dd class="font-semibold text-slate-800"><?php echo e($trainee->payment_amount ? ($trainee->payment_currency ?: 'PHP').' '.number_format((float) $trainee->payment_amount, 2) : 'Not recorded'); ?></dd></div>
                    <div><dt class="text-slate-500">Reference</dt><dd class="mt-1 break-all font-semibold text-slate-800"><?php echo e($paymentReference ?: 'Not available'); ?></dd></div>
                    <div class="flex items-center justify-between gap-4"><dt class="text-slate-500">Verified</dt><dd class="font-semibold text-slate-800"><?php echo e($trainee->payment_verified_at?->format('M d, Y g:i A') ?? 'Not verified'); ?></dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg bg-sky-50 text-sky-700"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'book-open','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'book-open','class' => 'h-5 w-5']); ?>
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
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Modules completed</p><p class="font-bold text-slate-950"><?php echo e($summary['completed_modules']); ?> of <?php echo e($summary['total_modules']); ?> published modules</p></div>
                </div>
                <div class="mt-5 h-2 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label="Module progress" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?php echo e($summary['progress_percent']); ?>">
                    <div class="h-full rounded-full bg-purple-600" style="width: <?php echo e($summary['progress_percent']); ?>%"></div>
                </div>
                <div class="mt-3 flex items-center justify-between text-xs font-semibold text-slate-500"><span><?php echo e($summary['progress_percent']); ?>% overall progress</span><span><?php echo e($summary['in_progress_modules']); ?> in progress</span></div>
                <p class="mt-5 text-sm text-slate-600">Last module activity: <span class="font-semibold text-slate-800"><?php echo e($summary['last_activity']?->format('M d, Y g:i A') ?? 'No module opened yet'); ?></span></p>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5">
                <div class="flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-lg <?php echo e($summary['assessment_ready'] ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-800'); ?>"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'square-check','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'square-check','class' => 'h-5 w-5']); ?>
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
                    <div><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Assessment readiness</p><p class="font-bold text-slate-950"><?php echo e($assessmentLabel); ?></p></div>
                </div>
                <p class="mt-5 text-sm leading-6 text-slate-600"><?php echo e($summary['assessment_ready'] ? 'All currently published modules are complete. The trainer can proceed once assessment recording is available.' : 'Complete the remaining learning requirements before trainer assessment.'); ?></p>
                <div class="mt-4 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="text-xs font-bold text-slate-700">Assessment result: Not recorded yet</p>
                    <p class="mt-1 text-xs leading-5 text-slate-500">A real score or competency result will appear here after the assessment backend is implemented.</p>
                </div>
            </section>
        </div>

        <div class="dashboard-panel">
            <p class="text-sm font-bold text-slate-950">Lifecycle controls</p>
            <p class="mt-1 text-xs leading-5 text-slate-500">Pause, resume, mark completion progress, or permanently delete this trainee. Official graduation and alumni access open only after every required module is complete.</p>
            <div class="mt-3 flex flex-wrap gap-2">
                <?php if($trainee->learning_status === \App\Models\EnrollmentApplication::LEARNING_ACTIVE): ?>
                    <form method="POST" action="<?php echo e(route('admin.learning.trainees.status', $trainee)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><input type="hidden" name="learning_status" value="paused"><button class="min-h-10 rounded-lg border border-amber-200 bg-amber-50 px-4 text-xs font-bold text-amber-900 hover:bg-amber-100">Pause</button></form>
                <?php elseif($trainee->learning_status !== \App\Models\EnrollmentApplication::LEARNING_GRADUATED): ?>
                    <form method="POST" action="<?php echo e(route('admin.learning.trainees.status', $trainee)); ?>"><?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><input type="hidden" name="learning_status" value="active"><button class="min-h-10 rounded-lg border border-emerald-200 bg-emerald-50 px-4 text-xs font-bold text-emerald-800 hover:bg-emerald-100">Resume</button></form>
                <?php endif; ?>
                <?php if($trainee->learning_status !== \App\Models\EnrollmentApplication::LEARNING_GRADUATED): ?>
                    <?php if($modulesComplete): ?>
                        <form method="POST" action="<?php echo e(route('admin.learning.trainees.status', $trainee)); ?>" data-confirm="Graduate <?php echo e($trainee->first_name); ?> <?php echo e($trainee->last_name); ?>? Required modules are complete, so this unlocks the official graduate record and alumni access.">
                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                            <input type="hidden" name="learning_status" value="graduated">
                            <button class="min-h-10 rounded-lg border border-purple-200 bg-purple-50 px-4 text-xs font-bold text-purple-800 hover:bg-purple-100">Graduate</button>
                        </form>
                    <?php elseif($trainee->learning_status !== \App\Models\EnrollmentApplication::LEARNING_PENDING_GRADUATE): ?>
                        <form method="POST" action="<?php echo e(route('admin.learning.trainees.status', $trainee)); ?>" data-confirm="Mark <?php echo e($trainee->first_name); ?> <?php echo e($trainee->last_name); ?> as Pending Graduate? Required modules are still incomplete, so they will not be counted as a graduate or alumni.">
                            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                            <input type="hidden" name="learning_status" value="pending_graduate">
                            <button class="min-h-10 rounded-lg border border-indigo-200 bg-indigo-50 px-4 text-xs font-bold text-indigo-800 hover:bg-indigo-100">Pending Graduate</button>
                        </form>
                    <?php endif; ?>
                <?php endif; ?>
                <form method="POST" action="<?php echo e(route('admin.learning.trainees.destroy', $trainee)); ?>" data-confirm-title="<?php echo e($trainee->archiveTitle()); ?>" data-confirm="<?php echo e($trainee->archiveMessage()); ?>" data-confirm-action="Archive trainee">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <button type="submit" class="min-h-10 rounded-lg border border-red-200 bg-red-50 px-4 text-xs font-bold text-red-800 hover:bg-red-100">Archive trainee</button>
                </form>
            </div>
            <form method="POST" action="<?php echo e(route('admin.learning.trainees.status', $trainee)); ?>" class="mt-3 grid max-w-3xl gap-2 sm:grid-cols-[12rem_minmax(14rem,1fr)_auto]">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <select name="learning_status" class="form-field text-sm"><?php $__currentLoopData = $learningStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if($trainee->learning_status === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
                <input name="learning_status_notes" value="<?php echo e($trainee->learning_status_notes); ?>" class="form-field text-sm" placeholder="Optional status note">
                <button class="secondary-action">Save status</button>
            </form>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => $trainee->last_name.', '.$trainee->first_name.' | Trainee Record'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\learning\trainees-show.blade.php ENDPATH**/ ?>