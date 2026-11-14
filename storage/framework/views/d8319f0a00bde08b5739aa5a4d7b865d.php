<?php $__env->startSection('content'); ?>
<?php
    $categoryLabels = ['basic' => 'Basic competencies', 'common' => 'Common competencies', 'core' => 'Core competencies', 'custom' => 'Institutional / Custom competencies'];
    $statusSymbols = ['not_assessed' => '-', 'in_progress' => 'IP', 'competent' => 'C', 'not_yet_competent' => 'NYC'];
    $selectedTraineeIds = collect(old('trainee_ids', []))->map(fn ($id) => (int) $id);
?>

<section class="mx-auto max-w-none space-y-5" data-competency-board>
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 xl:flex-row xl:items-end xl:justify-between">
        <div><p class="dashboard-section-kicker">Training records</p><h1 class="dashboard-section-title mt-2 text-3xl">Batch grading board</h1></div>
        <?php if($selectedBatch): ?>
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                <span><strong class="text-slate-950"><?php echo e($summary['trainees']); ?></strong> <span class="text-slate-500">trainees</span></span>
                <span><strong class="text-slate-950"><?php echo e($summary['competent']); ?></strong><span class="text-slate-500"> / <?php echo e($summary['possible']); ?> competent</span></span>
                <span><strong class="text-violet-700"><?php echo e($summary['percent']); ?>%</strong> <span class="text-slate-500">batch completion</span></span>
            </div>
        <?php endif; ?>
    </header>

    <form method="GET" action="<?php echo e(route('trainer.competencies.index')); ?>" class="grid gap-3 border-b border-slate-200 pb-5 md:grid-cols-[minmax(13rem,1fr)_minmax(12rem,1fr)_10rem_auto] md:items-end">
        <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="competency-search">Search trainee</label><input id="competency-search" class="form-field" name="search" value="<?php echo e($filters['search'] ?? ''); ?>" maxlength="100" placeholder="Name or email"></div>
        <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="competency-batch">Batch</label><select id="competency-batch" class="form-field" name="batch_id"><?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($batch->id); ?>" <?php if((int)($filters['batch_id'] ?? 0) === $batch->id): echo 'selected'; endif; ?>><?php echo e($batch->name); ?> <?php echo e($batch->year); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="competency-schedule">Class</label><select id="competency-schedule" class="form-field" name="schedule"><option value="">AM and PM</option><option value="AM" <?php if(($filters['schedule'] ?? '') === 'AM'): echo 'selected'; endif; ?>>AM</option><option value="PM" <?php if(($filters['schedule'] ?? '') === 'PM'): echo 'selected'; endif; ?>>PM</option></select></div>
        <div class="flex gap-2"><button class="primary-action min-h-11 flex-1 md:flex-none" type="submit">Apply</button><a class="secondary-action min-h-11 flex-1 md:flex-none" href="<?php echo e(route('trainer.competencies.index')); ?>">Reset</a></div>
    </form>

    <?php if(! $selectedBatch): ?>
        <div class="border border-slate-200 bg-white px-6 py-14 text-center text-slate-500">Create a training batch before recording competencies.</div>
    <?php else: ?>
        <div class="competency-board-toolbar">
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-slate-950"><?php echo e($selectedBatch->name); ?> <?php echo e($selectedBatch->year); ?><?php echo e(! empty($filters['schedule']) ? ' | '.$filters['schedule'].' class' : ''); ?></p>
                <p class="mt-1 text-xs font-medium text-slate-500">Evaluate only after the trainee marks the module as done. Locked and in-progress cells stay closed.</p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold text-slate-600" aria-label="Competency status legend"><span class="competency-legend is-competent">C Competent</span><span class="competency-legend is-progress">IP In progress</span><span class="competency-legend is-not-yet">NYC Not yet competent</span><span class="competency-legend is-empty">Evaluate Ready</span><span class="competency-legend is-waiting">In progress Closed</span><span class="competency-legend is-locked">Locked Closed</span></div>
            </div>
            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                <?php if($summary['trainees'] > 0 && $unitsByCategory->flatten()->isNotEmpty()): ?>
                    <span class="text-xs font-bold text-violet-700" data-selected-trainee-count>0 selected</span>
                    <button type="button" data-dashboard-dialog-open="bulk-competency-dialog" data-bulk-update-open class="primary-action gap-2" disabled><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'layer-group','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'layer-group','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Bulk update</button>
                <?php endif; ?>
                <?php if($unitsByCategory->flatten()->isNotEmpty()): ?>
                <div class="flex border border-slate-200 bg-white" aria-label="Scroll competency columns"><button type="button" class="competency-scroll-button" data-competency-scroll="left" aria-label="Scroll competencies left" title="Scroll left"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'chevron-left']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-left']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></button><button type="button" class="competency-scroll-button border-l border-slate-200" data-competency-scroll="right" aria-label="Scroll competencies right" title="Scroll right"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'chevron-right']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-right']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></button></div>
                <?php endif; ?>
                <a class="secondary-action gap-2" href="<?php echo e(route('trainer.competencies.export', ['trainingBatch' => $selectedBatch, 'schedule' => $filters['schedule'] ?? null])); ?>"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'file-excel','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'file-excel','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Export Excel</a>
                <a class="secondary-action gap-2" href="<?php echo e(route('trainer.competencies.chart', ['trainingBatch' => $selectedBatch, 'chart' => 'progress', 'schedule' => $filters['schedule'] ?? null])); ?>"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'table-columns','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'table-columns','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Progress</a>
                <a class="secondary-action gap-2" href="<?php echo e(route('trainer.competencies.chart', ['trainingBatch' => $selectedBatch, 'chart' => 'achievement', 'schedule' => $filters['schedule'] ?? null])); ?>"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'list-check','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'list-check','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Achievement</a>
            </div>
        </div>

        <?php if($traineeLimitReached): ?><div class="border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">The board is showing the first 100 matching trainees. Narrow the class or search filter before a bulk update.</div><?php endif; ?>

        <?php if($unitsByCategory->flatten()->isEmpty()): ?>
            <div class="border border-slate-200 bg-white px-6 py-14 text-center text-slate-500">
                <p class="font-semibold text-slate-700">No published modules for this batch yet.</p>
                <p class="mt-2 text-sm">Create classwork from the trainer or admin Modules page. Only those units appear here, grouped by the admin catalog category.</p>
            </div>
        <?php else: ?>
        <div class="competency-matrix-shell" data-competency-scroller tabindex="0" aria-label="Batch competency grading matrix">
            <table class="competency-matrix">
                <thead>
                    <tr><th class="competency-select-column" rowspan="2"><input type="checkbox" data-select-all-trainees aria-label="Select all visible trainees" title="Select all visible trainees"></th><th class="competency-trainee-column" rowspan="2">Trainee</th><?php $__currentLoopData = $unitsByCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $units): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><th class="competency-category-heading is-<?php echo e($category); ?>" colspan="<?php echo e($units->count()); ?>"><?php echo e($categoryLabels[$category] ?? str($category)->headline()); ?></th><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tr>
                    <tr><?php $__currentLoopData = $unitsByCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $units): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><th class="competency-unit-heading is-<?php echo e($category); ?>" title="<?php echo e($unit->title); ?>"><span><?php echo e($unit->code ?: 'U'.str_pad((string)$unit->sort_order, 2, '0', STR_PAD_LEFT)); ?></span><small><?php echo e($unit->title); ?></small></th><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?> <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></tr>
                </thead>
                <tbody>
                <?php $__empty_1 = true; $__currentLoopData = $trainees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trainee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td class="competency-select-column"><input type="checkbox" name="trainee_ids[]" value="<?php echo e($trainee->id); ?>" form="bulk-competency-form" data-trainee-selector <?php if($selectedTraineeIds->contains($trainee->id)): echo 'checked'; endif; ?> aria-label="Select <?php echo e($trainee->first_name); ?> <?php echo e($trainee->last_name); ?>"></td>
                        <th scope="row" class="competency-trainee-column">
                            <span class="flex items-center gap-2">
                                <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $trainee->user,'name' => trim($trainee->first_name.' '.$trainee->last_name),'class' => 'grid h-8 w-8 place-items-center rounded-full bg-purple-100 text-[10px] font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trainee->user),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(trim($trainee->first_name.' '.$trainee->last_name)),'class' => 'grid h-8 w-8 place-items-center rounded-full bg-purple-100 text-[10px] font-black text-purple-800']); ?>
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
                                <span class="min-w-0">
                                    <span class="block truncate"><?php echo e($trainee->last_name); ?>, <?php echo e($trainee->first_name); ?></span>
                                    <small><?php echo e($trainee->schedule_preference); ?> | <?php echo e($trainee->email); ?></small>
                                    <?php if (isset($component)) { $__componentOriginal1cb871248b084f1659af43a69ab30a65 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cb871248b084f1659af43a69ab30a65 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.graduate-batch-badge','data' => ['application' => $trainee,'class' => 'mt-1.5']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('graduate-batch-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['application' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trainee),'class' => 'mt-1.5']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cb871248b084f1659af43a69ab30a65)): ?>
