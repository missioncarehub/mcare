<?php
    $layout = \App\Support\AccountPortal::dashboardLayoutFor($user);
    $canContactAdmin = $canContactAdmin ?? ($user->role !== 'admin');
    $contactTopics = $contactTopics ?? \App\Models\AdminContactMessage::topics();
    $contactMessages = $contactMessages ?? collect();
    $contactHasErrors = $errors->contactAdmin->any();
?>



<?php $__env->startSection('content'); ?>
<section class="space-y-6">
    <header class="border-b border-slate-200 pb-6">
        <p class="dashboard-section-kicker">Role-aware assistance</p>
        <h1 class="dashboard-section-title mt-2 text-3xl">Help for <?php echo e($roleLabel); ?></h1>
        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Quick guidance for the actions available in your current portal.</p>
    </header>

    <div class="grid gap-5 md:grid-cols-3">
        <?php $__currentLoopData = $topics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$topicTitle, $description]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <section class="dashboard-panel space-y-3">
                <span class="grid h-10 w-10 place-items-center rounded-xl bg-purple-50 text-purple-700"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'circle-check','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'circle-check','class' => 'h-5 w-5']); ?>
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
                <h2 class="text-lg font-bold text-slate-950"><?php echo e($topicTitle); ?></h2>
                <p class="text-sm leading-6 text-slate-600"><?php echo e($description); ?></p>
            </section>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <section class="dashboard-panel flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-950">Still need help?</h2>
            <p class="mt-2 text-sm text-slate-600">
                <?php if($canContactAdmin): ?>
                    Send a message to MCARE administration and include your account email: <?php echo e($user->email); ?>

                <?php else: ?>
                    Review messages sent from trainee, trainer, and alumni accounts. Your account email: <?php echo e($user->email); ?>

                <?php endif; ?>
            </p>
        </div>
        <?php if($canContactAdmin): ?>
            <button type="button" class="primary-action shrink-0" data-dashboard-dialog-open="contact-admin-dialog">Contact admin</button>
        <?php else: ?>
            <a href="<?php echo e(route('admin.contact-messages.index')); ?>" class="primary-action shrink-0">Review messages</a>
        <?php endif; ?>
    </section>

    <?php if($canContactAdmin && $contactMessages->isNotEmpty()): ?>
        <section class="dashboard-panel space-y-4" aria-labelledby="contact-history-title">
            <header class="border-b border-slate-100 pb-4">
                <p class="dashboard-section-kicker">Your inbox</p>
                <h2 id="contact-history-title" class="mt-1 text-lg font-bold text-slate-950">Messages to administration</h2>
            </header>
            <div class="space-y-3">
                <?php $__currentLoopData = $contactMessages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <article class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <h3 class="font-bold text-slate-950"><?php echo e($contact->subject); ?></h3>
                                <p class="mt-1 text-xs font-semibold text-slate-500"><?php echo e($contact->topicLabel()); ?> · <?php echo e($contact->created_at?->format('M d, Y g:i A')); ?></p>
                            </div>
                            <span class="inline-flex w-fit rounded-full px-2.5 py-1 text-xs font-bold <?php echo e($contact->isPending() ? 'bg-amber-50 text-amber-700' : ($contact->status === \App\Models\AdminContactMessage::STATUS_CLOSED ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700')); ?>"><?php echo e($contact->statusLabel()); ?></span>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700"><?php echo e($contact->message); ?></p>
                        <?php if(filled($contact->admin_reply)): ?>
                            <div class="mt-3 rounded-lg border border-purple-100 bg-white p-3">
                                <p class="text-xs font-bold uppercase tracking-wide text-purple-700">Admin reply</p>
                                <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700"><?php echo e($contact->admin_reply); ?></p>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </section>
    <?php endif; ?>
</section>

<?php if($canContactAdmin): ?>
    <dialog id="contact-admin-dialog" data-dashboard-dialog data-auto-open="<?php echo e($contactHasErrors ? 'true' : 'false'); ?>" class="m-auto max-h-[92vh] w-[min(96vw,36rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="contact-admin-title">
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div>
                <p class="dashboard-section-kicker">Help desk</p>
                <h2 id="contact-admin-title" class="mt-1 text-xl font-bold text-slate-950">Contact admin</h2>
                <p class="mt-1 text-sm text-slate-500">Administration will see your name and <?php echo e($user->email); ?>.</p>
            </div>
            <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Close contact admin"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
        <form method="POST" action="<?php echo e(route('account.contact.store')); ?>" class="grid gap-4 p-6" data-dashboard-dialog-form data-submit-label="Sending message...">
            <?php echo csrf_field(); ?>
            <?php if($contactHasErrors): ?>
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm font-medium text-rose-800" role="alert"><?php echo e($errors->contactAdmin->first()); ?></div>
            <?php endif; ?>
            <div>
                <label for="contact-topic" class="form-label">Topic</label>
                <select id="contact-topic" name="topic" required class="form-field">
                    <?php $__currentLoopData = $contactTopics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if(old('topic') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>
            <div>
                <label for="contact-subject" class="form-label">Subject</label>
                <input id="contact-subject" name="subject" type="text" required maxlength="160" value="<?php echo e(old('subject')); ?>" class="form-field" placeholder="Short summary of your request">
            </div>
            <div>
                <label for="contact-message" class="form-label">Message</label>
                <textarea id="contact-message" name="message" rows="5" required maxlength="2000" class="form-field" placeholder="Describe what you need help with."><?php echo e(old('message')); ?></textarea>
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                <button type="submit" data-action-button class="primary-action">Send message</button>
            </div>
        </form>
    </dialog>
<?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make($layout, ['title' => $roleLabel.' Help | MCARE'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\account\help.blade.php ENDPATH**/ ?>