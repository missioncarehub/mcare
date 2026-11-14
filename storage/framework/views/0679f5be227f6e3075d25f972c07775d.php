<?php
    $label = $type === \App\Models\OfficialDocument::TYPE_COTC ? 'COTC' : 'TOR';
    $isCotc = $type === \App\Models\OfficialDocument::TYPE_COTC;
    $isTor = $type === \App\Models\OfficialDocument::TYPE_TOR;
    $checksComplete = (bool) ($eligibility['eligible'] ?? false);
    $isGraduated = (bool) ($eligibility['graduated'] ?? false)
        || $record->learning_status === \App\Models\EnrollmentApplication::LEARNING_GRADUATED;
    $canIssueTor = (bool) ($eligibility['can_issue_tor'] ?? ($isGraduated && $checksComplete));
    $canAct = $isTor ? $canIssueTor : $checksComplete;
    $torBlockers = [];
    if ($isTor && ! $canIssueTor) {
        if (! $isGraduated) {
            $torBlockers[] = 'Graduate the trainee first';
        }
        if (! $checksComplete) {
            $torBlockers[] = 'Finish all completion checks';
        }
    }
    $textAction = 'training-record-text-action';
?>

<div class="training-record-actions">
    <?php if($document): ?>
        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500"><?php echo e(str($document->status)->headline()); ?></span>
    <?php endif; ?>

    <?php if($isTor && ! $canIssueTor): ?>
        <p class="max-w-[14rem] text-[11px] font-medium leading-4 text-amber-800">
            <?php echo e(implode(' · ', $torBlockers)); ?>.
        </p>
    <?php endif; ?>

    <?php if(!$document): ?>
        <form method="POST" action="<?php echo e(route('admin.learning.documents.generate', [$record, $type])); ?>">
            <?php echo csrf_field(); ?>
            <button class="<?php echo e($textAction); ?>" <?php if(! $canAct): echo 'disabled'; endif; ?> title="<?php echo e($isTor && ! $canAct ? implode('. ', $torBlockers) : ''); ?>">Generate <?php echo e($label); ?></button>
        </form>
    <?php elseif($document->status === 'queued'): ?>
        <form method="POST" action="<?php echo e(route('admin.learning.documents.generate', [$record, $type])); ?>">
            <?php echo csrf_field(); ?>
            <button class="<?php echo e($textAction); ?>" <?php if(! $canAct): echo 'disabled'; endif; ?>>Generate <?php echo e($label); ?> now</button>
        </form>
    <?php elseif($isCotc && $document->status === 'generated'): ?>
        <a class="<?php echo e($textAction); ?>" href="<?php echo e(route('admin.learning.documents.preview', $document)); ?>">Review PDF</a>
        <form method="POST" action="<?php echo e(route('admin.learning.documents.release', $document)); ?>">
            <?php echo csrf_field(); ?>
            <?php echo method_field('PATCH'); ?>
            <button class="<?php echo e($textAction); ?>">Release to trainee</button>
        </form>
    <?php elseif($isCotc && in_array($document->status, ['released', 'downloaded'])): ?>
        <a class="<?php echo e($textAction); ?>" href="<?php echo e(route('admin.learning.documents.download', $document)); ?>">Admin copy</a>
        <button type="button" data-dashboard-dialog-open="reissue-<?php echo e($type); ?>-<?php echo e($record->id); ?>" class="<?php echo e($textAction); ?>">Reissue</button>
    <?php elseif($isTor && in_array($document->status, ['generated', 'released', 'downloaded'])): ?>
        <?php if($canIssueTor): ?>
            <a class="<?php echo e($textAction); ?>" href="<?php echo e(route('admin.learning.documents.preview', $document)); ?>">Preview</a>
            <a class="<?php echo e($textAction); ?>" href="<?php echo e(route('admin.learning.documents.download', $document)); ?>">Download</a>
            <button type="button" data-dashboard-dialog-open="reissue-<?php echo e($type); ?>-<?php echo e($record->id); ?>" class="<?php echo e($textAction); ?>">Reissue</button>
        <?php else: ?>
            <button type="button" class="<?php echo e($textAction); ?>" disabled>Preview</button>
            <button type="button" class="<?php echo e($textAction); ?>" disabled>Download</button>
        <?php endif; ?>
    <?php elseif($document->status === 'failed'): ?>
        <?php if($canAct): ?>
            <button type="button" data-dashboard-dialog-open="reissue-<?php echo e($type); ?>-<?php echo e($record->id); ?>" class="<?php echo e($textAction); ?>">Retry as new version</button>
        <?php else: ?>
            <button type="button" class="<?php echo e($textAction); ?>" disabled>Retry as new version</button>
        <?php endif; ?>
    <?php else: ?>
        <span class="text-sm text-slate-500">Queue status: <?php echo e(str($document->status)->headline()); ?></span>
    <?php endif; ?>
</div>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\learning\partials\training-record-document-actions.blade.php ENDPATH**/ ?>