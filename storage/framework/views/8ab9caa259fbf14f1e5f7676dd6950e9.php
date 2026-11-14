<?php $__env->startSection('content'); ?>
<?php
    $cotcType = \App\Models\OfficialDocument::TYPE_COTC;
    $torType = \App\Models\OfficialDocument::TYPE_TOR;
    $activeTab = ($activeTab ?? 'active') === 'graduates' ? 'graduates' : 'active';
    $isGraduatesTab = $activeTab === 'graduates';
    $visibleRecords = $isGraduatesTab ? $graduates : $activeTrainees;
    $tabQuery = fn (string $tab): array => array_filter([
        'tab' => $tab === 'active' ? null : $tab,
        'batch_id' => $filters['batch_id'] ?? null,
        'schedule' => $filters['schedule'] ?? null,
        'eligibility' => $filters['eligibility'] ?? null,
    ], fn ($value) => filled($value));
?>
<section class="space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
        <a href="<?php echo e(route('admin.learning.certificate-templates')); ?>" class="secondary-action">Certificate templates</a>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-dashboard-dialog-open="competency-excel-dialog" class="secondary-action inline-flex items-center gap-2"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'file-excel','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'file-excel','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Export competency Excel</button>
            <button type="button" data-dashboard-dialog-open="batch-tor-dialog" class="primary-action inline-flex items-center gap-2"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'folder-open','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'folder-open','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Prepare batch TOR ZIP</button>
        </div>
    </header>

    <form method="GET" class="dashboard-panel grid gap-4 md:grid-cols-4">
        <input type="hidden" name="tab" value="<?php echo e($activeTab); ?>">
        <div><label class="form-label">Batch</label><select name="batch_id" class="form-field"><option value="">All batches</option><?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($batch->id); ?>" <?php if((int)($filters['batch_id'] ?? 0) === $batch->id): echo 'selected'; endif; ?>><?php echo e($batch->name); ?> <?php echo e($batch->year); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div><label class="form-label">Class</label><select name="schedule" class="form-field"><option value="">AM and PM</option><option value="AM" <?php if(($filters['schedule'] ?? '') === 'AM'): echo 'selected'; endif; ?>>AM</option><option value="PM" <?php if(($filters['schedule'] ?? '') === 'PM'): echo 'selected'; endif; ?>>PM</option></select></div>
        <div><label class="form-label">Completion gate</label><select name="eligibility" class="form-field"><option value="">All states</option><option value="eligible" <?php if(($filters['eligibility'] ?? '') === 'eligible'): echo 'selected'; endif; ?>>Eligible</option><option value="blocked" <?php if(($filters['eligibility'] ?? '') === 'blocked'): echo 'selected'; endif; ?>>Blocked</option></select></div>
        <div class="flex items-end gap-2"><button class="primary-action">Apply</button><a class="secondary-action" href="<?php echo e(route('admin.learning.certificates')); ?>">Reset</a></div>
    </form>

    <nav class="lms-context-tabs" aria-label="Training record groups">
        <a href="<?php echo e(route('admin.learning.certificates', $tabQuery('active'))); ?>" class="<?php echo e($isGraduatesTab ? '' : 'is-active'); ?>" <?php if (! ($isGraduatesTab)): ?> aria-current="page" <?php endif; ?>>Active trainees (<?php echo e($activeTrainees->count()); ?>)</a>
        <a href="<?php echo e(route('admin.learning.certificates', $tabQuery('graduates'))); ?>" class="<?php echo e($isGraduatesTab ? 'is-active' : ''); ?>" <?php if($isGraduatesTab): ?> aria-current="page" <?php endif; ?>>Graduates (<?php echo e($graduates->count()); ?>)</a>
    </nav>

    <p class="text-sm leading-6 text-slate-600">Generate COTC and Generate TOR use the active certificate template. Change the title, statement, logo, or signatory from <a href="<?php echo e(route('admin.learning.certificate-templates')); ?>" class="font-bold text-purple-700 hover:text-purple-900">Certificate templates</a>. Enrollment ID and document number stay on every file.</p>

    <?php echo $__env->make('admin.learning.partials.training-record-table', [
        'showHeading' => false,
        'records' => $visibleRecords,
        'empty' => $isGraduatesTab
            ? 'No graduates match these filters.'
            : 'No active trainees match these filters.',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <?php $__currentLoopData = $visibleRecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php $__currentLoopData = [$cotcType => 'COTC', $torType => 'TOR']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => $typeLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <dialog id="reissue-<?php echo e($type); ?>-<?php echo e($record->id); ?>" data-dashboard-dialog class="m-auto w-[min(94vw,34rem)] rounded-xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/45">
                <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4"><div><h2 class="text-xl font-bold text-slate-950">Reissue <?php echo e($typeLabel); ?></h2><p class="mt-1 text-xs text-slate-500">The current version will be revoked and retained in the audit history.</p></div><button type="button" data-dashboard-dialog-close class="secondary-action" aria-label="Close">Close</button></div>
                <form method="POST" action="<?php echo e(route('admin.learning.documents.reissue', [$record, $type])); ?>" class="space-y-4 p-6"><?php echo csrf_field(); ?><label class="form-label" for="reason-<?php echo e($type); ?>-<?php echo e($record->id); ?>">Reason for reissue</label><textarea id="reason-<?php echo e($type); ?>-<?php echo e($record->id); ?>" class="form-field min-h-28" name="reason" minlength="10" maxlength="1000" required></textarea><div class="flex justify-end gap-2"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button class="primary-action">Queue new version</button></div></form>
            </dialog>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

    <section class="space-y-3">
        <div><p class="dashboard-section-kicker">Batch exports</p><h2 class="mt-2 text-xl font-bold text-slate-950">Recent TOR archives</h2></div>
        <div class="dashboard-table-wrap overflow-x-auto"><table class="dashboard-table w-full min-w-[44rem]"><thead><tr><th>Batch</th><th>Status</th><th>Progress</th><th>Expires</th><th class="text-right">File</th></tr></thead><tbody><?php $__empty_1 = true; $__currentLoopData = $exports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $export): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><tr><td><?php echo e($export->batch?->name); ?> <?php echo e($export->batch?->year); ?></td><td><?php echo e(str($export->status)->headline()); ?></td><td><?php echo e($export->processed_records); ?> / <?php echo e($export->total_records); ?></td><td><?php echo e($export->expires_at?->format('M j, Y g:i A') ?? '-'); ?></td><td class="text-right"><?php if($export->isDownloadable()): ?><a class="secondary-action" href="<?php echo e(route('admin.learning.batch-exports.download', $export)); ?>">Download ZIP</a><?php elseif($export->failure_reason): ?><span class="text-xs text-red-700">Failed</span><?php else: ?><span class="text-xs text-slate-500">Processing</span><?php endif; ?></td></tr><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="5" class="py-10 text-center text-slate-500">No batch exports requested.</td></tr><?php endif; ?></tbody></table></div>
    </section>

    <dialog id="batch-tor-dialog" data-dashboard-dialog class="m-auto w-[min(94vw,34rem)] rounded-xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/45">
        <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4"><div><h2 class="text-xl font-bold text-slate-950">Prepare batch TOR ZIP</h2><p class="mt-1 text-xs text-slate-500">The queue worker generates eligible TORs in chunks and creates one expiring archive.</p></div><button type="button" data-dashboard-dialog-close class="secondary-action" aria-label="Close">Close</button></div>
        <form method="POST" action="<?php echo e(route('admin.learning.batch-exports.store')); ?>" class="space-y-4 p-6"><?php echo csrf_field(); ?><div><label class="form-label" for="export-batch">Training batch</label><select id="export-batch" class="form-field" name="training_batch_id" required><option value="">Select batch</option><?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($batch->id); ?>"><?php echo e($batch->name); ?> <?php echo e($batch->year); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div><div class="flex justify-end gap-2"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button class="primary-action">Queue export</button></div></form>
    </dialog>

    <dialog id="competency-excel-dialog" data-dashboard-dialog class="m-auto w-[min(94vw,34rem)] rounded-xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/45">
        <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4"><div><h2 class="text-xl font-bold text-slate-950">Export competency Excel</h2><p class="mt-1 text-xs text-slate-500">Download a read-only progress matrix, achievement outcomes, and status guide.</p></div><button type="button" data-dashboard-dialog-close class="secondary-action" aria-label="Close">Close</button></div>
        <form method="GET" action="<?php echo e(route('admin.learning.competency-workbooks.download')); ?>" class="space-y-4 p-6">
            <div><label class="form-label" for="competency-export-batch">Training batch</label><select id="competency-export-batch" class="form-field" name="batch_id" required><option value="">Select batch</option><?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($batch->id); ?>" <?php if((int)($filters['batch_id'] ?? 0) === $batch->id): echo 'selected'; endif; ?>><?php echo e($batch->name); ?> <?php echo e($batch->year); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
            <div><label class="form-label" for="competency-export-schedule">Class</label><select id="competency-export-schedule" class="form-field" name="schedule"><option value="">AM and PM</option><option value="AM" <?php if(($filters['schedule'] ?? '') === 'AM'): echo 'selected'; endif; ?>>AM</option><option value="PM" <?php if(($filters['schedule'] ?? '') === 'PM'): echo 'selected'; endif; ?>>PM</option></select></div>
            <div class="flex justify-end gap-2"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button class="primary-action"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'save','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'save','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Download Excel</button></div>
        </form>
    </dialog>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Training Records | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\learning\certificates.blade.php ENDPATH**/ ?>