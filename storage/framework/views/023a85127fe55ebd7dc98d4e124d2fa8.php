<?php $__env->startSection('content'); ?>
    <?php
        $badgeClasses = [
            'pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'denied' => 'bg-red-50 text-red-700 ring-red-100',
        ];
    ?>

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="<?php echo e(route('admin.applications.index')); ?>" class="inline-flex w-fit items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:border-purple-200 hover:text-purple-700">Back to applications</a>
        <span class="inline-flex w-fit rounded-full px-4 py-2 text-sm font-bold ring-1 <?php echo e($badgeClasses[$admission->status] ?? 'bg-slate-50 text-slate-700 ring-slate-100'); ?>"><?php echo e($admission->statusLabel()); ?></span>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_22rem]">
        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-purple-700"><?php echo e($admission->application_number); ?></p>
            <h2 class="mt-2 text-2xl font-bold text-slate-950"><?php echo e($admission->fullName()); ?></h2>
            <dl class="mt-6 grid gap-4 sm:grid-cols-2">
                <?php $__currentLoopData = [
                    'Email' => $admission->email,
                    'Contact number' => $admission->contact_number,
                    'Program' => $admission->program,
                    'Preferred schedule' => $admission->schedule_preference ?: 'Not specified',
                    'Educational attainment' => $admission->educational_attainment,
                    'Submitted' => $admission->created_at?->format('M d, Y g:i A'),
                ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $label => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-500"><?php echo e($label); ?></dt>
                        <dd class="mt-1 font-semibold text-slate-900"><?php echo e($value ?: '—'); ?></dd>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </dl>
            <?php if(filled($admission->notes)): ?>
                <div class="mt-6">
                    <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Applicant note</p>
                    <p class="mt-2 text-sm leading-6 text-slate-700"><?php echo e($admission->notes); ?></p>
                </div>
            <?php endif; ?>
            <?php if($admission->enrollment): ?>
                <p class="mt-6 text-sm font-semibold text-purple-800">This number is already linked to a submitted enrollment.</p>
                <a href="<?php echo e(route('admin.enrollments.show', $admission->enrollment)); ?>" class="primary-action mt-3 inline-flex items-center justify-center">Open enrollment record</a>
            <?php endif; ?>

            
            <?php
                $stages = $admission->progressStages();
                $currentStage = $admission->currentStage();
                $stageBadge = [
                    'complete' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                    'in_progress' => 'bg-amber-50 text-amber-700 ring-amber-100',
                    'pending' => 'bg-slate-50 text-slate-500 ring-slate-100',
                    'blocked' => 'bg-red-50 text-red-700 ring-red-100',
                ];
                $stateLabels = ['complete' => 'Done', 'in_progress' => 'In progress', 'pending' => 'Pending', 'blocked' => 'Blocked'];
            ?>
            <div class="mt-8 rounded-xl border border-purple-100 bg-purple-50/40 p-4">
                <div class="flex items-center justify-between">
                    <p class="text-xs font-bold uppercase tracking-wider text-purple-800">Applicant progress</p>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-bold ring-1 <?php echo e($stageBadge[$currentStage['state']] ?? 'bg-slate-50 text-slate-500 ring-slate-100'); ?>">Now: <?php echo e($currentStage['label']); ?></span>
                </div>
                <ol class="mt-3 space-y-2">
                    <?php $__currentLoopData = $stages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $stage): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li class="flex items-start gap-3 rounded-lg bg-white/70 p-3 ring-1 ring-slate-100">
                            <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full text-[11px] font-black ring-1 <?php echo e($stageBadge[$stage['state']] ?? 'bg-slate-50 text-slate-500 ring-slate-100'); ?>"><?php echo e($index + 1); ?></span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-slate-900"><?php echo e($stage['label']); ?>

                                    <span class="ml-2 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 <?php echo e($stageBadge[$stage['state']] ?? 'bg-slate-50 text-slate-500 ring-slate-100'); ?>"><?php echo e($stateLabels[$stage['state']] ?? ucfirst($stage['state'])); ?></span>
                                </p>
                                <p class="mt-0.5 text-xs text-slate-600"><?php echo e($stage['description']); ?></p>
                            </div>
                        </li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ol>
            </div>
        </section>

        <aside class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Review decision</p>
            <form method="POST" action="<?php echo e(route('admin.applications.update', $admission)); ?>" class="mt-4 space-y-4" data-form-draft="admin.applications.<?php echo e($admission->id); ?>" <?php if($errors->any()): ?> data-form-draft-server-old="1" <?php endif; ?>>
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500" for="status">Decision</label>
                    <select id="status" name="status" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" <?php if($admission->enrollment && $admission->isApproved()): echo 'disabled'; endif; ?>>
                        <option value="approved" <?php if(old('status', $admission->status) === 'approved'): echo 'selected'; endif; ?>>Approve for enrollment</option>
                        <option value="denied" <?php if(old('status', $admission->status) === 'denied'): echo 'selected'; endif; ?>>Deny</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500" for="admin_notes">Notes</label>
                    <textarea id="admin_notes" name="admin_notes" class="min-h-28 w-full rounded-xl border border-slate-200 px-3 py-2 text-sm" placeholder="Required when denying"><?php echo e(old('admin_notes', $admission->admin_notes)); ?></textarea>
                </div>
                <button type="submit" class="primary-action w-full" <?php if($admission->enrollment && $admission->isApproved()): echo 'disabled'; endif; ?>>Save decision</button>
            </form>
            <?php if($admission->reviewed_at): ?>
                <p class="mt-4 text-xs text-slate-500">Last reviewed <?php echo e($admission->reviewed_at->format('M d, Y g:i A')); ?><?php if($admission->reviewer): ?> by <?php echo e($admission->reviewer->name); ?><?php endif; ?>.</p>
            <?php endif; ?>
        </aside>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Review application '.$admission->application_number.' | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\applications\show.blade.php ENDPATH**/ ?>