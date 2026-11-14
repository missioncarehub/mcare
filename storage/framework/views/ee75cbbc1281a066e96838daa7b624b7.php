<?php
    $notificationLayout = match (auth()->user()?->role) {
        'admin' => 'admin.layouts.app',
        'trainer' => 'trainer.layouts.app',
        'trainee' => 'trainee.layouts.app',
        'alumni' => 'alumni.layouts.app',
        default => 'trainee.layouts.app',
    };
?>



<?php $__env->startSection('content'); ?>
<section class="space-y-6">
    <header class="flex flex-col gap-3 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <?php if (! (auth()->user()?->role === 'admin')): ?>
            <div><p class="dashboard-section-kicker">Account updates</p><h1 class="mt-2 dashboard-section-title text-2xl sm:text-3xl">Notification center</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Review classroom, career, and system updates connected to this account.</p></div>
        <?php else: ?>
            <p class="max-w-2xl text-sm leading-6 text-slate-600">Review classroom, career, and system updates connected to this account.</p>
        <?php endif; ?>
        <?php if($notifications->contains(fn ($notification) => $notification->read_at === null)): ?>
            <form method="POST" action="<?php echo e(route('notifications.read-all')); ?>"><input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>"><button type="submit" class="secondary-action inline-flex items-center justify-center gap-2"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'circle-check','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'circle-check','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Mark all as read</button></form>
        <?php endif; ?>
    </header>

    <section class="dashboard-panel divide-y divide-slate-100 p-0" aria-label="Notifications">
        <?php $__empty_1 = true; $__currentLoopData = $notifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $notification): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $notificationData = is_array($notification->data) ? $notification->data : [];
                $notificationUrl = $notificationData['url'] ?? route('notifications.index');
            ?>
            <article class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between <?php echo e($notification->read_at ? 'bg-white' : 'bg-purple-50/40'); ?>">
                <div class="flex min-w-0 gap-4"><span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-100 text-purple-700"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => $notificationData['icon'] ?? 'bell','class' => 'h-5 w-5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($notificationData['icon'] ?? 'bell'),'class' => 'h-5 w-5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></span><div class="min-w-0"><h2 class="font-bold text-slate-950"><?php echo e($notificationData['title'] ?? 'MCARE update'); ?></h2><p class="mt-1 text-sm leading-6 text-slate-600"><?php echo e($notificationData['message'] ?? 'Open the linked workspace for more details.'); ?></p><p class="mt-2 text-xs font-semibold text-slate-400"><?php echo e($notification->created_at?->format('M d, Y g:i A') ?? 'Recently'); ?></p></div></div>
                <div class="flex w-full shrink-0 flex-col items-stretch gap-2 sm:w-auto sm:flex-row sm:items-center">
                    <?php if($notificationUrl): ?><a href="<?php echo e($notificationUrl); ?>" class="secondary-action inline-flex items-center justify-center text-sm">Open</a><?php endif; ?>
                    <?php if(! $notification->read_at): ?>
                        <form method="POST" action="<?php echo e(route('notifications.read', $notification)); ?>" class="w-full sm:w-auto"><input type="hidden" name="_token" value="<?php echo e(csrf_token()); ?>"><input type="hidden" name="_method" value="PATCH"><button type="submit" class="primary-action w-full text-sm sm:w-auto">Mark read</button></form>
                    <?php else: ?>
                        <span class="text-xs font-bold text-emerald-700">Read</span>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="py-16 text-center"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'bell','class' => 'mx-auto h-9 w-9 text-slate-300']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'bell','class' => 'mx-auto h-9 w-9 text-slate-300']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?><h2 class="mt-4 text-lg font-bold text-slate-900">You are all caught up</h2><p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">New MCARE updates will appear in this notification center.</p></div>
        <?php endif; ?>
    </section>

    <?php if($notifications->hasPages()): ?><div class="dashboard-panel py-4"><?php echo e($notifications->links()); ?></div><?php endif; ?>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make($notificationLayout, ['title' => 'Notifications | MCARE'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\notifications\index.blade.php ENDPATH**/ ?>