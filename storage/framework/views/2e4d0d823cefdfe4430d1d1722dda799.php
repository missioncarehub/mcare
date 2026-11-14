<?php $__env->startSection('content'); ?>
    <?php
        $update = $editingUpdate;
        $formHasErrors = $errors->publicUpdate->any();
        $formAction = $update ? route('admin.public-settings.update', $update) : route('admin.public-settings.store');
        $useOld = $formHasErrors;
        $socialHasErrors = $errors->publicSocial->any();
        $socialUseOld = $socialHasErrors;
        $socialFacebook = $socialUseOld ? old('social_facebook_url') : ($siteSettings->facebook_url ?? '');
        $socialInstagram = $socialUseOld ? old('social_instagram_url') : ($siteSettings->instagram_url ?? '');
        $socialYoutube = $socialUseOld ? old('social_youtube_url') : ($siteSettings->youtube_url ?? '');
    ?>

    <style>
        .public-update-dialog {
            width: min(96vw, 64rem);
            max-width: 64rem;
            max-height: 92vh;
            overflow: hidden;
        }
        .public-update-dialog[open] {
            display: flex;
            flex-direction: column;
        }
        .public-update-dialog > form {
            min-height: 0;
            overflow: auto;
        }
        .public-update-layout {
            display: grid;
            grid-template-columns: minmax(18rem, 1fr) minmax(18rem, 1fr);
            gap: 1.25rem;
        }
        @media (max-width: 860px) {
            .public-update-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-end md:justify-between">
            <p class="max-w-3xl text-sm text-slate-600">Manage landing-page social links and Facebook cards. Saved social URLs appear in the public footer. The Discover row shows the first 3 published Facebook updates.</p>
            <button type="button" data-public-update-dialog-open class="inline-flex w-fit items-center justify-center gap-2 rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800">
                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'plus','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'plus','class' => 'h-4 w-4']); ?>
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
                Add Facebook update
            </button>
        </header>

        <section id="social-links" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-display text-lg font-bold text-slate-900">Social media links</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">These URLs appear as Facebook, Instagram, and YouTube icons in the landing page footer. Leave a field blank to hide that icon.</p>
                </div>
            </div>
            <form method="POST" action="<?php echo e(route('admin.public-settings.social')); ?>" class="space-y-4 p-5" data-single-action>
                <?php echo csrf_field(); ?>
                <?php echo method_field('PATCH'); ?>
                <div class="grid gap-4 md:grid-cols-3">
                    <div>
                        <label for="social_facebook_url" class="mb-1 block text-xs font-semibold text-slate-700">Facebook</label>
                        <input id="social_facebook_url" name="social_facebook_url" type="url" value="<?php echo e($socialFacebook); ?>" maxlength="500" placeholder="https://www.facebook.com/your-page" class="form-field">
                        <?php $__errorArgs = ['social_facebook_url', 'publicSocial'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="social_instagram_url" class="mb-1 block text-xs font-semibold text-slate-700">Instagram</label>
                        <input id="social_instagram_url" name="social_instagram_url" type="url" value="<?php echo e($socialInstagram); ?>" maxlength="500" placeholder="https://www.instagram.com/your-profile" class="form-field">
                        <?php $__errorArgs = ['social_instagram_url', 'publicSocial'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="social_youtube_url" class="mb-1 block text-xs font-semibold text-slate-700">YouTube</label>
                        <input id="social_youtube_url" name="social_youtube_url" type="url" value="<?php echo e($socialYoutube); ?>" maxlength="500" placeholder="https://www.youtube.com/@your-channel" class="form-field">
                        <?php $__errorArgs = ['social_youtube_url', 'publicSocial'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" data-action-button class="primary-action inline-flex items-center justify-center gap-2">
                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'save','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'save','class' => 'h-4 w-4']); ?>
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
                        Save social links
                    </button>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-display text-lg font-bold text-slate-900">Landing page cards</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Paste a public Facebook link. MCARE embeds it on the Discover section.</p>
                </div>
                <span class="w-fit rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700"><?php echo e($updates->where('is_published', true)->count()); ?> published</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">Update</th>
                            <th class="px-4 py-3">Facebook link</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php $__empty_1 = true; $__currentLoopData = $updates; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-700"><?php echo e($item->position); ?></td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900"><?php echo e($item->title); ?></p>
                                    <?php if($item->description): ?>
                                        <p class="mt-1 max-w-xs text-xs text-slate-500 line-clamp-2"><?php echo e($item->description); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <a href="<?php echo e($item->facebook_url); ?>" target="_blank" rel="noopener noreferrer" class="break-all text-xs font-semibold text-purple-700 hover:text-purple-800"><?php echo e($item->facebook_url); ?></a>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 <?php echo e($item->is_published ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-100 text-slate-600 ring-slate-200'); ?>">
                                        <?php echo e($item->is_published ? 'Published' : 'Hidden'); ?>

                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="<?php echo e(route('admin.public-settings.edit', $item)); ?>" data-dashboard-prefetch class="inline-flex items-center gap-2 rounded-lg border border-purple-200 bg-white px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50">
                                            <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'pencil','class' => 'h-3.5 w-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'pencil','class' => 'h-3.5 w-3.5']); ?>
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
                                            Edit
                                        </a>
                                        <form method="POST" action="<?php echo e(route('admin.public-settings.destroy', $item)); ?>" data-confirm="Remove this public update from the landing page?">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50">
                                                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'trash-2','class' => 'h-3.5 w-3.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash-2','class' => 'h-3.5 w-3.5']); ?>
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
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="5" class="px-4 py-14 text-center text-slate-500">No Facebook updates yet. Add a public link to show it on the landing page.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <dialog
        id="public-update-editor"
        data-public-update-dialog
        data-auto-open="<?php echo e(($update || $formHasErrors) ? 'true' : 'false'); ?>"
        data-cancel-url="<?php echo e($update ? route('admin.public-settings.index') : ''); ?>"
        class="public-update-dialog m-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45"
        aria-labelledby="public-update-dialog-title"
    >
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div>
                <p class="dashboard-section-kicker">Landing page</p>
                <h2 id="public-update-dialog-title" class="mt-1 font-display text-xl font-bold text-slate-900"><?php echo e($update ? 'Edit public update' : 'Add Facebook update'); ?></h2>
                <p class="mt-1 text-xs text-slate-500">Use a public Facebook post, video, or reel URL from the official MCARE page.</p>
            </div>
            <?php if($update): ?>
                <a href="<?php echo e(route('admin.public-settings.index')); ?>" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900" aria-label="Close update editor" title="Close">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark','class' => 'h-4 w-4']); ?>
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
                </a>
            <?php else: ?>
                <button type="button" data-public-update-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900" aria-label="Close update editor" title="Close">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark','class' => 'h-4 w-4']); ?>
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
                </button>
            <?php endif; ?>
        </div>

        <form method="POST" action="<?php echo e($formAction); ?>" class="p-6" data-single-action data-public-update-form data-saving-label="<?php echo e($update ? 'Saving changes...' : 'Saving Facebook update...'); ?>">
            <?php echo csrf_field(); ?>
            <?php if($update): ?>
                <?php echo method_field('PATCH'); ?>
            <?php endif; ?>

            <div class="public-update-layout">
                <div class="space-y-4">
                    <div>
                        <label for="update_title" class="mb-1 block text-xs font-semibold text-slate-700">Card title</label>
                        <input id="update_title" name="title" value="<?php echo e($useOld ? old('title') : ($update->title ?? '')); ?>" required maxlength="160" placeholder="Training highlights" class="form-field">
                        <?php $__errorArgs = ['title', 'publicUpdate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="update_description" class="mb-1 block text-xs font-semibold text-slate-700">Short description</label>
                        <textarea id="update_description" name="description" rows="4" maxlength="500" placeholder="Optional caption shown under the Facebook embed" class="form-field"><?php echo e($useOld ? old('description') : ($update->description ?? '')); ?></textarea>
                        <?php $__errorArgs = ['description', 'publicUpdate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div>
                        <label for="update_position" class="mb-1 block text-xs font-semibold text-slate-700">Display order</label>
                        <input id="update_position" name="position" type="number" min="1" max="99" required value="<?php echo e($useOld ? old('position', $nextPosition) : ($update->position ?? $nextPosition)); ?>" class="form-field">
                        <?php $__errorArgs = ['position', 'publicUpdate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="update_facebook_url" class="mb-1 block text-xs font-semibold text-slate-700">Facebook link</label>
                        <input id="update_facebook_url" name="facebook_url" type="url" value="<?php echo e($useOld ? old('facebook_url') : ($update->facebook_url ?? '')); ?>" required maxlength="500" placeholder="https://www.facebook.com/..." class="form-field">
                        <p class="mt-1 text-xs leading-5 text-slate-500">Copy the public post or video URL from Facebook. Private posts will not embed.</p>
                        <?php $__errorArgs = ['facebook_url', 'publicUpdate'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <label class="flex items-start gap-3 rounded-lg border border-purple-100 bg-purple-50 p-4 text-sm text-slate-700">
                        <input type="checkbox" name="is_published" value="1" <?php if($useOld ? old('is_published', true) : ($update->is_published ?? true)): echo 'checked'; endif; ?> class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-500">
                        <span>
                            <span class="block font-bold text-slate-900">Publish on landing page</span>
                            <span class="mt-1 block leading-5">Uncheck to keep this link saved without showing it publicly.</span>
                        </span>
                    </label>
                </div>
            </div>

            <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                <?php if($update): ?>
                    <a href="<?php echo e(route('admin.public-settings.index')); ?>" class="secondary-action text-center">Cancel</a>
                <?php else: ?>
                    <button type="button" data-public-update-dialog-close class="secondary-action">Cancel</button>
                <?php endif; ?>
                <button type="submit" data-action-button class="primary-action inline-flex items-center justify-center gap-2">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'save','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'save','class' => 'h-4 w-4']); ?>
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
                    <?php echo e($update ? 'Save update' : 'Save Facebook update'); ?>

                </button>
            </div>
        </form>
    </dialog>

    <script>
        (() => {
            const dialog = document.querySelector('[data-public-update-dialog]');
            const form = document.querySelector('[data-public-update-form]');
            if (!dialog || !form) return;

            const openDialog = () => {
                if (!dialog.open) dialog.showModal();
                requestAnimationFrame(() => form.querySelector('input:not([type="hidden"])')?.focus());
            };

            const closeDialog = () => {
                if (dialog.dataset.cancelUrl) {
                    window.location.assign(dialog.dataset.cancelUrl);
                    return;
                }
                dialog.close();
            };

            document.querySelectorAll('[data-public-update-dialog-open]').forEach((button) => {
                button.addEventListener('click', openDialog);
            });
            dialog.querySelectorAll('[data-public-update-dialog-close]').forEach((button) => {
                button.addEventListener('click', closeDialog);
            });
            dialog.addEventListener('cancel', (event) => {
                if (!dialog.dataset.cancelUrl) return;
                event.preventDefault();
                closeDialog();
            });
            dialog.addEventListener('click', (event) => {
                const bounds = dialog.getBoundingClientRect();
                const outside = event.clientX < bounds.left || event.clientX > bounds.right
                    || event.clientY < bounds.top || event.clientY > bounds.bottom;
                if (outside) closeDialog();
            });
            form.addEventListener('submit', () => {
                form.querySelectorAll('[data-action-button]').forEach((button) => {
                    button.disabled = true;
                    button.classList.add('cursor-not-allowed', 'opacity-70');
                    button.textContent = form.dataset.savingLabel || 'Saving...';
                });
            });
            if (dialog.dataset.autoOpen === 'true') openDialog();
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Public Settings | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\public-updates\index.blade.php ENDPATH**/ ?>