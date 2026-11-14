<?php $__env->startSection('content'); ?>
<div class="w-full space-y-7">
    <header class="border-b border-stone-200 pb-6">
        <p class="text-sm font-bold uppercase tracking-[0.16em] text-violet-700">My trainings</p>
        <h1 class="mt-2 text-3xl font-bold text-stone-950">Batch training overview</h1>
        <p class="mt-2 text-stone-600">The same enrollment and training windows maintained by the admin, grouped by batch.</p>
    </header>
    <?php if($assignedBatch): ?>
        <div class="border border-violet-200 bg-violet-50 p-4 text-sm text-violet-950">
            <strong>Current assignment:</strong> <?php echo e($assignedBatch->name); ?> <?php echo e($assignedBatch->year); ?>.
            Teaching tools, rosters, schedules, classwork, and competency records are scoped to this batch.
        </div>
    <?php else: ?>
        <div class="border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950">
            <strong>No current batch is assigned.</strong> Ask an administrator to assign one before managing learners or publishing classwork.
        </div>
    <?php endif; ?>
    <div class="grid gap-5 lg:grid-cols-2">
        <?php $__empty_1 = true; $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <article class="border border-stone-200 bg-white p-6">
                <div class="flex items-start justify-between gap-4">
                    <div><h2 class="text-xl font-bold text-stone-950"><?php echo e($batch->name); ?> <?php echo e($batch->year); ?></h2><p class="mt-1 text-sm text-stone-600"><?php echo e($batch->trainingStateLabel()); ?></p></div>
                    <div class="flex flex-wrap justify-end gap-2">
                        <span class="px-3 py-1 text-xs font-bold <?php echo e($batch->is_active ? 'bg-emerald-50 text-emerald-800' : 'bg-stone-100 text-stone-600'); ?>"><?php echo e($batch->is_active ? 'Active' : 'Inactive'); ?></span>
                        <?php if($assignedBatch?->id === $batch->id): ?>
                            <span class="px-3 py-1 text-xs font-bold bg-violet-100 text-violet-800">Assigned to you</span>
                        <?php elseif($batch->trainer): ?>
                            <span class="px-3 py-1 text-xs font-bold bg-stone-100 text-stone-600">Assigned to <?php echo e($batch->trainer->name); ?></span>
                        <?php else: ?>
                            <span class="px-3 py-1 text-xs font-bold bg-amber-50 text-amber-800">Unassigned</span>
                        <?php endif; ?>
                    </div>
                </div>
                <dl class="mt-5 grid gap-4 sm:grid-cols-2">
                    <div class="bg-stone-50 p-4"><dt class="text-xs font-bold uppercase text-stone-500">Training window</dt><dd class="mt-2 font-semibold text-stone-900"><?php echo e($batch->training_starts_at?->format('M d, Y') ?? 'Not set'); ?> — <?php echo e($batch->training_ends_at?->format('M d, Y') ?? 'Open-ended'); ?></dd></div>
                    <div class="bg-stone-50 p-4"><dt class="text-xs font-bold uppercase text-stone-500">Learners / modules</dt><dd class="mt-2 font-semibold text-stone-900"><?php echo e($batch->applications_count); ?> learners · <?php echo e($batch->modules_count); ?> modules</dd></div>
                    <div class="bg-stone-50 p-4"><dt class="text-xs font-bold uppercase text-stone-500">AM</dt><dd class="mt-2 font-semibold text-stone-900"><?php echo e($batch->scheduleLabelFor('AM')); ?></dd><p class="mt-1 text-sm text-stone-600"><?php echo e($batch->roomFor('AM') ?: 'Room not set'); ?></p></div>
                    <div class="bg-stone-50 p-4"><dt class="text-xs font-bold uppercase text-stone-500">PM</dt><dd class="mt-2 font-semibold text-stone-900"><?php echo e($batch->scheduleLabelFor('PM')); ?></dd><p class="mt-1 text-sm text-stone-600"><?php echo e($batch->roomFor('PM') ?: 'Room not set'); ?></p></div>
                </dl>
            </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <p class="border border-stone-200 bg-white p-6 text-stone-600">No training batch has been scheduled by the admin yet.</p>
        <?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => 'My Trainings | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\trainings.blade.php ENDPATH**/ ?>