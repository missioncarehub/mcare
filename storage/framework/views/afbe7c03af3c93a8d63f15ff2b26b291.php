<?php $__env->startSection('content'); ?>
<?php
    $selectedBatchId = (int) request('batch_id');
    $selectedBatch = $selectedBatchId ? $batches->firstWhere('id', $selectedBatchId) : null;
    $stats = array_merge([
        'total' => method_exists($announcements, 'total') ? $announcements->total() : collect($announcements)->count(),
        'published' => collect($announcements)->where('is_published', true)->count(),
        'scheduled' => 0,
    ], $streamStats ?? []);
?>

<div class="lms-page" data-lms-stream data-lms-role="trainer">
    <nav class="lms-context-tabs" aria-label="Filter stream by class">
        <a href="<?php echo e(route('trainer.stream')); ?>" class="<?php echo e($selectedBatchId === 0 ? 'is-active' : ''); ?>" <?php if($selectedBatchId === 0): ?> aria-current="page" <?php endif; ?>>All classes</a>
        <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <a href="<?php echo e(route('trainer.stream', ['batch_id' => $batch->id])); ?>" class="<?php echo e($selectedBatchId === $batch->id ? 'is-active' : ''); ?>" <?php if($selectedBatchId === $batch->id): ?> aria-current="page" <?php endif; ?>>
                <?php echo e($batch->name); ?> <?php echo e($batch->year); ?>

            </a>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </nav>

    <div class="lms-stream-layout">
        <main class="lms-stream-feed">
            <section class="lms-composer" data-announcement-composer aria-labelledby="announcement-composer-title">
                <div class="lms-composer-heading">
                    <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => auth()->user(),'name' => auth()->user()?->name ?? 'MCARE Trainer','class' => 'lms-avatar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(auth()->user()),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(auth()->user()?->name ?? 'MCARE Trainer'),'class' => 'lms-avatar']); ?>
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
                    <div>
                        <p class="lms-eyebrow">New post</p>
                        <h2 id="announcement-composer-title">Share with your class</h2>
                    </div>
                </div>

                <form method="POST" action="<?php echo e(route('trainer.announcements.store')); ?>" class="lms-form-grid">
                    <?php echo csrf_field(); ?>
                    <div class="lms-field">
                        <label for="announcement-batch">Class</label>
                        <select id="announcement-batch" name="training_batch_id">
                            <option value="">All active classes</option>
                            <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($batch->id); ?>" <?php if((string) old('training_batch_id', $selectedBatchId ?: '') === (string) $batch->id): echo 'selected'; endif; ?>>
                                    <?php echo e($batch->name); ?> <?php echo e($batch->year); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="lms-field">
                        <label for="announcement-kind">Post type</label>
                        <select id="announcement-kind" name="kind">
                            <?php $__currentLoopData = ['announcement' => 'Announcement', 'news' => 'Class news', 'reminder' => 'Reminder']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php if(old('kind', 'announcement') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="lms-field lms-field-wide">
                        <label for="announcement-title">Title</label>
                        <input id="announcement-title" name="title" value="<?php echo e(old('title')); ?>" maxlength="160" required placeholder="Example: Skills demonstration moved to Room 202">
                    </div>
                    <div class="lms-field lms-field-wide">
                        <label for="announcement-message">Message</label>
                        <textarea id="announcement-message" name="message" rows="4" maxlength="3000" required placeholder="Write the information trainees need to know."><?php echo e(old('message')); ?></textarea>
                    </div>
                    <input type="hidden" name="audience" value="trainees">
                    <div class="lms-form-options lms-field-wide">
                        <label class="lms-check">
                            <input type="hidden" name="is_pinned" value="0">
                            <input type="checkbox" name="is_pinned" value="1" <?php if(old('is_pinned')): echo 'checked'; endif; ?>>
                            <span>Pin to the top</span>
                        </label>
                        <label class="lms-check">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" <?php if(old('is_published', true)): echo 'checked'; endif; ?>>
                            <span>Publish or schedule</span>
                        </label>
                        <div class="lms-field lms-compact-field">
                            <label for="announcement-posted-at">Post at</label>
                            <input id="announcement-posted-at" type="datetime-local" name="posted_at" value="<?php echo e(old('posted_at')); ?>">
                        </div>
                        <div class="lms-field lms-compact-field">
                            <label for="announcement-expires">Hide after</label>
                            <input id="announcement-expires" type="datetime-local" name="expires_at" value="<?php echo e(old('expires_at')); ?>">
                        </div>
                    </div>
                    <div class="lms-form-actions lms-field-wide">
                        <button class="primary-action">Post announcement</button>
                    </div>
                </form>
            </section>

            <section class="lms-post-list" aria-label="Class announcements">
                <?php $__empty_1 = true; $__currentLoopData = $announcements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $announcement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $postedAt = $announcement->posted_at ?? $announcement->created_at;
                        $postKind = str($announcement->kind ?? 'announcement')->headline();
                    ?>
                    <article class="lms-post-card <?php echo e($announcement->is_pinned ? 'is-pinned' : ''); ?>" data-announcement-card>
                        <header class="lms-post-header">
                            <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $announcement->trainer ?: auth()->user(),'name' => $announcement->trainer?->name ?? auth()->user()?->name ?? 'MCARE Trainer','class' => 'lms-avatar']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($announcement->trainer ?: auth()->user()),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($announcement->trainer?->name ?? auth()->user()?->name ?? 'MCARE Trainer'),'class' => 'lms-avatar']); ?>
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
                            <div class="min-w-0 flex-1">
                                <div class="lms-post-author-line">
                                    <strong><?php echo e($announcement->trainer?->name ?? auth()->user()?->name ?? 'MCARE Trainer'); ?></strong>
                                    <?php if($announcement->is_pinned): ?>
                                        <span class="lms-status-chip is-purple">Pinned</span>
                                    <?php endif; ?>
                                </div>
                                <p><?php echo e($postKind); ?> - <?php echo e($announcement->batch ? $announcement->batch->name.' '.$announcement->batch->year : 'All active classes'); ?> - <?php echo e($postedAt?->diffForHumans() ?? 'Draft'); ?></p>
                            </div>
                        </header>

                        <div class="lms-post-body">
                            <h2><?php echo e($announcement->title); ?></h2>
                            <p><?php echo e($announcement->message); ?></p>
                        </div>

                        <footer class="lms-card-footer">
                            <span class="lms-status-chip <?php echo e($announcement->is_published ? 'is-green' : 'is-neutral'); ?>">
                                <?php echo e($announcement->is_published ? 'Published' : 'Draft'); ?>

                            </span>
                            <div class="lms-card-actions">
                                <details class="lms-inline-editor" data-announcement-editor>
                                    <summary>Edit</summary>
                                    <form method="POST" action="<?php echo e(route('trainer.announcements.update', $announcement)); ?>" class="lms-form-grid">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('PATCH'); ?>
                                        <div class="lms-field">
                                            <label for="announcement-<?php echo e($announcement->id); ?>-batch">Class</label>
                                            <select id="announcement-<?php echo e($announcement->id); ?>-batch" name="training_batch_id">
                                                <option value="">All active classes</option>
                                                <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($batch->id); ?>" <?php if((int) $announcement->training_batch_id === $batch->id): echo 'selected'; endif; ?>><?php echo e($batch->name); ?> <?php echo e($batch->year); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                        <div class="lms-field">
                                            <label for="announcement-<?php echo e($announcement->id); ?>-kind">Post type</label>
                                            <select id="announcement-<?php echo e($announcement->id); ?>-kind" name="kind">
                                                <?php $__currentLoopData = ['announcement' => 'Announcement', 'news' => 'Class news', 'reminder' => 'Reminder']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                    <option value="<?php echo e($value); ?>" <?php if(($announcement->kind ?? 'announcement') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                            </select>
                                        </div>
                                        <div class="lms-field lms-field-wide">
                                            <label for="announcement-<?php echo e($announcement->id); ?>-title">Title</label>
                                            <input id="announcement-<?php echo e($announcement->id); ?>-title" name="title" value="<?php echo e($announcement->title); ?>" maxlength="160" required>
                                        </div>
                                        <div class="lms-field lms-field-wide">
                                            <label for="announcement-<?php echo e($announcement->id); ?>-message">Message</label>
                                            <textarea id="announcement-<?php echo e($announcement->id); ?>-message" name="message" rows="4" maxlength="3000" required><?php echo e($announcement->message); ?></textarea>
                                        </div>
                                        <input type="hidden" name="audience" value="<?php echo e($announcement->audience ?: 'trainees'); ?>">
                                        <div class="lms-form-options lms-field-wide">
                                            <label class="lms-check"><input type="hidden" name="is_pinned" value="0"><input type="checkbox" name="is_pinned" value="1" <?php if($announcement->is_pinned): echo 'checked'; endif; ?>><span>Pin to top</span></label>
                                            <label class="lms-check"><input type="hidden" name="is_published" value="0"><input type="checkbox" name="is_published" value="1" <?php if($announcement->is_published): echo 'checked'; endif; ?>><span>Published</span></label>
                                            <div class="lms-field lms-compact-field">
                                                <label for="announcement-<?php echo e($announcement->id); ?>-posted">Post at</label>
                                                <input id="announcement-<?php echo e($announcement->id); ?>-posted" type="datetime-local" name="posted_at" value="<?php echo e($announcement->posted_at?->format('Y-m-d\TH:i')); ?>">
                                            </div>
                                            <div class="lms-field lms-compact-field">
                                                <label for="announcement-<?php echo e($announcement->id); ?>-expires">Hide after</label>
                                                <input id="announcement-<?php echo e($announcement->id); ?>-expires" type="datetime-local" name="expires_at" value="<?php echo e($announcement->expires_at?->format('Y-m-d\TH:i')); ?>">
                                            </div>
                                        </div>
                                        <div class="lms-form-actions lms-field-wide">
                                            <button type="button" class="secondary-action" data-close-inline-editor>Cancel</button>
                                            <button class="primary-action">Save changes</button>
                                        </div>
                                    </form>
                                </details>
                                <form method="POST" action="<?php echo e(route('trainer.announcements.destroy', $announcement)); ?>" data-confirm="Delete this announcement? Trainees will no longer see it.">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button class="lms-text-action is-danger">Delete</button>
                                </form>
                            </div>
                        </footer>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="lms-empty-state">
                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'bell']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'bell']); ?>
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
                        <h2>No class posts yet</h2>
                        <p>Use the announcement composer to publish the first update for <?php echo e($selectedBatch?->name ?? 'your classes'); ?>.</p>
                    </div>
                <?php endif; ?>
            </section>

            <?php if(method_exists($announcements, 'hasPages') && $announcements->hasPages()): ?>
                <div class="lms-pagination"><?php echo e($announcements->links()); ?></div>
            <?php endif; ?>
        </main>

        <aside class="lms-stream-sidebar" aria-label="Stream overview">
            <section class="lms-side-card">
                <p class="lms-eyebrow">At a glance</p>
                <dl class="lms-stat-list">
                    <div><dt>Classes</dt><dd><?php echo e($batches->count()); ?></dd></div>
                    <div><dt>All posts</dt><dd><?php echo e($stats['total']); ?></dd></div>
                    <div><dt>Published</dt><dd><?php echo e($stats['published']); ?></dd></div>
                    <div><dt>Scheduled</dt><dd><?php echo e($stats['scheduled']); ?></dd></div>
                </dl>
            </section>
            <section class="lms-side-card">
                <h2>Posting guide</h2>
                <ul class="lms-guidance-list">
                    <li>Use announcements for lasting class updates.</li>
                    <li>Use reminders for deadlines and room changes.</li>
                    <li>Pin only the item trainees need first.</li>
                </ul>
            </section>
        </aside>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => 'Class Stream | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\stream.blade.php ENDPATH**/ ?>