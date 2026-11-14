<?php $__env->startSection('content'); ?>
<?php
    $classSummary = $classSummary ?? ['average' => null, 'highest' => null, 'lowest' => null, 'passed' => 0];
    $audienceLabel = $quiz->targetTrainee
        ? trim($quiz->targetTrainee->first_name.' '.$quiz->targetTrainee->last_name)
        : ($quiz->batch ? $quiz->batch->name.' '.$quiz->batch->year : 'Assigned class');
?>

<div class="lms-page" data-quiz-results>
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Quiz results</p>
            <h1><?php echo e($quiz->title); ?></h1>
            <p><?php echo e($audienceLabel); ?> - <?php echo e($quiz->questions->count()); ?> questions - Passing score <?php echo e(number_format((float) $quiz->passing_score_percent)); ?>%</p>
        </div>
        <div class="lms-header-actions">
            <a href="<?php echo e(route('trainer.quizzes.edit', $quiz)); ?>" class="secondary-action">Edit quiz</a>
            <a href="<?php echo e($quiz->training_module_id ? route('trainer.modules.show', ['module' => $quiz->training_module_id, 'tab' => 'assessments']).'#assessments' : route('trainer.resources')); ?>" class="secondary-action">Back to module</a>
        </div>
    </header>

    <section class="lms-metric-grid" aria-label="Quiz result summary">
        <article class="lms-metric-card">
            <span>Submissions</span>
            <strong><?php echo e($attempts->total()); ?></strong>
            <small>Graded attempts</small>
        </article>
        <article class="lms-metric-card">
            <span>Average score</span>
            <strong><?php echo e($classSummary['average'] !== null ? number_format((float) $classSummary['average'], 1).'%' : '-'); ?></strong>
            <small>Whole class</small>
        </article>
        <article class="lms-metric-card">
            <span>Highest / lowest</span>
            <strong><?php echo e($classSummary['highest'] !== null ? number_format((float) $classSummary['highest'], 1).'%' : '-'); ?> / <?php echo e($classSummary['lowest'] !== null ? number_format((float) $classSummary['lowest'], 1).'%' : '-'); ?></strong>
            <small>Whole class</small>
        </article>
        <article class="lms-metric-card">
            <span>Passed</span>
            <strong><?php echo e($classSummary['passed']); ?></strong>
            <small>Whole class</small>
        </article>
    </section>

    <section class="lms-results-panel" aria-labelledby="learner-results-title">
        <div class="lms-section-heading">
            <div>
                <p class="lms-eyebrow">Learner work</p>
                <h2 id="learner-results-title">Submitted attempts</h2>
            </div>
        </div>

        <div class="lms-responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Learner</th>
                        <th>Attempt</th>
                        <th>Submitted</th>
                        <th>Files / Activity</th>
                        <th>Points</th>
                        <th>Score</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $attempts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attempt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $learner = $attempt->application;
                            $learnerName = $learner
                                ? trim($learner->first_name.' '.$learner->last_name)
                                : ($attempt->application?->user?->name ?? 'Trainee');
                            $submittedFiles = collect($attempt->answers ?? [])->filter(fn ($ans) => is_array($ans) && ($ans['type'] ?? null) === 'file');
                        ?>
                        <tr>
                            <td data-label="Learner">
                                <span class="flex items-center gap-3">
                                    <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $learner?->user,'name' => $learnerName,'class' => 'grid h-9 w-9 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($learner?->user),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($learnerName),'class' => 'grid h-9 w-9 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $attributes = $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e)): ?>
<?php $component = $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e; ?>
<?php unset($__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e); ?>
<?php endif; ?>
                                    <span class="min-w-0"><strong class="block truncate"><?php echo e($learnerName); ?></strong><small class="block truncate"><?php echo e($learner?->email ?? $learner?->user?->email); ?></small></span>
                                </span>
                            </td>
                            <td data-label="Attempt">#<?php echo e($attempt->attempt_number); ?></td>
                            <td data-label="Submitted"><?php echo e($attempt->submitted_at?->format('M d, Y g:i A') ?? '-'); ?></td>
                            <td data-label="Files / Activity">
                                <?php if($submittedFiles->isNotEmpty()): ?>
                                    <div class="flex flex-wrap gap-1.5">
                                        <?php $__currentLoopData = $submittedFiles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $qId => $fileAnswer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <a href="<?php echo e(route('trainer.quizzes.attempts.download', ['quiz' => $quiz, 'attempt' => $attempt, 'question' => $qId])); ?>" class="inline-flex items-center gap-1 rounded-md bg-purple-50 px-2 py-1 text-[11px] font-bold text-purple-700 hover:bg-purple-100 hover:text-purple-900" title="<?php echo e($fileAnswer['original_name'] ?? 'Document'); ?>">
                                                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'document','class' => 'h-3.5 w-3.5 text-purple-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'document','class' => 'h-3.5 w-3.5 text-purple-600']); ?>
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
                                                <span><?php echo e(str($fileAnswer['original_name'] ?? 'File')->limit(16)); ?></span>
                                            </a>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                <?php else: ?>
                                    <span class="text-xs text-slate-400">-</span>
                                <?php endif; ?>
                            </td>
                            <td data-label="Points"><?php echo e(number_format((float) $attempt->earned_points, 1)); ?> / <?php echo e(number_format((float) $attempt->total_points, 1)); ?></td>
                            <td data-label="Score"><strong><?php echo e(number_format((float) $attempt->score_percent, 1)); ?>%</strong></td>
                            <td data-label="Result">
                                <span class="lms-status-chip <?php echo e($attempt->passed ? 'is-green' : 'is-red'); ?>">
                                    <?php echo e($attempt->passed ? 'Passed' : 'Needs review'); ?>

                                </span>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="7">
                                <div class="lms-empty-state">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'clipboard-list']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard-list']); ?>
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
                                    <h2>No submissions yet</h2>
                                    <p>Scores will appear here after trainees submit the quiz.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if($attempts->hasPages()): ?>
            <div class="lms-pagination"><?php echo e($attempts->links()); ?></div>
        <?php endif; ?>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => 'Quiz Results | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\quizzes\results.blade.php ENDPATH**/ ?>