<?php $attributes = $__attributesOriginal1cb871248b084f1659af43a69ab30a65; ?>
<?php unset($__attributesOriginal1cb871248b084f1659af43a69ab30a65); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cb871248b084f1659af43a69ab30a65)): ?>
<?php $component = $__componentOriginal1cb871248b084f1659af43a69ab30a65; ?>
<?php unset($__componentOriginal1cb871248b084f1659af43a69ab30a65); ?>
<?php endif; ?>
                                    <a href="<?php echo e(route('trainer.competencies.edit', $trainee)); ?>" class="competency-evaluate-link">Evaluate record</a>
                                </span>
                            </span>
                        </th>
                        <?php $__currentLoopData = $unitsByCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $units): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $record = $recordsByTrainee->get($trainee->id)?->get($unit->id);
                                    $status = $record?->status ?? 'not_assessed';
                                    $results = $record?->outcomeResults?->keyBy('competency_outcome_id') ?? collect();
                                    $gate = ($evaluationByTrainee ?? collect())->get($trainee->id)?->get($unit->id) ?? ['evaluable' => false, 'reason' => 'locked'];
                                    $canEvaluate = (bool) ($gate['evaluable'] ?? false) || (bool) $record?->locked_at;
                                    $waitReason = $gate['reason'] ?? 'locked';
                                    $cellState = ! $canEvaluate
                                        ? ($waitReason === 'in_progress' ? 'waiting' : 'module-locked')
                                        : str($status)->replace('_', '-');
                                    $waitLabel = $waitReason === 'in_progress' ? 'In progress' : 'Locked';
                                    $waitTitle = $waitReason === 'in_progress'
                                        ? 'Trainee is still in progress. Evaluation opens after they mark this module as done.'
                                        : 'This module is locked. Evaluation opens after the trainee marks it as done.';
                                    $payload = [
                                        'trainee_name' => trim($trainee->first_name.' '.$trainee->last_name),
                                        'unit_id' => $unit->id,
                                        'unit_code' => $unit->code ?: 'Unit '.$unit->sort_order,
                                        'unit_title' => $unit->title,
                                        'status' => $status,
                                        'score' => $record?->percentage_score,
                                        'notes' => $record?->notes,
                                        'locked' => (bool) $record?->locked_at,
                                        'update_url' => route('trainer.competencies.update', $trainee),
                                        'full_url' => route('trainer.competencies.edit', $trainee),
                                        'outcomes' => $unit->outcomes->map(fn ($outcome) => ['id' => $outcome->id, 'title' => $outcome->title, 'status' => $results->get($outcome->id)?->status ?? 'not_assessed'])->values()->all(),
                                    ];
                                    $encodedPayload = base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
                                ?>
                                <td class="competency-record-cell is-<?php echo e($cellState); ?>">
                                    <?php if($canEvaluate): ?>
                                        <button type="button" data-competency-cell data-record-payload="<?php echo e($encodedPayload); ?>" aria-label="<?php echo e($status === 'not_assessed' ? 'Evaluate' : 'Update evaluation for'); ?> <?php echo e($unit->title); ?> for <?php echo e($trainee->first_name); ?> <?php echo e($trainee->last_name); ?>" title="<?php echo e($status === 'not_assessed' ? 'Evaluate this competency' : ($statuses[$status].($record?->percentage_score ? ' | '.$record->percentage_score.'%' : ''))); ?>">
                                            <?php if($status === 'not_assessed'): ?>
                                                <span>Evaluate</span>
                                            <?php else: ?>
                                                <span><?php echo e($statusSymbols[$status]); ?></span>
                                                <?php if($record?->percentage_score): ?><small><?php echo e(number_format((float) $record->percentage_score, 0)); ?></small><?php endif; ?>
                                            <?php endif; ?>
                                            <?php if($record?->locked_at): ?><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'lock','class' => 'competency-lock-icon']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'lock','class' => 'competency-lock-icon']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?><?php endif; ?>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" disabled aria-disabled="true" title="<?php echo e($waitTitle); ?>" aria-label="<?php echo e($waitLabel); ?> <?php echo e($unit->title); ?> for <?php echo e($trainee->first_name); ?> <?php echo e($trainee->last_name); ?>">
                                            <span><?php echo e($waitLabel); ?></span>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr><td colspan="<?php echo e($unitsByCategory->flatten()->count() + 2); ?>" class="px-6 py-14 text-center text-sm text-slate-500">No approved trainees match this batch and class.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if($selectedBatch && $summary['trainees'] > 0 && $unitsByCategory->flatten()->isNotEmpty()): ?>
        <dialog id="bulk-competency-dialog" data-dashboard-dialog data-auto-open="<?php echo e($errors->any() ? 'true' : 'false'); ?>" class="m-auto max-h-[92vh] w-[min(94vw,40rem)] overflow-y-auto rounded-lg border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="bulk-competency-title">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4"><div><p class="dashboard-section-kicker">Batch action</p><h2 id="bulk-competency-title" class="mt-1 text-xl font-bold text-slate-950">Update selected trainees</h2><p class="mt-1 text-sm text-slate-500" data-bulk-dialog-count>0 trainees selected</p></div><button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Close bulk update" title="Close"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
