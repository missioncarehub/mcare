<?php $__env->startSection('content'); ?>
    <?php
        $badgeClasses = [
            'profile_submitted' => 'bg-sky-50 text-sky-700 ring-sky-100',
            'pre_enlistment' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'denied' => 'bg-red-50 text-red-700 ring-red-100',
        ];

        $paymentBadgeClasses = [
            'not_selected' => 'bg-slate-50 text-slate-700 ring-slate-100',
            'onsite_pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'online_pending' => 'bg-purple-50 text-purple-700 ring-purple-100',
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'expired' => 'bg-red-50 text-red-700 ring-red-100',
        ];
    ?>

    <div class="space-y-6">
        <!-- Status Summary Horizontal Cards Grid -->
        <?php $enrollCardIcons = ['profile_submitted' => ['icon' => 'file-text', 'tone' => 'bg-sky-50 text-sky-700 ring-sky-100'], 'pre_enlistment' => ['icon' => 'clipboard-list', 'tone' => 'bg-amber-50 text-amber-700 ring-amber-100'], 'approved' => ['icon' => 'circle-check', 'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'], 'denied' => ['icon' => 'xmark', 'tone' => 'bg-red-50 text-red-700 ring-red-100']]; ?>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <a href="<?php echo e(route('admin.enrollments.index')); ?>" class="group flex items-start justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-purple-300 hover:shadow-sm <?php if($selectedStatus === ''): ?> ring-2 ring-purple-500 border-purple-300 bg-purple-50/20 <?php endif; ?>">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-purple-700">Total enrollments</span>
                    <span class="mt-2 block font-display text-2xl font-extrabold text-slate-900"><?php echo e($totalApplications); ?></span>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'users']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'users']); ?>
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
            </a>
            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="<?php echo e(route('admin.enrollments.index', ['status' => $status])); ?>" class="group flex items-start justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-purple-300 hover:shadow-sm <?php if($selectedStatus === $status): ?> ring-2 ring-purple-500 border-purple-300 bg-purple-50/20 <?php endif; ?>">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-purple-700"><?php echo e($label); ?></span>
                        <span class="mt-2 block font-display text-2xl font-extrabold text-slate-900"><?php echo e($counts[$status] ?? 0); ?></span>
                    </div>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg ring-1 <?php echo e($enrollCardIcons[$status]['tone'] ?? 'bg-slate-50 text-slate-600 ring-slate-100'); ?>"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => $enrollCardIcons[$status]['icon'] ?? 'circle-question']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($enrollCardIcons[$status]['icon'] ?? 'circle-question')]); ?>
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
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <!-- Filter Controls Form -->
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="<?php echo e(route('admin.enrollments.index')); ?>" data-auto-filter class="space-y-4">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
                    <div class="sm:col-span-2">
                        <label for="search" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Search</label>
                        <input id="search" name="search" type="search" value="<?php echo e($search); ?>" placeholder="Name, email, or contact number" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder-slate-400 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                    </div>
                    <div>
                        <label for="status" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Status</label>
                        <select id="status" name="status" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <option value="">All statuses</option>
                            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($status); ?>" <?php if($selectedStatus === $status): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label for="batch_id" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Batch</label>
                        <select id="batch_id" name="batch_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <option value="">All batches</option>
                            <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($batch->id); ?>" <?php if($batchId === $batch->id): echo 'selected'; endif; ?>><?php echo e($batch->name); ?> <?php echo e($batch->year); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label for="schedule" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Schedule</label>
                        <select id="schedule" name="schedule" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <option value="">AM and PM</option>
                            <option value="AM" <?php if($schedule === 'AM'): echo 'selected'; endif; ?>>AM students</option>
                            <option value="PM" <?php if($schedule === 'PM'): echo 'selected'; endif; ?>>PM students</option>
                        </select>
                    </div>
                    <div>
                        <label for="enrollment_state" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Window</label>
                        <select id="enrollment_state" name="enrollment_state" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <option value="">Any window</option>
                            <option value="open" <?php if($enrollmentState === 'open'): echo 'selected'; endif; ?>>Open</option>
                            <option value="continuous" <?php if($enrollmentState === 'continuous'): echo 'selected'; endif; ?>>Continuous enrollment</option>
                            <option value="upcoming" <?php if($enrollmentState === 'upcoming'): echo 'selected'; endif; ?>>Starting soon</option>
                            <option value="closed" <?php if($enrollmentState === 'closed'): echo 'selected'; endif; ?>>Closed</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="rounded-xl bg-purple-700 px-5 py-2 text-sm font-semibold text-white transition hover:bg-purple-800">
                        Filter
                    </button>
                    <a href="<?php echo e(route('admin.enrollments.index')); ?>" class="rounded-xl border border-slate-200 bg-white px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Reset
                    </a>
                </div>
            </form>
        </div>

        <!-- Applicant Queue Data Table -->
        <div class="w-full max-w-full overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-left text-sm border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5">Applicant</th>
                        <th class="px-5 py-3.5">Program</th>
                        <th class="px-5 py-3.5">Batch</th>
                        <th class="px-5 py-3.5">Schedule</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Payment</th>
                        <th class="px-5 py-3.5">Submitted</th>
                        <th class="px-5 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php $__empty_1 = true; $__currentLoopData = $applications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $application): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $application->user,'application' => $application,'useEnrollmentPhoto' => true,'class' => 'grid h-11 w-11 place-items-center rounded-full bg-purple-100 text-sm font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($application->user),'application' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($application),'use-enrollment-photo' => true,'class' => 'grid h-11 w-11 place-items-center rounded-full bg-purple-100 text-sm font-black text-purple-800']); ?>
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
                                        <p class="font-bold text-slate-900"><?php echo e($application->last_name); ?>, <?php echo e($application->first_name); ?></p>
                                        <p class="text-xs text-slate-500"><?php echo e($application->email); ?></p>
                                        <p class="text-xs text-slate-400"><?php echo e($application->contact_number); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 font-semibold text-slate-700"><?php echo e($application->program); ?></td>
                            <td class="px-5 py-4 text-slate-600"><?php echo e($application->batch ? $application->batch->name.' '.$application->batch->year : 'Unassigned'); ?></td>
                            <td class="px-5 py-4 text-slate-600"><?php echo e($application->schedule_preference); ?></td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 <?php echo e($badgeClasses[$application->status] ?? 'bg-slate-50 text-slate-700 ring-slate-100'); ?>">
                                    <?php echo e($application->statusLabel()); ?>

                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-bold ring-1 <?php echo e($paymentBadgeClasses[$application->payment_status] ?? 'bg-slate-50 text-slate-700 ring-slate-100'); ?>">
                                    <?php echo e($application->paymentStatusLabel()); ?>

                                </span>
                            </td>
                            <td class="px-5 py-4 text-slate-500 text-xs"><?php echo e($application->created_at?->format('M d, Y')); ?></td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <a href="<?php echo e(route('admin.enrollments.show', $application)); ?>" class="inline-flex items-center justify-center rounded-lg border border-purple-200 bg-white px-3 py-1.5 text-xs font-bold text-purple-700 transition hover:bg-purple-50">
                                        Review
                                    </a>
                                    <form method="POST" action="<?php echo e(route('admin.enrollments.destroy', $application)); ?>" data-confirm-title="<?php echo e($application->accountDeletionTitle()); ?>" data-confirm="<?php echo e($application->accountDeletionMessage()); ?>" <?php if($application->accountDeletionDetail()): ?> data-confirm-detail="<?php echo e($application->accountDeletionDetail()); ?>" <?php endif; ?> data-confirm-action="<?php echo e($application->accountDeletionAction()); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-700 transition hover:bg-red-50" title="<?php echo e($application->accountDeletionAction()); ?>">
                                            <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'trash-2','class' => 'h-3.5 w-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash-2','class' => 'h-3.5 w-3.5']); ?>
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
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-slate-500">
                                <p class="text-base font-bold text-slate-800">No enrollments found</p>
                                <p class="mt-1 text-xs text-slate-500">Try adjusting your search query or status filter options.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if($applications->hasPages()): ?>
                <div class="border-t border-slate-200 px-5 py-3 bg-slate-50">
                    <?php echo e($applications->links()); ?>

                </div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Enrollments | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\enrollments\index.blade.php ENDPATH**/ ?>