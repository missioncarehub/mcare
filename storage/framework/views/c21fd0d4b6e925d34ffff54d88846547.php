<?php $__env->startSection('content'); ?>
    <?php
        $documentsReadyForApproval = $pendingDocumentApprovals === [];
        $releasedForReview = $application->isReleasedForReview();
    ?>

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="<?php echo e(route('admin.enrollments.show', $application)); ?>" class="secondary-action">
            Back to application
        </a>
        <span class="dashboard-pill <?php echo e($documentsReadyForApproval ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100'); ?>">
            <?php echo e($documentsReadyForApproval ? 'All verified' : count($pendingDocumentApprovals).' pending'); ?>

        </span>
    </div>

    <section class="border border-slate-200 bg-white p-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-purple-700"><?php echo e($application->last_name); ?>, <?php echo e($application->first_name); ?></p>
            <h1 class="mt-1 text-2xl font-bold text-slate-950">Review documents</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Preview each file and choose one status. A remark is required when the document is not verified.</p>
        </div>

        <?php if (! ($releasedForReview)): ?>
            <div class="mt-6 border border-amber-200 bg-amber-50 p-4" role="status">
                <p class="text-sm font-bold text-amber-950">Document decisions stay locked until this enrollment is released for review.</p>
                <p class="mt-1 text-sm leading-6 text-amber-900">You can still preview uploaded files. Saving review status or emailing a revision request becomes available after payment is verified.</p>
            </div>
        <?php endif; ?>

        <?php $__errorArgs = ['documents'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
            <div class="mt-6 border border-amber-200 bg-amber-50 p-4" role="alert">
                <p class="text-sm font-bold text-amber-950"><?php echo e($message); ?></p>
            </div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>

        <form id="document-review-form" method="POST" action="<?php echo e(route('admin.enrollments.documents.review', $application)); ?>" class="mt-6" data-document-review-form data-form-draft="admin.enrollments.documents.<?php echo e($application->id); ?>" <?php if($errors->any()): ?> data-form-draft-server-old="1" <?php endif; ?>>
            <?php echo csrf_field(); ?>
            <?php $__currentLoopData = $documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $storedReview = data_get($application->document_review, $key, []);
                    $defaultDocumentStatus = $document['path'] ? 'unreviewed' : 'missing';
                    $currentDocumentStatus = old("documents.$key.status", $storedReview['status'] ?? $defaultDocumentStatus);
                    $documentStatusOptions = [
                        'unreviewed' => 'Pending',
                        'accepted' => 'Verified',
                        'replace' => 'Needs replacement',
                        'missing' => 'Rejected',
                    ];
                ?>
                <div id="document-card-<?php echo e($key); ?>" class="grid items-start gap-4 border-t border-slate-200 py-4 md:grid-cols-[minmax(14rem,0.8fr)_minmax(18rem,1.2fr)]">
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm font-bold text-slate-900"><?php echo e($document['label']); ?></p>
                            <?php if($document['path']): ?>
                                <button type="button"
                                    class="document-preview-trigger secondary-action !px-3 !py-1.5 text-xs"
                                    data-document-key="<?php echo e($key); ?>"
                                    data-document-label="<?php echo e($document['label']); ?>"
                                    data-document-mime="<?php echo e($document['mime'] ?? ''); ?>"
                                    data-document-url="<?php echo e(route('admin.enrollments.documents.content', [$application, $key])); ?>"
                                    aria-haspopup="dialog">
                                    Preview
                                </button>
                            <?php else: ?>
                                <span class="text-xs font-bold text-red-600">Not uploaded</span>
                            <?php endif; ?>
                        </div>
                        <p class="mt-2 text-xs leading-5 text-slate-500"><?php echo e($document['path'] ? 'Private preview; access is logged.' : 'Applicant must upload this file.'); ?></p>
                    </div>
                    <div>
                        <fieldset>
                            <legend class="sr-only">Review status for <?php echo e($document['label']); ?></legend>
                            <div class="grid gap-2 sm:grid-cols-2">
                                <?php $__currentLoopData = $documentStatusOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $statusValue => $statusLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <label class="flex items-center gap-2.5 border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 has-[:checked]:border-purple-300 has-[:checked]:bg-purple-50 has-[:checked]:text-purple-800">
                                        <input
                                            type="radio"
                                            name="documents[<?php echo e($key); ?>][status]"
                                            value="<?php echo e($statusValue); ?>"
                                            class="h-4 w-4 border-slate-300 text-purple-700 focus:ring-purple-600"
                                            <?php if($currentDocumentStatus === $statusValue): echo 'checked'; endif; ?>
                                            <?php if($loop->first): echo 'required'; endif; ?>
                                        >
                                        <span><?php echo e($statusLabel); ?></span>
                                    </label>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </fieldset>
                        <label for="note-<?php echo e($key); ?>" class="mt-3 block text-xs font-bold uppercase tracking-wider text-slate-500">Feedback or problem found</label>
                        <textarea id="note-<?php echo e($key); ?>" name="documents[<?php echo e($key); ?>][note]" rows="2" maxlength="500" class="mt-2 w-full border border-slate-200 bg-white px-3 py-2 text-sm focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600" placeholder="Required for Needs replacement or Rejected. Example: Image is blurry; upload a clear copy showing all corners."><?php echo e(old("documents.$key.note", $storedReview['note'] ?? '')); ?></textarea>
                        <?php $__errorArgs = ['documents.'.$key.'.note'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <p class="mt-1 text-xs font-semibold text-red-600"><?php echo e($message); ?></p>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap gap-3">
                    <button type="submit" name="review_action" value="save" class="secondary-action" <?php if(! $releasedForReview): echo 'disabled'; endif; ?>>Save review</button>
                    <button type="submit" class="primary-action" <?php if(! $releasedForReview): echo 'disabled'; endif; ?>>Done for review</button>
                </div>
                <?php if($documentsReadyForApproval && $application->documents_reviewed_at): ?>
                    <p class="text-xs text-slate-500">Last reviewed <?php echo e($application->documents_reviewed_at->format('M d, Y g:i A')); ?> by <?php echo e($application->documentReviewer?->name ?? 'Admin'); ?></p>
                <?php elseif(! $documentsReadyForApproval): ?>
                    <p class="text-xs text-amber-700">Verify every required document before this review can be completed.</p>
                <?php endif; ?>
            </div>

            <section class="mt-6 border border-amber-200 bg-amber-50/40 p-6">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h2 class="text-sm font-bold uppercase tracking-wider text-amber-900">Request revisions from the applicant</h2>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-amber-900/90">
                            Mark each problem document as <strong>Needs replacement</strong> and add a short note. <strong>Save review</strong> keeps that choice. <strong>Email revision request</strong> saves the statuses first, then emails the applicant a link to re-upload only the flagged files.
                        </p>
                    </div>
                </div>

                <div class="mt-4 space-y-3">
                    <label for="revise-remark" class="block text-xs font-bold uppercase tracking-wider text-amber-900">Overall remark (optional)</label>
                    <textarea id="revise-remark" name="remark" rows="3" maxlength="2000" class="w-full border border-amber-200 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500" placeholder="Example: Two of your documents were unclear. Please re-upload sharp, complete scans."><?php echo e(old('remark')); ?></textarea>
                    <?php $__errorArgs = ['remark'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="text-xs font-bold text-red-700"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    <div class="flex flex-wrap items-center gap-3">
                        <button type="submit" class="primary-action" formmethod="post" formaction="<?php echo e(route('admin.enrollments.documents.request-revisions', $application)); ?>" <?php if(! $releasedForReview): echo 'disabled'; endif; ?>>Email revision request</button>
                        <p class="text-xs text-amber-900/80">The email includes a link that opens the applicant's document revision page so they can replace only the files marked Needs replacement.</p>
                    </div>
                </div>
            </section>
        </form>
    </section>

    <div id="document-preview-modal"
        class="fixed inset-0 z-[100] hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="document-preview-title"
        aria-hidden="true">
        <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-sm" data-document-modal-close></div>
        <div class="relative mx-auto flex h-full w-full max-w-6xl flex-col p-3 sm:p-6">
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-white/15 bg-slate-950 shadow-2xl">
                <header class="flex shrink-0 items-center justify-between gap-4 border-b border-white/10 px-4 py-3 text-white sm:px-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-purple-300">Private document preview</p>
                        <h2 id="document-preview-title" class="truncate text-base font-bold sm:text-lg">Document</h2>
                    </div>
                    <button type="button" data-document-modal-close class="inline-flex shrink-0 items-center justify-center rounded-full border border-white/20 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/10 focus:outline-none focus:ring-4 focus:ring-purple-300/40">
                        Close
                    </button>
                </header>
                <div class="relative min-h-0 flex-1 overflow-hidden bg-slate-900">
                    <div class="pointer-events-none absolute inset-0 z-20 grid grid-cols-2 grid-rows-4 overflow-hidden opacity-[0.12]" aria-hidden="true">
                        <?php for($i = 0; $i < 8; $i++): ?>
                            <span class="grid -rotate-12 place-items-center whitespace-nowrap text-xs font-black uppercase tracking-widest text-white sm:text-sm">ADMIN REVIEW · <?php echo e($application->email); ?> · <?php echo e(now()->format('Y-m-d H:i')); ?></span>
                        <?php endfor; ?>
                    </div>
                    <iframe id="document-preview-frame" class="relative z-10 hidden h-full min-h-[70vh] w-full bg-white" title="Document preview"></iframe>
                    <div id="document-preview-image-wrap" class="relative z-10 hidden h-full overflow-auto bg-slate-100 p-4 sm:p-8">
                        <img id="document-preview-image" src="" alt="" class="mx-auto h-auto max-w-full select-none object-contain" draggable="false">
                    </div>
                    <div id="document-preview-unavailable" class="relative z-10 hidden h-full min-h-[70vh] place-items-center bg-white p-8 text-center">
                        <p class="max-w-md font-semibold leading-6 text-slate-700">This file type cannot be previewed in the browser. Ask the applicant to upload a PDF, JPG, or PNG.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('document-preview-modal');
            if (!modal) return;

            const frame = document.getElementById('document-preview-frame');
            const imageWrap = document.getElementById('document-preview-image-wrap');
            const image = document.getElementById('document-preview-image');
            const unavailable = document.getElementById('document-preview-unavailable');
            const title = document.getElementById('document-preview-title');
            let activeTrigger = null;
            let previousScrollY = 0;

            const resetViewer = () => {
                frame.classList.add('hidden');
                imageWrap.classList.add('hidden');
                unavailable.classList.add('hidden');
                frame.removeAttribute('src');
                image.removeAttribute('src');
                image.removeAttribute('alt');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
                resetViewer();
                window.scrollTo({ top: previousScrollY, behavior: 'auto' });
                activeTrigger?.focus({ preventScroll: true });
            };

            const openModal = (trigger) => {
                activeTrigger = trigger;
                previousScrollY = window.scrollY;
                const label = trigger.dataset.documentLabel || 'Document';
                const url = trigger.dataset.documentUrl;
                const mime = trigger.dataset.documentMime || '';
                title.textContent = label;
                resetViewer();

                if (mime === 'application/pdf' || url.toLowerCase().endsWith('.pdf')) {
                    frame.src = `${url}#toolbar=0&navpanes=0&scrollbar=1&view=FitH`;
                    frame.title = `${label} preview`;
                    frame.classList.remove('hidden');
                } else if (mime.startsWith('image/')) {
                    image.src = url;
                    image.alt = `${label} preview`;
                    imageWrap.classList.remove('hidden');
                } else {
                    unavailable.classList.remove('hidden');
                    unavailable.classList.add('grid');
                }

                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
                modal.querySelector('[data-document-modal-close]')?.focus();
            };

            document.querySelectorAll('.document-preview-trigger').forEach((trigger) => {
                trigger.addEventListener('click', () => openModal(trigger));
            });
            modal.querySelectorAll('[data-document-modal-close]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Review Documents | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\enrollments\document-review.blade.php ENDPATH**/ ?>