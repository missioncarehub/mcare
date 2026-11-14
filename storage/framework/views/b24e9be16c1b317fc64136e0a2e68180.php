<?php $__env->startSection('content'); ?>
<?php
    $isPdf = $mimeType === 'application/pdf';
    $isImage = str_starts_with($mimeType, 'image/');
    $contentUrl = route('admin.enrollments.documents.content', [$application, $document]);
    $watermark = 'ADMIN REVIEW - '.$application->email.' - '.now()->format('Y-m-d H:i');
?>
<div class="mx-auto max-w-7xl space-y-5">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <p class="text-sm text-slate-600"><?php echo e($application->first_name); ?> <?php echo e($application->last_name); ?> · <?php echo e($application->email); ?></p>
        <a href="<?php echo e(route('admin.enrollments.document-review', $application)); ?>" class="inline-flex items-center justify-center rounded-full border border-purple-200 bg-white px-5 py-3 text-sm font-bold text-purple-700">Back to document review</a>
    </header>
    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900">Preview only. The private source file remains behind admin authentication and every view is recorded.</div>
    <section class="relative min-h-[75vh] overflow-hidden rounded-xl border border-slate-200 bg-slate-950">
        <div class="pointer-events-none absolute inset-0 z-20 grid grid-cols-2 grid-rows-4 overflow-hidden opacity-[0.12]" aria-hidden="true"><?php for($i = 0; $i < 8; $i++): ?><span class="grid -rotate-12 place-items-center whitespace-nowrap text-sm font-black uppercase tracking-widest text-white"><?php echo e($watermark); ?></span><?php endfor; ?></div>
        <?php if($isPdf): ?>
            <iframe class="relative z-10 h-[82vh] min-h-[720px] w-full bg-white" src="<?php echo e($contentUrl); ?>#toolbar=0&navpanes=0&scrollbar=1&view=FitH" title="<?php echo e($label); ?> preview"></iframe>
        <?php elseif($isImage): ?>
            <div class="relative z-10 flex min-h-[75vh] items-start justify-center overflow-auto p-5"><img src="<?php echo e($contentUrl); ?>" alt="<?php echo e($label); ?>" class="h-auto max-w-full select-none object-contain" draggable="false"></div>
        <?php else: ?>
            <div class="relative z-10 grid min-h-[75vh] place-items-center bg-white p-8 text-center"><p class="font-bold text-slate-700">This file type cannot be previewed by the browser. Ask the applicant to upload PDF, JPG, or PNG.</p></div>
        <?php endif; ?>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => $label.' Preview | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\enrollments\document-preview.blade.php ENDPATH**/ ?>