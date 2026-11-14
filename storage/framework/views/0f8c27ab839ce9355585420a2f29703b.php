<?php $__env->startSection('content'); ?>
    <section class="rounded-3xl border border-purple-100 bg-white p-7 shadow-xl shadow-purple-100/40 sm:p-8">
        <div class="flex flex-col gap-6">
            <div>
                <p class="max-w-3xl text-sm leading-6 text-slate-500">
                    Track login activity, review decisions, document downloads, and schedule changes for audit and anti-abuse review.
                </p>
            </div>
            <form method="GET" action="<?php echo e(route('admin.logs.index')); ?>" data-auto-filter class="grid w-full gap-3 sm:grid-cols-2 lg:grid-cols-[10rem_11rem_minmax(14rem,1fr)_auto]">
                <label class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Coverage
                    <select name="period" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-purple-400 focus:ring-4 focus:ring-purple-100">
                        <?php $__currentLoopData = ['all' => 'All activity', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($value); ?>" <?php if($period === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </label>
                <label class="text-xs font-bold uppercase tracking-wide text-slate-500">
                    Reference date
                    <input name="date" type="date" value="<?php echo e($anchorDate->format('Y-m-d')); ?>" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-3 text-sm font-semibold text-slate-700 outline-none focus:border-purple-400 focus:ring-4 focus:ring-purple-100">
                </label>
                <label class="text-xs font-bold uppercase tracking-wide text-slate-500 sm:col-span-2 lg:col-span-1">
                    Search
                    <input name="search" type="search" value="<?php echo e($search); ?>" placeholder="Action, account, or IP" class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm outline-none focus:border-purple-400 focus:ring-4 focus:ring-purple-100">
                </label>
                <button class="self-end rounded-xl bg-purple-700 px-5 py-3 text-sm font-bold text-white hover:bg-purple-800">Apply</button>
            </form>
        </div>
        <div class="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm font-semibold text-slate-600">Showing <span class="font-black text-slate-900"><?php echo e($rangeLabel); ?></span></p>
            <div class="flex flex-wrap gap-2">
                <a href="<?php echo e(route('admin.logs.print', request()->query())); ?>" target="_blank" rel="noopener" class="secondary-action">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'print','class' => 'mr-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'print','class' => 'mr-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Print report
                </a>
                <a href="<?php echo e(route('admin.logs.export', request()->query())); ?>" class="primary-action">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'file-excel','class' => 'mr-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'file-excel','class' => 'mr-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Export for Excel
                </a>
            </div>
        </div>
    </section>

    <section class="mt-8 overflow-hidden rounded-3xl border border-slate-100 bg-white shadow-xl shadow-slate-200/60">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Time</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Admin</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Action</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">IP</th>
                        <th class="px-6 py-4 text-left text-xs font-bold uppercase tracking-wide text-slate-500">Details</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr class="hover:bg-purple-50/40">
                            <td class="px-6 py-5 text-sm font-semibold text-slate-700"><?php echo e($log->created_at->format('M d, Y g:i A')); ?></td>
                            <td class="px-6 py-5">
                                <p class="font-bold text-slate-900"><?php echo e($log->user?->name ?? 'System / unknown'); ?></p>
                                <p class="mt-1 text-sm text-slate-500"><?php echo e($log->user?->email ?? 'No account attached'); ?></p>
                            </td>
                            <td class="px-6 py-5">
                                <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700 ring-1 ring-purple-100"><?php echo e($log->action); ?></span>
                            </td>
                            <td class="px-6 py-5 text-sm text-slate-600"><?php echo e($log->ip_address ?? 'Unknown'); ?></td>
                            <td class="px-6 py-5 text-sm text-slate-600">
                                <?php if($log->meta): ?>
                                    <div class="max-w-lg rounded-2xl bg-slate-50 p-3">
                                        <?php echo $__env->make('admin.logs.partials.meta-details', ['meta' => $log->meta], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="italic text-slate-400">No extra details</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-14 text-center">
                                <p class="text-lg font-bold text-slate-900">No admin logs yet</p>
                                <p class="mt-2 text-sm text-slate-500">Security events will appear here as admins use the system.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($logs->hasPages()): ?>
            <div class="border-t border-slate-100 px-6 py-4">
                <?php echo e($logs->links()); ?>

            </div>
        <?php endif; ?>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Admin Logs | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\logs\index.blade.php ENDPATH**/ ?>