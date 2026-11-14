<?php $__env->startSection('content'); ?>
    <?php
        $paymentBadgeClasses = [
            'onsite_pending' => 'bg-amber-50 text-amber-800 ring-amber-200',
            'online_pending' => 'bg-purple-50 text-purple-800 ring-purple-200',
            'partially_paid' => 'bg-sky-50 text-sky-800 ring-sky-200',
            'paid' => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            'expired' => 'bg-red-50 text-red-800 ring-red-200',
        ];
    ?>

    <section class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <p class="max-w-3xl text-sm leading-6 text-slate-600">
                Record on-site tuition transactions, verify physical official receipts, track remaining balances (₱22,000 program fee), and manage trainee payment milestones.
            </p>
            <div class="flex flex-wrap items-center gap-3">
                <button type="button" data-dashboard-dialog-open="record-onsite-payment-dialog" class="primary-action">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'plus','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'plus','class' => 'h-4 w-4']); ?>
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
                    <span>Record On-Site Payment</span>
                </button>
                <a href="<?php echo e(route('admin.announcements.index')); ?>" class="secondary-action">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'bullhorn','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'bullhorn','class' => 'h-4 w-4']); ?>
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
                    <span>Send Payment Reminder</span>
                </a>
            </div>
        </header>

        <!-- Financial Stats -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <article class="dashboard-stat min-h-0">
                <div>
                    <p class="dashboard-stat-label">Total Tuition Collected</p>
                    <p class="dashboard-stat-value text-emerald-700">₱<?php echo e(number_format((float) $stats['total_collected'], 2)); ?></p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
            </article>
            <article class="dashboard-stat min-h-0">
                <div>
                    <p class="dashboard-stat-label">Fully Paid Trainees</p>
                    <p class="dashboard-stat-value"><?php echo e($stats['fully_paid']); ?></p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'user-check','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'user-check','class' => 'h-5 w-5']); ?>
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
            <article class="dashboard-stat min-h-0">
                <div>
                    <p class="dashboard-stat-label">Partially Paid (Downpayment)</p>
                    <p class="dashboard-stat-value text-sky-700"><?php echo e($stats['partially_paid']); ?></p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700 ring-1 ring-sky-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'signal','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'signal','class' => 'h-5 w-5']); ?>
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
            <article class="dashboard-stat min-h-0">
                <div>
                    <p class="dashboard-stat-label">Pending On-Site Tickets</p>
                    <p class="dashboard-stat-value text-amber-700"><?php echo e($stats['pending_tickets']); ?></p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'clipboard-list','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard-list','class' => 'h-5 w-5']); ?>
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

        <!-- Filter Form -->
        <form method="GET" action="<?php echo e(route('admin.payment-schedules.index')); ?>" data-auto-filter class="dashboard-panel grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div class="md:col-span-2">
                <label for="payment-search" class="mb-2 block text-xs font-bold uppercase text-slate-500">Search enrollee or OR #</label>
                <input id="payment-search" name="search" value="<?php echo e($filters['search'] ?? ''); ?>" class="form-field" placeholder="Name, email, OR receipt number, or reference">
            </div>
            <div>
                <label for="payment-method" class="mb-2 block text-xs font-bold uppercase text-slate-500">Method</label>
                <select id="payment-method" name="method" class="form-field">
                    <option value="">All methods</option>
                    <option value="onsite" <?php if(($filters['method'] ?? '') === 'onsite'): echo 'selected'; endif; ?>>On-site</option>
                    <option value="online" <?php if(($filters['method'] ?? '') === 'online'): echo 'selected'; endif; ?>>Online</option>
                </select>
            </div>
            <div>
                <label for="payment-status" class="mb-2 block text-xs font-bold uppercase text-slate-500">Status</label>
                <select id="payment-status" name="status" class="form-field">
                    <option value="">All statuses</option>
                    <?php $__currentLoopData = $paymentStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <?php if($value !== 'not_selected'): ?>
                            <option value="<?php echo e($value); ?>" <?php if(($filters['status'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endif; ?>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label for="payment-batch" class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch</label>
                <select id="payment-batch" name="batch_id" class="form-field">
                    <option value="">All batches</option>
                    <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($batch->id); ?>" <?php if((int) ($filters['batch_id'] ?? 0) === $batch->id): echo 'selected'; endif; ?>><?php echo e($batch->name); ?> <?php echo e($batch->year); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label for="payment-schedule" class="mb-2 block text-xs font-bold uppercase text-slate-500">Schedule</label>
                <select id="payment-schedule" name="schedule" class="form-field">
                    <option value="">AM and PM</option>
                    <option value="AM" <?php if(($filters['schedule'] ?? '') === 'AM'): echo 'selected'; endif; ?>>AM</option>
                    <option value="PM" <?php if(($filters['schedule'] ?? '') === 'PM'): echo 'selected'; endif; ?>>PM</option>
                </select>
            </div>
            <div class="flex items-end gap-2 md:col-span-2 xl:col-span-6">
                <button type="submit" class="primary-action">Apply filters</button>
                <a href="<?php echo e(route('admin.payment-schedules.index')); ?>" class="secondary-action">Reset</a>
            </div>
        </form>

        <!-- Payments Table -->
        <section class="dashboard-table-wrap">
            <div class="overflow-auto max-h-[72vh]">
                <table class="dashboard-table w-full min-w-[78rem]">
                    <thead class="sticky top-0 z-10">
                        <tr>
                            <th>Enrollee</th>
                            <th>Batch / Schedule</th>
                            <th>Tuition Breakdown</th>
                            <th>Latest Reference</th>
                            <th>Payment Status</th>
                            <th>On-Site Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $application): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $totalFee = (float) ($application->total_program_fee ?? 22000.00);
                                $totalPaid = (float) ($application->total_paid_amount ?? 0.00);
                                $balance = $application->remainingBalance();
                                $pendingTicket = $application->paymentTransactions->first(fn ($transaction) => $transaction->isOnsiteTicket());
                            ?>
                            <tr class="align-top">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $application->user,'application' => $application,'useEnrollmentPhoto' => true,'class' => 'grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($application->user),'application' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($application),'use-enrollment-photo' => true,'class' => 'grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800']); ?>
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
                                        <div class="min-w-0"><p class="font-bold text-slate-950"><?php echo e($application->last_name); ?>, <?php echo e($application->first_name); ?></p><p class="mt-0.5 text-xs text-slate-500"><?php echo e($application->email); ?></p></div>
                                    </div>
                                    <div class="mt-2 flex items-center gap-3">
                                        <a href="<?php echo e(route('admin.enrollments.show', $application)); ?>" class="text-xs font-semibold text-purple-700 hover:text-purple-900">View profile</a>
                                    </div>
                                </td>
                                <td>
                                    <p class="font-bold text-slate-900"><?php echo e($application->batch ? $application->batch->name.' '.$application->batch->year : 'Unassigned'); ?></p>
                                    <p class="mt-1 text-xs text-slate-600"><?php echo e($application->schedule_preference); ?> · <?php echo e($application->batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule pending'); ?></p>
                                </td>
                                <td>
                                    <div class="space-y-1 text-xs">
                                        <div class="flex justify-between gap-4">
                                            <span class="text-slate-500">Program Fee:</span>
                                            <span class="font-bold text-slate-900">₱<?php echo e(number_format($totalFee, 2)); ?></span>
                                        </div>
                                        <div class="flex justify-between gap-4">
                                            <span class="text-slate-500">Total Paid:</span>
                                            <span class="font-bold text-emerald-700">₱<?php echo e(number_format($totalPaid, 2)); ?></span>
                                        </div>
                                        <div class="flex justify-between gap-4 border-t border-slate-100 pt-1">
                                            <span class="text-slate-500">Balance:</span>
                                            <span class="font-bold <?php echo e($balance <= 0 ? 'text-emerald-700' : 'text-amber-700'); ?>">
                                                ₱<?php echo e(number_format($balance, 2)); ?>

                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-xs">
                                    <?php
                                        $latestReference = $application->latestPaymentReference();
                                    ?>
                                    <?php if($pendingTicket): ?>
                                        <p class="font-mono font-bold text-purple-900"><?php echo e($pendingTicket->reference_number ?: $pendingTicket->ticket_number); ?></p>
                                        <p class="mt-1 text-[11px] font-semibold text-amber-700">On-site reference · ₱<?php echo e(number_format((float) $pendingTicket->amount, 2)); ?></p>
                                    <?php else: ?>
                                        <p class="font-mono font-bold text-slate-900">
                                            <?php echo e($latestReference ?: 'Reference pending'); ?>

                                        </p>
                                        <?php if($application->payment_method === 'online' && filled($application->payment_reference) && $application->payment_reference !== $latestReference): ?>
                                            <p class="mt-1 font-mono text-[11px] text-slate-600">MCARE ref: <?php echo e($application->payment_reference); ?></p>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <p class="mt-1 text-[11px] text-slate-500">
                                        Channel: <span class="font-semibold text-slate-700"><?php echo e(str($application->payment_method)->headline()); ?></span>
                                    </p>
                                </td>
                                <td>
                                    <span class="inline-flex rounded-lg px-3 py-1.5 text-xs font-bold ring-1 ring-inset <?php echo e($paymentBadgeClasses[$application->payment_status] ?? 'bg-slate-100 text-slate-700 ring-slate-200'); ?>">
                                        <?php echo e($application->paymentStatusLabel()); ?>

                                    </span>
                                    <?php if($application->payment_verified_at): ?>
                                        <p class="mt-1.5 text-[11px] text-slate-500">
                                            Verified by <?php echo e($application->paymentVerifier?->name ?? 'Admin'); ?><br>
                                            <?php echo e($application->payment_verified_at->format('M d, Y')); ?>

                                        </p>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="flex flex-col gap-2">
                                        <button type="button" class="inline-flex items-center justify-center gap-1 rounded-lg bg-purple-700 px-3 py-1.5 text-xs font-bold text-white hover:bg-purple-800 transition" data-record-for-app data-app-id="<?php echo e($application->id); ?>" data-app-name="<?php echo e($application->last_name); ?>, <?php echo e($application->first_name); ?>" data-app-email="<?php echo e($application->email); ?>" data-app-batch="<?php echo e($application->batch ? $application->batch->name.' '.$application->batch->year : 'Unassigned'); ?>" data-app-schedule="<?php echo e($application->schedule_preference); ?>" data-app-status="<?php echo e($application->paymentStatusLabel()); ?>" data-app-balance="<?php echo e($balance); ?>" data-app-downpayment="<?php echo e((float) ($application->downpayment_amount ?? 0)); ?>" data-app-reference="<?php echo e($latestReference ?? ''); ?>">
                                            <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'plus','class' => 'h-3.5 w-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'plus','class' => 'h-3.5 w-3.5']); ?>
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
                                            <span>Record Payment</span>
                                        </button>

                                        <!-- Fast Status Toggle -->
                                        <form method="POST" action="<?php echo e(route('admin.payment-schedules.update', $application)); ?>" class="flex flex-wrap items-center gap-1.5">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('PATCH'); ?>
                                            <?php if($totalPaid <= 0): ?>
                                                <button name="action" value="return_pending" title="Return to pending verification" class="rounded-md border border-slate-200 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-50">
                                                    Pending
                                                </button>
                                                <button name="action" value="mark_expired" title="Mark payment expired" class="rounded-md border border-rose-200 bg-white px-2 py-1 text-[11px] font-semibold text-rose-700 hover:bg-rose-50">
                                                    Expire
                                                </button>
                                            <?php else: ?>
                                                <span class="text-[11px] font-semibold text-slate-500">Status follows the verified ledger.</span>
                                            <?php endif; ?>
                                        </form>
                                    </div>

                                    <!-- Collapsible Transaction Ledger -->
                                    <?php if($application->paymentTransactions->isNotEmpty()): ?>
                                        <details class="group mt-3 rounded-lg border border-slate-200 bg-slate-50/70 p-2 text-xs">
                                            <summary class="cursor-pointer font-semibold text-purple-700 hover:text-purple-900 select-none">
                                                Transaction History (<?php echo e($application->paymentTransactions->count()); ?>)
                                            </summary>
                                            <div class="mt-2 space-y-2">
                                                <?php $__currentLoopData = $application->paymentTransactions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tx): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <div class="rounded-lg border border-slate-200 bg-white p-2.5 text-xs space-y-1">
                                                        <div class="flex items-center justify-between">
                                                            <span class="font-bold text-slate-900">₱<?php echo e(number_format((float) $tx->amount, 2)); ?></span>
                                                            <span class="rounded px-2 py-0.5 text-[10px] font-bold <?php echo e($tx->status === 'verified' ? 'bg-emerald-100 text-emerald-800' : ($tx->status === 'pending_verification' ? 'bg-amber-100 text-amber-800' : 'bg-rose-100 text-rose-800')); ?>">
                                                                <?php echo e($tx->statusLabel()); ?>

                                                            </span>
                                                        </div>
                                                        <p class="text-[11px] text-slate-600">
                                                            <?php if($tx->reference_number || $tx->ticket_number): ?>
                                                                Ref #: <strong class="font-mono text-purple-900"><?php echo e($tx->reference_number ?: $tx->ticket_number); ?></strong>
                                                                <?php if($tx->or_number): ?>
                                                                    · OR #: <strong class="font-mono"><?php echo e($tx->or_number); ?></strong>
                                                                <?php endif; ?>
                                                                · <?php echo e($tx->typeLabel()); ?> · <?php echo e($tx->paid_at?->format('M d, Y') ?? $tx->created_at->format('M d, Y g:i A')); ?>

                                                            <?php elseif($tx->or_number): ?>
                                                                OR #: <strong class="font-mono"><?php echo e($tx->or_number); ?></strong> · <?php echo e($tx->typeLabel()); ?> · <?php echo e($tx->paid_at?->format('M d, Y') ?? 'N/A'); ?>

                                                            <?php else: ?>
                                                                <?php echo e($tx->typeLabel()); ?> · <?php echo e($tx->paid_at?->format('M d, Y') ?? 'N/A'); ?>

                                                            <?php endif; ?>
                                                        </p>
                                                        <?php if($tx->receipt_proof_path): ?>
                                                            <a href="<?php echo e(route('admin.payment-schedules.transactions.proof', $tx)); ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-[11px] font-bold text-purple-700 hover:text-purple-900">
                                                                View uploaded receipt proof
                                                            </a>
                                                        <?php endif; ?>
                                                        <?php if($tx->recordedByAdmin): ?>
                                                            <p class="text-[10px] text-slate-400">Recorded by <?php echo e($tx->recordedByAdmin->name); ?></p>
                                                        <?php endif; ?>
                                                        <?php if($tx->status === 'pending_verification'): ?>
                                                            <form method="POST" action="<?php echo e(route('admin.payment-schedules.transactions.verify', $tx)); ?>" class="mt-2 space-y-2 border-t border-slate-100 pt-2" data-single-action>
                                                                    <?php echo csrf_field(); ?>
                                                                    <?php echo method_field('PATCH'); ?>
                                                                    <?php if($tx->isOnsiteTicket()): ?>
                                                                        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                                                                            <input name="or_number" required maxlength="100" pattern="[A-Za-z0-9][A-Za-z0-9._-]*" class="form-field text-xs font-mono" placeholder="Cashier OR #" aria-label="Cashier official receipt number">
                                                                            <input name="paid_at" type="date" required max="<?php echo e(now()->toDateString()); ?>" value="<?php echo e(now()->toDateString()); ?>" class="form-field text-xs" aria-label="Payment date">
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div class="flex items-center gap-1.5">
                                                                        <button name="action" value="verify" class="rounded bg-emerald-600 px-2 py-1 text-[11px] font-bold text-white hover:bg-emerald-700"><?php echo e($tx->isOnsiteTicket() ? 'Verify Ticket' : 'Verify OR'); ?></button>
                                                                        <button name="action" value="reject" class="rounded bg-rose-600 px-2 py-1 text-[11px] font-bold text-white hover:bg-rose-700">Reject</button>
                                                                    </div>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </div>
                                        </details>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="py-14 text-center">
                                    <p class="font-bold text-slate-950">No payment records found</p>
                                    <p class="mt-2 text-sm text-slate-500">Only enrollees who chose online or on-site payment appear here.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if($payments->hasPages()): ?>
                <div class="border-t border-slate-200 px-5 py-4"><?php echo e($payments->links()); ?></div>
            <?php endif; ?>
        </section>

        <style>
            #record-onsite-payment-dialog {
                width: min(96vw, 72rem);
                max-width: 72rem;
                max-height: 92vh;
            }
            #record-onsite-payment-dialog .record-onsite-layout {
                display: grid;
                grid-template-columns: minmax(18rem, 0.9fr) minmax(24rem, 1.2fr);
                align-items: stretch;
                gap: 1.25rem;
            }
            @media (max-width: 860px) {
                #record-onsite-payment-dialog .record-onsite-layout {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <!-- Native HTML5 Dialog: Record On-Site Payment -->
        <dialog id="record-onsite-payment-dialog" data-dashboard-dialog class="m-auto rounded-2xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="record-dialog-title">
            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                <div>
                    <h2 id="record-dialog-title" class="font-display text-xl font-bold text-slate-950">Record On-Site Payment</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Paste the enrollee reference number, then click Find to load their name and balance.</p>
                </div>
                <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900" aria-label="Close dialog" title="Close">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark','class' => 'h-4 w-4']); ?>
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

            <form id="record-onsite-payment-form" method="POST" action="#" class="p-6" data-dashboard-dialog-form data-submit-label="Recording payment..." data-lookup-url="<?php echo e(route('admin.payment-schedules.lookup')); ?>" data-store-url="<?php echo e(url('/admin/payment-scheduling')); ?>">
                <?php echo csrf_field(); ?>

                <div class="mb-5">
                    <label for="record-lookup-query" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Reference number</label>
                    <div class="flex gap-2">
                        <input id="record-lookup-query" type="text" maxlength="100" class="form-field font-mono" placeholder="e.g. MCARE-SITE-260903-XXXXXXXX" autocomplete="off" data-lookup-input>
                        <button type="button" id="record-lookup-button" class="secondary-action shrink-0">Find</button>
                    </div>
                    <p id="record-lookup-status" class="mt-1.5 text-xs text-slate-500" role="status" aria-live="polite">Paste the on-site reference, PayMongo ID, OR #, ticket, or enrollment #, then click Find.</p>
                </div>

                <div class="record-onsite-layout">
                    <div id="record-enrollee-card" class="record-enrollee-panel rounded-xl border border-purple-200 bg-purple-50/70 p-4">
                        <p class="text-[11px] font-bold uppercase tracking-wider text-purple-700">Enrollee / Trainee</p>
                        <div id="record-enrollee-empty" class="mt-4 text-sm leading-6 text-slate-600">
                            Find a reference number to load the enrollee name, email, batch, and remaining balance here.
                        </div>
                        <div id="record-enrollee-details" hidden>
                            <p id="record-enrollee-name" class="mt-1 font-display text-lg font-bold text-slate-950"></p>
                            <p id="record-enrollee-email" class="mt-0.5 text-sm text-slate-600"></p>
                            <p id="record-enrollee-meta" class="mt-2 text-xs text-slate-600"></p>
                            <p id="record-enrollee-match" class="mt-2 text-xs font-semibold text-purple-800"></p>
                        </div>
                        <input type="hidden" id="record-application-id" value="">
                    </div>

                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="record-paid-at" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Payment Date</label>
                                <input id="record-paid-at" name="paid_at" type="date" required value="<?php echo e(now()->toDateString()); ?>" class="form-field">
                            </div>
                            <div>
                                <label for="record-amount" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Amount Paid (PHP)</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-bold text-slate-500">₱</span>
                                    <input id="record-amount" name="amount" type="number" step="0.01" min="1" max="100000" required placeholder="0.00" class="form-field pl-8">
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" id="record-btn-downpayment" hidden class="rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">Downpayment</button>
                            <button type="button" id="record-btn-clear-balance" hidden class="rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">Clear Balance</button>
                        </div>

                        <div>
                            <label for="record-tx-type" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Payment Classification</label>
                            <select id="record-tx-type" name="transaction_type" class="form-field" required>
                                <?php $__currentLoopData = \App\Models\PaymentTransaction::types(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($type); ?>"><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>

                        <div>
                            <label for="record-notes" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Admin Notes / Remarks (Optional)</label>
                            <textarea id="record-notes" name="notes" rows="3" maxlength="1000" class="form-field" placeholder="e.g. Received cash payment at registration desk..."></textarea>
                        </div>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                    <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                    <button type="submit" id="record-submit-button" data-action-button class="primary-action" disabled>
                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'check','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check','class' => 'h-4 w-4']); ?>
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
                        <span>Save & Credit Payment</span>
                    </button>
                </div>
            </form>
        </dialog>
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const dialog = document.getElementById('record-onsite-payment-dialog');
            const form = document.getElementById('record-onsite-payment-form');
            const lookupInput = document.getElementById('record-lookup-query');
            const lookupButton = document.getElementById('record-lookup-button');
            const lookupStatus = document.getElementById('record-lookup-status');
            const enrolleeCard = document.getElementById('record-enrollee-card');
            const enrolleeEmpty = document.getElementById('record-enrollee-empty');
            const enrolleeDetails = document.getElementById('record-enrollee-details');
            const enrolleeName = document.getElementById('record-enrollee-name');
            const enrolleeEmail = document.getElementById('record-enrollee-email');
            const enrolleeMeta = document.getElementById('record-enrollee-meta');
            const enrolleeMatch = document.getElementById('record-enrollee-match');
            const applicationIdInput = document.getElementById('record-application-id');
            const amountInput = document.getElementById('record-amount');
            const txTypeSelect = document.getElementById('record-tx-type');
            const downpaymentBtn = document.getElementById('record-btn-downpayment');
            const clearBalanceBtn = document.getElementById('record-btn-clear-balance');
            const submitButton = document.getElementById('record-submit-button');
            const lookupUrl = form?.dataset.lookupUrl || '';
            const storeUrl = form?.dataset.storeUrl || '';
            const money = (value) => Number(value || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

            let lookupTimer = null;
            let lookupController = null;
            let enrolleeLocked = false;
            let lastLookedUp = '';

            const setStatus = (message, isError = false) => {
                if (!lookupStatus) return;
                lookupStatus.textContent = message;
                lookupStatus.className = `mt-1.5 text-xs ${isError ? 'font-semibold text-rose-700' : 'text-slate-500'}`;
            };

            const setSubmitEnabled = (enabled) => {
                if (!submitButton) return;
                submitButton.disabled = !enabled;
            };

            const updateFormAction = (appId) => {
                if (!form || !appId) return;
                form.action = `${storeUrl}/${appId}/transactions`;
            };

            const applyAmountPresets = (payload) => {
                const downpayment = Number.parseFloat(payload.downpayment_amount || '0');
                const balance = Number.parseFloat(payload.balance || '0');
                const suggested = payload.suggested_amount == null || payload.suggested_amount === ''
                    ? null
                    : Number.parseFloat(payload.suggested_amount);

                if (downpaymentBtn) {
                    if (downpayment > 0) {
                        downpaymentBtn.hidden = false;
                        downpaymentBtn.textContent = `₱${money(downpayment)} Downpayment`;
                        downpaymentBtn.dataset.presetAmount = downpayment.toFixed(2);
                    } else {
                        downpaymentBtn.hidden = true;
                        delete downpaymentBtn.dataset.presetAmount;
                    }
                }

                if (clearBalanceBtn) {
                    if (balance > 0) {
                        clearBalanceBtn.hidden = false;
                        clearBalanceBtn.textContent = `Clear Balance (₱${money(balance)})`;
                        clearBalanceBtn.dataset.balanceAmount = balance.toFixed(2);
                    } else {
                        clearBalanceBtn.hidden = true;
                        delete clearBalanceBtn.dataset.balanceAmount;
                    }
                }

                if (!amountInput) return;

                if (suggested != null && !Number.isNaN(suggested) && suggested > 0) {
                    amountInput.value = suggested.toFixed(2);
                    return;
                }

                if (downpayment > 0) {
                    amountInput.value = (balance > 0 && balance < downpayment ? balance : downpayment).toFixed(2);
                    return;
                }

                amountInput.value = '';
            };

            const hideEnrollee = () => {
                enrolleeLocked = false;
                lastLookedUp = '';
                if (enrolleeEmpty) enrolleeEmpty.hidden = false;
                if (enrolleeDetails) enrolleeDetails.hidden = true;
                if (applicationIdInput) applicationIdInput.value = '';
                if (form) form.action = '#';
                if (downpaymentBtn) downpaymentBtn.hidden = true;
                if (clearBalanceBtn) clearBalanceBtn.hidden = true;
                setSubmitEnabled(false);
            };

            const showEnrollee = (payload) => {
                enrolleeLocked = true;
                if (enrolleeName) enrolleeName.textContent = payload.name || '';
                if (enrolleeEmail) enrolleeEmail.textContent = payload.email || '';
                if (enrolleeMeta) {
                    enrolleeMeta.textContent = [
                        payload.batch || 'Unassigned',
                        payload.schedule || '',
                        payload.payment_status || '',
                        `Balance: ₱${money(payload.balance)}`,
                    ].filter(Boolean).join(' · ');
                }
                if (enrolleeMatch) {
                    enrolleeMatch.textContent = payload.matched_label
                        ? `Matched ${payload.matched_label}.`
                        : '';
                }
                if (applicationIdInput) applicationIdInput.value = payload.application_id || '';
                if (enrolleeEmpty) enrolleeEmpty.hidden = true;
                if (enrolleeDetails) enrolleeDetails.hidden = false;
                updateFormAction(payload.application_id);
                applyAmountPresets(payload);

                if (payload.suggested_type && txTypeSelect) {
                    txTypeSelect.value = payload.suggested_type;
                } else if (txTypeSelect) {
                    txTypeSelect.value = Number.parseFloat(payload.balance || '0') <= 0
                        ? 'balance_settlement'
                        : 'downpayment';
                }

                if (payload.already_paid) {
                    setStatus('This enrollee is already fully paid. A new on-site payment cannot be recorded.', true);
                    setSubmitEnabled(false);
                    return;
                }

                if (payload.already_recorded) {
                    setStatus('This official receipt is already on the ledger for this enrollee.');
                } else {
                    setStatus('Enrollee found. Review the amount, then save to credit this payment. MCARE will generate the official OR number automatically.');
                }

                setSubmitEnabled(true);
            };

            const lookupEnrollee = async (query, { force = false } = {}) => {
                const value = String(query || '').trim();
                if (value.length < 3) {
                    if (!enrolleeLocked) {
                        setStatus('Type at least 3 characters from the reference number, OR, ticket, or enrollment number.');
                    }
                    return;
                }

                if (!force && enrolleeLocked) {
                    return;
                }

                if (!force && value.toUpperCase() === lastLookedUp) {
                    return;
                }

                lastLookedUp = value.toUpperCase();
                lookupController?.abort();
                lookupController = new AbortController();
                setStatus('Looking up enrollee...');

                try {
                    const response = await fetch(`${lookupUrl}?q=${encodeURIComponent(value)}`, {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        signal: lookupController.signal,
                    });

                    if (!response.ok) {
                        setStatus('The enrollee lookup could not be completed. Try again.', true);
                        return;
                    }

                    const payload = await response.json();
                    if (!payload?.found) {
                        hideEnrollee();
                        lastLookedUp = value.toUpperCase();
                        setStatus('No enrollee matched that reference number. Check the value from the ledger or payment slip.', true);
                        return;
                    }

                    showEnrollee(payload);
                } catch (error) {
                    if (error?.name === 'AbortError') return;
                    setStatus('The enrollee lookup could not be completed. Try again.', true);
                }
            };

            const scheduleLookup = () => {
                window.clearTimeout(lookupTimer);
                lookupTimer = window.setTimeout(() => lookupEnrollee(lookupInput?.value || ''), 350);
            };

            lookupInput?.addEventListener('input', () => {
                if (enrolleeLocked && lookupInput.value.trim().toUpperCase() !== lastLookedUp) {
                    enrolleeLocked = false;
                }
                scheduleLookup();
            });

            lookupInput?.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    lookupEnrollee(lookupInput.value, { force: true });
                }
            });

            lookupButton?.addEventListener('click', (event) => {
                event.preventDefault();
                lookupEnrollee(lookupInput?.value || '', { force: true });
            });

            downpaymentBtn?.addEventListener('click', () => {
                if (amountInput && downpaymentBtn.dataset.presetAmount) {
                    amountInput.value = downpaymentBtn.dataset.presetAmount;
                }
                if (txTypeSelect) txTypeSelect.value = 'downpayment';
            });

            clearBalanceBtn?.addEventListener('click', () => {
                const balance = clearBalanceBtn.dataset.balanceAmount || '';
                if (amountInput && balance) amountInput.value = balance;
                if (txTypeSelect) txTypeSelect.value = 'balance_settlement';
            });

            form?.addEventListener('submit', (event) => {
                if (!applicationIdInput?.value || form.action.endsWith('#')) {
                    event.preventDefault();
                    setStatus('Find the enrollee by reference number first.', true);
                }
            });

            const resetDialog = () => {
                window.clearTimeout(lookupTimer);
                lookupController?.abort();
                hideEnrollee();
                if (lookupInput) {
                    lookupInput.value = '';
                    lookupInput.placeholder = 'e.g. MCARE-SITE-260903-XXXXXXXX';
                }
                if (amountInput) amountInput.value = '';
                if (txTypeSelect) txTypeSelect.value = 'downpayment';
                setStatus('Paste the reference number from the ledger, then click Find.');
            };

            dialog?.addEventListener('close', resetDialog);

            document.querySelectorAll('[data-record-for-app]').forEach((button) => {
                button.addEventListener('click', () => {
                    const reference = (button.dataset.appReference || '').trim();

                    if (dialog && typeof dialog.showModal === 'function' && !dialog.open) {
                        dialog.showModal();
                    }

                    if (reference && lookupInput) {
                        lookupInput.value = reference;
                        lookupEnrollee(reference, { force: true });
                        lookupInput.focus();
                        return;
                    }

                    const appId = button.dataset.appId;
                    const balance = parseFloat(button.dataset.appBalance || '0');
                    const downpayment = parseFloat(button.dataset.appDownpayment || '0');

                    showEnrollee({
                        application_id: appId,
                        name: button.dataset.appName,
                        email: button.dataset.appEmail,
                        batch: button.dataset.appBatch,
                        schedule: button.dataset.appSchedule,
                        payment_status: button.dataset.appStatus,
                        balance,
                        downpayment_amount: downpayment,
                        suggested_amount: downpayment > 0 ? downpayment : null,
                        matched_label: '',
                        can_record: balance > 0,
                        already_paid: balance <= 0,
                    });

                    if (lookupInput) lookupInput.value = '';
                });
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Payment Management & Ledger | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\payment-schedules\index.blade.php ENDPATH**/ ?>