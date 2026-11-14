<?php $__env->startSection('content'); ?>
    <?php
        $badgeClasses = [
            'pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'denied' => 'bg-red-50 text-red-700 ring-red-100',
        ];
        $appCardIcons = [
            'pending' => ['icon' => 'clipboard-list', 'tone' => 'bg-amber-50 text-amber-700 ring-amber-100'],
            'approved' => ['icon' => 'circle-check', 'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
            'denied' => ['icon' => 'xmark', 'tone' => 'bg-red-50 text-red-700 ring-red-100'],
        ];
        $stageBadge = [
            'complete' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'in_progress' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'pending' => 'bg-slate-50 text-slate-500 ring-slate-100',
            'blocked' => 'bg-red-50 text-red-700 ring-red-100',
        ];
        $stateLabels = [
            'complete' => 'Done',
            'in_progress' => 'In progress',
            'pending' => 'Pending',
            'blocked' => 'Blocked',
        ];
        $activeCount = (int) ($counts['pending'] ?? 0) + (int) ($counts['denied'] ?? 0);
    ?>

    <div class="space-y-6">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <a href="<?php echo e(route('admin.applications.index')); ?>" class="group flex items-start justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-purple-300 hover:shadow-sm <?php if($selectedStatus === ''): ?> ring-2 ring-purple-500 border-purple-300 bg-purple-50/20 <?php endif; ?>">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-purple-700">Needs review</span>
                    <span class="mt-2 block font-display text-2xl font-extrabold text-slate-900"><?php echo e($activeCount); ?></span>
                    <span class="mt-1 block text-[10px] font-semibold uppercase tracking-wider text-slate-400">Approved are hidden by default · <?php echo e($totalApplications); ?> total</span>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'clipboard-list']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard-list']); ?>
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
                <a href="<?php echo e(route('admin.applications.index', ['status' => $status])); ?>" class="group flex items-start justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 transition hover:border-purple-300 hover:shadow-sm <?php if($selectedStatus === $status): ?> ring-2 ring-purple-500 border-purple-300 bg-purple-50/20 <?php endif; ?>">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-500 group-hover:text-purple-700"><?php echo e($label); ?></span>
                        <span class="mt-2 block font-display text-2xl font-extrabold text-slate-900"><?php echo e($counts[$status] ?? 0); ?></span>
                    </div>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg ring-1 <?php echo e($appCardIcons[$status]['tone'] ?? 'bg-slate-50 text-slate-600 ring-slate-100'); ?>"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => $appCardIcons[$status]['icon'] ?? 'circle-question']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($appCardIcons[$status]['icon'] ?? 'circle-question')]); ?>
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

        <?php if($unusedApprovedExpirySummary): ?>
            <p class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-medium leading-6 text-amber-900">
                <?php echo e($unusedApprovedExpirySummary); ?>

                <a href="<?php echo e(route('account.settings')); ?>#unused-approved-expiry" class="ml-1 font-bold text-purple-800 hover:text-purple-900">Change in Settings</a>
            </p>
        <?php endif; ?>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="<?php echo e(route('admin.applications.index')); ?>" data-auto-filter class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="sm:col-span-2">
                    <label for="search" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Search</label>
                    <input id="search" name="search" type="search" value="<?php echo e($search); ?>" placeholder="Number, name, email, or contact" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 placeholder-slate-400 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
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
            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Number</th>
                        <th class="px-4 py-3">Applicant</th>
                        <th class="px-4 py-3">Program</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Current stage</th>
                        <th class="px-4 py-3">Submitted</th>
                        <th class="px-4 py-3 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $__empty_1 = true; $__currentLoopData = $admissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $admission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $hasEnrollment = $admission->enrollment !== null;
                            $currentStage = $admission->currentStage();
                            $stages = $admission->progressStages();
                            $completeCount = collect($stages)->where('state', 'complete')->count();
                        ?>
                        <tr>
                            <td class="px-4 py-3 font-bold text-slate-950"><?php echo e($admission->application_number); ?></td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-900"><?php echo e($admission->fullName()); ?></p>
                                <p class="text-xs text-slate-500"><?php echo e($admission->email); ?></p>
                            </td>
                            <td class="px-4 py-3 text-slate-700"><?php echo e($admission->program); ?></td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 <?php echo e($badgeClasses[$admission->status] ?? 'bg-slate-50 text-slate-700 ring-slate-100'); ?>"><?php echo e($admission->statusLabel()); ?></span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs font-bold text-slate-900"><?php echo e($currentStage['label']); ?></p>
                                <span class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 <?php echo e($stageBadge[$currentStage['state']] ?? 'bg-slate-50 text-slate-500 ring-slate-100'); ?>"><?php echo e($stateLabels[$currentStage['state']] ?? ucfirst($currentStage['state'])); ?></span>
                                <p class="mt-1 text-[10px] font-semibold text-slate-500"><?php echo e($completeCount); ?>/<?php echo e(count($stages)); ?> steps complete</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($admission->created_at?->format('M d, Y')); ?></td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="<?php echo e(route('admin.applications.show', $admission)); ?>" class="inline-flex items-center gap-2 rounded-lg border border-purple-200 bg-white px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50">
                                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'eye','class' => 'h-3.5 w-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'eye','class' => 'h-3.5 w-3.5']); ?>
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
                                        Review
                                    </a>
                                    <form method="POST" action="<?php echo e(route('admin.applications.destroy', $admission)); ?>" data-confirm="<?php echo e($hasEnrollment ? 'This application is linked to a submitted enrollment and cannot be deleted.' : 'Delete application '.$admission->application_number.'? This cannot be undone.'); ?>">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" <?php if($hasEnrollment): echo 'disabled'; endif; ?> class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40" title="<?php echo e($hasEnrollment ? 'Delete is disabled while an enrollment is linked.' : 'Delete application'); ?>">
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
                            <td colspan="7" class="px-4 py-14 text-center text-slate-500">No applications match these filters.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if($admissions->hasPages()): ?>
                <div class="border-t border-slate-100 px-4 py-4"><?php echo e($admissions->links()); ?></div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Applications | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\applications\index.blade.php ENDPATH**/ ?>