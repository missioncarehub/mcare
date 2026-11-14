<?php $__env->startSection('content'); ?>
    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-end md:justify-between">
            <p class="text-sm text-slate-600">Review AM and PM class sessions across configured batches.</p>
            <a href="<?php echo e(route('admin.batches.index')); ?>" class="inline-flex w-fit items-center justify-center gap-2 rounded-xl border border-purple-200 bg-white px-5 py-2.5 text-sm font-semibold text-purple-700 transition hover:bg-purple-50">
                Manage batches
            </a>
        </header>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="<?php echo e(route('admin.schedules.index')); ?>" data-auto-filter class="grid grid-cols-1 gap-4 md:grid-cols-[minmax(16rem,1fr)_auto] md:items-end">
                <input type="hidden" name="month" value="<?php echo e($calendarMonth->format('Y-m')); ?>">
                <?php if($calendarSelectedDate): ?>
                    <input type="hidden" name="date" value="<?php echo e($calendarSelectedDate); ?>">
                <?php endif; ?>
                <div>
                    <label for="schedule-batch-id" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Batch</label>
                    <select id="schedule-batch-id" name="batch_id" class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                        <option value="">All batches</option>
                        <?php $__currentLoopData = $calendarBatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($batch->id); ?>" <?php if((int) $selectedBatchId === (int) $batch->id): echo 'selected'; endif; ?>>
                                <?php echo e(trim($batch->name.' '.$batch->year)); ?>

                                <?php if($batch->am_days || $batch->pm_days): ?>
                                    · AM <?php echo e($batch->am_days ?: '—'); ?> · PM <?php echo e($batch->pm_days ?: '—'); ?>

                                <?php endif; ?>
                            </option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <p class="text-sm text-slate-500 md:pb-2">
                    <?php if($selectedBatch): ?>
                        Showing <?php echo e(trim($selectedBatch->name.' '.$selectedBatch->year)); ?> only.
                    <?php else: ?>
                        Showing every batch with a training window in this month.
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <?php if (isset($component)) { $__componentOriginalbe6a3b5a8a13afac890ee49a30b72876 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbe6a3b5a8a13afac890ee49a30b72876 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.training-calendar','data' => ['month' => $calendarMonth,'sessions' => $calendarSessions,'selectedDate' => $calendarSelectedDate,'monthRoute' => url()->current(),'editable' => true,'eyebrow' => 'Admin master calendar','heading' => $calendarMonth->format('F Y').' schedule overview','description' => $calendarDescription,'emptyMessage' => 'No batch sessions are scheduled for this date.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('training-calendar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['month' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarMonth),'sessions' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarSessions),'selected-date' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarSelectedDate),'month-route' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(url()->current()),'editable' => true,'eyebrow' => 'Admin master calendar','heading' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarMonth->format('F Y').' schedule overview'),'description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($calendarDescription),'empty-message' => 'No batch sessions are scheduled for this date.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbe6a3b5a8a13afac890ee49a30b72876)): ?>
<?php $attributes = $__attributesOriginalbe6a3b5a8a13afac890ee49a30b72876; ?>
<?php unset($__attributesOriginalbe6a3b5a8a13afac890ee49a30b72876); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbe6a3b5a8a13afac890ee49a30b72876)): ?>
<?php $component = $__componentOriginalbe6a3b5a8a13afac890ee49a30b72876; ?>
<?php unset($__componentOriginalbe6a3b5a8a13afac890ee49a30b72876); ?>
<?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Schedules | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\schedules\index.blade.php ENDPATH**/ ?>