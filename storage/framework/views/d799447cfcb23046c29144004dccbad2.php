<?php $__env->startSection('content'); ?>
    <?php
        $statusStyles = [
            \App\Models\EnrollmentApplication::LEARNING_ACTIVE => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            \App\Models\EnrollmentApplication::LEARNING_PAUSED => 'bg-amber-50 text-amber-900 ring-amber-200',
            \App\Models\EnrollmentApplication::LEARNING_PENDING_GRADUATE => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
            \App\Models\EnrollmentApplication::LEARNING_GRADUATED => 'bg-purple-50 text-purple-800 ring-purple-200',
            \App\Models\EnrollmentApplication::LEARNING_WITHDRAWN => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];
        $hasActiveFilters = collect($filters)->contains(fn ($value) => filled($value));
        $isGraduatedTab = $isGraduatedTab ?? false;
        $graduatedCount = (int) ($statusCounts[\App\Models\EnrollmentApplication::LEARNING_GRADUATED] ?? 0);
        $currentCount = max(0, (int) collect($statusCounts)->sum() - $graduatedCount);
        $rosterBaseQuery = collect($filters)->except(['learning_status'])->filter(fn ($value) => filled($value))->all();
    ?>

    <section class="space-y-6">
        <p class="max-w-3xl text-sm leading-6 text-slate-600">Use the roster table to scan payment, module completion, and assessment status. Open View details for the full trainee record.</p>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            <?php $__currentLoopData = $learningStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $statusSelected = $value === \App\Models\EnrollmentApplication::LEARNING_GRADUATED
                        ? $isGraduatedTab
                        : (! $isGraduatedTab && ($filters['learning_status'] ?? '') === $value);
                ?>
                <a href="<?php echo e(route('admin.learning.trainees', $rosterBaseQuery + [
                    'tab' => $value === \App\Models\EnrollmentApplication::LEARNING_GRADUATED ? 'graduated' : 'current',
                    'learning_status' => $value,
                ])); ?>" class="dashboard-stat min-h-0 p-4 <?php echo e($statusSelected ? 'border-purple-300 ring-2 ring-purple-100' : ''); ?>">
                    <div>
                        <p class="dashboard-stat-label"><?php echo e($label); ?></p>
                        <p class="dashboard-stat-value text-2xl"><?php echo e($statusCounts[$value] ?? 0); ?></p>
                    </div>
                    <?php
                        $statusIcons = ['active' => 'users', 'paused' => 'circle-minus', 'pending_graduate' => 'clock', 'graduated' => 'award', 'withdrawn' => 'xmark'];
                    ?>
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg ring-1 <?php echo e($statusStyles[$value]); ?>"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => $statusIcons[$value] ?? 'circle-question']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statusIcons[$value] ?? 'circle-question')]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?></span>
                </a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>

        <details class="dashboard-panel trainee-filter-panel" <?php if($hasActiveFilters): ?> open <?php endif; ?>>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 outline-none">
                <div>
                    <p class="dashboard-section-kicker">Roster filters</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">Search or narrow the summarized trainee list</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-purple-700">
                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'chevron-down','class' => 'trainee-accordion-chevron h-4 w-4 transition-transform']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-down','class' => 'trainee-accordion-chevron h-4 w-4 transition-transform']); ?>
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
                </span>
            </summary>

            <form method="GET" data-auto-filter class="mt-5 grid gap-4 border-t border-slate-200 pt-5 md:grid-cols-2 xl:grid-cols-8">
                <input type="hidden" name="tab" value="<?php echo e($isGraduatedTab ? 'graduated' : 'current'); ?>">
                <div class="md:col-span-2 xl:col-span-2">
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Search trainee</label>
                    <input name="search" value="<?php echo e($filters['search'] ?? ''); ?>" class="form-field" placeholder="Name or email">
                </div>
                <div class="md:col-span-2 xl:col-span-2">
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch</label>
                    <select name="batch_id" class="form-field">
                        <option value="">All batches</option>
                        <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($batch->id); ?>" <?php if((int) ($filters['batch_id'] ?? 0) === $batch->id): echo 'selected'; endif; ?>><?php echo e($batch->name); ?> <?php echo e($batch->year); ?> · <?php echo e($batch->approved_trainees_count); ?> trainees</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Schedule</label>
                    <select name="schedule" class="form-field"><option value="">AM and PM</option><option value="AM" <?php if(($filters['schedule'] ?? '') === 'AM'): echo 'selected'; endif; ?>>AM</option><option value="PM" <?php if(($filters['schedule'] ?? '') === 'PM'): echo 'selected'; endif; ?>>PM</option></select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Learner status</label>
                    <?php if($isGraduatedTab): ?>
                        <input type="hidden" name="learning_status" value="<?php echo e(\App\Models\EnrollmentApplication::LEARNING_GRADUATED); ?>">
                        <p class="form-field bg-slate-50 font-semibold text-slate-700">Graduated</p>
                    <?php else: ?>
                        <select name="learning_status" class="form-field">
                            <option value="">All current statuses</option>
                            <?php $__currentLoopData = $learningStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php if($value === \App\Models\EnrollmentApplication::LEARNING_GRADUATED) continue; ?>
                                <option value="<?php echo e($value); ?>" <?php if(($filters['learning_status'] ?? '') === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch progress</label>
                    <select name="training_state" class="form-field"><option value="">Any progress</option><option value="not_started" <?php if(($filters['training_state'] ?? '') === 'not_started'): echo 'selected'; endif; ?>>Not started</option><option value="in_progress" <?php if(($filters['training_state'] ?? '') === 'in_progress'): echo 'selected'; endif; ?>>In progress</option><option value="completed" <?php if(($filters['training_state'] ?? '') === 'completed'): echo 'selected'; endif; ?>>Completed</option></select>
                </div>
                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Joined from</label><input name="joined_from" type="date" value="<?php echo e($filters['joined_from'] ?? ''); ?>" class="form-field"></div>
                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Joined to</label><input name="joined_to" type="date" value="<?php echo e($filters['joined_to'] ?? ''); ?>" class="form-field"></div>
                <div class="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-6"><button class="primary-action">Filter trainees</button><a href="<?php echo e(route('admin.learning.trainees', ['tab' => $isGraduatedTab ? 'graduated' : 'current'])); ?>" class="secondary-action">Reset</a><a href="<?php echo e(route('admin.learning.trainees.export', request()->query())); ?>" class="secondary-action">Export Excel CSV</a></div>
            </form>
        </details>

        <nav class="lms-context-tabs" aria-label="Trainee roster sections">
            <a href="<?php echo e(route('admin.learning.trainees', $rosterBaseQuery + ['tab' => 'current'])); ?>" class="<?php echo e($isGraduatedTab ? '' : 'is-active'); ?>" <?php if (! ($isGraduatedTab)): ?> aria-current="page" <?php endif; ?>>Current trainees (<?php echo e($currentCount); ?>)</a>
            <a href="<?php echo e(route('admin.learning.trainees', $rosterBaseQuery + ['tab' => 'graduated'])); ?>" class="<?php echo e($isGraduatedTab ? 'is-active' : ''); ?>" <?php if($isGraduatedTab): ?> aria-current="page" <?php endif; ?>>Graduates (<?php echo e($graduatedCount); ?>)</a>
            <a href="<?php echo e(route('admin.learning.trainees.archive')); ?>">Archive</a>
        </nav>

        <div class="dashboard-table-wrap overflow-x-auto" data-trainee-roster>
            <table class="dashboard-table w-full min-w-[72rem]">
                <thead>
                    <tr>
                        <th>Trainee</th>
                        <th>Batch</th>
                        <th>Payment</th>
                        <th>Modules</th>
                        <th>Assessment</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $trainees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trainee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php
                            $summary = $traineeSummaries->get($trainee->id, [
                                'total_modules' => 0,
                                'completed_modules' => 0,
                                'in_progress_modules' => 0,
                                'progress_percent' => 0,
                                'last_activity' => null,
                                'assessment_ready' => false,
                            ]);
                        ?>
                        <tr data-trainee-card data-trainee-row="<?php echo e($trainee->id); ?>">
                            <td>
                                <div class="flex min-w-0 items-center gap-3">
                                    <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $trainee->user,'application' => $trainee,'useEnrollmentPhoto' => true,'class' => 'grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-sm font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trainee->user),'application' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trainee),'use-enrollment-photo' => true,'class' => 'grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-sm font-black text-purple-800']); ?>
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
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-bold text-slate-950"><?php echo e($trainee->last_name); ?>, <?php echo e($trainee->first_name); ?></p>
                                            <span class="dashboard-pill <?php echo e($statusStyles[$trainee->learning_status] ?? $statusStyles[\App\Models\EnrollmentApplication::LEARNING_ACTIVE]); ?>"><?php echo e($trainee->learningStatusLabel()); ?></span>
                                        </div>
                                        <p class="mt-1 truncate text-xs text-slate-500"><?php echo e($trainee->email); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-800"><?php echo e($trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : 'Unassigned batch'); ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?php echo e($trainee->schedule_preference ?: 'Schedule pending'); ?></p>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-800"><?php echo e($trainee->paymentStatusLabel()); ?></p>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-800"><?php echo e($summary['completed_modules']); ?> of <?php echo e($summary['total_modules']); ?></p>
                                <p class="mt-1 text-xs text-slate-500"><?php echo e($summary['progress_percent']); ?>% complete</p>
                            </td>
                            <td>
                                <p class="font-semibold <?php echo e($summary['assessment_ready'] ? 'text-emerald-700' : 'text-amber-800'); ?>"><?php echo e($summary['assessment_ready'] ? 'Ready' : 'Pending'); ?></p>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <form method="POST" action="<?php echo e(route('admin.learning.trainees.destroy', $trainee)); ?>" class="inline-flex" data-confirm-title="<?php echo e($trainee->archiveTitle()); ?>" data-confirm="<?php echo e($trainee->archiveMessage()); ?>" data-confirm-action="Archive trainee">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg border border-red-200 bg-white px-3 text-xs font-bold uppercase tracking-wide text-red-700 hover:bg-red-50" aria-label="Archive <?php echo e($trainee->first_name); ?> <?php echo e($trainee->last_name); ?>">
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
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                    <a href="<?php echo e(route('admin.learning.trainees.show', $trainee)); ?>" class="secondary-action inline-flex h-10 items-center gap-2">
                                        <span>View details</span>
                                        <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'arrow-right','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'arrow-right','class' => 'h-4 w-4']); ?>
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
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="py-14 text-center">
                                <p class="text-lg font-bold text-slate-950"><?php echo e($isGraduatedTab ? 'No graduates found' : 'No trainees found'); ?></p>
                                <p class="mt-2 text-sm text-slate-500"><?php echo e($isGraduatedTab ? 'No graduated trainees match the current filters.' : 'No current trainees match the current filters.'); ?></p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php if($trainees->hasPages()): ?>
                <div class="border-t border-slate-200 px-5 py-4"><?php echo e($trainees->links()); ?></div>
            <?php endif; ?>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Trainee Records | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\learning\trainees-lifecycle.blade.php ENDPATH**/ ?>