<?php $__env->startSection('content'); ?>
<?php
    $statusMark = fn (?string $status) => match ($status) {
        \App\Models\TraineeCompetencyRecord::STATUS_COMPETENT => 'C',
        \App\Models\TraineeCompetencyRecord::STATUS_NOT_YET_COMPETENT => 'NYC',
        \App\Models\TraineeCompetencyRecord::STATUS_IN_PROGRESS => 'IP',
        default => '',
    };
    $categoryLabels = ['basic' => 'Basic Competencies', 'common' => 'Common Competencies', 'core' => 'Core Competencies', 'custom' => 'Institutional / Custom Competencies'];
?>

<style>
    .record-chart { color: #172033; }
    .record-chart table { border-collapse: collapse; width: 100%; }
    .record-chart th, .record-chart td { border: 1px solid #94a3b8; padding: .35rem; text-align: center; vertical-align: middle; }
    .record-chart th { background: #f1f5f9; font-size: .68rem; font-weight: 800; }
    .record-chart td { font-size: .72rem; }
    .record-chart .trainee-name { min-width: 12rem; text-align: left; font-weight: 700; }
    .record-chart .unit-heading { min-width: 5rem; max-width: 8rem; }
    .record-chart .outcome-heading { min-width: 8.5rem; max-width: 12rem; text-align: left; }
    @media print {
        @page { size: A3 landscape; margin: 8mm; }
        body { background: #fff !important; }
        .dashboard-navigation-progress, .dashboard-sidebar, .dashboard-topbar, .chart-actions { display: none !important; }
        .dashboard-layout, .dashboard-main { margin: 0 !important; padding: 0 !important; width: 100% !important; }
        .record-chart { max-width: none !important; }
        .record-chart-section { break-after: page; }
        .record-chart-section:last-child { break-after: auto; }
    }
</style>

<section class="record-chart mx-auto max-w-none space-y-5">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-slate-300 pb-4">
        <div>
            <p class="text-xs font-bold uppercase text-violet-700">Mission Care Training Center</p>
            <h1 class="mt-1 text-2xl font-black text-slate-950"><?php echo e(ucfirst($chart)); ?> Chart</h1>
            <p class="mt-1 text-sm text-slate-600">Caregiving NC II | <?php echo e($batch->name); ?> <?php echo e($batch->year); ?><?php echo e($schedule ? ' | '.$schedule.' class' : ''); ?></p>
        </div>
        <div class="chart-actions flex flex-wrap gap-2">
            <a class="secondary-action" href="<?php echo e(route('trainer.competencies.index', ['batch_id' => $batch->id, 'schedule' => $schedule])); ?>">Back to records</a>
            <button type="button" class="primary-action" onclick="window.print()">Print chart</button>
        </div>
    </header>

    <?php if($trainees->isEmpty()): ?>
        <div class="border border-slate-200 bg-white p-8 text-center text-slate-500">No approved trainees are assigned to this batch and class.</div>
    <?php elseif($chart === 'progress'): ?>
        <div class="overflow-x-auto bg-white">
            <table>
                <thead>
                    <tr>
                        <th rowspan="2" class="trainee-name">Trainee</th>
                        <?php $__currentLoopData = $unitsByCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $units): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <th colspan="<?php echo e($units->count()); ?>"><?php echo e($categoryLabels[$category] ?? ucfirst($category)); ?></th>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                    <tr>
                        <?php $__currentLoopData = $unitsByCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $units): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <th class="unit-heading" title="<?php echo e($unit->title); ?>"><?php echo e($unit->code); ?></th>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tr>
                </thead>
                <tbody>
                    <?php $__currentLoopData = $trainees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trainee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td class="trainee-name"><?php echo e($trainee->last_name); ?>, <?php echo e($trainee->first_name); ?> <?php echo e($trainee->middle_name); ?></td>
                            <?php $__currentLoopData = $unitsByCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $units): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php $record = $recordsByTrainee->get($trainee->id)?->get($unit->id); ?>
                                    <td title="<?php echo e($record ? \App\Models\TraineeCompetencyRecord::statuses()[$record->status] : 'Not assessed'); ?>"><?php echo e($statusMark($record?->status)); ?></td>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <?php $__currentLoopData = $unitsByCategory; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $category => $units): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <section class="record-chart-section space-y-3">
                <h2 class="text-lg font-black text-slate-950"><?php echo e($categoryLabels[$category] ?? ucfirst($category)); ?></h2>
                <?php $__currentLoopData = $units; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $unit): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="mb-5 overflow-x-auto bg-white">
                        <table>
                            <thead>
                                <tr>
                                    <th colspan="<?php echo e($unit->outcomes->count() + 2); ?>" class="text-left"><?php echo e($unit->code); ?> | <?php echo e($unit->title); ?></th>
                                </tr>
                                <tr>
                                    <th class="trainee-name">Trainee</th>
                                    <?php $__currentLoopData = $unit->outcomes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $outcome): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <th class="outcome-heading"><?php echo e($outcome->title); ?></th>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    <th class="unit-heading">Unit result</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $__currentLoopData = $trainees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trainee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <?php
                                        $record = $recordsByTrainee->get($trainee->id)?->get($unit->id);
                                        $outcomeResults = $record?->outcomeResults?->keyBy('competency_outcome_id') ?? collect();
                                    ?>
                                    <tr>
                                        <td class="trainee-name"><?php echo e($trainee->last_name); ?>, <?php echo e($trainee->first_name); ?></td>
                                        <?php $__currentLoopData = $unit->outcomes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $outcome): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <td><?php echo e($statusMark($outcomeResults->get($outcome->id)?->status)); ?></td>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        <td class="font-bold"><?php echo e($statusMark($record?->status)); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </section>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    <?php endif; ?>

    <footer class="text-xs text-slate-500">Legend: C - Competent | NYC - Not Yet Competent | IP - In Progress | Blank - Not Assessed</footer>
</section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => ucfirst($chart).' Chart | MCARE Trainer'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\competencies\chart.blade.php ENDPATH**/ ?>