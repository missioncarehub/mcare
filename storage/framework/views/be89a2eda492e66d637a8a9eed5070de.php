<?php
    $inputId = $inputId ?? 'question-file';
    $templateUrl = $templateUrl ?? route('trainer.quizzes.import-template');
?>

<div class="lms-field lms-field-wide">
    <label for="<?php echo e($inputId); ?>">Import questions from a file</label>
    <input id="<?php echo e($inputId); ?>" type="file" name="question_file" accept=".txt,.text,.csv,.json,text/plain,text/csv,application/json">
    <p class="lms-muted-note">Optional. Use a .txt, .csv, or .json file. In a text file, leave a blank line between questions and mark the correct choice with * or <code>Answer: B</code>. <a href="<?php echo e($templateUrl); ?>">Download a sample text file</a>.</p>
    <?php $__errorArgs = ['question_file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="lms-field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
</div>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\quizzes\partials\question-file.blade.php ENDPATH**/ ?>