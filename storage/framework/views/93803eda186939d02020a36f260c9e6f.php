<?php $__env->startSection('content'); ?>
<?php
    $categoryLabels = ['basic' => 'Basic competencies', 'common' => 'Common competencies', 'core' => 'Core competencies', 'custom' => 'Institutional / Custom competencies'];
?>
<section class="w-full space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="dashboard-section-kicker">Evaluate trainee</p><h1 class="dashboard-section-title mt-2 text-3xl"><?php echo e($trainee->first_name); ?> <?php echo e($trainee->last_name); ?></h1><p class="mt-2 text-sm text-slate-600"><?php echo e($trainee->batch?->name); ?> <?php echo e($trainee->batch?->year); ?> · <?php echo e($trainee->schedule_preference); ?> class</p><?php if (isset($component)) { $__componentOriginal1cb871248b084f1659af43a69ab30a65 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1cb871248b084f1659af43a69ab30a65 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.graduate-batch-badge','data' => ['application' => $trainee,'class' => 'mt-2']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('graduate-batch-badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['application' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trainee),'class' => 'mt-2']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1cb871248b084f1659af43a69ab30a65)): ?>
<?php $attributes = $__attributesOriginal1cb871248b084f1659af43a69ab30a65; ?>
<?php unset($__attributesOriginal1cb871248b084f1659af43a69ab30a65); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1cb871248b084f1659af43a69ab30a65)): ?>
<?php $component = $__componentOriginal1cb871248b084f1659af43a69ab30a65; ?>
<?php unset($__componentOriginal1cb871248b084f1659af43a69ab30a65); ?>
<?php endif; ?></div>
        <a class="secondary-action" href="<?php echo e(route('trainer.competencies.index')); ?>">Back to records</a>
    </header>

    <?php if($unitsByCategory->flatten()->isEmpty()): ?>
        <div class="border border-slate-200 bg-white px-6 py-14 text-center text-slate-500">
            <p class="font-semibold text-slate-700">No published modules for this trainee's batch yet.</p>
            <p class="mt-2 text-sm">Publish classwork from the trainer or admin Modules page and those units will appear here by category.</p>
        </div>
    <?php else: ?>
    <form method="POST" action="<?php echo e(route('trainer.competencies.update', $trainee)); ?>" class="space-y-6">
        <?php echo csrf_field(); ?>
        <?php echo method_field('PATCH'); ?>
        <?php $__currentLoopData = $unitsByCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $units): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <section class="space-y-3">
                <div><p class="dashboard-section-kicker"><?php echo e($categoryLabels[$category] ?? str($category)->headline()); ?></p><p class="mt-1 text-sm text-slate-500">Progress uses the unit result; Achievement uses every learning outcome below it.</p></div>
                <?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $record = $recordsByUnit->get($unit->id);
                        $results = $record?->outcomeResults?->keyBy('competency_outcome_id') ?? collect();
                        $locked = (bool) $record?->locked_at;
                        // Path: resources/views/trainer/competencies/edit.blade.php | Label: Disable evaluation until "Mark as done"
                        $canEvaluate = ($evaluableUnitIds ?? collect())->contains($unit->id) || $locked;
                        $inputDisabled = $locked || ! $canEvaluate;
                    ?>
                    <details class="dashboard-panel p-0 <?php echo e(! $canEvaluate ? 'opacity-70' : ''); ?>" <?php if($record && $record->status !== 'not_assessed'): ?> open <?php endif; ?>>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5">
                            <span><span class="block font-bold text-slate-950"><?php echo e($unit->title); ?></span><span class="mt-1 block text-xs text-slate-500"><?php echo e($unit->code ?: str($category)->headline()); ?> · <?php echo e($unit->outcomes->count()); ?> outcomes</span></span>
                            <span class="dashboard-pill <?php echo e(! $canEvaluate ? 'bg-slate-100 text-slate-500 ring-slate-200' : ($record?->status === 'competent' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : ($record?->status === 'not_yet_competent' ? 'bg-red-50 text-red-700 ring-red-100' : 'bg-slate-100 text-slate-600 ring-slate-200'))); ?>"><?php echo e(! $canEvaluate ? 'Waiting for trainee' : $statuses[$record?->status ?? 'not_assessed']); ?></span>
                        </summary>
                        <div class="border-t border-slate-200 p-5">
                            <input type="hidden" name="records[<?php echo e($unit->id); ?>][unit_id]" value="<?php echo e($unit->id); ?>">
                            <?php if($locked): ?><div class="mb-4 border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-800">Locked after official document generation. An admin must reissue or resolve the official record.</div><?php endif; ?>
                            <?php if(! $canEvaluate): ?>
                                <div class="mb-4 border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                                    The trainee has not marked the module linked to this competency as done yet. You can evaluate this unit once they submit the module for evaluation.
                                </div>
                            <?php endif; ?>
                            <div class="grid gap-4 md:grid-cols-[1fr_12rem]">
                                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Unit result</label><select class="form-field" name="records[<?php echo e($unit->id); ?>][status]" <?php if($inputDisabled): echo 'disabled'; endif; ?>><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $statusLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if(old("records.{$unit->id}.status", $record?->status ?? 'not_assessed') === $value): echo 'selected'; endif; ?>><?php echo e($statusLabel); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Final score (%)</label><input class="form-field" type="number" min="0" max="100" step="0.01" name="records[<?php echo e($unit->id); ?>][percentage_score]" value="<?php echo e(old("records.{$unit->id}.percentage_score", $record?->percentage_score)); ?>" <?php if($inputDisabled): echo 'disabled'; endif; ?>></div>
                            </div>
                            <div class="mt-5 divide-y divide-slate-100 border-y border-slate-200">
                                <?php $__currentLoopData = $unit->outcomes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $outcome): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $outcomeStatus = old("records.{$unit->id}.outcomes.{$outcome->id}", $results->get($outcome->id)?->status ?? 'not_assessed'); ?>
                                    <div class="grid gap-3 py-3 sm:grid-cols-[1fr_12rem] sm:items-center"><label class="text-sm font-medium text-slate-800" for="outcome-<?php echo e($outcome->id); ?>"><?php echo e($outcome->title); ?></label><select id="outcome-<?php echo e($outcome->id); ?>" class="form-field" name="records[<?php echo e($unit->id); ?>][outcomes][<?php echo e($outcome->id); ?>]" <?php if($inputDisabled): echo 'disabled'; endif; ?>><?php $__currentLoopData = $statuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $statusLabel): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($value); ?>" <?php if($outcomeStatus === $value): echo 'selected'; endif; ?>><?php echo e($statusLabel); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <div class="mt-4"><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Trainer notes</label><textarea class="form-field min-h-24" name="records[<?php echo e($unit->id); ?>][notes]" maxlength="1000" <?php if($inputDisabled): echo 'disabled'; endif; ?>><?php echo e(old("records.{$unit->id}.notes", $record?->notes)); ?></textarea></div>
                            <?php if($locked): ?>
                                <input type="hidden" name="records[<?php echo e($unit->id); ?>][status]" value="<?php echo e($record->status); ?>">
                                <input type="hidden" name="records[<?php echo e($unit->id); ?>][percentage_score]" value="<?php echo e($record->percentage_score); ?>">
                                <input type="hidden" name="records[<?php echo e($unit->id); ?>][notes]" value="<?php echo e($record->notes); ?>">
                                <?php $__currentLoopData = $unit->outcomes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $outcome): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><input type="hidden" name="records[<?php echo e($unit->id); ?>][outcomes][<?php echo e($outcome->id); ?>]" value="<?php echo e($results->get($outcome->id)?->status ?? 'not_assessed'); ?>"><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php elseif(! $canEvaluate): ?>
                                
                                <input type="hidden" name="records[<?php echo e($unit->id); ?>][status]" value="not_assessed">
                                <input type="hidden" name="records[<?php echo e($unit->id); ?>][percentage_score]" value="">
                                <input type="hidden" name="records[<?php echo e($unit->id); ?>][notes]" value="">
                                <?php $__currentLoopData = $unit->outcomes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $outcome): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><input type="hidden" name="records[<?php echo e($unit->id); ?>][outcomes][<?php echo e($outcome->id); ?>]" value="not_assessed"><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </section>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        <div class="sticky bottom-4 flex justify-end border border-slate-200 bg-white p-4 shadow-lg"><button class="primary-action" type="submit">Save evaluation</button></div>
    </form>
    <?php endif; ?>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => 'Trainee Competency Record | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\competencies\edit.blade.php ENDPATH**/ ?>