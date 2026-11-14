<?php $__env->startSection('content'); ?>
<?php
    $attemptCollection = collect($attempts);
    $inProgressAttempt = $attemptCollection->firstWhere('status', 'in_progress');
    $attemptsRemaining = max(0, (int) $quiz->attempt_limit - $attemptCollection->count());
    $isUpcoming = $quiz->available_at && $quiz->available_at->isFuture();
    $isClosed = $quiz->due_at && $quiz->due_at->isPast();
    $moduleReturnUrl = $quiz->training_module_id
        ? route('trainee.modules.show', $quiz->training_module_id).'#assessments'
        : route('trainee.modules.index');
?>

<div class="lms-page" data-quiz-detail>
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Quiz details</p>
            <h1><?php echo e($quiz->title); ?></h1>
            <p><?php echo e($quiz->batch?->name ?? 'Assigned class'); ?> - Posted by <?php echo e($quiz->trainer?->name ?? 'MCARE Trainer'); ?></p>
        </div>
        <a href="<?php echo e($moduleReturnUrl); ?>" class="secondary-action">Back to module</a>
    </header>

    <?php if(session('status')): ?>
        <div class="lms-inline-alert is-success" style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 12px 16px; border-radius: 12px; margin-bottom: 16px;">
            <strong>Success:</strong> <?php echo e(session('status')); ?>

        </div>
    <?php endif; ?>

    <?php if(session('error')): ?>
        <div class="lms-inline-alert is-danger" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 12px; margin-bottom: 16px;">
            <strong>Notice:</strong> <?php echo e(session('error')); ?>

        </div>
    <?php endif; ?>

    <div class="lms-detail-layout">
        <main class="lms-detail-main">
            <section class="lms-detail-card">
                <div class="lms-detail-icon"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
<?php endif; ?></div>
                <div>
                    <p class="lms-eyebrow">Instructions</p>
                    <h2>Before you begin</h2>
                    <div class="lms-rich-copy">
                        <?php echo nl2br(e($quiz->instructions ?: 'Answer every question, review your choices, and submit before the timer ends.')); ?>

                    </div>
                </div>
            </section>

            <section class="lms-results-panel" aria-labelledby="attempt-history-title">
                <div class="lms-section-heading">
                    <div>
                        <p class="lms-eyebrow">Your work</p>
                        <h2 id="attempt-history-title">Attempt history</h2>
                    </div>
                </div>
                <div class="lms-attempt-list">
                    <?php $__empty_1 = true; $__currentLoopData = $attemptCollection->sortByDesc('attempt_number'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $attempt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <article class="lms-attempt-row">
                            <span class="lms-attempt-number">#<?php echo e($attempt->attempt_number); ?></span>
                            <div>
                                <strong><?php echo e($attempt->status === 'graded' ? 'Submitted' : 'In progress'); ?></strong>
                                <small><?php echo e(($attempt->submitted_at ?? $attempt->started_at)?->format('M d, Y g:i A')); ?></small>
                            </div>
                            <?php if($attempt->status === 'graded'): ?>
                                <span class="lms-score-badge <?php echo e($attempt->passed ? 'is-passed' : 'is-review'); ?>"><?php echo e(number_format((float) $attempt->score_percent, 1)); ?>%</span>
                                <a href="<?php echo e(route('trainee.quiz-attempts.result', $attempt)); ?>" class="lms-text-action">View result</a>
                            <?php else: ?>
                                <a href="<?php echo e(route('trainee.quiz-attempts.show', $attempt)); ?>" class="primary-action">Continue</a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="lms-empty-copy">You have not started this quiz yet.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>

        <aside class="lms-detail-sidebar">
            <section class="lms-side-card">
                <h2>Quiz summary</h2>
                <dl class="lms-summary-list">
                    <div><dt>Questions</dt><dd><?php echo e($quiz->questions->count()); ?></dd></div>
                    <div><dt>Time limit</dt><dd><?php echo e($quiz->time_limit_minutes ? $quiz->time_limit_minutes.' minutes' : 'No limit'); ?></dd></div>
                    <div><dt>Attempts</dt><dd><?php echo e($attemptsRemaining); ?> remaining</dd></div>
                    <div><dt>Passing score</dt><dd><?php echo e(number_format((float) $quiz->passing_score_percent)); ?>%</dd></div>
                    <div><dt>Available</dt><dd><?php echo e($quiz->available_at?->format('M d, Y g:i A') ?? 'Now'); ?></dd></div>
                    <div><dt>Due</dt><dd><?php echo e($quiz->due_at?->format('M d, Y g:i A') ?? 'No deadline'); ?></dd></div>
                </dl>

                <?php if($inProgressAttempt): ?>
                    <a href="<?php echo e(route('trainee.quiz-attempts.show', $inProgressAttempt)); ?>" class="primary-action lms-full-action">Continue attempt</a>
                <?php elseif($canStart): ?>
                    <form method="POST" action="<?php echo e(route('trainee.quizzes.start', $quiz)); ?>" data-confirm="Start this quiz now? The timer begins as soon as the attempt opens.">
                        <?php echo csrf_field(); ?>
                        <button class="primary-action lms-full-action">Start quiz</button>
                    </form>
                <?php else: ?>
                    <button class="primary-action lms-full-action" disabled>
                        <?php echo e($isUpcoming ? 'Not available yet' : ($isClosed ? 'Quiz closed' : 'No attempts left')); ?>

                    </button>
                <?php endif; ?>
                <p class="lms-side-note">Your timer continues if you close or refresh the page.</p>
            </section>
        </aside>
    </div>

    <?php if (isset($component)) { $__componentOriginalad64fd8082a658cbdd3ae55129ff9480 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad64fd8082a658cbdd3ae55129ff9480 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.classroom-comments','data' => ['commentable' => $quiz,'comments' => $classroomComments,'privateRecipients' => $privateCommentRecipients]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('classroom-comments'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['commentable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($quiz),'comments' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($classroomComments),'private-recipients' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($privateCommentRecipients)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalad64fd8082a658cbdd3ae55129ff9480)): ?>
<?php $attributes = $__attributesOriginalad64fd8082a658cbdd3ae55129ff9480; ?>
<?php unset($__attributesOriginalad64fd8082a658cbdd3ae55129ff9480); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalad64fd8082a658cbdd3ae55129ff9480)): ?>
<?php $component = $__componentOriginalad64fd8082a658cbdd3ae55129ff9480; ?>
<?php unset($__componentOriginalad64fd8082a658cbdd3ae55129ff9480); ?>
<?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainee.layouts.app', ['title' => 'Quiz Details | MCARE Trainee'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainee\quizzes\show.blade.php ENDPATH**/ ?>