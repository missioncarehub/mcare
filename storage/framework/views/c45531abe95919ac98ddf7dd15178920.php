<?php $__env->startSection('content'); ?>
<?php
    $catalogUnits = $catalogUnits ?? collect();
    $catalogOutcomes = $catalogOutcomes ?? collect();
    $trainerCatalogUnits = $trainerCatalogUnits ?? $catalogUnits->where('is_selectable', true);
    $editingPreset = $editingPreset ?? null;
    $editingOutcome = $editingOutcome ?? null;
    $catalogErrors = $errors->catalog ?? null;
    $outcomeErrors = $errors->catalogOutcome ?? null;
    $openPresetDialog = ($catalogErrors && $catalogErrors->any()) || $editingPreset;
    $openOutcomeDialog = ($outcomeErrors && $outcomeErrors->any()) || $editingOutcome;
    $presetOutcomesText = old('outcomes', $editingPreset?->outcomes?->pluck('title')->implode("\n") ?? '');
    $selectableChecked = filter_var(old('is_selectable', $editingPreset?->is_selectable ?? true), FILTER_VALIDATE_BOOLEAN);
    $torChecked = filter_var(old('is_tor_included', $editingPreset?->is_tor_included ?? false), FILTER_VALIDATE_BOOLEAN);
    $outcomeRequiredChecked = filter_var(old('is_required', $editingOutcome?->is_required ?? true), FILTER_VALIDATE_BOOLEAN);
    $activeTab = $activeTab ?? 'modules';
    $isUnitsTab = $activeTab === 'units';
    $isOutcomesTab = $activeTab === 'outcomes';
    $selectedOutcomeUnitId = (string) old('competency_unit_id', $editingOutcome?->competency_unit_id ?? ($filters['unit_id'] ?? ''));
?>
<section class="space-y-6">
    <header class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <p class="max-w-3xl text-sm leading-6 text-slate-600">
            <?php if($isUnitsTab): ?>
                Add, edit, or delete Caregiving NC II competency units. Selectable units appear when trainers create classwork.
            <?php elseif($isOutcomesTab): ?>
                Add, edit, or delete learning outcomes for each unit. These titles become suggested submodules in classwork.
            <?php else: ?>
                Upload Caregiving NC II core modules on behalf of a trainer, assign them to specific batches, and manage learning codes and subtopics.
            <?php endif; ?>
        </p>
        <?php if($isUnitsTab): ?>
            <button type="button" class="primary-action" data-dashboard-dialog-open="admin-preset-dialog">Add unit</button>
        <?php elseif($isOutcomesTab): ?>
            <button type="button" class="primary-action" data-dashboard-dialog-open="admin-outcome-dialog">Add outcome</button>
        <?php else: ?>
            <button type="button" class="primary-action" data-dashboard-dialog-open="admin-module-dialog"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
