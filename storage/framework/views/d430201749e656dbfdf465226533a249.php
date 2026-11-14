<?php $__env->startSection('content'); ?>
<?php
    $questionCount = $quiz->questions->count();
?>

<div class="lms-page lms-quiz-taking-page" data-quiz-attempt data-remaining-seconds="<?php echo e($remainingSeconds === null ? 'unlimited' : max(0, (int) $remainingSeconds)); ?>">
    <header class="lms-quiz-taking-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Attempt <?php echo e($attempt->attempt_number); ?></p>
            <h1><?php echo e($quiz->title); ?></h1>
            <p><?php echo e($questionCount); ?> <?php echo e(str('question')->plural($questionCount)); ?> - Answer every item before submitting.</p>
        </div>
        <div class="lms-quiz-timer" data-quiz-timer role="timer" aria-live="polite">
            <span>Time remaining</span>
            <strong data-quiz-timer-value><?php echo e($remainingSeconds === null ? 'No limit' : '--:--'); ?></strong>
        </div>
    </header>

    <?php if($errors->any()): ?>
        <div class="lms-inline-alert is-danger" role="alert">
            <strong>Your answers were not submitted.</strong>
            <span><?php echo e($errors->first()); ?></span>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?php echo e(route('trainee.quiz-attempts.submit', $attempt)); ?>" enctype="multipart/form-data" class="lms-attempt-form" data-quiz-attempt-form data-confirm="Submit this quiz now? You will not be able to change this attempt after submission.">
        <?php echo csrf_field(); ?>
        <nav class="lms-question-jump" aria-label="Jump to a quiz question">
            <?php $__currentLoopData = $quiz->questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $questionIndex => $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a href="#quiz-question-<?php echo e($question->id); ?>" data-question-jump><?php echo e($questionIndex + 1); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </nav>

        <div class="lms-take-question-list">
            <?php $__currentLoopData = $quiz->questions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $questionIndex => $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <fieldset class="lms-take-question" id="quiz-question-<?php echo e($question->id); ?>" data-answer-question>
                    <legend class="sr-only">Question <?php echo e($questionIndex + 1); ?> of <?php echo e($questionCount); ?>: <?php echo e($question->prompt); ?></legend>
                    <div class="lms-take-question-heading">
                        <span>Question <?php echo e($questionIndex + 1); ?> of <?php echo e($questionCount); ?></span>
                        <strong><?php echo e($question->prompt); ?></strong>
                        <small><?php echo e(number_format((float) $question->points, 1)); ?> <?php echo e(str('point')->plural((float) $question->points)); ?></small>
                    </div>

                    <?php if($question->isFileUpload()): ?>
                        <div class="lms-activity-upload">
                            <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'upload-cloud','class' => 'mx-auto h-8 w-8 text-purple-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'upload-cloud','class' => 'mx-auto h-8 w-8 text-purple-600']); ?>
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
                            <p class="mt-2 text-sm font-bold text-slate-900">Upload your completed activity document</p>
                            <p class="mt-1 text-xs text-slate-500">Accepted formats: <strong><?php echo e(\App\Support\TrainingModuleFiles::humanLabel()); ?></strong> (Max 20MB)</p>
                            <div class="mt-4 flex justify-center">
                                <input type="file" name="file_answers[<?php echo e($question->id); ?>]" accept="<?php echo e(\App\Support\TrainingModuleFiles::acceptAttribute()); ?>" class="lms-activity-file-input block w-full max-w-md text-xs text-slate-700 file:mr-4 file:rounded-none file:border-0 file:bg-purple-600 file:px-4 file:py-2.5 file:text-xs file:font-bold file:text-white file:transition hover:file:bg-purple-700 cursor-pointer">
                            </div>
                        </div>
                    <?php elseif($question->isEnumeration()): ?>
                        <div class="space-y-3">
                            <textarea name="text_answers[<?php echo e($question->id); ?>]" rows="4" maxlength="5000" class="lms-take-written-answer" placeholder="Type your enumeration or written answers here..."><?php echo e(old('text_answers.'.$question->id)); ?></textarea>
                            <div class="lms-activity-upload is-compact">
                                <span class="font-bold text-slate-700">Or attach your activity document (<?php echo e(\App\Support\TrainingModuleFiles::humanLabel()); ?>):</span>
                                <input type="file" name="file_answers[<?php echo e($question->id); ?>]" accept="<?php echo e(\App\Support\TrainingModuleFiles::acceptAttribute()); ?>" class="lms-activity-file-input mt-2 block w-full text-xs text-slate-700 file:mr-3 file:rounded-none file:border-0 file:bg-purple-100 file:px-3 file:py-1.5 file:text-xs file:font-bold file:text-purple-800 hover:file:bg-purple-200">
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="lms-answer-options">
                            <?php $__currentLoopData = $question->options; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionIndex => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <label class="lms-answer-option">
                                    <input type="radio" name="answers[<?php echo e($question->id); ?>]" value="<?php echo e($optionIndex); ?>" <?php if((string) old('answers.'.$question->id) === (string) $optionIndex): echo 'checked'; endif; ?>>
                                    <span class="lms-answer-text"><span class="lms-answer-key"><?php echo e(chr(65 + $optionIndex)); ?>.</span> <?php echo e($option); ?></span>
                                </label>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </fieldset>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <footer class="lms-sticky-submit">
            <span class="lms-answer-progress" data-answer-progress>0 of <?php echo e($questionCount); ?> answered</span>
            <button type="submit" class="primary-action" data-submit-quiz>Review and submit</button>
        </footer>
    </form>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainee.layouts.app', ['title' => 'Take Quiz | MCARE Trainee'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainee\quizzes\take.blade.php ENDPATH**/ ?>