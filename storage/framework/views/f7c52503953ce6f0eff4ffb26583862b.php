<?php
    $module = $module ?? null;
    $prefix = $prefix ?? 'admin';
    $errorBag = $errorBag ?? 'default';
    $isEdit = $module instanceof \App\Models\TrainingModule;
    $currentTrainerId = (int) old('trainer_id', $isEdit ? $module->trainer_id : 0);
    $currentBatchId = (int) old('training_batch_id', $isEdit ? $module->training_batch_id : 0);
    $currentCategory = old('competency_category', $isEdit ? ($module->competency_category ?: 'custom') : 'custom');
    $currentCompletion = old('completion_mode', $isEdit ? $module->completion_mode : 'assessed');
    $isPublished = filter_var(old('is_published', $isEdit ? $module->is_published : true), FILTER_VALIDATE_BOOLEAN);
    $lockUntilPrevious = filter_var(old('lock_until_previous', $isEdit ? $module->locksUntilPreviousFinished() : true), FILTER_VALIDATE_BOOLEAN);
    $submoduleTitles = old(
        'submodule_titles',
        $isEdit ? ($module->submodules->pluck('title')->filter()->values()->all() ?: ['']) : ['']
    );
    $previewKey = $prefix.'-module-file-'.($isEdit ? $module->id : 'new');
?>

<form
    method="POST"
    action="<?php echo e($isEdit ? route('admin.learning.modules.update', $module) : route('admin.learning.modules.store')); ?>"
    enctype="multipart/form-data"
    class="lms-composer-form grid gap-4 md:grid-cols-2 xl:grid-cols-4"
    data-dashboard-dialog-form
    data-module-preset-form
    data-submit-label="<?php echo e($isEdit ? 'Saving module...' : 'Adding module...'); ?>"
