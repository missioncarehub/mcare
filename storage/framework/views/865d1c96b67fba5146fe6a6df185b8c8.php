<?php $__env->startSection('content'); ?>
    <section class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="dashboard-section-kicker">Trainee records</p>
                <h1 class="dashboard-section-title mt-2 text-2xl">Archive</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Trainees removed from the roster stay here with their account and learning records. Restore a trainee to return them to the current or graduate list.</p>
            </div>
            <a href="<?php echo e(route('admin.learning.trainees')); ?>" class="secondary-action">Back to trainees</a>
        </header>

        <div class="dashboard-table-wrap overflow-x-auto">
            <table class="dashboard-table w-full min-w-[48rem]">
                <thead>
                    <tr>
                        <th>Trainee</th>
                        <th>Batch</th>
                        <th>Status</th>
                        <th>Archived</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $trainees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trainee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td>
                                <p class="font-bold text-slate-950"><?php echo e($trainee->last_name); ?>, <?php echo e($trainee->first_name); ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?php echo e($trainee->email); ?></p>
                            </td>
                            <td><?php echo e($trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : 'Unassigned batch'); ?></td>
                            <td><?php echo e($trainee->learningStatusLabel()); ?></td>
                            <td>
                                <p class="font-semibold text-slate-800"><?php echo e($trainee->archived_at?->format('M d, Y')); ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?php echo e($trainee->archivedBy?->name ?: 'Administrator'); ?></p>
                            </td>
                            <td class="text-right">
                                <form method="POST" action="<?php echo e(route('admin.learning.trainees.restore', $trainee)); ?>" class="inline-flex" data-confirm="Restore <?php echo e($trainee->first_name); ?> <?php echo e($trainee->last_name); ?> to the trainee roster?">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('PATCH'); ?>
                                    <button type="submit" class="primary-action">Restore</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="py-14 text-center">
                                <p class="text-lg font-bold text-slate-950">The archive is empty</p>
                                <p class="mt-2 text-sm text-slate-500">Deleted trainees will appear here.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if($trainees->hasPages()): ?>
                <div class="border-t border-slate-200 px-5 py-4"><?php echo e($trainees->links()); ?></div>
            <?php endif; ?>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Trainee archive | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\learning\trainees-archive.blade.php ENDPATH**/ ?>