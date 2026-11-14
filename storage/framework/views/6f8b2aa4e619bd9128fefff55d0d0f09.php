<?php $__env->startSection('content'); ?>
    <div class="space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <p class="dashboard-section-kicker">Alumni standing</p>
            <h1 class="dashboard-section-title mt-2 text-2xl">Junior and senior alumni</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">This list combines alumni claims and trainees who graduated in the system. Approved claims and graduates start as junior alumni. Promote someone to senior alumni only when you decide they should hold that standing.</p>
        </header>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Name</th>
                        <th class="px-4 py-3">Email</th>
                        <th class="px-4 py-3">Source</th>
                        <th class="px-4 py-3">Record</th>
                        <th class="px-4 py-3">Alumni standing</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $__empty_1 = true; $__currentLoopData = $people; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $person): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-4 py-3 font-semibold text-slate-900"><?php echo e($person['name']); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo e($person['email']); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo e($person['source']); ?></td>
                            <td class="px-4 py-3 text-slate-700"><?php echo e($person['detail']); ?></td>
                            <td class="px-4 py-3">
                                <?php if($person['rank']): ?>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 <?php echo e($person['rank'] === \App\Models\AlumniProfile::RANK_SENIOR ? 'bg-purple-50 text-purple-800 ring-purple-100' : 'bg-slate-50 text-slate-700 ring-slate-200'); ?>"><?php echo e(\App\Models\AlumniProfile::ranks()[$person['rank']]); ?></span>
                                        <?php if($person['promote_url']): ?>
                                            <form method="POST" action="<?php echo e($person['promote_url']); ?>" data-confirm="Promote <?php echo e($person['name']); ?> to senior alumni?">
                                                <?php echo csrf_field(); ?>
                                                <?php echo method_field('PATCH'); ?>
                                                <button type="submit" class="inline-flex items-center rounded-lg border border-purple-200 bg-white px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50">Promote to senior</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-slate-500"><?php echo e($person['status']); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-slate-500">No alumni claims or training graduates yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if($people->hasPages()): ?>
                <div class="border-t border-slate-100 px-4 py-4"><?php echo e($people->links()); ?></div>
            <?php endif; ?>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Alumni standing | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\alumni-standing\index.blade.php ENDPATH**/ ?>