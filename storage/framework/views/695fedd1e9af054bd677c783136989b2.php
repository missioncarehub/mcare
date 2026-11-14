<?php
    use Illuminate\Support\Str;

    $formatKey = function ($key) {
        return Str::of((string) $key)->replace(['_', '-'], ' ')->title()->toString();
    };

    $formatValue = function ($value) {
        if (is_null($value) || $value === '') {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }
        if (is_array($value)) {
            if (empty($value)) {
                return '—';
            }
            if (array_is_list($value)) {
                return implode(', ', array_map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v), $value));
            }
            return null;
        }
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}/', $value)) {
            try {
                return \Illuminate\Support\Carbon::parse($value)->format('M d, Y g:i A');
            } catch (\Throwable $e) {
                return $value;
            }
        }
        return (string) $value;
    };

    $meta = $meta ?? [];
    $hasBeforeAfter = is_array($meta)
        && array_key_exists('before', $meta)
        && array_key_exists('after', $meta)
        && is_array($meta['before'])
        && is_array($meta['after']);

    $changes = [];
    if ($hasBeforeAfter) {
        $allKeys = array_unique(array_merge(array_keys($meta['before']), array_keys($meta['after'])));
        foreach ($allKeys as $k) {
            $b = $meta['before'][$k] ?? null;
            $a = $meta['after'][$k] ?? null;
            if ($b !== $a) {
                $changes[$k] = ['before' => $b, 'after' => $a];
            }
        }
    }
?>

<?php if($hasBeforeAfter): ?>
    <?php if(empty($changes)): ?>
        <em>No field changes recorded.</em>
    <?php else: ?>
        <?php $__currentLoopData = $changes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $change): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="section">
                <div class="section-title"><?php echo e($formatKey($key)); ?></div>
                <div class="row"><span class="k">From:</span><span class="v from"><?php echo e($formatValue($change['before']) ?? '(nested)'); ?></span></div>
                <div class="row"><span class="k">To:</span><span class="v to"><?php echo e($formatValue($change['after']) ?? '(nested)'); ?></span></div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
<?php else: ?>
    <?php $__currentLoopData = $meta; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $formattedValue = $formatValue($value); ?>
        <?php if(is_array($value) && $formattedValue === null): ?>
            <div class="section">
                <div class="section-title"><?php echo e($formatKey($key)); ?></div>
                <?php $__currentLoopData = $value; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subKey => $subValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="row">
                        <span class="k"><?php echo e($formatKey($subKey)); ?>:</span>
                        <span class="v"><?php echo e($formatValue($subValue) ?? '(nested)'); ?></span>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        <?php else: ?>
            <div class="row">
                <span class="k"><?php echo e($formatKey($key)); ?>:</span>
                <span class="v"><?php echo e($formattedValue); ?></span>
            </div>
        <?php endif; ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php endif; ?>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\logs\partials\meta-details-print.blade.php ENDPATH**/ ?>