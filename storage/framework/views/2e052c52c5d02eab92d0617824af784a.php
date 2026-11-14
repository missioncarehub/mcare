<?php
    $commentLayout = match (auth()->user()?->role) {
        'admin' => 'admin.layouts.app',
        'trainer' => 'trainer.layouts.app',
        default => 'trainee.layouts.app',
    };
?>



<?php $__env->startSection('content'); ?>
<div class="lms-page">
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Classroom conversation</p>
            <h1><?php echo e($commentable->title); ?></h1>
            <p>Review class discussion and permission-scoped private feedback.</p>
        </div>
        <a href="<?php echo e($returnUrl); ?>" class="secondary-action">Back to classwork</a>
    </header>

    <?php if (isset($component)) { $__componentOriginalad64fd8082a658cbdd3ae55129ff9480 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad64fd8082a658cbdd3ae55129ff9480 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.classroom-comments','data' => ['commentable' => $commentable,'comments' => $classroomComments,'privateRecipients' => $privateCommentRecipients]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('classroom-comments'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['commentable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($commentable),'comments' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($classroomComments),'private-recipients' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($privateCommentRecipients)]); ?>
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

<?php echo $__env->make($commentLayout, ['title' => 'Classroom Comments | MCARE'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\classroom\comments\index.blade.php ENDPATH**/ ?>