<?php endif; ?></button></div>
            <form id="bulk-competency-form" method="POST" action="<?php echo e(route('trainer.competencies.bulk-update')); ?>" class="space-y-5 p-6" data-dashboard-dialog-form data-submit-label="Updating records...">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?><input type="hidden" name="batch_id" value="<?php echo e($selectedBatch->id); ?>">
                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="bulk-unit">Competency</label><select id="bulk-unit" class="form-field" name="unit_id" required><option value="">Select competency</option><?php $__currentLoopData = $unitsByCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $units): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><optgroup label="<?php echo e($categoryLabels[$category] ?? str($category)->headline()); ?>"><?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($unit->id); ?>" <?php if((int)old('unit_id') === $unit->id): echo 'selected'; endif; ?>><?php echo e($unit->code ?: 'Unit '.$unit->sort_order); ?> | <?php echo e($unit->title); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></optgroup><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="bulk-status">Result</label><select id="bulk-status" class="form-field" name="status" data-bulk-status required><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if(old('status', 'in_progress') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                    <div data-bulk-score-wrap class="<?php echo \Illuminate\Support\Arr::toCssClasses(['hidden' => old('status', 'in_progress') !== 'competent']); ?>"><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="bulk-score">Shared score (%)</label><input id="bulk-score" class="form-field" type="number" name="percentage_score" min="75" max="100" step="0.01" value="<?php echo e(old('percentage_score')); ?>" data-bulk-score></div>
                </div>
                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="bulk-notes">Batch note</label><textarea id="bulk-notes" class="form-field min-h-24" name="notes" maxlength="1000"><?php echo e(old('notes')); ?></textarea></div>
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" data-action-button class="primary-action gap-2"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'check','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Update selected</button></div>
            </form>
        </dialog>
    <?php endif; ?>

    <div class="competency-drawer-backdrop" data-competency-drawer-backdrop hidden></div>
    <aside class="competency-record-drawer" data-competency-drawer aria-hidden="true" aria-labelledby="competency-drawer-title">
        <form method="POST" data-competency-drawer-form class="flex h-full flex-col">
            <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6"><div class="min-w-0"><p class="dashboard-section-kicker">Evaluate competency</p><p class="mt-1 text-[11px] font-bold uppercase tracking-wide text-violet-700" data-drawer-unit-code>Competency</p><h2 id="competency-drawer-title" class="mt-1 truncate text-xl font-bold text-slate-950" data-drawer-trainee>Trainee</h2><p class="mt-1 text-sm text-slate-500" data-drawer-unit-title></p></div><button type="button" data-competency-drawer-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Close evaluation" title="Close"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
<?php endif; ?></button></div>
            <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                <div data-drawer-lock-notice class="hidden border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-800">Locked after official document generation.</div>
                <input type="hidden" data-drawer-unit-id>
                <div class="grid gap-4 sm:grid-cols-2"><div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="drawer-status">Unit result</label><select id="drawer-status" class="form-field" data-drawer-status><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>"><?php echo e($label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div><div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="drawer-score">Final score (%)</label><input id="drawer-score" class="form-field" type="number" min="0" max="100" step="0.01" data-drawer-score></div></div>
                <section><p class="mb-2 text-xs font-bold uppercase text-slate-500">Achievement outcomes</p><div class="divide-y divide-slate-100 border-y border-slate-200" data-drawer-outcomes></div></section>
                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="drawer-notes">Trainer notes</label><textarea id="drawer-notes" class="form-field min-h-24" maxlength="1000" data-drawer-notes></textarea></div>
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:flex-row sm:justify-between sm:px-6"><a class="secondary-action" href="#" data-drawer-full-record>Full trainee record</a><button type="submit" class="primary-action gap-2" data-drawer-save><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'check','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'check','class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>Save evaluation</button></div>
        </form>
    </aside>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => 'Competency Records | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\competencies\index.blade.php ENDPATH**/ ?>