<?php $__env->startSection('content'); ?>
<section class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <p class="max-w-3xl text-sm leading-6 text-slate-600">
            Post institutional updates, batch schedule announcements, or monthly tuition payment reminders. You can target all trainees, a specific batch, or an individual enrollee, with optional email delivery.
        </p>
        <a href="<?php echo e(route('admin.payment-schedules.index')); ?>" class="secondary-action">
            <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'credit-card','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'credit-card','class' => 'h-4 w-4']); ?>
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
            <span>View Payment Ledger</span>
        </a>
    </header>

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-[440px_1fr]">
        <!-- Create Announcement Form -->
        <aside class="dashboard-panel space-y-5">
            <header class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-950">Publish Notice or Reminder</h2>
                <p class="mt-1 text-xs text-slate-500">Sends an in-app notice, and can also send email and SMS.</p>
            </header>

            <form method="POST" action="<?php echo e(route('admin.announcements.store')); ?>" class="space-y-4" id="admin-announcement-form">
                <?php echo csrf_field(); ?>

                <div>
                    <label for="announcement-title" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Title / Subject</label>
                    <input id="announcement-title" name="title" required maxlength="200" value="<?php echo e(old('title')); ?>" class="form-field" placeholder="e.g. Monthly Tuition Due Date: September 2026">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="announcement-kind" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Category</label>
                        <select id="announcement-kind" name="kind" class="form-field" required>
                            <?php $__currentLoopData = $kinds; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($key); ?>" <?php if(old('kind', 'reminder') === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label for="announcement-due" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Due Date (Optional)</label>
                        <input id="announcement-due" name="due_date" type="date" value="<?php echo e(old('due_date')); ?>" class="form-field">
                    </div>
                </div>

                <div>
                    <label for="announcement-target" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Target Audience</label>
                    <select id="announcement-target" name="target_type" class="form-field" required>
                        <?php $__currentLoopData = $targetTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($key); ?>" <?php if(old('target_type', 'all') === $key): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div id="target-batch-wrap" class="space-y-1.5 <?php echo e(old('target_type') === 'batch' ? '' : 'hidden'); ?>">
                    <label for="announcement-batch" class="block text-xs font-bold uppercase text-slate-600">Select Training Batch</label>
                    <select id="announcement-batch" name="training_batch_id" class="form-field">
                        <option value="">-- Choose Batch --</option>
                        <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($batch->id); ?>" <?php if((int) old('training_batch_id') === $batch->id): echo 'selected'; endif; ?>>
                                <?php echo e($batch->name); ?> (<?php echo e($batch->year); ?>)
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div id="target-user-wrap" class="space-y-1.5 <?php echo e(old('target_type') === 'user' ? '' : 'hidden'); ?>">
                    <label for="announcement-user" class="block text-xs font-bold uppercase text-slate-600">Select Specific Enrollee</label>
                    <select id="announcement-user" name="target_user_id" class="form-field">
                        <option value="">-- Choose Enrollee --</option>
                        <?php $__currentLoopData = $approvedTrainees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trainee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($trainee->id); ?>" <?php if((int) old('target_user_id') === $trainee->id): echo 'selected'; endif; ?>>
                                <?php echo e($trainee->name); ?> (<?php echo e($trainee->email); ?>) - <?php echo e($trainee->enrollmentApplication?->batch?->name ?? 'Unassigned'); ?>

                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>

                <div>
                    <label for="announcement-message" class="mb-1.5 block text-xs font-bold uppercase text-slate-600">Message Content</label>
                    <textarea id="announcement-message" name="message" rows="4" required maxlength="5000" class="form-field" placeholder="Provide instructions, installment details, or announcement notes..."><?php echo e(old('message')); ?></textarea>
                </div>

                <div class="space-y-3 rounded-xl border border-sky-100 bg-sky-50/50 p-3.5">
                    <label class="flex items-start gap-2.5">
                        <input type="checkbox" checked disabled class="mt-0.5 rounded border-slate-300 text-purple-600">
                        <span class="text-xs text-slate-700">
                            <strong class="font-bold text-slate-950">In-app</strong><br>
                            Always saved in the trainee notification bell.
                        </span>
                    </label>
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="send_email" value="1" <?php if(old('send_email', true)): echo 'checked'; endif; ?> class="mt-0.5 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                        <span class="text-xs text-slate-700">
                            <strong class="font-bold text-slate-950">Email</strong><br>
                            Sends a branded email through the configured mail service.
                        </span>
                    </label>
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="send_sms" value="1" <?php if(old('send_sms')): echo 'checked'; endif; ?> class="mt-0.5 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                        <span class="text-xs text-slate-700">
                            <strong class="font-bold text-slate-950">SMS</strong><br>
                            Sends through Semaphore to a valid 09XX or +63 mobile number. 160 characters is 1 credit. Numbers that are not Philippine mobiles are skipped.
                        </span>
                    </label>
                    <p class="text-xs font-semibold text-slate-600" data-announcement-sms-count>0 characters in the text that will be sent · 0 SMS credits</p>
                </div>

                <button type="submit" class="primary-action w-full justify-center">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'paper-plane','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'paper-plane','class' => 'h-4 w-4']); ?>
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
                    <span>Publish & Send Notice</span>
                </button>
            </form>
        </aside>

        <!-- Published Announcements Table -->
        <section class="dashboard-table-wrap">
            <div class="border-b border-slate-100 px-5 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-950">Notice History</h2>
                    <p class="text-xs text-slate-500">Recent announcements, reminders, and notifications sent by administrators.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="dashboard-table w-full min-w-[50rem]">
                    <thead>
                        <tr>
                            <th>Notice</th>
                            <th>Audience</th>
                            <th>Category</th>
                            <th>Due Date</th>
                            <th>Delivery</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $announcements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td>
                                    <p class="font-bold text-slate-950"><?php echo e($item->title); ?></p>
                                    <p class="mt-1 text-xs text-slate-600 line-clamp-2"><?php echo e($item->message); ?></p>
                                    <p class="mt-1.5 text-[11px] text-slate-400">
                                        By <?php echo e($item->author?->name ?? 'Admin'); ?> · <?php echo e($item->posted_at?->format('M d, Y g:i A') ?? $item->created_at->format('M d, Y')); ?>

                                    </p>
                                </td>
                                <td>
                                    <?php if($item->target_type === 'all'): ?>
                                        <span class="inline-flex rounded-md bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700 ring-1 ring-inset ring-blue-200">All Batches</span>
                                    <?php elseif($item->target_type === 'batch'): ?>
                                        <span class="inline-flex rounded-md bg-purple-50 px-2.5 py-1 text-xs font-bold text-purple-700 ring-1 ring-inset ring-purple-200">
                                            Batch: <?php echo e($item->batch?->name ?? 'Deleted Batch'); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-md bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                            <?php echo e($item->targetUser?->name ?? 'Individual Enrollee'); ?>

                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="inline-flex rounded-md bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        <?php echo e($kinds[$item->kind] ?? str($item->kind)->headline()); ?>

                                    </span>
                                </td>
                                <td>
                                    <?php if($item->due_date): ?>
                                        <span class="font-bold text-rose-700 text-xs"><?php echo e($item->due_date->format('M d, Y')); ?></span>
                                    <?php else: ?>
                                        <span class="text-xs text-slate-400">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-600">In-app</span>
                                    <?php if($item->send_email): ?>
                                        <span class="mt-1 inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Email</span>
                                    <?php endif; ?>
                                    <?php if($item->send_sms): ?>
                                        <?php $smsDelivery = $item->sms_delivery ?? []; ?>
                                        <span class="mt-1 inline-flex items-center gap-1 rounded-md bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-700">SMS <?php echo e($smsDelivery['sent'] ?? 0); ?> sent, <?php echo e($smsDelivery['failed'] ?? 0); ?> failed, <?php echo e($smsDelivery['skipped'] ?? 0); ?> skipped</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="POST" action="<?php echo e(route('admin.announcements.destroy', $item)); ?>" onsubmit="return confirm('Are you sure you want to delete this notice?')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="inline-flex items-center text-xs font-semibold text-red-600 hover:text-red-800">
                                            <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'trash','class' => 'h-3.5 w-3.5 mr-1']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash','class' => 'h-3.5 w-3.5 mr-1']); ?>
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
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    <p class="font-bold text-slate-900">No announcements posted yet</p>
                                    <p class="mt-1 text-xs">Use the form on the left to send notices or monthly tuition reminders.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if($announcements->hasPages()): ?>
                <div class="border-t border-slate-200 px-5 py-4">
                    <?php echo e($announcements->links()); ?>

                </div>
            <?php endif; ?>
        </section>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const targetSelect = document.getElementById('announcement-target');
        const batchWrap = document.getElementById('target-batch-wrap');
        const userWrap = document.getElementById('target-user-wrap');

        const syncAudienceFields = () => {
            const val = targetSelect?.value;
            if (batchWrap) batchWrap.classList.toggle('hidden', val !== 'batch');
            if (userWrap) userWrap.classList.toggle('hidden', val !== 'user');
        };

        if (targetSelect) {
            targetSelect.addEventListener('change', syncAudienceFields);
            syncAudienceFields();
        }
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Announcements & Reminders | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\announcements\index.blade.php ENDPATH**/ ?>