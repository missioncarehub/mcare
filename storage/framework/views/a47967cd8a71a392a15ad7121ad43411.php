<?php $__env->startSection('content'); ?>
    <?php
        $batch = $editingBatch;
        $formAction = $batch ? route('admin.batches.update', $batch) : route('admin.batches.store');
        $batchFormHasErrors = collect([
            'training_program_id', 'name', 'year', 'is_active', 'show_on_enrollment_page', 'is_continuous_enrollment', 'enrollment_starts_at', 'enrollment_ends_at',
            'training_starts_at', 'training_ends_at', 'am_days', 'am_start_time',
            'am_end_time', 'am_room', 'pm_days', 'pm_start_time', 'pm_end_time',
            'pm_room', 'trainer_id', 'notes',
        ])->contains(fn (string $field): bool => $errors->has($field));
    ?>

    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-end md:justify-between">
            <p class="text-sm text-slate-600">Create enrollment windows, assign trainers, and manage AM/PM class details.</p>
            <div class="flex flex-wrap items-center gap-2">
                <a href="<?php echo e(route('admin.training-programs.index')); ?>" class="inline-flex w-fit items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Manage programs
                </a>
                <button type="button" data-batch-dialog-open class="inline-flex w-fit items-center justify-center gap-2 rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800">
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
                    Create batch
                </button>
            </div>
        </header>

        <section class="space-y-6">
            <dialog
                id="schedule-editor"
                data-batch-dialog
                data-auto-open="<?php echo e(($batch || $batchFormHasErrors) ? 'true' : 'false'); ?>"
                data-cancel-url="<?php echo e($batch ? route('admin.batches.index') : ''); ?>"
                class="m-auto max-h-[90vh] w-[min(94vw,56rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45"
                aria-labelledby="batch-dialog-title"
            >
                <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
                    <div>
                        <p class="dashboard-section-kicker">Batch scheduling</p>
                        <h2 id="batch-dialog-title" class="mt-1 font-display text-xl font-bold text-slate-900"><?php echo e($batch ? 'Edit batch' : 'Create batch'); ?></h2>
                        <p class="mt-1 text-xs text-slate-500">Enrollment window, class periods, and room assignments.</p>
                    </div>
                    <?php if($batch): ?>
                        <a href="<?php echo e(route('admin.batches.index')); ?>" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900" aria-label="Close batch editor" title="Close">
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
                        <button type="button" data-batch-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900" aria-label="Close batch editor" title="Close">
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

                <div class="px-6 pb-6">
                <form method="POST" action="<?php echo e($formAction); ?>" class="mt-5 space-y-4" data-single-action data-batch-form>
                    <?php echo csrf_field(); ?>
                    <?php if($batch): ?>
                        <?php echo method_field('PATCH'); ?>
                    <?php endif; ?>

                    <label class="flex items-start gap-3 rounded-xl border border-purple-200 bg-purple-50 p-4">
                        <input type="hidden" name="is_continuous_enrollment" value="0">
                        <input type="checkbox" name="is_continuous_enrollment" value="1" <?php if(old('is_continuous_enrollment', $batch?->is_continuous_enrollment ?? true)): echo 'checked'; endif; ?> class="mt-1 rounded border-purple-300 text-purple-700 focus:ring-purple-600">
                        <span><strong class="block text-sm text-purple-950">Continuous enrollment</strong><span class="mt-1 block text-xs leading-5 text-purple-800">Keep this latest active batch open while training is already in progress. New trainees receive only its currently active module.</span></span>
                    </label>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label for="training_program_id" class="mb-1 block text-xs font-semibold text-slate-700">Training program</label>
                            <select id="training_program_id" name="training_program_id" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                <option value="">Select a program</option>
                                <?php $__currentLoopData = $programs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $program): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($program->id); ?>" <?php if((string) old('training_program_id', $batch->training_program_id ?? '') === (string) $program->id): echo 'selected'; endif; ?>><?php echo e($program->name); ?><?php echo e($program->is_active ? '' : ' (inactive)'); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['training_program_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="name" class="mb-1 block text-xs font-semibold text-slate-700">Batch name</label>
                            <input id="name" name="name" type="text" value="<?php echo e(old('name', $batch->name ?? 'Batch 1')); ?>" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="year" class="mb-1 block text-xs font-semibold text-slate-700">Year</label>
                            <input id="year" name="year" type="number" min="2024" max="2100" value="<?php echo e(old('year', $batch->year ?? now()->year)); ?>" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <?php $__errorArgs = ['year'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <label class="flex items-start gap-2.5 rounded-xl border border-slate-200 bg-slate-50 p-3 text-xs font-medium text-slate-700 cursor-pointer">
                            <input name="is_active" type="checkbox" value="1" <?php if(old('is_active', $batch->is_active ?? false)): echo 'checked'; endif; ?> class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                            <span>Enable this batch for enrollment</span>
                        </label>
                        <label class="flex items-start gap-2.5 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-xs font-medium text-emerald-900 cursor-pointer">
                            <input name="show_on_enrollment_page" type="checkbox" value="1" <?php if(old('show_on_enrollment_page', $batch->show_on_enrollment_page ?? false)): echo 'checked'; endif; ?> class="mt-0.5 h-4 w-4 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                            <span><strong class="block">Show on enrollment page</strong><span class="mt-1 block font-normal text-emerald-800">Public only while active and inside its enrollment window.</span></span>
                        </label>
                        <div>
                            <label for="trainer_id" class="mb-1 block text-xs font-semibold text-slate-700">Assigned trainer</label>
                            <select id="trainer_id" name="trainer_id" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                <option value="">Needs trainer assignment</option>
                                <?php $__currentLoopData = $trainers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trainer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($trainer->id); ?>" <?php if((string) old('trainer_id', $batch->trainer_id ?? '') === (string) $trainer->id): echo 'selected'; endif; ?>>
                                        <?php echo e($trainer->name); ?> (<?php echo e($trainer->email); ?>)
                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['trainer_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="enrollment_starts_at" class="mb-1 block text-xs font-semibold text-slate-700">Enrollment starts</label>
                            <input id="enrollment_starts_at" name="enrollment_starts_at" type="datetime-local" value="<?php echo e(old('enrollment_starts_at', $batch?->enrollment_starts_at?->format('Y-m-d\TH:i'))); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <?php $__errorArgs = ['enrollment_starts_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="enrollment_ends_at" class="mb-1 block text-xs font-semibold text-slate-700">Enrollment deadline</label>
                            <input id="enrollment_ends_at" name="enrollment_ends_at" type="datetime-local" value="<?php echo e(old('enrollment_ends_at', $batch?->enrollment_ends_at?->format('Y-m-d\TH:i'))); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <p class="mt-1 text-[11px] text-slate-500">Optional when continuous enrollment is enabled.</p>
                            <?php $__errorArgs = ['enrollment_ends_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="training_starts_at" class="mb-1 block text-xs font-semibold text-slate-700">Training starts</label>
                            <input id="training_starts_at" name="training_starts_at" type="datetime-local" value="<?php echo e(old('training_starts_at', $batch?->training_starts_at?->format('Y-m-d\TH:i'))); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <?php $__errorArgs = ['training_starts_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div>
                            <label for="training_ends_at" class="mb-1 block text-xs font-semibold text-slate-700">Training completes</label>
                            <input id="training_ends_at" name="training_ends_at" type="datetime-local" value="<?php echo e(old('training_ends_at', $batch?->training_ends_at?->format('Y-m-d\TH:i'))); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <?php $__errorArgs = ['training_ends_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-700">AM Class Block</span>
                        <div>
                            <label for="am_days" class="mb-1 block text-xs font-medium text-slate-600">Days (e.g. MWF)</label>
                            <input id="am_days" name="am_days" type="text" value="<?php echo e(old('am_days', $batch->am_days ?? 'MWF')); ?>" required placeholder="MWF" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <?php $__errorArgs = ['am_days'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="am_start_time" class="mb-1 block text-xs font-medium text-slate-600">Starts</label>
                                <input id="am_start_time" name="am_start_time" type="time" value="<?php echo e(old('am_start_time', $batch?->am_start_time ? substr($batch->am_start_time, 0, 5) : '08:00')); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                <?php $__errorArgs = ['am_start_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div>
                                <label for="am_end_time" class="mb-1 block text-xs font-medium text-slate-600">Ends</label>
                                <input id="am_end_time" name="am_end_time" type="time" value="<?php echo e(old('am_end_time', $batch?->am_end_time ? substr($batch->am_end_time, 0, 5) : '12:00')); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                <?php $__errorArgs = ['am_end_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <input name="am_room" type="text" value="<?php echo e(old('am_room', $batch->am_room ?? '')); ?>" placeholder="Room / Skills Lab" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                        <?php $__errorArgs = ['am_room'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 space-y-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-purple-700">PM Class Block</span>
                        <div>
                            <label for="pm_days" class="mb-1 block text-xs font-medium text-slate-600">Days (e.g. TTH)</label>
                            <input id="pm_days" name="pm_days" type="text" value="<?php echo e(old('pm_days', $batch->pm_days ?? 'TTH')); ?>" required placeholder="TTH" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <?php $__errorArgs = ['pm_days'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label for="pm_start_time" class="mb-1 block text-xs font-medium text-slate-600">Starts</label>
                                <input id="pm_start_time" name="pm_start_time" type="time" value="<?php echo e(old('pm_start_time', $batch?->pm_start_time ? substr($batch->pm_start_time, 0, 5) : '13:00')); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                <?php $__errorArgs = ['pm_start_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div>
                                <label for="pm_end_time" class="mb-1 block text-xs font-medium text-slate-600">Ends</label>
                                <input id="pm_end_time" name="pm_end_time" type="time" value="<?php echo e(old('pm_end_time', $batch?->pm_end_time ? substr($batch->pm_end_time, 0, 5) : '17:00')); ?>" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                <?php $__errorArgs = ['pm_end_time'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <input name="pm_room" type="text" value="<?php echo e(old('pm_room', $batch->pm_room ?? '')); ?>" placeholder="Room / Lecture Room" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                        <?php $__errorArgs = ['pm_room'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <p class="mt-1 text-xs font-medium text-red-600"><?php echo e($message); ?></p> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div>
                        <label for="notes" class="mb-1 block text-xs font-semibold text-slate-700">Private admin notes</label>
                        <textarea id="notes" name="notes" rows="2" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600"><?php echo e(old('notes', $batch->notes ?? '')); ?></textarea>
                    </div>

                    <button type="submit" data-action-button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-800">
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
                        <?php echo e($batch ? 'Save batch schedule' : 'Create batch schedule'); ?>

                    </button>

                    <?php if($batch): ?>
                        <a href="<?php echo e(route('admin.batches.index')); ?>" class="block text-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancel edit
                        </a>
                    <?php else: ?>
                        <button type="button" data-batch-dialog-close class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Cancel
                        </button>
                    <?php endif; ?>
                </form>
                </div>
            </dialog>

            <section class="space-y-4">
                <div class="flex items-center justify-between rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="font-display text-xl font-bold text-slate-900">Configured batches</h2>
                        <p class="text-xs text-slate-500">Active batch handles incoming applicant enrollments.</p>
                    </div>
                    <a href="<?php echo e(route('admin.payment-schedules.index')); ?>" class="rounded-xl border border-purple-200 bg-white px-4 py-2 text-xs font-bold text-purple-700 hover:bg-purple-50">
                        Payment scheduling
                    </a>
                </div>

                <?php $__empty_1 = true; $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <article class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm space-y-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h3 class="text-lg font-bold text-slate-900"><?php echo e($item->name); ?> <?php echo e($item->year); ?></h3>
                                    <span class="rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-bold text-indigo-700 ring-1 ring-indigo-100"><?php echo e($item->program?->name ?? 'Program not assigned'); ?></span>
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 <?php echo e($item->acceptsEnrollment() ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-100 text-slate-700 ring-slate-200'); ?>"><?php echo e($item->enrollmentStateLabel()); ?></span>
                                    <span class="rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-bold text-purple-700 ring-1 ring-purple-100"><?php echo e($item->trainingStateLabel()); ?></span>
                                    <span class="rounded-full px-2.5 py-0.5 text-xs font-bold ring-1 <?php echo e($item->show_on_enrollment_page ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100'); ?>"><?php echo e($item->show_on_enrollment_page ? 'Shown publicly' : 'Hidden from enrollment'); ?></span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500"><?php echo e($item->is_continuous_enrollment ? 'No enrollment deadline' : 'Enrollment ends '.$item->enrollment_ends_at?->format('M d, Y g:i A')); ?></p>
                                <p class="mt-1 text-xs font-semibold <?php echo e($item->trainer ? 'text-violet-700' : 'text-amber-700'); ?>">
                                    Trainer: <?php echo e($item->trainer?->name ?? 'Needs assignment'); ?>

                                </p>
                            </div>
                            <div class="flex gap-2">
                                <a href="<?php echo e(route('admin.batches.edit', $item)); ?>" data-dashboard-prefetch class="inline-flex items-center gap-2 rounded-lg border border-purple-200 bg-white px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50">
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
                                <?php ($batchHasRelatedRecords = ($item->applications_count + $item->modules_count + $item->announcements_count + $item->quizzes_count + $item->official_documents_count + $item->document_exports_count) > 0); ?>
                                <form method="POST" action="<?php echo e(route('admin.batches.destroy', $item)); ?>" data-confirm="<?php echo e($batchHasRelatedRecords ? 'This batch has related records and cannot be deleted.' : 'Delete this batch schedule? This cannot be undone.'); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" <?php if($batchHasRelatedRecords): echo 'disabled'; endif; ?> class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40" title="<?php echo e($batchHasRelatedRecords ? 'Delete is disabled while related records exist.' : 'Delete batch schedule'); ?>">
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
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <span class="text-xs font-bold uppercase text-slate-500">AM Class</span>
                                <p class="mt-1 text-sm font-bold text-slate-900"><?php echo e($item->scheduleLabelFor('AM')); ?></p>
                                <p class="text-xs text-slate-500"><?php echo e($item->am_room ?: 'Room TBA'); ?></p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <span class="text-xs font-bold uppercase text-slate-500">PM Class</span>
                                <p class="mt-1 text-sm font-bold text-slate-900"><?php echo e($item->scheduleLabelFor('PM')); ?></p>
                                <p class="text-xs text-slate-500"><?php echo e($item->pm_room ?: 'Room TBA'); ?></p>
                            </div>
                            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                <span class="text-xs font-bold uppercase text-slate-500">Applicants</span>
                                <p class="mt-1 text-xl font-bold text-slate-900"><?php echo e($item->applications_count); ?></p>
                                <?php if($batchHasRelatedRecords): ?><p class="mt-1 text-xs font-semibold text-amber-700">Deletion protected by related records.</p><?php endif; ?>
                            </div>
                        </div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="rounded-xl border border-slate-200 bg-white p-8 text-center shadow-sm">
                        <p class="text-base font-bold text-slate-900">No batch schedules configured yet</p>
                        <p class="mt-1 text-xs text-slate-500">Create a batch schedule to start accepting learner enrollments.</p>
                    </div>
                <?php endif; ?>

                <?php if($batches->hasPages()): ?>
                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                        <?php echo e($batches->links()); ?>

                    </div>
                <?php endif; ?>
            </section>
        </section>
    </div>

    <script>
        (() => {
            const dialog = document.querySelector('[data-batch-dialog]');
            const form = document.querySelector('[data-batch-form]');

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

            document.querySelectorAll('[data-batch-dialog-open]').forEach((button) => {
                button.addEventListener('click', openDialog);
            });

            dialog.querySelectorAll('[data-batch-dialog-close]').forEach((button) => {
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
                    button.textContent = '<?php echo e($batch ? 'Saving changes...' : 'Creating batch...'); ?>';
                });
            });

            if (dialog.dataset.autoOpen === 'true') openDialog();
        })();
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Batches | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\batches\index.blade.php ENDPATH**/ ?>