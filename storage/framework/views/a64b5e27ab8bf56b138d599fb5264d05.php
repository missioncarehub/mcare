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
            return null; // nested associative array, handled separately
        }
        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}[T\s]\d{2}:\d{2}/', $value)) {
            try {
                return \Illuminate\Support\Carbon::parse($value)->format('M d, Y g:i A');
            } catch (\Throwable $e) {
                return $value;
            }
        }
        if (is_numeric($value) && is_string($value) && strlen($value) === 10 && ctype_digit($value)) {
            try {
                return \Illuminate\Support\Carbon::createFromTimestamp((int) $value)->format('M d, Y g:i A');
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
        <p class="italic text-slate-500">No field changes recorded.</p>
    <?php else: ?>
        <div class="max-w-md space-y-1.5">
            <p class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Changed fields (<?php echo e(count($changes)); ?>)</p>
            <?php $__currentLoopData = $changes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $change): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-purple-700"><?php echo e($formatKey($key)); ?></p>
                    <div class="mt-1 space-y-0.5 text-xs">
                        <div class="flex gap-2">
                            <span class="w-10 shrink-0 text-slate-400">From:</span>
                            <span class="font-semibold text-rose-700 break-all"><?php echo e($formatValue($change['before']) ?? '(nested value)'); ?></span>
                        </div>
                        <div class="flex gap-2">
                            <span class="w-10 shrink-0 text-slate-400">To:</span>
                            <span class="font-semibold text-emerald-700 break-all"><?php echo e($formatValue($change['after']) ?? '(nested value)'); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    <?php endif; ?>
<?php else: ?>
    <dl class="max-w-md space-y-1 text-xs">
        <?php $__currentLoopData = $meta; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php $formattedValue = $formatValue($value); ?>
            <?php if(is_array($value) && $formattedValue === null): ?>
                <div class="rounded-lg border border-slate-200 bg-white px-2.5 py-1.5">
                    <dt class="text-[11px] font-bold uppercase tracking-wide text-slate-500"><?php echo e($formatKey($key)); ?></dt>
                    <dd class="mt-1 space-y-0.5">
                        <?php $__currentLoopData = $value; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $subKey => $subValue): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex gap-2">
                                <span class="min-w-[8rem] font-semibold text-slate-700"><?php echo e($formatKey($subKey)); ?>:</span>
                                <span class="text-slate-600 break-all"><?php echo e($formatValue($subValue) ?? '(nested)'); ?></span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </dd>
                </div>
            <?php else: ?>
                <div class="flex gap-2">
                    <dt class="min-w-[8rem] font-semibold text-slate-700"><?php echo e($formatKey($key)); ?>:</dt>
                    <dd class="text-slate-600 break-all"><?php echo e($formattedValue); ?></dd>
                </div>
            <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </dl>
<?php endif; ?>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\logs\partials\meta-details.blade.php ENDPATH**/ ?>