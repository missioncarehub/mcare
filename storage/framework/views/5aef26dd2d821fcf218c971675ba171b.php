<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('trainer.quizzes.partials.form', [
        'quiz' => null,
        'formAction' => route('trainer.quizzes.store'),
        'formMethod' => 'POST',
        'pageTitle' => 'Create new quiz',
        'pageDescription' => 'Design an in-module assessment, set time limits, and configure questions.',
        'submitLabel' => 'Save & publish quiz',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => 'Create Quiz | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\quizzes\create.blade.php ENDPATH**/ ?>