<?php endif; ?>Add module</button>
        <?php endif; ?>
    </header>

    <nav class="lms-context-tabs" aria-label="LMS module sections">
        <a href="<?php echo e(route('admin.learning.modules')); ?>" class="<?php echo e($isUnitsTab || $isOutcomesTab ? '' : 'is-active'); ?>" <?php if (! ($isUnitsTab || $isOutcomesTab)): ?> aria-current="page" <?php endif; ?>>Learning modules</a>
        <a href="<?php echo e(route('admin.learning.modules', ['tab' => 'units'])); ?>" class="<?php echo e($isUnitsTab ? 'is-active' : ''); ?>" <?php if($isUnitsTab): ?> aria-current="page" <?php endif; ?>>Units</a>
        <a href="<?php echo e(route('admin.learning.modules', ['tab' => 'outcomes'])); ?>" class="<?php echo e($isOutcomesTab ? 'is-active' : ''); ?>" <?php if($isOutcomesTab): ?> aria-current="page" <?php endif; ?>>Outcomes</a>
    </nav>

    <?php if($isUnitsTab): ?>
    <section class="dashboard-table-wrap overflow-x-auto" aria-labelledby="catalog-units-title">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="catalog-units-title" class="text-base font-bold text-slate-950">Competency units</h2>
                <p class="mt-1 text-sm text-slate-600">Trainers pick selectable units when creating classwork. Units already used by a module or trainee record cannot be deleted.</p>
            </div>
        </div>
        <table class="dashboard-table w-full min-w-[52rem]">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Hours</th>
                    <th>Trainer dropdown</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $catalogUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $unitInUse = ((int) ($unit->training_modules_count ?? 0) + (int) ($unit->trainee_records_count ?? 0)) > 0;
                    ?>
                    <tr>
                        <td class="font-mono text-xs font-bold"><?php echo e($unit->code); ?></td>
                        <td>
                            <p class="font-semibold text-slate-950"><?php echo e($unit->title); ?></p>
                            <p class="mt-0.5 text-xs text-slate-500"><?php echo e($unit->outcomes->count()); ?> <?php echo e(\Illuminate\Support\Str::plural('outcome', $unit->outcomes->count())); ?></p>
                        </td>
                        <td><?php echo e(\App\Models\CompetencyUnit::categoryLabels()[$unit->category] ?? $unit->category); ?></td>
                        <td><?php echo e($unit->suggestedHours()); ?></td>
                        <td><?php echo e($unit->is_selectable ? 'Available' : 'Hidden'); ?></td>
                        <td>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="<?php echo e(route('admin.learning.modules', ['tab' => 'outcomes', 'unit_id' => $unit->id])); ?>" class="secondary-action !min-h-9 !px-3 text-xs">Outcomes</a>
                                <a href="<?php echo e(route('admin.learning.modules', ['tab' => 'units', 'edit_unit' => $unit->id])); ?>" class="secondary-action !min-h-9 !px-3 text-xs">Edit</a>
                                <form method="POST" action="<?php echo e(route('admin.learning.modules.presets.destroy', $unit)); ?>" data-confirm="<?php echo e($unitInUse ? 'This unit is in use and cannot be deleted.' : 'Delete unit '.$unit->code.' and its unused outcomes?'); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="secondary-action !min-h-9 !px-3 text-xs border-red-200 text-red-700 hover:border-red-300 hover:bg-red-50" <?php if($unitInUse): echo 'disabled'; endif; ?>>Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="py-6 text-sm text-slate-600">No competency units are stored yet. Add a unit to populate the trainer dropdown.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <?php if($isOutcomesTab): ?>
    <section class="dashboard-table-wrap overflow-x-auto" aria-labelledby="catalog-outcomes-title">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="catalog-outcomes-title" class="text-base font-bold text-slate-950">Competency outcomes</h2>
                <p class="mt-1 text-sm text-slate-600">Each outcome belongs to a unit and can become a classwork submodule. Outcomes already used in classwork or trainee results cannot be deleted.</p>
            </div>
            <form method="GET" class="flex flex-wrap items-end gap-2">
                <input type="hidden" name="tab" value="outcomes">
                <div>
                    <label class="mb-1 block text-[11px] font-bold uppercase text-slate-500" for="outcome-unit-filter">Unit</label>
                    <select id="outcome-unit-filter" name="unit_id" class="form-field min-w-[16rem]" onchange="this.form.submit()">
                        <option value="">All units</option>
                        <?php $__currentLoopData = $catalogUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($unit->id); ?>" <?php if((string) ($filters['unit_id'] ?? '') === (string) $unit->id): echo 'selected'; endif; ?>><?php echo e($unit->code); ?> — <?php echo e($unit->title); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
            </form>
        </div>
        <table class="dashboard-table w-full min-w-[52rem]">
            <thead>
                <tr>
                    <th>Unit</th>
                    <th>Outcome</th>
                    <th>Order</th>
                    <th>Required</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $catalogOutcomes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $outcome): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $outcomeInUse = ((int) ($outcome->trainee_results_count ?? 0) + (int) ($outcome->submodules_count ?? 0)) > 0;
                    ?>
                    <tr>
                        <td>
                            <p class="font-mono text-xs font-bold"><?php echo e($outcome->unit?->code); ?></p>
                            <p class="text-xs text-slate-500"><?php echo e($outcome->unit?->title); ?></p>
                        </td>
                        <td class="font-semibold text-slate-950"><?php echo e($outcome->title); ?></td>
                        <td><?php echo e($outcome->sort_order); ?></td>
                        <td><?php echo e($outcome->is_required ? 'Yes' : 'No'); ?></td>
                        <td>
                            <div class="flex flex-wrap items-center gap-2">
                                <a href="<?php echo e(route('admin.learning.modules', ['tab' => 'outcomes', 'unit_id' => $outcome->competency_unit_id, 'edit_outcome' => $outcome->id])); ?>" class="secondary-action !min-h-9 !px-3 text-xs">Edit</a>
                                <form method="POST" action="<?php echo e(route('admin.learning.modules.outcomes.destroy', $outcome)); ?>" data-confirm="<?php echo e($outcomeInUse ? 'This outcome is in use and cannot be deleted.' : 'Delete this outcome?'); ?>">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="secondary-action !min-h-9 !px-3 text-xs border-red-200 text-red-700 hover:border-red-300 hover:bg-red-50" <?php if($outcomeInUse): echo 'disabled'; endif; ?>>Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="5" class="py-6 text-sm text-slate-600">No competency outcomes match this filter. Add an outcome or choose another unit.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
    <?php endif; ?>

    <dialog id="admin-preset-dialog" data-dashboard-dialog data-auto-open="<?php echo e($openPresetDialog ? 'true' : 'false'); ?>" class="lms-workflow-dialog" aria-labelledby="admin-preset-dialog-title">
        <div class="lms-dialog-header">
            <div>
                <p class="lms-eyebrow">Admin LMS catalog</p>
                <h2 id="admin-preset-dialog-title"><?php echo e($editingPreset ? 'Edit competency unit' : 'Add competency unit'); ?></h2>
                <p>These records are stored in competency units and shown to trainers when they create classwork.</p>
            </div>
            <button type="button" data-dashboard-dialog-close class="lms-dialog-close" aria-label="Close course preset editor"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></button>
        </div>
        <form
            method="POST"
            action="<?php echo e($editingPreset ? route('admin.learning.modules.presets.update', $editingPreset) : route('admin.learning.modules.presets.store')); ?>"
            class="lms-composer-form grid gap-4 md:grid-cols-2"
            data-dashboard-dialog-form
            data-submit-label="<?php echo e($editingPreset ? 'Saving preset...' : 'Saving preset...'); ?>"
        >
            <?php echo csrf_field(); ?>
            <?php if($editingPreset): ?>
                <?php echo method_field('PATCH'); ?>
            <?php endif; ?>
            <div>
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="preset-category">Category</label>
                <select id="preset-category" name="category" class="form-field" required>
                    <?php $__currentLoopData = \App\Models\CompetencyUnit::categoryLabels(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($value); ?>" <?php if(old('category', $editingPreset?->category) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['category', 'catalog'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="preset-code">Module code</label>
                <input id="preset-code" name="code" value="<?php echo e(old('code', $editingPreset?->code)); ?>" class="form-field font-mono font-bold" maxlength="40" required placeholder="e.g. HCS323301">
                <?php $__errorArgs = ['code', 'catalog'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="md:col-span-2">
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="preset-title">Title</label>
                <input id="preset-title" name="title" value="<?php echo e(old('title', $editingPreset?->title)); ?>" class="form-field" maxlength="255" required>
                <?php $__errorArgs = ['title', 'catalog'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="preset-hours">Nominal hours</label>
                <input id="preset-hours" name="estimated_hours" type="number" min="1" max="500" value="<?php echo e(old('estimated_hours', $editingPreset?->estimated_hours)); ?>" class="form-field" placeholder="e.g. 40">
                <?php $__errorArgs = ['estimated_hours', 'catalog'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="flex flex-col justify-end gap-2 pb-1">
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="is_selectable" value="0">
                    <input type="checkbox" name="is_selectable" value="1" <?php if($selectableChecked): echo 'checked'; endif; ?>>
                    Show this preset in the trainer dropdown
                </label>
                <label class="flex items-center gap-2 text-sm text-slate-700">
                    <input type="hidden" name="is_tor_included" value="0">
                    <input type="checkbox" name="is_tor_included" value="1" <?php if($torChecked): echo 'checked'; endif; ?>>
                    Include on TESDA TOR
                </label>
            </div>
            <div class="md:col-span-2">
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="preset-outcomes">Subtopics / learning outcomes</label>
                <textarea id="preset-outcomes" name="outcomes" rows="6" class="form-field" placeholder="One outcome per line"><?php echo e($presetOutcomesText); ?></textarea>
                <p class="mt-1 text-xs text-slate-500">Each line becomes a suggested submodule when a trainer selects this preset.</p>
                <?php $__errorArgs = ['outcomes', 'catalog'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div class="md:col-span-2 flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                <button type="submit" class="primary-action"><?php echo e($editingPreset ? 'Save unit' : 'Save unit'); ?></button>
            </div>
        </form>
    </dialog>

    <dialog id="admin-outcome-dialog" data-dashboard-dialog data-auto-open="<?php echo e($openOutcomeDialog ? 'true' : 'false'); ?>" class="lms-workflow-dialog is-compact" aria-labelledby="admin-outcome-dialog-title">
        <div class="lms-dialog-header">
            <div>
                <p class="lms-eyebrow">Admin LMS catalog</p>
                <h2 id="admin-outcome-dialog-title"><?php echo e($editingOutcome ? 'Edit competency outcome' : 'Add competency outcome'); ?></h2>
                <p>Each outcome belongs to one unit and can be used as a classwork submodule.</p>
            </div>
            <button type="button" data-dashboard-dialog-close class="lms-dialog-close" aria-label="Close outcome editor"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></button>
        </div>
        <form
            method="POST"
            action="<?php echo e($editingOutcome ? route('admin.learning.modules.outcomes.update', $editingOutcome) : route('admin.learning.modules.outcomes.store')); ?>"
            class="lms-composer-form grid gap-4"
            data-dashboard-dialog-form
            data-submit-label="Saving outcome..."
        >
            <?php echo csrf_field(); ?>
            <?php if($editingOutcome): ?>
                <?php echo method_field('PATCH'); ?>
            <?php endif; ?>
            <div>
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="outcome-unit">Competency unit</label>
                <select id="outcome-unit" name="competency_unit_id" class="form-field" required>
                    <option value="">Choose a unit</option>
                    <?php $__currentLoopData = $catalogUnits; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($unit->id); ?>" <?php if((string) $selectedOutcomeUnitId === (string) $unit->id): echo 'selected'; endif; ?>><?php echo e($unit->code); ?> — <?php echo e($unit->title); ?></option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
                <?php $__errorArgs = ['competency_unit_id', 'catalogOutcome'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <div>
                <label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="outcome-title">Outcome title</label>
                <input id="outcome-title" name="title" value="<?php echo e(old('title', $editingOutcome?->title)); ?>" class="form-field" maxlength="255" required>
                <?php $__errorArgs = ['title', 'catalogOutcome'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-2 text-xs font-bold text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="is_required" value="0">
                <input type="checkbox" name="is_required" value="1" <?php if($outcomeRequiredChecked): echo 'checked'; endif; ?>>
                Required outcome
            </label>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                <button type="submit" class="primary-action">Save outcome</button>
            </div>
        </form>
    </dialog>

    <dialog id="admin-module-dialog" data-dashboard-dialog data-auto-open="<?php echo e($errors->hasAny(['trainer_id', 'training_batch_id', 'module_code', 'competency_category', 'completion_mode', 'estimated_hours', 'title', 'description', 'module_file', 'supplementary_files', 'supplementary_files.*']) && ! old('_editing_module_id') ? 'true' : 'false'); ?>" class="lms-workflow-dialog" aria-labelledby="admin-module-dialog-title">
        <div class="lms-dialog-header">
            <div><p class="lms-eyebrow">Admin LMS</p><h2 id="admin-module-dialog-title">Add a learning module</h2><p>Upload a protected module and assign its trainer and batch.</p></div>
            <button type="button" data-dashboard-dialog-close class="lms-dialog-close" aria-label="Close module creator"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></button>
        </div>
        <?php echo $__env->make('admin.learning.partials.module-composer-form', [
            'prefix' => 'admin',
            'errorBag' => 'default',
            'trainers' => $trainers,
            'batches' => $batches,
            'trainerCatalogUnits' => $trainerCatalogUnits,
        ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
    </dialog>

    <?php if (! ($isUnitsTab || $isOutcomesTab)): ?>
    <form method="GET" data-auto-filter class="dashboard-panel grid gap-4 md:grid-cols-4">
        <div class="md:col-span-2">
            <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Search module, code, or trainer</label>
            <input name="search" value="<?php echo e($filters['search'] ?? ''); ?>" class="form-field" placeholder="Search by title, code (e.g. HCS323301), trainer...">
        </div>
        <div>
            <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch</label>
            <select name="batch_id" class="form-field">
                <option value="">All batches</option>
                <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <option value="<?php echo e($batch->id); ?>" <?php if((int)($filters['batch_id'] ?? 0) === $batch->id): echo 'selected'; endif; ?>><?php echo e($batch->name); ?> <?php echo e($batch->year); ?></option>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </select>
        </div>
        <div>
            <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Publication</label>
            <select name="published" class="form-field">
                <option value="">All states</option>
                <option value="yes" <?php if(($filters['published'] ?? '') === 'yes'): echo 'selected'; endif; ?>>Published</option>
                <option value="no" <?php if(($filters['published'] ?? '') === 'no'): echo 'selected'; endif; ?>>Draft</option>
            </select>
        </div>
        <div class="flex gap-2 md:col-span-4">
            <button class="primary-action">Filter modules</button>
            <a href="<?php echo e(route('admin.learning.modules')); ?>" class="secondary-action">Reset</a>
        </div>
    </form>

    <div class="dashboard-table-wrap overflow-x-auto">
        <table class="dashboard-table w-full min-w-[64rem]">
            <thead>
                <tr>
                    <th>Module</th>
                    <th>Trainer</th>
                    <th>Batch</th>
                    <th>File</th>
                    <th>Publication</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <div class="flex flex-wrap items-center gap-1.5 mb-1">
                                <?php if($module->module_code): ?>
                                    <span class="rounded bg-purple-100 px-2 py-0.5 text-xs font-mono font-bold text-purple-900 ring-1 ring-purple-300">
                                        <?php echo e($module->module_code); ?>

                                    </span>
                                <?php endif; ?>
                                <?php if($module->topic): ?>
                                    <span class="text-xs font-medium text-slate-500"><?php echo e($module->topic); ?></span>
                                <?php endif; ?>
                            </div>
                            <p class="font-bold text-slate-950"><?php echo e($module->title); ?></p>
                            <p class="mt-1 max-w-md text-xs line-clamp-2 text-slate-600"><?php echo e($module->description); ?></p>
                        </td>
                        <td><?php echo e($module->trainer?->name ?? 'Unassigned'); ?></td>
                        <td><?php echo e($module->batch ? $module->batch->name.' '.$module->batch->year : 'General'); ?></td>
                        <td>
                            <span class="break-all text-xs"><?php echo e($module->original_file_name); ?></span>
                            <?php if(count($module->supplementaryList()) > 0): ?>
                                <p class="mt-1 text-[11px] font-semibold text-purple-700">+ <?php echo e(count($module->supplementaryList())); ?> supplementary</p>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="dashboard-pill <?php echo e($module->delivery_status === 'active' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : ($module->delivery_status === 'closed' ? 'bg-amber-50 text-amber-700 ring-amber-100' : 'bg-slate-100 text-slate-700 ring-slate-200')); ?>">
                                <?php echo e($module->deliveryStatusLabel()); ?>

                            </span>
                            <p class="mt-2 text-xs text-slate-500"><?php echo e($module->published_at?->format('M d, Y g:i A') ?? 'Not published'); ?></p>
                            <p class="mt-1 text-xs font-semibold <?php echo e($module->locksUntilPreviousFinished() ? 'text-amber-800' : 'text-sky-800'); ?>"><?php echo e($module->lockSettingLabel()); ?></p>
                        </td>
                        <td>
                            <div class="flex flex-wrap gap-2" aria-label="Module actions">
                                <a href="<?php echo e(route('admin.learning.modules.quizzes', $module)); ?>" class="admin-module-action" aria-label="Module quizzes" title="Quizzes">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'clipboard-list','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'clipboard-list','class' => 'h-4 w-4']); ?>
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
                                    <span class="admin-module-action-tooltip" aria-hidden="true">Quizzes</span>
                                </a>
                                <a href="<?php echo e(route('admin.learning.modules.preview', $module)); ?>" class="admin-module-action" aria-label="Preview module" title="Preview module">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'eye','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'eye','class' => 'h-4 w-4']); ?>
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
                                    <span class="admin-module-action-tooltip" aria-hidden="true">Preview</span>
                                </a>
                                <button type="button" data-dashboard-dialog-open="edit-module-<?php echo e($module->id); ?>" class="admin-module-action" aria-label="Edit module" title="Edit module">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'pencil','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'pencil','class' => 'h-4 w-4']); ?>
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
                                    <span class="admin-module-action-tooltip" aria-hidden="true">Edit</span>
                                </button>
                                <a href="<?php echo e(route('classroom-comments.index', ['type' => 'module', 'id' => $module->id])); ?>" class="admin-module-action" aria-label="Open module comments" title="Open module comments">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'message-circle','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'message-circle','class' => 'h-4 w-4']); ?>
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
                                    <span class="admin-module-action-tooltip" aria-hidden="true">Comments</span>
                                </a>
                                <button type="button" data-dashboard-dialog-open="delete-module-<?php echo e($module->id); ?>" class="admin-module-action is-danger" aria-label="Permanently delete module" title="Permanently delete module">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'trash-2','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'trash-2','class' => 'h-4 w-4']); ?>
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
                                    <span class="admin-module-action-tooltip" aria-hidden="true">Delete</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="6" class="py-14 text-center text-slate-500">No modules match these filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        <?php if($modules->hasPages()): ?><div class="border-t border-slate-200 px-5 py-4"><?php echo e($modules->links()); ?></div><?php endif; ?>
    </div>

    <?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
            $impact = $moduleImpacts[$module->id] ?? [];
            $updateErrors = $errors->moduleUpdate ?? null;
            $openEditDialog = $updateErrors && $updateErrors->any() && (int) old('_editing_module_id') === (int) $module->id;
        ?>
        <dialog id="edit-module-<?php echo e($module->id); ?>" data-dashboard-dialog data-auto-open="<?php echo e($openEditDialog ? 'true' : 'false'); ?>" class="lms-workflow-dialog" aria-labelledby="edit-module-title-<?php echo e($module->id); ?>">
            <div class="lms-dialog-header">
                <div><p class="lms-eyebrow">Admin LMS</p><h2 id="edit-module-title-<?php echo e($module->id); ?>">Edit learning module</h2><p>Update this module’s assignment, details, or learning file.</p></div>
                <button type="button" data-dashboard-dialog-close class="lms-dialog-close" aria-label="Close module editor"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></button>
            </div>
            <?php echo $__env->make('admin.learning.partials.module-composer-form', [
                'prefix' => 'edit-'.$module->id,
                'module' => $module,
                'errorBag' => 'moduleUpdate',
                'trainers' => $trainers,
                'batches' => $batches,
                'trainerCatalogUnits' => $trainerCatalogUnits,
            ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </dialog>
        <dialog id="delete-module-<?php echo e($module->id); ?>" data-dashboard-dialog class="m-auto max-h-[92vh] w-[min(94vw,42rem)] overflow-y-auto rounded-xl border border-red-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="delete-module-title-<?php echo e($module->id); ?>">
            <div class="border-b border-red-100 bg-red-50 px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="dashboard-section-kicker text-red-700">Irreversible admin action</p>
                        <h2 id="delete-module-title-<?php echo e($module->id); ?>" class="mt-1 text-xl font-bold text-red-950">Permanently delete module?</h2>
                        <p class="mt-1 text-sm text-red-800">“<?php echo e($module->title); ?>” and its module-specific learning history will be removed permanently.</p>
                    </div>
                    <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-red-200 text-red-700 hover:bg-white" aria-label="Close deletion dialog" title="Close"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'xmark']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'xmark']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></button>
                </div>
            </div>
            <div class="space-y-5 p-6">
                <div>
                    <h3 class="text-sm font-bold text-slate-950">What will be removed</h3>
                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                        <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-xs text-slate-500">Affected trainees</dt><dd class="text-lg font-bold text-slate-950"><?php echo e($impact['affected_trainees'] ?? 0); ?></dd></div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-xs text-slate-500">Parent progress / grades</dt><dd class="text-lg font-bold text-slate-950"><?php echo e($impact['parent_progress_records'] ?? 0); ?></dd></div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-xs text-slate-500">Submodule progress / grades</dt><dd class="text-lg font-bold text-slate-950"><?php echo e($impact['submodule_progress_records'] ?? 0); ?></dd></div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-xs text-slate-500">Quizzes / attempts</dt><dd class="text-lg font-bold text-slate-950"><?php echo e($impact['quizzes'] ?? 0); ?> / <?php echo e($impact['quiz_attempts'] ?? 0); ?></dd></div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-xs text-slate-500">Questions / attendance</dt><dd class="text-lg font-bold text-slate-950"><?php echo e($impact['quiz_questions'] ?? 0); ?> / <?php echo e($impact['quiz_attendance'] ?? 0); ?></dd></div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2"><dt class="text-xs text-slate-500">Comments / notifications</dt><dd class="text-lg font-bold text-slate-950"><?php echo e($impact['comments'] ?? 0); ?> / <?php echo e($impact['notifications'] ?? 0); ?></dd></div>
                        <div class="rounded-lg bg-slate-50 px-3 py-2 sm:col-span-2"><dt class="text-xs text-slate-500">Stored module and submission files</dt><dd class="text-lg font-bold text-slate-950"><?php echo e($impact['stored_files'] ?? 0); ?></dd></div>
                    </dl>
                </div>

                <?php if(($impact['affected_trainees'] ?? 0) > 0): ?>
                    <p class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-900"><?php echo e($impact['affected_trainees']); ?> connected trainee(s) will lose this module’s progress, grades, and quiz history. Existing official COTC and TOR documents stay on file.</p>
                <?php endif; ?>
                <p class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">This action cannot be undone. Existing official documents are never removed by this action.</p>

                <form method="POST" action="<?php echo e(route('admin.learning.modules.destroy', $module)); ?>" class="space-y-4" data-dashboard-dialog-form data-submit-label="Deleting module...">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('DELETE'); ?>
                    <div>
                        <label class="form-label" for="delete-module-confirmation-<?php echo e($module->id); ?>">Type DELETE to confirm</label>
                        <input id="delete-module-confirmation-<?php echo e($module->id); ?>" name="confirmation" class="form-field" type="text" value="" autocomplete="off" autocapitalize="characters" spellcheck="false" pattern="DELETE" required>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end">
                        <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                        <button type="submit" data-action-button class="min-h-10 rounded-lg border border-red-700 bg-red-700 px-4 text-sm font-bold text-white hover:bg-red-800">Permanently delete module</button>
                    </div>
                </form>
            </div>
        </dialog>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-module-preset-form]').forEach((form) => {
            const presetSelect = form.querySelector('[data-role="module-preset"]');
            const codeInput = form.querySelector('[data-role="module-code"]');
            const titleInput = form.querySelector('[data-role="module-title"]');
            const topicInput = form.querySelector('[data-role="module-topic"]');
            const categoryInput = form.querySelector('[data-role="module-category"]');
            const hoursInput = form.querySelector('[data-role="module-hours"]');
            const datalist = form.querySelector('[data-role="module-subtopics"]');
            const submoduleList = form.querySelector('[data-role="module-submodule-list"]');
            const addSubmoduleButton = form.querySelector('[data-role="module-add-submodule"]');

            const renderSubmodules = (outcomes, locked = false) => {
                if (!submoduleList) return;
                submoduleList.innerHTML = '';
                const titles = outcomes.length ? outcomes : [''];
                titles.forEach((outcome) => {
                    const input = document.createElement('input');
                    input.name = 'submodule_titles[]';
                    input.value = outcome;
                    input.maxLength = 255;
                    input.className = 'form-field';
                    input.placeholder = 'Submodule or competency outcome';
                    input.readOnly = locked;
                    submoduleList.appendChild(input);
                });
            };

            addSubmoduleButton?.addEventListener('click', () => {
                const input = document.createElement('input');
                input.name = 'submodule_titles[]';
                input.maxLength = 255;
                input.className = 'form-field';
                input.placeholder = 'Submodule or competency outcome';
                submoduleList?.appendChild(input);
            });

            // Path: resources/views/admin/learning/modules.blade.php | Label: Lock category dropdown after preset selection
            const lockCategoryFromPreset = (locked) => {
                if (!categoryInput) return;
                categoryInput.dataset.presetLocked = locked ? '1' : '0';
                categoryInput.classList.toggle('pointer-events-none', locked);
                categoryInput.classList.toggle('opacity-70', locked);
                categoryInput.setAttribute('aria-readonly', locked ? 'true' : 'false');
                categoryInput.setAttribute('tabindex', locked ? '-1' : '0');
            };

            categoryInput?.addEventListener('change', () => {
                if (categoryInput.value === 'custom' && submoduleList?.querySelector('input[readonly]')) {
                    renderSubmodules([], false);
                }
            });

            if (!presetSelect) {
                return;
            }

            presetSelect.addEventListener('change', () => {
                const selectedOption = presetSelect.selectedOptions[0];
                if (!selectedOption || !selectedOption.value) {
                    lockCategoryFromPreset(false);
                    return;
                }

                const code = selectedOption.dataset.code || '';
                const title = selectedOption.dataset.title || '';
                const category = selectedOption.dataset.category || '';
                const hours = selectedOption.dataset.hours || '';
                let outcomes = [];

                try {
                    outcomes = JSON.parse(selectedOption.dataset.outcomes || '[]');
                } catch (e) {}

                if (codeInput && code) codeInput.value = code;
                if (titleInput && title) titleInput.value = title;
                if (categoryInput && category) categoryInput.value = category;
                if (hoursInput && hours) hoursInput.value = hours;

                if (datalist) {
                    datalist.innerHTML = '';
                    outcomes.forEach(outcome => {
                        const opt = document.createElement('option');
                        opt.value = outcome;
                        datalist.appendChild(opt);
                    });
                }

                renderSubmodules(outcomes, true);
                lockCategoryFromPreset(true);

                if (topicInput && outcomes.length > 0 && !topicInput.value) {
                    topicInput.value = outcomes[0];
                }
            });
        });

        // Path: resources/views/admin/learning/modules.blade.php | Label: Primary lesson file inline preview
        document.querySelectorAll('[data-file-preview-input]').forEach((input) => {
            const key = input.dataset.filePreviewInput;
            const panel = document.querySelector('[data-file-preview-panel="' + CSS.escape(key) + '"]');
            if (!panel) return;
            const nameEl = panel.querySelector('[data-file-preview-name]');
            const sizeEl = panel.querySelector('[data-file-preview-size]');
            const bodyEl = panel.querySelector('[data-file-preview-body]');
            const removeBtn = document.querySelector('[data-file-preview-remove="' + CSS.escape(key) + '"]');

            const formatBytes = (b) => {
                if (!b) return '';
                if (b < 1024) return b + ' B';
                if (b < 1024*1024) return (b/1024).toFixed(1) + ' KB';
                return (b/1024/1024).toFixed(2) + ' MB';
            };

            const showFile = (file, url) => {
                bodyEl.innerHTML = '';
                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = file.name;
                    img.className = 'max-h-72 w-auto rounded-lg border border-slate-200 bg-white';
                    bodyEl.appendChild(img);
                } else if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
                    const embed = document.createElement('iframe');
                    embed.src = url;
                    embed.title = file.name;
                    embed.className = 'h-72 w-full rounded-lg border border-slate-200 bg-white';
                    bodyEl.appendChild(embed);
                } else {
                    const note = document.createElement('p');
                    note.className = 'text-xs text-slate-500';
                    note.textContent = 'Preview not available for this file type — the file will still be uploaded.';
                    bodyEl.appendChild(note);
                }
            };

            const renderPreview = async (file) => {
                if (!file) {
                    panel.classList.add('hidden');
                    if (bodyEl) bodyEl.innerHTML = '';
                    return;
                }
                panel.classList.remove('hidden');
                if (nameEl) nameEl.textContent = file.name;
                if (sizeEl) sizeEl.textContent = formatBytes(file.size);
                if (!bodyEl) return;
                const localUrl = URL.createObjectURL(file);
                const canStamp = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf') || file.type.startsWith('image/');
                const previewUrl = input.dataset.watermarkPreviewUrl;
                if (!canStamp || !previewUrl) {
                    showFile(file, localUrl);
                    return;
                }
                bodyEl.innerHTML = '<p class="text-xs font-semibold text-slate-500">Embedding watermark...</p>';
                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const body = new FormData();
                    body.append('module_file', file);
                    const response = await fetch(previewUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/pdf, image/*, application/json' },
                        body,
                    });
                    if (!response.ok) throw new Error('watermark failed');
                    const stamped = URL.createObjectURL(await response.blob());
                    showFile(file, stamped);
                } catch (error) {
                    showFile(file, localUrl);
                }
            };

            input.addEventListener('change', () => {
                renderPreview(input.files && input.files[0] ? input.files[0] : null);
            });
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    input.value = '';
                    renderPreview(null);
                });
            }
        });
    });
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'LMS Module Management | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\learning\modules.blade.php ENDPATH**/ ?>