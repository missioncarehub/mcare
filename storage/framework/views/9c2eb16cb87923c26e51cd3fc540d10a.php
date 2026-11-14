<?php
    $cotcType = \App\Models\OfficialDocument::TYPE_COTC;
    $torType = \App\Models\OfficialDocument::TYPE_TOR;
?>

<section class="space-y-3">
    <?php if($showHeading ?? true): ?>
        <div>
            <p class="dashboard-section-kicker"><?php echo e($kicker); ?></p>
            <h2 class="mt-2 text-xl font-bold text-slate-950"><?php echo e($heading); ?> <span class="text-sm font-semibold text-slate-500">(<?php echo e($records->count()); ?>)</span></h2>
        </div>
    <?php endif; ?>

    <div class="dashboard-table-wrap overflow-x-auto">
        <table class="dashboard-table w-full min-w-[78rem]">
            <thead>
                <tr>
                    <th>Trainee</th>
                    <th>Batch / class</th>
                    <th>Status</th>
                    <th>Completion checks</th>
                    <th>Quiz scores</th>
                    <th>COTC</th>
                    <th>TOR</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $records; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $cotc = $record->officialDocuments->first(fn ($document) => $document->type === $cotcType);
                        $tor = $record->officialDocuments->first(fn ($document) => $document->type === $torType);
                        $eligibility = $record->completion_eligibility;
                        $checks = collect($eligibility['checks'] ?? []);
                        $passedCount = $checks->where('passed', true)->count();
                        $pendingChecks = $checks->where('passed', false);
                        $quizSummaries = collect($eligibility['quiz_summaries'] ?? []);
                        $quizPassed = $quizSummaries->where('result', 'Passed')->count();
                    ?>
                    <tr>
                        <td>
                            <p class="font-bold text-slate-950"><?php echo e($record->last_name); ?>, <?php echo e($record->first_name); ?> <?php echo e($record->middle_name); ?></p>
                            <?php if(filled($record->email)): ?>
                                <p class="mt-1 text-xs text-slate-500"><?php echo e($record->email); ?></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <p><?php echo e($record->batch ? $record->batch->name.' '.$record->batch->year : 'Unassigned'); ?></p>
                            <p class="mt-1 text-xs text-slate-500"><?php echo e($record->schedule_preference); ?> class</p>
                        </td>
                        <td>
                            <span class="dashboard-pill <?php echo e($eligibility['eligible'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100'); ?>"><?php echo e($eligibility['eligible'] ? 'Eligible for issuance' : 'Completion checks pending'); ?></span>
                            <?php if($record->learning_status === \App\Models\EnrollmentApplication::LEARNING_GRADUATED): ?>
                                <p class="mt-2"><span class="dashboard-pill bg-indigo-50 text-indigo-700 ring-indigo-100">Graduated</span></p>
                            <?php else: ?>
                                <p class="mt-2"><span class="dashboard-pill bg-slate-100 text-slate-700 ring-slate-200">Not graduated</span></p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <p class="font-semibold text-slate-800"><?php echo e($passedCount); ?> of <?php echo e($checks->count()); ?> passed</p>
                            <?php $__empty_2 = true; $__currentLoopData = $pendingChecks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $check): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?>
                                <p class="mt-1 text-xs text-slate-500">WAIT — <?php echo e($check['label']); ?> (<?php echo e($check['detail']); ?>)</p>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?>
                                <p class="mt-1 text-xs text-emerald-700">All completion checks passed</p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($quizSummaries->isEmpty()): ?>
                                <p class="text-xs text-slate-500">No published quizzes</p>
                            <?php else: ?>
                                <p class="text-xs font-semibold text-slate-700"><?php echo e($quizPassed); ?> of <?php echo e($quizSummaries->count()); ?> passed</p>
                                <div class="mt-2 flex flex-col items-start gap-1.5">
                                    <?php $__currentLoopData = $quizSummaries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $summary): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <button
                                            type="button"
                                            class="text-left text-sm font-bold text-purple-700 underline decoration-purple-200 underline-offset-2 hover:text-purple-900"
                                            data-dashboard-dialog-open="quiz-score-summary-dialog"
                                            data-quiz-trainee="<?php echo e(trim($record->last_name.', '.$record->first_name.' '.$record->middle_name)); ?>"
                                            data-quiz-title="<?php echo e($summary['title']); ?>"
                                            data-quiz-score="<?php echo e($summary['score']); ?>"
                                            data-quiz-percentage="<?php echo e($summary['percentage']); ?>"
                                            data-quiz-attempts="<?php echo e($summary['attempts']); ?> <?php echo e(\Illuminate\Support\Str::plural('attempt', $summary['attempts'])); ?>"
                                            data-quiz-result="<?php echo e($summary['result']); ?>"
                                            data-quiz-taken="<?php echo e($summary['taken_at']); ?>"
                                        ><?php echo e($summary['title']); ?></button>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo $__env->make('admin.learning.partials.training-record-document-actions', [
                                'record' => $record,
                                'type' => $cotcType,
                                'document' => $cotc,
                                'eligibility' => $eligibility,
                            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </td>
                        <td>
                            <?php echo $__env->make('admin.learning.partials.training-record-document-actions', [
                                'record' => $record,
                                'type' => $torType,
                                'document' => $tor,
                                'eligibility' => $eligibility,
                            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="7" class="py-14 text-center text-slate-500"><?php echo e($empty); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <dialog id="quiz-score-summary-dialog" data-dashboard-dialog class="m-auto w-[min(94vw,32rem)] rounded-xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="quiz-score-summary-title">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-wider text-purple-700" data-quiz-modal-trainee>Trainee</p>
                <h2 id="quiz-score-summary-title" class="mt-1 text-xl font-bold text-slate-950" data-quiz-modal-title>Quiz score</h2>
            </div>
            <button type="button" data-dashboard-dialog-close class="secondary-action" aria-label="Close">Close</button>
        </div>
        <dl class="grid grid-cols-2 gap-4 p-6">
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Score</dt>
                <dd class="mt-1 text-sm font-semibold text-slate-950" data-quiz-modal-score>—</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Percentage</dt>
                <dd class="mt-1 text-sm font-semibold text-slate-950" data-quiz-modal-percentage>—</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Attempts</dt>
                <dd class="mt-1 text-sm font-semibold text-slate-950" data-quiz-modal-attempts>—</dd>
            </div>
            <div>
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Result</dt>
                <dd class="mt-1 text-sm font-semibold text-slate-950" data-quiz-modal-result>—</dd>
            </div>
            <div class="col-span-2">
                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Date</dt>
                <dd class="mt-1 text-sm font-semibold text-slate-950" data-quiz-modal-taken>—</dd>
            </div>
        </dl>
    </dialog>
</section>
<script>
document.querySelectorAll('[data-quiz-title]').forEach((button) => {
    button.addEventListener('click', () => {
        const dialog = document.getElementById('quiz-score-summary-dialog');
        if (!dialog) return;
        dialog.querySelector('[data-quiz-modal-trainee]').textContent = button.dataset.quizTrainee || 'Trainee';
        dialog.querySelector('[data-quiz-modal-title]').textContent = button.dataset.quizTitle || 'Quiz score';
        dialog.querySelector('[data-quiz-modal-score]').textContent = button.dataset.quizScore || '—';
        dialog.querySelector('[data-quiz-modal-percentage]').textContent = button.dataset.quizPercentage || '—';
        dialog.querySelector('[data-quiz-modal-attempts]').textContent = button.dataset.quizAttempts || '—';
        const result = dialog.querySelector('[data-quiz-modal-result]');
        result.textContent = button.dataset.quizResult || '—';
        result.className = 'mt-1 text-sm font-semibold ' + (button.dataset.quizResult === 'Passed'
            ? 'text-emerald-700'
            : (button.dataset.quizResult === 'Failed' ? 'text-red-700' : 'text-slate-950'));
        dialog.querySelector('[data-quiz-modal-taken]').textContent = button.dataset.quizTaken || '—';
    });
});
</script>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\learning\partials\training-record-table.blade.php ENDPATH**/ ?>