<?php $__env->startSection('content'); ?>
<section class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <p class="max-w-3xl text-sm leading-6 text-slate-600">
            Review help-desk messages from trainees, trainers, and alumni. Reply here so the sender can see the update on their Help page.
        </p>
        <span class="text-sm font-semibold text-slate-500"><?php echo e($pendingCount); ?> pending</span>
    </header>

    <section class="dashboard-panel" aria-labelledby="contact-messages-title">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="dashboard-section-kicker">Help desk</p>
                <h2 id="contact-messages-title" class="dashboard-section-title text-xl">Contact admin</h2>
            </div>
            <form method="GET" action="<?php echo e(route('admin.contact-messages.index')); ?>" class="flex flex-wrap items-center gap-2">
                <label for="contact-status-filter" class="sr-only">Filter by status</label>
                <select id="contact-status-filter" name="status" class="form-field w-auto min-w-[10rem]" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if($currentStatus === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </form>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-3 font-bold">Sender</th>
                        <th class="px-3 py-3 font-bold">Topic</th>
                        <th class="px-3 py-3 font-bold">Subject</th>
                        <th class="px-3 py-3 font-bold">Status</th>
                        <th class="px-3 py-3 font-bold">Submitted</th>
                        <th class="px-3 py-3 font-bold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $__empty_1 = true; $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-3 py-4">
                                <p class="font-bold text-slate-900"><?php echo e($message->user?->name ?? 'Removed account'); ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?php echo e($message->user?->email); ?></p>
                            </td>
                            <td class="px-3 py-4 font-semibold text-slate-800"><?php echo e($message->topicLabel()); ?></td>
                            <td class="px-3 py-4 text-slate-700"><?php echo e($message->subject); ?></td>
                            <td class="px-3 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold <?php echo e($message->isPending() ? 'bg-amber-50 text-amber-700' : ($message->status === \App\Models\AdminContactMessage::STATUS_CLOSED ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700')); ?>"><?php echo e($message->statusLabel()); ?></span>
                            </td>
                            <td class="px-3 py-4 text-slate-500"><?php echo e($message->created_at?->format('M d, Y g:i A')); ?></td>
                            <td class="px-3 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button type="button" data-dashboard-dialog-open="contact-message-<?php echo e($message->id); ?>" class="secondary-action inline-flex items-center gap-2"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'pencil','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'pencil','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Review</button>
                                    <form method="POST" action="<?php echo e(route('admin.contact-messages.destroy', $message)); ?>" data-confirm="Remove this contact message?">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="secondary-action border-red-200 text-red-700 hover:bg-red-50">Remove</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr><td colspan="6" class="px-3 py-10 text-center text-slate-500">No contact messages yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php $__currentLoopData = $messages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <dialog id="contact-message-<?php echo e($message->id); ?>" data-dashboard-dialog class="m-auto max-h-[90vh] w-[min(96vw,36rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="contact-message-title-<?php echo e($message->id); ?>">
                <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
                    <div>
                        <h2 id="contact-message-title-<?php echo e($message->id); ?>" class="font-display text-xl font-bold text-slate-900">Contact review</h2>
                        <p class="mt-1 text-xs text-slate-500"><?php echo e($message->topicLabel()); ?></p>
                    </div>
                    <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Close contact review"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
<?php endif; ?></button>
                </div>
                <form method="POST" action="<?php echo e(route('admin.contact-messages.update', $message)); ?>" class="grid gap-4 p-6" data-dashboard-dialog-form data-submit-label="Saving reply...">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                        <p><span class="font-bold text-slate-900">Sender:</span> <?php echo e($message->user?->name ?? 'Removed account'); ?></p>
                        <p class="mt-1"><span class="font-bold text-slate-900">Email:</span> <?php echo e($message->user?->email); ?></p>
                        <p class="mt-1"><span class="font-bold text-slate-900">Subject:</span> <?php echo e($message->subject); ?></p>
                        <p class="mt-3 whitespace-pre-line"><?php echo e($message->message); ?></p>
                    </div>
                    <div>
                        <label for="contact-status-<?php echo e($message->id); ?>" class="form-label">Status</label>
                        <select id="contact-status-<?php echo e($message->id); ?>" name="status" required class="form-field">
                            <?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if($message->status === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div>
                        <label for="contact-reply-<?php echo e($message->id); ?>" class="form-label">Reply to sender <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea id="contact-reply-<?php echo e($message->id); ?>" name="admin_reply" rows="4" maxlength="2000" class="form-field" placeholder="This reply appears on the sender's Help page."><?php echo e($message->admin_reply); ?></textarea>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                        <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                        <button type="submit" data-action-button class="primary-action">Save review</button>
                    </div>
                </form>
            </dialog>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php if($messages->hasPages()): ?>
            <div class="mt-5 border-t border-slate-100 pt-5"><?php echo e($messages->links()); ?></div>
        <?php endif; ?>
    </section>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Contact admin | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\contact-messages\index.blade.php ENDPATH**/ ?>