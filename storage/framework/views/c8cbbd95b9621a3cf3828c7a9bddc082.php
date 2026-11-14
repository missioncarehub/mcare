<?php $__env->startSection('content'); ?>
<?php
    $answers = collect($attempt->answers ?? []);
    $answerReviewAvailable = $answerReviewAvailable ?? false;
    $moduleReturnUrl = $quiz->training_module_id
        ? route('trainee.modules.show', $quiz->training_module_id).'#assessments'
        : route('trainee.modules.index');
?>

<div class="lms-page" data-quiz-result>
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Attempt <?php echo e($attempt->attempt_number); ?> result</p>
            <h1><?php echo e($quiz->title); ?></h1>
            <p>Submitted <?php echo e($attempt->submitted_at?->format('M d, Y g:i A')); ?></p>
        </div>
        <a href="<?php echo e($moduleReturnUrl); ?>" class="secondary-action">Back to module</a>
    </header>

    <section class="lms-result-hero <?php echo e($attempt->passed ? 'is-passed' : 'is-review'); ?>">
        <div class="lms-result-score">
            <span>Your score</span>
            <strong><?php echo e(number_format((float) $attempt->score_percent, 1)); ?>%</strong>
            <small><?php echo e(number_format((float) $attempt->earned_points, 1)); ?> of <?php echo e(number_format((float) $attempt->total_points, 1)); ?> points · Attempt <?php echo e($attempt->attempt_number); ?> of <?php echo e($gradedAttemptCount ?? $attempt->attempt_number); ?></small>
        </div>
        <div>
            <p class="lms-eyebrow"><?php echo e($attempt->passed ? 'Passed' : 'Keep learning'); ?></p>
            <h2><?php echo e($attempt->passed ? 'Great work - you reached the passing score.' : 'Your score has been recorded.'); ?></h2>
            <p>The passing score for this quiz is <?php echo e(number_format((float) $quiz->passing_score_percent)); ?>%.</p>
        </div>
    </section>

    <section class="lms-review-list" aria-labelledby="answer-review-title">
        <div class="lms-section-heading">
            <div>
                <p class="lms-eyebrow">Answer review</p>
                <h2 id="answer-review-title"><?php echo e($answerReviewAvailable ? 'Questions and answers' : 'Your submitted answers'); ?></h2>
                <?php if (! ($answerReviewAvailable)): ?>
                    <p>Correctness and answer keys unlock after the deadline or your final allowed attempt.</p>
                <?php endif; ?>
            </div>
        </div>

        <?php $__currentLoopData = $quiz->questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $questionIndex => $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $rawAnswer = $answers->get((string) $question->id, $answers->get($question->id));
                $isFile = is_array($rawAnswer) && ($rawAnswer['type'] ?? null) === 'file';
                $isText = is_array($rawAnswer) && ($rawAnswer['type'] ?? null) === 'text';
                $selectedIndex = is_numeric($rawAnswer) ? (int) $rawAnswer : null;
                $selectedOption = $selectedIndex !== null ? data_get($question->options, $selectedIndex) : null;
                $correctOption = $answerReviewAvailable && $question->requiresOptions()
                    ? data_get($question->options, (int) $question->correct_option)
                    : null;
                $isCorrect = $answerReviewAvailable
                    ? ($question->requiresOptions() ? ($selectedIndex !== null && $selectedIndex === (int) $question->correct_option) : ($isFile || $isText))
                    : false;
            ?>
            <article class="lms-review-card <?php echo e($answerReviewAvailable ? ($isCorrect ? 'is-correct' : 'is-incorrect') : ''); ?>">
                <header>
                    <span>Question <?php echo e($questionIndex + 1); ?></span>
                    <?php if($answerReviewAvailable): ?>
                        <span class="lms-status-chip <?php echo e($isCorrect ? 'is-green' : 'is-red'); ?>"><?php echo e($isCorrect ? 'Completed' : 'Incorrect'); ?></span>
                    <?php else: ?>
                        <span class="lms-status-chip is-neutral">Recorded</span>
                    <?php endif; ?>
                </header>
                <h3><?php echo e($question->prompt); ?></h3>
                <dl>
                    <div>
                        <dt>Your answer</dt>
                        <dd>
                            <?php if($isFile): ?>
                                <div class="mt-1 flex items-center gap-2">
                                    <span class="font-medium text-slate-800"><?php echo e($rawAnswer['original_name'] ?? 'Submitted Document'); ?></span>
                                    <a href="<?php echo e(route('trainee.quiz-attempts.download', ['attempt' => $attempt, 'question' => $question->id])); ?>" class="inline-flex items-center gap-1 rounded-md bg-purple-100 px-2.5 py-1 text-xs font-bold text-purple-800 hover:bg-purple-200">
                                        Download File
                                    </a>
                                </div>
                            <?php elseif($isText): ?>
                                <p class="whitespace-pre-line text-slate-800"><?php echo e($rawAnswer['content']); ?></p>
                            <?php else: ?>
                                <?php echo e($selectedOption ?? 'No answer'); ?>

                            <?php endif; ?>
                        </dd>
                    </div>
                    <?php if($answerReviewAvailable && ! $isCorrect && $correctOption): ?>
                        <div><dt>Correct answer</dt><dd><?php echo e($correctOption); ?></dd></div>
                    <?php endif; ?>
                </dl>
            </article>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </section>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainee.layouts.app', ['title' => 'Quiz Result | MCARE Trainee'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainee\quizzes\result.blade.php ENDPATH**/ ?>