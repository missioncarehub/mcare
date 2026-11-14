<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Attendance Management</h1>
            <p class="mt-1 text-sm text-slate-600">Track and record daily class attendance and monitor TESDA compliance rates.</p>
        </div>
        <?php if($selectedBatch): ?>
            <div class="flex items-center gap-2">
                <a href="<?php echo e(route('trainer.attendance.export', $selectedBatch)); ?>" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Export TESDA Workbook (.xlsx)</span>
                </a>
                <a href="<?php echo e(route('trainer.attendance.export', ['batch' => $selectedBatch, 'format' => 'csv'])); ?>" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50" title="Download raw CSV">
                    <i class="fa-solid fa-file-csv text-slate-500"></i>
                    <span>CSV</span>
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Controls Bar -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <form method="GET" action="<?php echo e(route('trainer.attendance.index')); ?>" class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-4 items-end">
            <div>
                <label for="batch_select" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Select Batch</label>
                <select id="batch_select" name="batch_id" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-purple-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-600/20">
                    <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <option value="<?php echo e($b->id); ?>" <?php if($selectedBatch && $selectedBatch->id === $b->id): echo 'selected'; endif; ?>>
                            <?php echo e($b->name); ?> <?php echo e($b->is_active ? '(Active)' : '(Inactive)'); ?>

                        </option>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </select>
            </div>

            <div>
                <label for="date_select" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Attendance Date</label>
                <input type="date" id="date_select" name="date" value="<?php echo e($selectedDate->toDateString()); ?>" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-purple-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-600/20">
            </div>

            <input type="hidden" name="tab" value="<?php echo e($activeTab); ?>">

            <div class="sm:col-span-1 lg:col-span-2 flex items-center gap-2">
                <a href="<?php echo e(route('trainer.attendance.index', ['batch_id' => $selectedBatch?->id, 'date' => $selectedDate->toDateString(), 'tab' => 'sheet'])); ?>" class="flex-1 text-center rounded-xl px-4 py-2.5 text-sm font-semibold transition <?php echo e($activeTab === 'sheet' ? 'bg-purple-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'); ?>">
                    Daily Sheet
                </a>
                <a href="<?php echo e(route('trainer.attendance.index', ['batch_id' => $selectedBatch?->id, 'date' => $selectedDate->toDateString(), 'tab' => 'summary'])); ?>" class="flex-1 text-center rounded-xl px-4 py-2.5 text-sm font-semibold transition <?php echo e($activeTab === 'summary' ? 'bg-purple-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'); ?>">
                    Summary & Compliance
                </a>
            </div>
        </form>
    </div>

    <?php if(! $selectedBatch): ?>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-12 text-center text-slate-500 shadow-sm">
            <p class="text-base font-semibold">No active training batches found.</p>
        </div>
    <?php elseif($activeTab === 'sheet'): ?>
        <!-- Daily Attendance Sheet -->
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between bg-slate-50/50">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        <?php echo e($selectedBatch->name); ?> &mdash; <?php echo e($selectedDate->format('F d, Y (l)')); ?>

                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Trainees enrolled on this date: <?php echo e($trainees->count()); ?></p>
                </div>
                <?php if (! ($isFutureDate)): ?>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="markAllPresent()" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50">
                            <i class="fa-solid fa-check-double text-emerald-600"></i>
                            <span>Mark All as Present</span>
                        </button>
                        <button type="submit" form="attendance_form" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-purple-800">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Attendance</span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if($isFutureDate): ?>
                <div class="border-b border-slate-100 bg-slate-50 px-5 py-3 text-sm text-slate-600">
                    This date is in the future. Attendance status will be available on the session day.
                </div>
            <?php endif; ?>

            <?php if($trainees->isEmpty()): ?>
                <div class="p-12 text-center text-slate-500">
                    <p class="font-medium">No enrolled trainees for this date.</p>
                </div>
            <?php elseif($isFutureDate): ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-100/75 text-xs uppercase tracking-wider text-slate-600 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="py-3.5 px-4 font-bold">#</th>
                                <th scope="col" class="py-3.5 px-4 font-bold">Trainee Name</th>
                                <th scope="col" class="py-3.5 px-4 font-bold">Schedule</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php $__currentLoopData = $trainees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $trainee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3.5 px-4 font-medium text-slate-400"><?php echo e($index + 1); ?></td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900"><?php echo e($trainee->full_name); ?></div>
                                        <div class="text-xs text-slate-500"><?php echo e($trainee->email ?? $trainee->user?->email); ?></div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 uppercase">
                                            <?php echo e($trainee->schedule_preference ?: 'AM'); ?>

                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <form id="attendance_form" method="POST" action="<?php echo e(route('trainer.attendance.store')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="batch_id" value="<?php echo e($selectedBatch->id); ?>">
                    <input type="hidden" name="date" value="<?php echo e($selectedDate->toDateString()); ?>">

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-100/75 text-xs uppercase tracking-wider text-slate-600 border-b border-slate-200">
                                <tr>
                                    <th scope="col" class="py-3.5 px-4 font-bold">#</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold">Trainee Name</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold">Schedule</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold text-center">Attendance Status</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold">Recorded Check-In</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold">Notes / Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php $__currentLoopData = $trainees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $trainee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $att = $existingAttendances->get($trainee->id);
                                        $currentStatus = $att?->status;
                                    ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3.5 px-4 font-medium text-slate-400"><?php echo e($index + 1); ?></td>
                                        <td class="py-3.5 px-4">
                                            <div class="font-bold text-slate-900"><?php echo e($trainee->full_name); ?></div>
                                            <div class="text-xs text-slate-500"><?php echo e($trainee->email ?? $trainee->user?->email); ?></div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 uppercase">
                                                <?php echo e($trainee->schedule_preference ?: 'AM'); ?>

                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center justify-center gap-2 sm:gap-4">
                                                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-emerald-700">
                                                    <input type="radio" name="records[<?php echo e($trainee->id); ?>][status]" value="present" class="status-radio text-emerald-600 focus:ring-emerald-500" <?php if($currentStatus === 'present'): echo 'checked'; endif; ?>>
                                                    <span>Present</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-amber-700">
                                                    <input type="radio" name="records[<?php echo e($trainee->id); ?>][status]" value="late" class="status-radio text-amber-600 focus:ring-amber-500" <?php if($currentStatus === 'late'): echo 'checked'; endif; ?>>
                                                    <span>Late</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-rose-700">
                                                    <input type="radio" name="records[<?php echo e($trainee->id); ?>][status]" value="absent" class="status-radio text-rose-600 focus:ring-rose-500" <?php if($currentStatus === 'absent'): echo 'checked'; endif; ?>>
                                                    <span>Absent</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-blue-700">
                                                    <input type="radio" name="records[<?php echo e($trainee->id); ?>][status]" value="excused" class="status-radio text-blue-600 focus:ring-blue-500" <?php if($currentStatus === 'excused'): echo 'checked'; endif; ?>>
                                                    <span>Excused</span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 text-xs text-slate-500">
                                            <?php if($att?->timed_in_at): ?>
                                                <span class="font-semibold text-slate-700"><?php echo e($att->timed_in_at->format('g:i A')); ?></span>
                                                <span class="text-[10px] text-slate-400 block">(<?php echo e($att->check_in_type === 'activity_time_in' ? 'Activity Time-In' : 'Class Sheet'); ?>)</span>
                                            <?php else: ?>
                                                <span class="text-slate-400">&mdash;</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <input type="text" name="records[<?php echo e($trainee->id); ?>][notes]" value="<?php echo e($att?->notes); ?>" placeholder="Optional remarks..." class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/50 p-4">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-purple-800">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Attendance</span>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <!-- Attendance Summary & Compliance Tab -->
        <?php if($summary): ?>
            <div class="space-y-6">
                <!-- Summary Stats Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Recorded Sessions</p>
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700 ring-1 ring-sky-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'calendar-days']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'calendar-days']); ?>
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
                        </div>
                        <p class="mt-2 text-3xl font-extrabold text-slate-900"><?php echo e($summary['total_days']); ?></p>
                        <p class="mt-1 text-xs text-slate-500">Training days recorded to date</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Batch Average Attendance</p>
                            <?php ($avgRate = $summary['average_rate']); ?>
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg <?php echo e($avgRate === null ? 'bg-slate-50 text-slate-600 ring-slate-100' : ($avgRate >= 80 ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100')); ?> ring-1"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'chart-column']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chart-column']); ?>
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
                        </div>
                        <p class="mt-2 text-3xl font-extrabold <?php echo e($avgRate === null ? 'text-slate-500' : ($avgRate >= 80 ? 'text-emerald-700' : 'text-amber-700')); ?>">
                            <?php echo e($avgRate === null ? '—' : $avgRate.'%'); ?>

                        </p>
                        <p class="mt-1 text-xs text-slate-500">TESDA standard benchmark: 80%</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Trainees in Roster</p>
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'users']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'users']); ?>
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
                        </div>
                        <p class="mt-2 text-3xl font-extrabold text-purple-700"><?php echo e(count($summary['trainees'])); ?></p>
                        <p class="mt-1 text-xs text-slate-500">Approved active enrolled learners</p>
                    </div>
                </div>

                <!-- Summary Roster Table -->
                <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between bg-slate-50/50">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">TESDA Attendance Compliance Roster</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Learners must maintain at least 80% attendance to qualify for certification.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="<?php echo e(route('trainer.attendance.export', $selectedBatch)); ?>" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-purple-800">
                                <i class="fa-solid fa-file-excel"></i>
                                <span>Download Excel Workbook (.xlsx)</span>
                            </a>
                            <a href="<?php echo e(route('trainer.attendance.export', ['batch' => $selectedBatch, 'format' => 'csv'])); ?>" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50">
                                <i class="fa-solid fa-file-csv text-slate-500"></i>
                                <span>CSV</span>
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-100/75 text-xs uppercase tracking-wider text-slate-600 border-b border-slate-200">
                                <tr>
                                    <th scope="col" class="py-3.5 px-4 font-bold">Trainee Name</th>
                                    <th scope="col" class="py-3.5 px-3 font-bold text-center">Schedule</th>
                                    <th scope="col" class="py-3.5 px-3 font-bold text-center">Present (P)</th>
                                    <th scope="col" class="py-3.5 px-3 font-bold text-center">Late (L)</th>
                                    <th scope="col" class="py-3.5 px-3 font-bold text-center">Absent (A)</th>
                                    <th scope="col" class="py-3.5 px-3 font-bold text-center">Excused (E)</th>
                                    <th scope="col" class="py-3.5 px-3 font-bold text-center">Sessions</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold text-center">Attendance %</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold text-center">TESDA Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php $__empty_1 = true; $__currentLoopData = $summary['trainees']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3.5 px-4">
                                            <div class="font-bold text-slate-900"><?php echo e($t['name']); ?></div>
                                            <div class="text-xs text-slate-500"><?php echo e($t['email']); ?></div>
                                        </td>
                                        <td class="py-3.5 px-3 text-center">
                                            <span class="inline-flex rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                                <?php echo e($t['schedule']); ?>

                                            </span>
                                        </td>
                                        <td class="py-3.5 px-3 text-center font-semibold text-emerald-700"><?php echo e($t['present']); ?></td>
                                        <td class="py-3.5 px-3 text-center font-semibold text-amber-700"><?php echo e($t['late']); ?></td>
                                        <td class="py-3.5 px-3 text-center font-semibold text-rose-700"><?php echo e($t['absent']); ?></td>
                                        <td class="py-3.5 px-3 text-center font-semibold text-blue-700"><?php echo e($t['excused']); ?></td>
                                        <td class="py-3.5 px-3 text-center font-medium text-slate-500"><?php echo e($t['total_sessions']); ?></td>
                                        <td class="py-3.5 px-4 text-center font-bold <?php echo e($t['attendance_rate'] === null ? 'text-slate-500' : ($t['attendance_rate'] >= 80 ? 'text-emerald-700' : 'text-amber-700')); ?>">
                                            <?php echo e($t['attendance_rate'] === null ? '—' : $t['attendance_rate'].'%'); ?>

                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <?php if($t['is_compliant'] === null): ?>
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">
                                                    Newly enrolled
                                                </span>
                                            <?php elseif($t['is_compliant']): ?>
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">
                                                    Compliant
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">
                                                    At Risk (&lt;80%)
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                    <tr>
                                        <td colspan="9" class="p-8 text-center text-slate-500">No trainees enrolled.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
    function markAllPresent() {
        document.querySelectorAll('input.status-radio[value="present"]').forEach(function(radio) {
            radio.checked = true;
        });
    }
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => 'Attendance Management | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\attendance\index.blade.php ENDPATH**/ ?>