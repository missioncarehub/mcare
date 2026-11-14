<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCARE Admin Log Report - <?php echo e($rangeLabel); ?></title>
    <?php if (isset($component)) { $__componentOriginald9e77967a5438b63fd29d241808e49d9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald9e77967a5438b63fd29d241808e49d9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.site-favicon','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('site-favicon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald9e77967a5438b63fd29d241808e49d9)): ?>
<?php $attributes = $__attributesOriginald9e77967a5438b63fd29d241808e49d9; ?>
<?php unset($__attributesOriginald9e77967a5438b63fd29d241808e49d9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald9e77967a5438b63fd29d241808e49d9)): ?>
<?php $component = $__componentOriginald9e77967a5438b63fd29d241808e49d9; ?>
<?php unset($__componentOriginald9e77967a5438b63fd29d241808e49d9); ?>
<?php endif; ?>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; padding: 28px; color: #172033; font: 12px/1.45 Arial, sans-serif; }
        .toolbar { display: flex; justify-content: flex-end; gap: 8px; margin-bottom: 20px; }
        .toolbar button { border: 0; border-radius: 8px; padding: 10px 16px; background: #6d28d9; color: #fff; font-weight: 700; cursor: pointer; }
        header { display: flex; justify-content: space-between; gap: 24px; padding-bottom: 16px; border-bottom: 2px solid #6d28d9; }
        h1 { margin: 3px 0 0; font-size: 24px; }
        .meta { color: #5b6475; text-align: right; }
        table { width: 100%; margin-top: 20px; border-collapse: collapse; }
        th, td { border: 1px solid #cfd5df; padding: 8px; text-align: left; vertical-align: top; }
        th { background: #eee9f8; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; }
        .details { max-width: 320px; overflow-wrap: anywhere; font: 10px/1.5 Arial, sans-serif; }
        .details .row { display: flex; gap: 6px; margin-bottom: 2px; }
        .details .row .k { font-weight: 700; color: #3f3355; min-width: 96px; }
        .details .row .v { color: #4b5563; }
        .details .section { margin-bottom: 6px; padding: 4px 6px; border: 1px solid #d5cee2; border-radius: 4px; background: #fbfaff; }
        .details .section-title { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #6d28d9; margin-bottom: 3px; }
        .details .from { color: #b91c1c; font-weight: 600; }
        .details .to { color: #047857; font-weight: 600; }
        footer { margin-top: 18px; color: #697386; font-size: 10px; }
        @page { size: landscape; margin: 12mm; }
        @media print {
            body { padding: 0; }
            .toolbar { display: none; }
            thead { display: table-header-group; }
            tr { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="toolbar"><button type="button" onclick="window.print()">Print this report</button></div>
    <header>
        <div>
            <strong>MISSION CARE TRAINING CENTER</strong>
            <h1>Admin Activity Log</h1>
        </div>
        <div class="meta">
            <div><strong>Coverage:</strong> <?php echo e($rangeLabel); ?></div>
            <div><strong>Generated:</strong> <?php echo e(now()->format('M d, Y g:i A')); ?></div>
            <div><strong>Records:</strong> <?php echo e($logs->count()); ?></div>
        </div>
    </header>
    <table>
        <thead>
            <tr><th>Date and time</th><th>Account</th><th>Action</th><th>IP</th><th>Subject</th><th>Details</th></tr>
        </thead>
        <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $logs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($log->created_at->format('M d, Y g:i A')); ?></td>
                    <td><strong><?php echo e($log->user?->name ?? 'System / unknown'); ?></strong><br><?php echo e($log->user?->email); ?></td>
                    <td><?php echo e($log->action); ?></td>
                    <td><?php echo e($log->ip_address ?? 'Unknown'); ?></td>
                    <td><?php echo e(class_basename($log->subject_type ?? '')); ?> <?php echo e($log->subject_id); ?></td>
                    <td class="details">
                        <?php if($log->meta): ?>
                            <?php echo $__env->make('admin.logs.partials.meta-details-print', ['meta' => $log->meta], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        <?php else: ?>
                            <em>No extra details</em>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="6">No activity was recorded for this report.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <footer>This system-generated report is intended for authorized MCARE administrative review.</footer>
</body>
</html>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\logs\print.blade.php ENDPATH**/ ?>