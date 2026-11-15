<?php
    $toastItems = [];

    $pushToast = function (string $type, string|array $message, int $duration) use (&$toastItems): void {
        $messages = is_array($message)
            ? $message
            : [trim($message)];
        $messages = array_values(array_unique(array_filter(
            array_map(fn ($item) => trim((string) $item), $messages),
            fn ($item) => $item !== '',
        )));

        if ($messages === []) {
            return;
        }

        $toastItems[] = [
            'type' => $type,
            'messages' => $messages,
            'duration' => $duration,
        ];
    };

    if (session('saved')) {
        $pushToast('success', (string) session('saved'), 5000);
    }

    if (session('status')) {
        $pushToast('success', (string) session('status'), 5000);
    }

    if (session('verification_notice')) {
        $pushToast('info', (string) session('verification_notice'), 7000);
    }

    $errorMessages = collect($errors->getBags())
        ->flatMap(fn ($bag) => $bag->all())
        ->map(fn ($message) => trim((string) $message))
        ->filter()
        ->unique()
        ->values()
        ->all();

    $sessionError = trim((string) (session('error') ?? session('alert') ?? ''));

    if ($sessionError !== '' && ! in_array($sessionError, $errorMessages, true)) {
        $errorMessages[] = $sessionError;
    }

    if ($errorMessages !== []) {
        $pushToast('error', $errorMessages, 8000);
    }

    $toastIcons = [
        'success' => 'circle-check',
        'error' => 'circle-minus',
        'info' => 'bell',
    ];
?>

<?php if($toastItems !== []): ?>
    <div
        class="dashboard-toast-region"
        data-dashboard-toast-region
        aria-live="polite"
        aria-relevant="additions"
    >
        <?php $__currentLoopData = $toastItems; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $toast): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div
                class="dashboard-toast dashboard-toast-<?php echo e($toast['type']); ?>"
                data-dashboard-toast
                data-auto-dismiss="<?php echo e($toast['duration']); ?>"
                role="<?php echo e($toast['type'] === 'error' ? 'alert' : 'status'); ?>"
            >
                <span class="dashboard-toast-icon" aria-hidden="true">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => $toastIcons[$toast['type']] ?? 'bell','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($toastIcons[$toast['type']] ?? 'bell'),'class' => 'h-4 w-4']); ?>
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
                </span>
                <div class="dashboard-toast-body">
                    <?php if(count($toast['messages']) === 1): ?>
                        <p><?php echo e($toast['messages'][0]); ?></p>
                    <?php else: ?>
                        <ul>
                            <?php $__currentLoopData = $toast['messages']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($message); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    <?php endif; ?>
                </div>
                <button type="button" class="dashboard-toast-dismiss" data-dashboard-toast-dismiss aria-label="Dismiss notification">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark','class' => 'h-3.5 w-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark','class' => 'h-3.5 w-3.5']); ?>
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
                </button>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views/components/dashboard-toasts.blade.php ENDPATH**/ ?>