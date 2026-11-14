<?php $__env->startSection('content'); ?>
    <?php echo $__env->make('trainer.quizzes.partials.form', [
        'formAction' => route('trainer.quizzes.update', $quiz),
        'formMethod' => 'PATCH',
        'pageTitle' => 'Edit quiz',
        'pageDescription' => 'Review the quiz settings, then revise questions before saving.',
        'submitLabel' => 'Save changes',
    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="lms-quiz-editor-followup">
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

<?php echo $__env->make('trainer.layouts.app', ['title' => 'Edit Quiz | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\quizzes\edit.blade.php ENDPATH**/ ?>