<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('trainer.quizzes.partials.form', [
        'formAction' => route('admin.learning.quizzes.update', $quiz),
        'formMethod' => 'PATCH',
        'pageTitle' => 'Edit quiz',
        'pageDescription' => 'Import questions from a file, or revise them before saving.',
        'submitLabel' => 'Save changes',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Edit Quiz | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\learning\quiz-edit.blade.php ENDPATH**/ ?>