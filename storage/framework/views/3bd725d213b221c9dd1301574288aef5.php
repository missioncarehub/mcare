<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'commentable',
    'comments' => collect(),
    'privateRecipients' => collect(),
    'readOnly' => false,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'commentable',
    'comments' => collect(),
    'privateRecipients' => collect(),
    'readOnly' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $viewer = auth()->user();
    $commentableType = $commentable instanceof \App\Models\TrainingModule ? 'module' : 'quiz';
    $classComments = collect($comments)->where('visibility', \App\Models\ClassroomComment::VISIBILITY_CLASS);
    $privateComments = collect($comments)->where('visibility', \App\Models\ClassroomComment::VISIBILITY_PRIVATE);
    $privateRecipientList = collect($privateRecipients);
    $traineePrivateRecipient = $viewer?->role === 'trainee' ? $privateRecipientList->first() : null;
    $readOnly = (bool) $readOnly;
?>

<section id="classroom-comments" class="lms-comments-panel" aria-labelledby="classroom-comments-title">
    <header class="lms-comments-header">
        <div>
            <p class="lms-eyebrow">Classroom conversation</p>
            <h2 id="classroom-comments-title">Comments and private feedback</h2>
            <p><?php echo e($readOnly ? 'This archived conversation remains visible as part of your completed training record. New posts and edits are locked.' : 'Class comments are visible to participants. Private comments are limited to their participants and authorized classroom staff.'); ?></p>
        </div>
        <span class="lms-comment-count"><?php echo e(collect($comments)->count()); ?> <?php echo e(str('comment')->plural(collect($comments)->count())); ?></span>
    </header>

    <div class="lms-comments-grid">
        <section class="lms-comment-column" aria-labelledby="class-comments-title">
            <div class="lms-comment-column-heading">
                <div><h3 id="class-comments-title">Class comments</h3><p>Visible to everyone who can open this classwork item.</p></div>
            </div>

            <div class="lms-comment-feed" aria-live="polite">
                <?php $__empty_1 = true; $__currentLoopData = $classComments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <article class="lms-comment" id="comment-<?php echo e($comment->id); ?>">
                        <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $comment->author,'name' => $comment->author?->name ?? 'MCARE user','class' => 'lms-comment-avatar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($comment->author),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($comment->author?->name ?? 'MCARE user'),'class' => 'lms-comment-avatar']); ?>
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
                        <div class="lms-comment-content">
                            <div class="lms-comment-meta">
                                <strong><?php echo e($comment->author?->name ?? 'MCARE user'); ?></strong>
                                <span><?php echo e($comment->created_at?->format('M d, Y g:i A')); ?><?php if($comment->edited_at): ?> · Edited <?php endif; ?></span>
                            </div>
                            <p><?php echo nl2br(e($comment->body)); ?></p>
                            <?php if (! ($readOnly)): ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['update', 'delete'], $comment)): ?>
                                    <details class="lms-comment-manage">
                                        <summary>Manage</summary>
                                        <div>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $comment)): ?>
                                                <form method="POST" action="<?php echo e(route('classroom-comments.update', $comment)); ?>" class="space-y-2">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('PATCH'); ?>
                                                    <textarea name="body" maxlength="2000" required class="form-field min-h-20"><?php echo e($comment->body); ?></textarea>
                                                    <button class="secondary-action text-xs">Save edit</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $comment)): ?>
                                                <form method="POST" action="<?php echo e(route('classroom-comments.destroy', $comment)); ?>" data-confirm="Remove this class comment?">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button class="lms-text-action is-danger text-xs">Remove</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="lms-comment-empty">No class comments yet.</p>
                <?php endif; ?>
            </div>

            <?php if($readOnly): ?>
                <p class="lms-comment-empty">Classroom posting is locked after graduation.</p>
            <?php else: ?>
                <form method="POST" action="<?php echo e(route('classroom-comments.store')); ?>" class="lms-comment-composer" data-dashboard-dialog-form data-submit-label="Posting...">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="commentable_type" value="<?php echo e($commentableType); ?>">
                    <input type="hidden" name="commentable_id" value="<?php echo e($commentable->id); ?>">
                    <input type="hidden" name="visibility" value="class">
                    <label for="class-comment-<?php echo e($commentableType); ?>-<?php echo e($commentable->id); ?>" class="flex items-center gap-2">
                        <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $viewer,'name' => $viewer?->name ?? 'MCARE user','class' => 'lms-comment-avatar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($viewer),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($viewer?->name ?? 'MCARE user'),'class' => 'lms-comment-avatar']); ?>
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
                        <span>Comment as <?php echo e($viewer?->name ?? 'MCARE user'); ?></span>
                    </label>
                    <textarea id="class-comment-<?php echo e($commentableType); ?>-<?php echo e($commentable->id); ?>" name="body" required maxlength="2000" rows="3" placeholder="Write a helpful question, answer, or update..."></textarea>
                    <div><span>Plain text · 2,000 characters maximum</span><button class="primary-action text-xs" data-action-button>Post comment</button></div>
                </form>
            <?php endif; ?>
        </section>

        <section class="lms-comment-column is-private" aria-labelledby="private-comments-title">
            <div class="lms-comment-column-heading">
                <div><h3 id="private-comments-title">Private comments</h3><p>Not visible to other trainees.</p></div>
            </div>

            <div class="lms-comment-feed" aria-live="polite">
                <?php $__empty_1 = true; $__currentLoopData = $privateComments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $comment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $privateCounterpart = $comment->author_id === $viewer?->id
                            ? $comment->recipient
                            : ($comment->recipient_user_id === $viewer?->id ? $comment->author : $comment->recipient);
                    ?>
                    <article class="lms-comment is-private" id="comment-<?php echo e($comment->id); ?>">
                        <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $comment->author,'name' => $comment->author?->name ?? 'MCARE user','class' => 'lms-comment-avatar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($comment->author),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($comment->author?->name ?? 'MCARE user'),'class' => 'lms-comment-avatar']); ?>
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
                        <div class="lms-comment-content">
                            <div class="lms-comment-meta">
                                <strong><?php echo e($comment->author?->name ?? 'MCARE user'); ?></strong>
                                <span><?php echo e($comment->created_at?->format('M d, Y g:i A')); ?><?php if($comment->edited_at): ?> · Edited <?php endif; ?></span>
                            </div>
                            <span class="lms-private-context">Private with <?php echo e($privateCounterpart?->name ?? 'classroom staff'); ?></span>
                            <p><?php echo nl2br(e($comment->body)); ?></p>
                            <?php if (! ($readOnly)): ?>
                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->any(['update', 'delete'], $comment)): ?>
                                    <details class="lms-comment-manage">
                                        <summary>Manage</summary>
                                        <div>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $comment)): ?>
                                                <form method="POST" action="<?php echo e(route('classroom-comments.update', $comment)); ?>" class="space-y-2">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('PATCH'); ?>
                                                    <textarea name="body" maxlength="2000" required class="form-field min-h-20"><?php echo e($comment->body); ?></textarea>
                                                    <button class="secondary-action text-xs">Save edit</button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $comment)): ?>
                                                <form method="POST" action="<?php echo e(route('classroom-comments.destroy', $comment)); ?>" data-confirm="Remove this private comment?">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('DELETE'); ?>
                                                    <button class="lms-text-action is-danger text-xs">Remove</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </details>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="lms-comment-empty">No private comments in this conversation yet.</p>
                <?php endif; ?>
            </div>

            <?php if($readOnly): ?>
                <p class="lms-comment-empty">Private classroom messaging is locked after graduation.</p>
            <?php elseif($privateRecipientList->isNotEmpty()): ?>
                <form method="POST" action="<?php echo e(route('classroom-comments.store')); ?>" class="lms-comment-composer is-private" data-dashboard-dialog-form data-submit-label="Sending...">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="commentable_type" value="<?php echo e($commentableType); ?>">
                    <input type="hidden" name="commentable_id" value="<?php echo e($commentable->id); ?>">
                    <input type="hidden" name="visibility" value="private">
                    <?php if($traineePrivateRecipient): ?>
                        <input type="hidden" name="recipient_user_id" value="<?php echo e($traineePrivateRecipient->id); ?>">
                        <p class="lms-private-recipient">Private conversation with <strong><?php echo e($traineePrivateRecipient->name); ?></strong></p>
                    <?php else: ?>
                        <label for="private-recipient-<?php echo e($commentableType); ?>-<?php echo e($commentable->id); ?>">Private recipient</label>
                        <select id="private-recipient-<?php echo e($commentableType); ?>-<?php echo e($commentable->id); ?>" name="recipient_user_id" class="form-field" required>
                            <option value="">Choose trainee or trainer</option>
                            <?php $__currentLoopData = $privateRecipientList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $recipient): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($recipient->id); ?>"><?php echo e($recipient->name); ?> · <?php echo e(ucfirst($recipient->role)); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    <?php endif; ?>
                    <label for="private-comment-<?php echo e($commentableType); ?>-<?php echo e($commentable->id); ?>" class="flex items-center gap-2">
                        <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $viewer,'name' => $viewer?->name ?? 'MCARE user','class' => 'lms-comment-avatar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($viewer),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($viewer?->name ?? 'MCARE user'),'class' => 'lms-comment-avatar']); ?>
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
                        <span>Send as <?php echo e($viewer?->name ?? 'MCARE user'); ?></span>
                    </label>
                    <textarea id="private-comment-<?php echo e($commentableType); ?>-<?php echo e($commentable->id); ?>" name="body" required maxlength="2000" rows="3" placeholder="Write feedback that only the selected participant and classroom staff can read..."></textarea>
                    <div><span>Private · Plain text</span><button class="primary-action text-xs" data-action-button>Send privately</button></div>
                </form>
            <?php else: ?>
                <p class="lms-comment-empty">No eligible private recipient is assigned to this classwork item.</p>
            <?php endif; ?>
        </section>
    </div>
</section>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\components\classroom-comments.blade.php ENDPATH**/ ?>