>
    <?php echo csrf_field(); ?>
    <?php if($isEdit): ?>
        <?php echo method_field('PATCH'); ?>
        <input type="hidden" name="_editing_module_id" value="<?php echo e($module->id); ?>">
    <?php endif; ?>

    <div class="md:col-span-2 xl:col-span-4">
        <label class="mb-2 block text-xs font-bold uppercase text-purple-800">Caregiving NC II Course Module Preset</label>
        <?php if (isset($component)) { $__componentOriginal13b067d034ee4037fee6975296e4467d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal13b067d034ee4037fee6975296e4467d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.module-preset-select','data' => ['id' => $prefix.'-module-preset-select','units' => $trainerCatalogUnits,'dataRole' => 'module-preset','class' => 'form-field border-purple-300 focus:border-purple-600']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('module-preset-select'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['id' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($prefix.'-module-preset-select'),'units' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trainerCatalogUnits),'data-role' => 'module-preset','class' => 'form-field border-purple-300 focus:border-purple-600']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal13b067d034ee4037fee6975296e4467d)): ?>
<?php $attributes = $__attributesOriginal13b067d034ee4037fee6975296e4467d; ?>
<?php unset($__attributesOriginal13b067d034ee4037fee6975296e4467d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal13b067d034ee4037fee6975296e4467d)): ?>
<?php $component = $__componentOriginal13b067d034ee4037fee6975296e4467d; ?>
<?php unset($__componentOriginal13b067d034ee4037fee6975296e4467d); ?>
<?php endif; ?>
        <small class="mt-1 block text-xs text-slate-500">This list is the same database catalog trainers use. Choosing a preset fills module code, title, hours, and every official learning outcome.</small>
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Trainer</label>
        <select name="trainer_id" class="form-field" required>
            <option value="">Select trainer</option>
            <?php $__currentLoopData = $trainers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trainer): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($trainer->id); ?>" <?php if($currentTrainerId === $trainer->id): echo 'selected'; endif; ?>><?php echo e($trainer->name); ?> · <?php echo e($trainer->email); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['trainer_id', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch Assignment</label>
        <select name="training_batch_id" class="form-field" required>
            <option value="">Select batch</option>
            <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <option value="<?php echo e($batch->id); ?>" <?php if($currentBatchId === $batch->id): echo 'selected'; endif; ?>><?php echo e($batch->name); ?> <?php echo e($batch->year); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['training_batch_id', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Module Code</label>
        <input id="<?php echo e($prefix); ?>-module-code" data-role="module-code" name="module_code" value="<?php echo e(old('module_code', $isEdit ? $module->module_code : '')); ?>" class="form-field font-mono font-bold" placeholder="e.g. HCS323301">
        <?php $__errorArgs = ['module_code', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Competency Category</label>
        <select id="<?php echo e($prefix); ?>-module-category" data-role="module-category" name="competency_category" class="form-field">
            <option value="custom" <?php if($currentCategory === 'custom'): echo 'selected'; endif; ?>>Institutional / Custom</option>
            <option value="core" <?php if($currentCategory === 'core'): echo 'selected'; endif; ?>>Core Competency</option>
            <option value="common" <?php if($currentCategory === 'common'): echo 'selected'; endif; ?>>Common Competency</option>
            <option value="basic" <?php if($currentCategory === 'basic'): echo 'selected'; endif; ?>>Basic Competency</option>
        </select>
        <?php $__errorArgs = ['competency_category', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div class="md:col-span-2">
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Module Requirement</label>
        <select name="completion_mode" class="form-field" required>
            <option value="assessed" <?php if($currentCompletion === 'assessed'): echo 'selected'; endif; ?>>Assessed module — classwork and trainer evaluation required</option>
            <option value="material_only" <?php if($currentCompletion === 'material_only'): echo 'selected'; endif; ?>>Learning material only — no Mark as Done</option>
        </select>
        <p class="mt-1 text-xs text-slate-500">Material-only modules remain readable but do not create a competency completion requirement.</p>
        <?php $__errorArgs = ['completion_mode', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Sub-topic / Learning Outcome</label>
        <input id="<?php echo e($prefix); ?>-module-topic" data-role="module-topic" name="topic" list="<?php echo e($prefix); ?>-subtopics-list" value="<?php echo e(old('topic', $isEdit ? $module->topic : '')); ?>" class="form-field" placeholder="e.g. Comfort infants and toddlers">
        <datalist id="<?php echo e($prefix); ?>-subtopics-list" data-role="module-subtopics"></datalist>
        <?php $__errorArgs = ['topic', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Nominal Hours</label>
        <input id="<?php echo e($prefix); ?>-module-hours" data-role="module-hours" name="estimated_hours" type="number" min="1" max="500" value="<?php echo e(old('estimated_hours', $isEdit ? $module->estimated_hours : '')); ?>" class="form-field" placeholder="e.g. 40">
        <?php $__errorArgs = ['estimated_hours', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div class="md:col-span-2 xl:col-span-4" data-submodule-builder>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Submodules / Competency Outcomes</label>
        <p class="mb-2 text-xs text-slate-500">Choosing a course preset loads every official outcome for that unit, the same way the trainer classwork composer does. For a custom assessed module, enter each outcome to evaluate separately.</p>
        <div class="space-y-2" data-role="module-submodule-list">
            <?php $__currentLoopData = $submoduleTitles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $submoduleTitle): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <input name="submodule_titles[]" value="<?php echo e($submoduleTitle); ?>" maxlength="255" class="form-field" placeholder="Submodule or competency outcome">
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
        <button type="button" class="secondary-action mt-2 text-xs" data-role="module-add-submodule">Add custom submodule</button>
        <?php $__errorArgs = ['submodule_titles', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        <?php $__errorArgs = ['submodule_titles.*', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div class="md:col-span-2 xl:col-span-4">
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Module Title</label>
        <input id="<?php echo e($prefix); ?>-module-title" data-role="module-title" name="title" value="<?php echo e(old('title', $isEdit ? $module->title : '')); ?>" class="form-field" required placeholder="e.g. Provide Care and Support to Infants and Toddlers">
        <?php $__errorArgs = ['title', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div class="md:col-span-2 xl:col-span-3">
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Instructions & Description</label>
        <textarea name="description" rows="3" class="form-field" required placeholder="Overview of the core module, competencies, and learning instructions."><?php echo e(old('description', $isEdit ? $module->description : '')); ?></textarea>
        <?php $__errorArgs = ['description', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500"><?php echo e(\App\Support\TrainingModuleFiles::humanLabel()); ?></label>
        <input name="module_file" type="file" accept="<?php echo e(\App\Support\TrainingModuleFiles::acceptAttribute()); ?>" class="form-field" <?php if(! $isEdit): echo 'required'; endif; ?> data-file-preview-input="<?php echo e($previewKey); ?>" data-watermark-preview-url="<?php echo e(route('admin.learning.modules.preview-watermark')); ?>">
        <?php if($isEdit && filled($module->original_file_name)): ?>
            <p class="mt-1 text-xs text-slate-500">Current file: <?php echo e($module->original_file_name); ?>. Leave empty to keep it.</p>
        <?php endif; ?>
        
        <div class="mt-3 hidden rounded-xl border border-slate-200 bg-slate-50 p-3" data-file-preview-panel="<?php echo e($previewKey); ?>">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs font-semibold text-slate-500">Preview</p>
                    <p class="mt-0.5 truncate text-sm font-bold text-slate-900" data-file-preview-name>—</p>
                    <p class="text-[11px] text-slate-500" data-file-preview-size></p>
                </div>
                <button type="button" class="secondary-action text-xs" data-file-preview-remove="<?php echo e($previewKey); ?>">Remove / replace</button>
            </div>
            <div class="mt-3" data-file-preview-body></div>
        </div>
        <?php $__errorArgs = ['module_file', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <div class="md:col-span-2 xl:col-span-4">
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Supplementary Handouts (Optional)</label>
        <input name="supplementary_files[]" type="file" multiple accept="<?php echo e(\App\Support\TrainingModuleFiles::acceptAttribute()); ?>" class="form-field">
        <p class="mt-1 text-xs text-slate-500">
            <?php if($isEdit && count($module->supplementaryList()) > 0): ?>
                <?php echo e(count($module->supplementaryList())); ?> supplementary file(s) already attached.
            <?php endif; ?>
            Up to <?php echo e(\App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_FILES); ?> files, <?php echo e(number_format(\App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_UPLOAD_KB / 1024)); ?> MB each.
        </p>
        <?php $__errorArgs = ['supplementary_files', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        <?php $__errorArgs = ['supplementary_files.*', $errorBag];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>

    <label class="flex items-center gap-3 text-sm font-semibold text-slate-700">
        <input type="hidden" name="is_published" value="0">
        <input name="is_published" type="checkbox" value="1" <?php if($isPublished): echo 'checked'; endif; ?>> Publish as the current active batch module
    </label>

    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 md:col-span-2 xl:col-span-3">
        <label class="flex items-start gap-3 text-sm font-semibold text-slate-800">
            <input type="hidden" name="lock_until_previous" value="0">
            <input name="lock_until_previous" type="checkbox" value="1" class="mt-1" <?php if($lockUntilPrevious): echo 'checked'; endif; ?>>
            <span>
                Lock this module until earlier modules are finished
                <span class="mt-1 block text-xs font-normal leading-5 text-slate-600">
                    Checked: trainees cannot open this module until they finish the modules before it. The first module in the batch stays open on its own.
                    Unchecked: trainees can open this module even when an earlier module is still unfinished. Later modules stay locked when their own lock is checked.
                </span>
            </span>
        </label>
    </div>

    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 md:col-span-2 md:flex-row md:justify-end xl:col-span-3">
        <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
        <button type="submit" class="primary-action" data-action-button><?php echo e($isEdit ? 'Save changes' : 'Add module'); ?></button>
    </div>
</form>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\learning\partials\module-composer-form.blade.php ENDPATH**/ ?>