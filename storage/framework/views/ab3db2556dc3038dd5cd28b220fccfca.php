<?php $__env->startSection('content'); ?>
<?php
    $viewerUrl = route('trainer.modules.content', $module);
    $downloadUrl = route('trainer.modules.download', $module);
    $supplementaryList = $module->supplementaryList();
    $isPrivate = filled($module->target_enrollment_application_id);
    $requestedTab = request()->query('tab', 'materials');
    $activeTab = in_array($requestedTab, ['materials', 'assessments', 'evaluations'], true)
        ? $requestedTab
        : 'materials';
    if (!$module->requiresEvaluation()) {
        $activeTab = 'materials';
    }
?>

<div class="w-full space-y-6" data-trainer-module-hub>
    <div>
        <a href="<?php echo e(route('trainer.resources')); ?>" class="lms-module-back">
            <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'chevron-left','class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-left','class' => 'h-4 w-4']); ?>
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
            Back to Classwork
        </a>

        <header class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <?php if($module->module_code): ?>
                            <span class="rounded bg-purple-100 px-2.5 py-0.5 text-xs font-mono font-bold text-purple-900 ring-1 ring-purple-300">
                                <?php echo e($module->module_code); ?>

                            </span>
                        <?php endif; ?>
                        <span class="rounded bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700">
                            <?php echo e(match($module->competency_category) {
                                'core' => 'Core',
                                'common' => 'Common',
                                'basic' => 'Basic',
                                default => 'Material',
                            }); ?>

                        </span>
                        <?php if($module->estimated_hours): ?>
                            <span class="rounded bg-stone-100 px-2 py-0.5 text-xs text-stone-600">
                                <?php echo e($module->estimated_hours); ?> hrs
                            </span>
                        <?php endif; ?>
                        <span class="rounded px-2.5 py-0.5 text-xs font-bold <?php echo e($module->delivery_status === 'active' ? 'bg-emerald-100 text-emerald-800' : ($module->delivery_status === 'closed' ? 'bg-amber-100 text-amber-800' : 'bg-stone-200 text-stone-700')); ?>">
                            <?php echo e(match($module->delivery_status) {
                                'active' => 'Active',
                                'available' => 'Custom',
                                'closed' => 'Closed',
                                default => 'Draft',
                            }); ?>

                        </span>
                        <span class="rounded px-2.5 py-0.5 text-xs font-bold <?php echo e($module->requiresEvaluation() ? 'bg-purple-100 text-purple-800' : 'bg-sky-100 text-sky-800'); ?>">
                            <?php echo e($module->requiresEvaluation() ? 'Assessed' : 'Material'); ?>

                        </span>
                    </div>

                    <h1 class="text-2xl font-bold text-stone-950 sm:text-3xl"><?php echo e($module->title); ?></h1>

                    <?php if($module->topic): ?>
                        <p class="text-sm font-semibold text-purple-800"><?php echo e($module->topic); ?></p>
                    <?php endif; ?>

                    <p class="text-xs text-stone-500">
                        <strong class="text-stone-800"><?php echo e($isPrivate ? trim($module->targetTrainee?->first_name.' '.$module->targetTrainee?->last_name).' (Private)' : ($module->batch ? $module->batch->name.' '.$module->batch->year : 'Entire class')); ?></strong>
                        <?php if($module->available_at): ?> · Opens <?php echo e($module->available_at->format('M d, Y')); ?> <?php endif; ?>
                        <?php if($module->due_at): ?> · Due <?php echo e($module->due_at->format('M d, Y')); ?> <?php endif; ?>
                    </p>
                    <form method="POST" action="<?php echo e(route('trainer.modules.update', $module)); ?>" class="mt-3 max-w-xl rounded-xl border border-stone-200 bg-stone-50 p-3">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>
                        <input type="hidden" name="module_code" value="<?php echo e($module->module_code); ?>">
                        <input type="hidden" name="competency_category" value="<?php echo e($module->competency_category); ?>">
                        <input type="hidden" name="completion_mode" value="<?php echo e($module->completion_mode ?? 'assessed'); ?>">
                        <input type="hidden" name="title" value="<?php echo e($module->title); ?>">
                        <input type="hidden" name="description" value="<?php echo e($module->description); ?>">
                        <input type="hidden" name="topic" value="<?php echo e($module->topic); ?>">
                        <input type="hidden" name="estimated_hours" value="<?php echo e($module->estimated_hours); ?>">
                        <input type="hidden" name="position" value="<?php echo e($module->position ?? 0); ?>">
                        <input type="hidden" name="audience_type" value="<?php echo e($isPrivate ? 'trainee' : 'batch'); ?>">
                        <input type="hidden" name="training_batch_id" value="<?php echo e($module->training_batch_id); ?>">
                        <input type="hidden" name="target_enrollment_application_id" value="<?php echo e($module->target_enrollment_application_id); ?>">
                        <input type="hidden" name="available_at" value="<?php echo e($module->available_at?->format('Y-m-d\TH:i')); ?>">
                        <input type="hidden" name="due_at" value="<?php echo e($module->due_at?->format('Y-m-d\TH:i')); ?>">
                        <input type="hidden" name="is_published" value="<?php echo e($module->is_published ? 1 : 0); ?>">
                        <input type="hidden" name="_return_to_module" value="1">
                        <label class="flex items-start gap-3 text-sm font-semibold text-stone-800">
                            <input type="hidden" name="lock_until_previous" value="0">
                            <input type="checkbox" name="lock_until_previous" value="1" class="mt-1" <?php if($module->locksUntilPreviousFinished()): echo 'checked'; endif; ?>>
                            <span>
                                Lock this module until earlier modules are finished
                                <span class="mt-1 block text-xs font-normal leading-5 text-stone-600">Checked: trainees cannot open this module until they finish the modules before it. The first module in the batch stays open on its own. Unchecked: trainees can open this module even when an earlier module is still unfinished. Later modules stay locked when their own lock is checked.</span>
                            </span>
                        </label>
                        <button class="secondary-action mt-3 text-xs">Save lock setting</button>
                    </form>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <?php if($module->delivery_status !== 'closed'): ?>
                    <form method="POST" action="<?php echo e(route('trainer.modules.update', $module)); ?>" data-confirm="<?php echo e($module->delivery_status === 'active' ? 'Close this module to future enrollees? Existing assigned trainees will keep access.' : 'Publish this as the current active module? The previous active module will close to new enrollees.'); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PATCH'); ?>
                        <input type="hidden" name="module_code" value="<?php echo e($module->module_code); ?>">
                        <input type="hidden" name="competency_category" value="<?php echo e($module->competency_category); ?>">
                        <input type="hidden" name="completion_mode" value="<?php echo e($module->completion_mode ?? 'assessed'); ?>">
                        <input type="hidden" name="title" value="<?php echo e($module->title); ?>">
                        <input type="hidden" name="description" value="<?php echo e($module->description); ?>">
                        <input type="hidden" name="topic" value="<?php echo e($module->topic); ?>">
                        <input type="hidden" name="estimated_hours" value="<?php echo e($module->estimated_hours); ?>">
                        <input type="hidden" name="position" value="<?php echo e($module->position ?? 0); ?>">
                        <input type="hidden" name="audience_type" value="<?php echo e($isPrivate ? 'trainee' : 'batch'); ?>">
                        <input type="hidden" name="training_batch_id" value="<?php echo e($module->training_batch_id); ?>">
                        <input type="hidden" name="target_enrollment_application_id" value="<?php echo e($module->target_enrollment_application_id); ?>">
                        <input type="hidden" name="available_at" value="<?php echo e($module->available_at?->format('Y-m-d\TH:i')); ?>">
                        <input type="hidden" name="due_at" value="<?php echo e($module->due_at?->format('Y-m-d\TH:i')); ?>">
                        <input type="hidden" name="is_published" value="<?php echo e($module->is_published ? 0 : 1); ?>">
                        <input type="hidden" name="lock_until_previous" value="<?php echo e($module->locksUntilPreviousFinished() ? 1 : 0); ?>">
                        <input type="hidden" name="_return_to_module" value="1">
                        <button class="secondary-action text-xs">
                            <?php echo e($module->delivery_status === 'active' ? 'Close to new' : 'Publish'); ?>

                        </button>
                    </form>
                    <?php else: ?>
                        <span class="max-w-xs text-xs leading-5 text-amber-800">Closed. Assigned trainees keep access.</span>
                    <?php endif; ?>
                </div>
            </div>

            <nav class="mt-5 flex flex-wrap gap-2 border-t border-stone-100 pt-4" aria-label="Module Hub sections">
                <a href="<?php echo e(route('trainer.modules.show', ['module' => $module, 'tab' => 'materials'])); ?>#materials" class="rounded-xl px-4 py-2 text-xs font-bold transition <?php echo e($activeTab === 'materials' ? 'bg-purple-700 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200'); ?>">
                    Materials (<?php echo e(count($supplementaryList) + 1); ?>)
                </a>
                <?php if($module->requiresEvaluation()): ?>
                <a href="<?php echo e(route('trainer.modules.show', ['module' => $module, 'tab' => 'assessments'])); ?>#assessments" class="rounded-xl px-4 py-2 text-xs font-bold transition <?php echo e($activeTab === 'assessments' ? 'bg-purple-700 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200'); ?>">
                    Assessments (<?php echo e($quizzes->count()); ?>)
                </a>
                <a href="<?php echo e(route('trainer.modules.show', ['module' => $module, 'tab' => 'evaluations'])); ?>#evaluations" class="rounded-xl px-4 py-2 text-xs font-bold transition <?php echo e($activeTab === 'evaluations' ? 'bg-purple-700 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200'); ?>">
                    Grades (<?php echo e($trainees->count()); ?>)
                </a>
                <?php endif; ?>
            </nav>
        </header>
    </div>

    <!-- SECTION 1: LEARNING MATERIALS & PREVIEW -->
    <?php if($activeTab === 'materials'): ?>
    <section id="materials" class="space-y-4">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-stone-950">Primary Lesson Material</h2>
                    <p class="text-xs text-stone-500"><?php echo e($module->original_file_name); ?> · <?php echo e($module->fileTypeLabel()); ?> (<?php echo e(number_format(($module->file_size ?? 0) / 1048576, 2)); ?> MB)</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="<?php echo e($viewerUrl); ?>" target="_blank" rel="noopener" class="secondary-action text-xs">Open in New Tab</a>
                    <a href="<?php echo e($downloadUrl); ?>" class="primary-action text-xs">Download File</a>
                </div>
            </div>

            <?php if (isset($component)) { $__componentOriginal63d8f2fdcf624a967dc47b4d6ec2784d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal63d8f2fdcf624a967dc47b4d6ec2784d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.module-file-preview','data' => ['module' => $module,'viewerUrl' => $viewerUrl]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('module-file-preview'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['module' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module),'viewer-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($viewerUrl)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal63d8f2fdcf624a967dc47b4d6ec2784d)): ?>
<?php $attributes = $__attributesOriginal63d8f2fdcf624a967dc47b4d6ec2784d; ?>
<?php unset($__attributesOriginal63d8f2fdcf624a967dc47b4d6ec2784d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal63d8f2fdcf624a967dc47b4d6ec2784d)): ?>
<?php $component = $__componentOriginal63d8f2fdcf624a967dc47b4d6ec2784d; ?>
<?php unset($__componentOriginal63d8f2fdcf624a967dc47b4d6ec2784d); ?>
<?php endif; ?>

            <!-- Supplementary Files List -->
            <?php if(count($supplementaryList) > 0): ?>
                <div class="border-t border-stone-100 pt-4">
                    <h3 class="text-sm font-bold text-stone-900 mb-2">Supplementary Worksheets & Handouts (<?php echo e(count($supplementaryList)); ?>)</h3>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <?php $__currentLoopData = $supplementaryList; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $idx => $supp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="flex items-center justify-between rounded-xl border border-stone-200 bg-stone-50 p-3 text-xs">
                                <div class="min-w-0 pr-2">
                                    <p class="font-bold text-stone-900 truncate"><?php echo e($supp['original_name']); ?></p>
                                    <p class="text-[11px] text-stone-500"><?php echo e($supp['human_size'] ?? ''); ?></p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <a href="<?php echo e(route('trainer.modules.supplementary.download', [$module, $idx])); ?>" class="secondary-action text-xs py-1 px-2.5">
                                        Download
                                    </a>
                                    <form method="POST" action="<?php echo e(route('trainer.modules.supplementary.destroy', [$module, $idx])); ?>" onsubmit="return confirm('Remove this supplementary file?')">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button class="rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-bold text-rose-700 hover:bg-rose-50">Remove</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Add Supplementary Files Form -->
            <details class="rounded-xl border border-dashed border-stone-300 bg-stone-50/50 p-3 text-xs">
                <summary class="cursor-pointer font-bold text-purple-700 hover:text-purple-900 select-none">
                    + Attach More Supplementary Handouts / Worksheets
                </summary>
                <form method="POST" action="<?php echo e(route('trainer.modules.update', $module)); ?>" enctype="multipart/form-data" class="mt-3 space-y-3">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PATCH'); ?>
                    <input type="hidden" name="module_code" value="<?php echo e($module->module_code); ?>">
                    <input type="hidden" name="competency_category" value="<?php echo e($module->competency_category); ?>">
                    <input type="hidden" name="completion_mode" value="<?php echo e($module->completion_mode ?? 'assessed'); ?>">
                    <input type="hidden" name="title" value="<?php echo e($module->title); ?>">
                    <input type="hidden" name="description" value="<?php echo e($module->description); ?>">
                    <input type="hidden" name="topic" value="<?php echo e($module->topic); ?>">
                    <input type="hidden" name="audience_type" value="<?php echo e($isPrivate ? 'trainee' : 'batch'); ?>">
                    <input type="hidden" name="training_batch_id" value="<?php echo e($module->training_batch_id); ?>">
                    <input type="hidden" name="target_enrollment_application_id" value="<?php echo e($module->target_enrollment_application_id); ?>">
                    <input type="hidden" name="estimated_hours" value="<?php echo e($module->estimated_hours); ?>">
                    <input type="hidden" name="position" value="<?php echo e($module->position ?? 0); ?>">
                    <input type="hidden" name="available_at" value="<?php echo e($module->available_at?->format('Y-m-d\TH:i')); ?>">
                    <input type="hidden" name="due_at" value="<?php echo e($module->due_at?->format('Y-m-d\TH:i')); ?>">
                    <input type="hidden" name="is_published" value="<?php echo e($module->is_published ? 1 : 0); ?>">
                    <input type="hidden" name="lock_until_previous" value="<?php echo e($module->locksUntilPreviousFinished() ? 1 : 0); ?>">
                    <input type="hidden" name="_return_to_module" value="1">
                    <div>
                        <label class="block text-xs font-semibold text-stone-700 mb-1">Select files to append</label>
                        <input name="supplementary_files[]" type="file" multiple accept="<?php echo e(\App\Support\TrainingModuleFiles::acceptAttribute()); ?>" class="form-field">
                        <p class="mt-1 text-[11px] text-stone-500"><?php echo e(count($supplementaryList)); ?> of <?php echo e(\App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_FILES); ?> slots used; <?php echo e(number_format(\App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_UPLOAD_KB / 1024)); ?> MB maximum per file.</p>
                    </div>
                    <button type="submit" class="primary-action text-xs">Upload Attachments</button>
                </form>
            </details>
        </div>
    </section>
    <?php endif; ?>

    <!-- SECTION 2: MODULE ASSESSMENTS -->
    <?php if($activeTab === 'assessments' && $module->requiresEvaluation()): ?>
    <section id="assessments" class="space-y-4">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-stone-950">Module Assessments</h2>
                    <p class="text-xs text-stone-500">Quizzes attached directly to this module for knowledge evaluation.</p>
                </div>
                <button type="button" class="primary-action text-xs" data-dashboard-dialog-open="module-quiz-dialog">Create quiz</button>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Module quiz score summary">
                <div class="rounded-xl bg-purple-50 p-3 text-purple-950">
                    <span class="block text-[10px] font-semibold uppercase tracking-wide text-purple-700">Quizzes</span>
                    <strong class="mt-1 block text-xl"><?php echo e($quizScoreSummary['quiz_count']); ?></strong>
                </div>
                <div class="rounded-xl bg-stone-100 p-3 text-stone-950">
                    <span class="block text-[10px] font-semibold uppercase tracking-wide text-stone-600">Graded submissions</span>
                    <strong class="mt-1 block text-xl"><?php echo e($quizScoreSummary['submission_count']); ?></strong>
                </div>
                <div class="rounded-xl bg-sky-50 p-3 text-sky-950">
                    <span class="block text-[10px] font-semibold uppercase tracking-wide text-sky-700">Average score</span>
                    <strong class="mt-1 block text-xl"><?php echo e($quizScoreSummary['average_score'] === null ? '—' : number_format($quizScoreSummary['average_score'], 1).'%'); ?></strong>
                </div>
                <div class="rounded-xl bg-emerald-50 p-3 text-emerald-950">
                    <span class="block text-[10px] font-semibold uppercase tracking-wide text-emerald-700">Passed</span>
                    <strong class="mt-1 block text-xl"><?php echo e($quizScoreSummary['passed_count']); ?></strong>
                </div>
                <div class="rounded-xl bg-amber-50 p-3 text-amber-950">
                    <span class="block text-[10px] font-semibold uppercase tracking-wide text-amber-700">Needs review</span>
                    <strong class="mt-1 block text-xl"><?php echo e($quizScoreSummary['needs_review_count']); ?></strong>
                </div>
            </div>

            <div class="space-y-3">
                <?php $__empty_1 = true; $__currentLoopData = $quizzes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quiz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <div class="flex flex-col gap-3 rounded-xl border border-stone-200 bg-stone-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-stone-950"><?php echo e($quiz->title); ?></h3>
                                <span class="rounded px-2 py-0.5 text-[10px] font-bold <?php echo e($quiz->is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-700'); ?>">
                                    <?php echo e($quiz->is_published ? 'Active' : 'Draft'); ?>

                                </span>
                            </div>
                            <p class="mt-1 text-xs text-stone-600"><?php echo e($quiz->questions->count()); ?> Questions · Passing Score: <?php echo e(number_format($quiz->passing_score_percent, 0)); ?>% · Time Limit: <?php echo e($quiz->time_limit_minutes ? $quiz->time_limit_minutes.' mins' : 'Unlimited'); ?></p>
                            <?php if($quiz->instructions): ?><p class="mt-1 text-xs text-stone-500 italic"><?php echo e(str($quiz->instructions)->limit(120)); ?></p><?php endif; ?>
                            <p class="mt-1 text-[11px] font-semibold <?php echo e($quiz->trainingSubmodule ? 'text-purple-700' : 'text-amber-700'); ?>"><?php echo e($quiz->trainingSubmodule?->title ?? 'Legacy module-wide assessment'); ?></p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="<?php echo e(route('trainer.quizzes.edit', $quiz)); ?>" class="secondary-action text-xs py-1.5 px-3">
                                Edit Questions
                            </a>
                            <a href="<?php echo e(route('trainer.quizzes.results', $quiz)); ?>" class="primary-action text-xs py-1.5 px-3">
                                View Submissions
                            </a>
                        </div>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-6 text-center text-xs text-stone-500">
                        No quiz has been created for this module yet.
                    </div>
                <?php endif; ?>
            </div>

            <dialog id="module-quiz-dialog" data-dashboard-dialog data-auto-open="<?php echo e(old('_composer') === 'module-quiz' && $errors->any() ? 'true' : 'false'); ?>" class="lms-workflow-dialog is-landscape" aria-labelledby="module-quiz-title">
                <div class="lms-dialog-header">
                    <div><p class="lms-eyebrow"><?php echo e($module->title); ?></p><h2 id="module-quiz-title">Create module quiz</h2><p>Create the draft, then add the answer key and questions.</p></div>
                    <button type="button" data-dashboard-dialog-close class="lms-dialog-close" aria-label="Close quiz creator"><?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
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
                <form method="POST" action="<?php echo e(route('trainer.modules.quizzes.store', $module)); ?>" enctype="multipart/form-data" class="lms-form-grid lms-composer-form" data-dashboard-dialog-form data-submit-label="Creating quiz...">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="_composer" value="module-quiz">
                    <div class="lms-field lms-field-span-2">
                        <label class="block text-xs font-bold text-purple-900 mb-1">Assessment / Quiz Title</label>
                        <input name="title" required maxlength="160" placeholder="e.g. <?php echo e($module->title); ?> - Mastery Assessment" class="form-field">
                    </div>
                    <div class="lms-field lms-field-span-2">
                        <label class="block text-xs font-bold text-purple-900 mb-1">Competency Submodule</label>
                        <select name="training_submodule_id" class="form-field" required>
                            <option value="">Choose the outcome this quiz measures</option>
                            <?php $__currentLoopData = $submodules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $submodule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($submodule->id); ?>" <?php if((string) old('training_submodule_id') === (string) $submodule->id): echo 'selected'; endif; ?>><?php echo e($submodule->position); ?>. <?php echo e($submodule->title); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <p class="mt-1 text-[10px] text-stone-500">A trainee must pass this quiz before marking the selected submodule done.</p>
                    </div>
                    <div class="lms-field lms-field-span-2">
                        <label class="block text-xs font-bold text-purple-900 mb-1">Instructions</label>
                        <textarea name="instructions" rows="3" placeholder="Instructions for trainees before starting the quiz..." class="form-field"></textarea>
                    </div>
                    <div class="lms-field">
                        <label class="block text-xs font-bold text-purple-900 mb-1">Time Limit (Minutes)</label>
                        <input type="number" name="time_limit_minutes" value="20" min="1" max="180" class="form-field">
                    </div>
                    <div class="lms-field">
                        <label class="block text-xs font-bold text-purple-900 mb-1">Passing Score (%)</label>
                        <input type="number" name="passing_score_percent" value="75" min="50" max="100" class="form-field">
                    </div>
                    <?php echo $__env->make('trainer.quizzes.partials.question-file', ['inputId' => 'module-quiz-question-file'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <p class="lms-field-wide text-xs text-stone-600">The assessment starts as a draft. Add the answer key and questions before publishing it to trainees.</p>
                    <div class="lms-form-actions lms-field-wide">
                        <button type="button" data-dashboard-dialog-close class="secondary-action text-xs">Cancel</button>
                        <button type="submit" data-action-button class="primary-action text-xs">Create & Add Questions</button>
                    </div>
                </form>
            </dialog>
        </div>
    </section>
    <?php endif; ?>

    <!-- SECTION 3: LEARNER GRADES & EVALUATION MATRIX -->
    <?php if($activeTab === 'evaluations' && $module->requiresEvaluation()): ?>
    <section id="evaluations" class="space-y-4">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-stone-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-stone-950">Submodule Competency Evaluation</h2>
                    <p class="text-xs text-stone-500">Evaluate each competency outcome separately. The main module result is calculated automatically and has no manual dropdown.</p>
                </div>
                <div class="text-xs text-stone-600">
                    Batch: <strong><?php echo e($module->batch ? $module->batch->name.' '.$module->batch->year : 'Current Roster'); ?></strong>
                </div>
            </div>

            <div class="space-y-5">
                <?php $__empty_1 = true; $__currentLoopData = $trainees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trainee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <?php
                        $parentProgress = $progressByApp->get($trainee->id);
                        $traineeChildProgress = $submoduleProgressByApp->get($trainee->id, collect());
                        $traineeChildSummaries = $submoduleAssessmentSummaryByApp->get($trainee->id, collect());
                    ?>
                    <article class="rounded-2xl border border-stone-200 bg-stone-50/60 p-4">
                        <div class="flex flex-col gap-3 border-b border-stone-200 pb-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $trainee->user,'name' => trim($trainee->first_name.' '.$trainee->last_name),'class' => 'grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trainee->user),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(trim($trainee->first_name.' '.$trainee->last_name)),'class' => 'grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800']); ?>
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
                                <div>
                                    <p class="font-bold text-stone-950"><?php echo e($trainee->last_name); ?>, <?php echo e($trainee->first_name); ?></p>
                                    <p class="text-[11px] text-stone-500"><?php echo e($trainee->email); ?></p>
                                </div>
                            </div>
                            <div class="text-xs text-stone-600">
                                Main module: <strong class="<?php echo e($parentProgress?->isTrainerValidated() ? 'text-emerald-700' : 'text-purple-800'); ?>"><?php echo e($parentProgress?->workflowStatusLabel() ?? 'Not assigned'); ?></strong>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 xl:grid-cols-2">
                            <?php $__currentLoopData = $submodules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $submodule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php
                                    $childProgress = $traineeChildProgress->get($submodule->id);
                                    $childSummary = $traineeChildSummaries->get($submodule->id, []);
                                    $childAverage = $childSummary['average_score'] ?? null;
                                    $childHasQuiz = ($childSummary['required_count'] ?? 0) > 0;
                                    $childAllPassed = (bool) ($childSummary['all_passed'] ?? false);
                                    $childReadyForRemediation = (bool) ($childSummary['ready_for_remediation_evaluation'] ?? false);
                                    $canMarkCompetent = ! $childHasQuiz || $childAllPassed;
                                ?>
                                <form method="POST" action="<?php echo e(route('trainer.modules.evaluate', $module)); ?>" class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="enrollment_application_id" value="<?php echo e($trainee->id); ?>">
                                    <input type="hidden" name="training_submodule_id" value="<?php echo e($submodule->id); ?>">

                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-wider text-purple-700">Outcome <?php echo e($submodule->position); ?></p>
                                            <h3 class="mt-1 text-sm font-bold leading-5 text-stone-950"><?php echo e($submodule->title); ?></h3>
                                        </div>
                                        <span class="shrink-0 rounded px-2 py-1 text-[10px] font-bold <?php echo e($childProgress?->isTrainerValidated() ? 'bg-emerald-100 text-emerald-800' : ($childProgress?->status === 'awaiting_evaluation' ? 'bg-purple-100 text-purple-800' : ($childProgress?->status === 'needs_remediation' ? 'bg-amber-100 text-amber-800' : 'bg-stone-100 text-stone-700'))); ?>">
                                            <?php echo e($childProgress?->workflowStatusLabel() ?? 'Ready to start'); ?>

                                        </span>
                                    </div>

                                    <div class="grid gap-2 text-xs sm:grid-cols-2">
                                        <div class="rounded-lg bg-purple-50 p-2.5 text-purple-900">
                                            <span class="block text-[10px] font-semibold">Quiz / activity</span>
                                            <strong><?php echo e($childHasQuiz ? (($childSummary['passed_count'] ?? 0).' / '.($childSummary['required_count'] ?? 0).' passed') : 'No quiz assigned'); ?></strong>
                                            <?php if($childAverage !== null): ?><span class="block text-[10px]">Average <?php echo e(number_format((float) $childAverage, 1)); ?>%</span><?php endif; ?>
                                        </div>
                                        <div class="rounded-lg bg-stone-100 p-2.5 text-stone-800">
                                            <span class="block text-[10px] font-semibold">Face-to-face grade</span>
                                            <strong><?php echo e($childProgress?->evaluated_at ? $childProgress->evaluated_at->format('M d, g:i A') : 'Awaiting trainer evaluation'); ?></strong>
                                        </div>
                                    </div>

                                    <?php if($childHasQuiz && ! $childAllPassed && ! $childReadyForRemediation): ?>
                                        <p class="rounded-lg bg-amber-50 p-2 text-[11px] font-semibold leading-4 text-amber-800">Competent unlocks after assigned classwork is passed. You can still record Not yet competent from the face-to-face session.</p>
                                    <?php elseif($childReadyForRemediation): ?>
                                        <p class="rounded-lg bg-amber-50 p-2 text-[11px] font-semibold leading-4 text-amber-800">Attempts are exhausted. You may record Not Yet Competent with remediation feedback.</p>
                                    <?php endif; ?>

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-stone-700">Practical Rating</label>
                                            <select name="practical_rating" class="form-field">
                                                <option value="pending" <?php if(!$childProgress || $childProgress->practical_rating === 'pending'): echo 'selected'; endif; ?>>Pending</option>
                                                <option value="competent" <?php if($childProgress?->practical_rating === 'competent'): echo 'selected'; endif; ?>>Competent</option>
                                                <option value="not_yet_competent" <?php if($childProgress?->practical_rating === 'not_yet_competent'): echo 'selected'; endif; ?>>Not Yet Competent</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-stone-700">Outcome</label>
                                            <select name="competency_outcome" class="form-field" required>
                                                <option value="in_progress" <?php if(!$childProgress || $childProgress->competency_outcome === 'in_progress'): echo 'selected'; endif; ?>>In Progress</option>
                                                <option value="competent" <?php if($childProgress?->competency_outcome === 'competent'): echo 'selected'; endif; ?>>Competent (Passed)</option>
                                                <option value="not_yet_competent" <?php if($childProgress?->competency_outcome === 'not_yet_competent'): echo 'selected'; endif; ?>>Not Yet Competent</option>
                                            </select>
                                            <?php if (! ($canMarkCompetent)): ?>
                                                <p class="mt-1 text-[10px] font-semibold leading-4 text-amber-700">Competent unlocks after assigned classwork is passed.</p>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-stone-700">Trainer Remarks</label>
                                        <textarea name="evaluation_remarks" rows="2" placeholder="Practical demonstration or remediation feedback..." class="form-field"><?php echo e($childProgress?->evaluation_remarks); ?></textarea>
                                    </div>

                                    <button type="submit" class="primary-action w-full justify-center py-2 text-xs">Save Submodule Evaluation</button>
                                </form>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <p class="rounded-xl bg-stone-50 p-6 text-center text-xs text-stone-500">No approved trainees are assigned to this delivery.</p>
                <?php endif; ?>
            </div>

            <div class="hidden" aria-hidden="true">
                <table class="dashboard-table w-full min-w-[58rem]">
                    <thead>
                        <tr>
                            <th>Learner</th>
                            <th>Material Progress</th>
                            <th>Quiz Score</th>
                            <th>Practical Demo / Activity</th>
                            <th>Competency Outcome</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__empty_1 = true; $__currentLoopData = $trainees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trainee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <?php
                                $progress = $progressByApp->get($trainee->id);
                                $isCompetent = $progress?->competency_outcome === 'competent';
                                $isNyc = $progress?->competency_outcome === 'not_yet_competent';
                                $assessmentSummary = $assessmentSummaryByApp->get($trainee->id, []);
                                $assessmentAverage = $assessmentSummary['average_score'] ?? null;
                                $readyForRemediation = (bool) ($assessmentSummary['ready_for_remediation_evaluation'] ?? false);
                                $canMarkCompetent = ($assessmentSummary['required_count'] ?? 0) === 0
                                    || (bool) ($assessmentSummary['all_passed'] ?? false);
                            ?>
                            <tr class="align-middle">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <?php if (isset($component)) { $__componentOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalaa6ddd3b8ee0acee5a2d1d7ac5c7e40e = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.user-avatar','data' => ['user' => $trainee->user,'name' => trim($trainee->first_name.' '.$trainee->last_name),'class' => 'grid h-9 w-9 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('user-avatar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['user' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($trainee->user),'name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(trim($trainee->first_name.' '.$trainee->last_name)),'class' => 'grid h-9 w-9 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800']); ?>
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
                                            <p class="font-bold text-stone-950"><?php echo e($trainee->last_name); ?>, <?php echo e($trainee->first_name); ?></p>
                                            <p class="truncate text-[11px] text-stone-500"><?php echo e($trainee->email); ?></p>
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
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold <?php echo e($progress?->isTrainerValidated() ? 'text-emerald-700' : ($progress?->status === 'awaiting_evaluation' ? 'text-purple-700' : 'text-stone-600')); ?>">
                                        <?php echo e($progress?->workflowStatusLabel() ?? 'Not assigned'); ?>

                                    </span>
                                </td>
                                <td>
                                    <span class="font-bold <?php echo e($assessmentAverage !== null ? 'text-purple-900' : 'text-stone-400'); ?>">
                                        <?php echo e($assessmentAverage !== null ? number_format((float)$assessmentAverage, 1).'%' : 'N/A'); ?>

                                    </span>
                                    <span class="mt-1 block text-[10px] text-stone-500">Quiz/activity only</span>
                                </td>
                                <td>
                                    <span class="rounded px-2 py-0.5 text-xs font-bold <?php echo e($progress?->practical_rating === 'competent' ? 'bg-emerald-100 text-emerald-800' : ($progress?->practical_rating === 'not_yet_competent' ? 'bg-rose-100 text-rose-800' : 'bg-stone-100 text-stone-600')); ?>">
                                        <?php echo e($progress?->practicalRatingLabel() ?? 'Pending'); ?>

                                    </span>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-bold <?php echo e($isCompetent ? 'bg-emerald-100 text-emerald-900 ring-1 ring-emerald-300' : ($isNyc ? 'bg-amber-100 text-amber-900 ring-1 ring-amber-300' : 'bg-stone-100 text-stone-700')); ?>">
                                        <?php echo e($isCompetent ? '🟢 Competent' : ($isNyc ? '🟡 For Remediation' : 'In Progress')); ?>

                                    </span>
                                    <?php if($progress?->evaluation_remarks): ?>
                                        <p class="mt-1 text-[10px] text-stone-500 italic max-w-xs truncate">"<?php echo e($progress->evaluation_remarks); ?>"</p>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($progress?->status === 'locked'): ?>
                                        <span class="inline-flex max-w-44 rounded-lg bg-stone-100 px-3 py-2 text-xs font-semibold leading-5 text-stone-600">Evaluation opens after this trainee becomes competent in the previous assigned module.</span>
                                    <?php else: ?>
                                    <details class="relative">
                                        <summary class="cursor-pointer rounded-lg bg-stone-100 hover:bg-stone-200 px-3 py-1.5 text-xs font-bold text-stone-800 select-none">
                                            <?php echo e($progress?->status === 'awaiting_evaluation' ? 'Evaluate Submission' : ($readyForRemediation ? 'Evaluate Remediation' : ($progress?->isTrainerValidated() ? 'View Completed Record' : 'Review'))); ?>

                                        </summary>
                                        <div class="absolute right-0 z-20 mt-2 w-80 rounded-2xl border border-stone-200 bg-white p-4 shadow-xl text-xs space-y-3">
                                            <div class="font-bold text-stone-900 border-b border-stone-100 pb-2">
                                                Grade: <?php echo e($trainee->first_name); ?> <?php echo e($trainee->last_name); ?>

                                            </div>
                                            <?php if($readyForRemediation): ?>
                                                <p class="rounded-lg bg-amber-50 p-2 text-amber-800">The trainee used every attempt and still has failed classwork. You may record Not Yet Competent and remediation feedback from the face-to-face session.</p>
                                            <?php elseif(($assessmentSummary['required_count'] ?? 0) > 0 && ! ($assessmentSummary['all_passed'] ?? false)): ?>
                                                <p class="rounded-lg bg-amber-50 p-2 text-amber-800">Competent unlocks after assigned classwork is passed. You can still record Not yet competent from the face-to-face session.</p>
                                            <?php endif; ?>
                                            <form method="POST" action="<?php echo e(route('trainer.modules.evaluate', $module)); ?>" class="space-y-3">
                                                <?php echo csrf_field(); ?>
                                                <input type="hidden" name="enrollment_application_id" value="<?php echo e($trainee->id); ?>">

                                                <div class="rounded-lg border border-purple-100 bg-purple-50 p-3">
                                                    <span class="block font-semibold text-purple-900">Quiz & Activity Average</span>
                                                    <strong class="mt-1 block text-lg text-purple-950"><?php echo e($assessmentAverage !== null ? number_format((float)$assessmentAverage, 1).'%' : 'No submitted score'); ?></strong>
                                                    <span class="mt-1 block text-[10px] text-purple-700">Calculated from this module's classwork only. This is not the official overall course grade.</span>
                                                </div>

                                                <div>
                                                    <label class="block font-semibold text-stone-700 mb-1">Practical Demonstration Rating</label>
                                                    <select name="practical_rating" class="form-field">
                                                        <option value="competent" <?php if($progress?->practical_rating === 'competent'): echo 'selected'; endif; ?>>Competent (C) - Passed</option>
                                                        <option value="not_yet_competent" <?php if($progress?->practical_rating === 'not_yet_competent'): echo 'selected'; endif; ?>>Not Yet Competent (NYC)</option>
                                                        <option value="pending" <?php if(!$progress || $progress->practical_rating === 'pending'): echo 'selected'; endif; ?>>Pending Evaluation</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block font-semibold text-stone-700 mb-1">Overall Module Outcome</label>
                                                    <select name="competency_outcome" class="form-field" required>
                                                        <option value="competent" data-competent-outcome-option <?php if($progress?->competency_outcome === 'competent'): echo 'selected'; endif; ?>>Competent (Passed)</option>
                                                        <option value="not_yet_competent" <?php if($progress?->competency_outcome === 'not_yet_competent'): echo 'selected'; endif; ?>>Not Yet Competent (For Remediation)</option>
                                                        <option value="in_progress" <?php if(!$progress || $progress->competency_outcome === 'in_progress'): echo 'selected'; endif; ?>>In Progress</option>
                                                    </select>
                                                    <?php if (! ($canMarkCompetent)): ?>
                                                        <p class="mt-1 text-[10px] font-semibold leading-4 text-amber-700">Competent is selectable, but saving it requires passed assigned classwork.</p>
                                                    <?php endif; ?>
                                                </div>

                                                <div>
                                                    <label class="block font-semibold text-stone-700 mb-1">Trainer Remarks</label>
                                                    <textarea name="evaluation_remarks" rows="2" placeholder="F2F demo feedback..." class="form-field"><?php echo e($progress?->evaluation_remarks); ?></textarea>
                                                </div>

                                                <button type="submit" class="primary-action w-full justify-center text-xs py-2">
                                                    Save Evaluation
                                                </button>
                                            </form>
                                        </div>
                                    </details>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <tr>
                                <td colspan="6" class="text-center py-6 text-stone-500">No approved trainees assigned to this batch.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if (isset($component)) { $__componentOriginalad64fd8082a658cbdd3ae55129ff9480 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalad64fd8082a658cbdd3ae55129ff9480 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.classroom-comments','data' => ['commentable' => $module,'comments' => $classroomComments,'privateRecipients' => $privateCommentRecipients]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('classroom-comments'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['commentable' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($module),'comments' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($classroomComments),'private-recipients' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($privateCommentRecipients)]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalad64fd8082a658cbdd3ae55129ff9480)): ?>
<?php $attributes = $__attributesOriginalad64fd8082a658cbdd3ae55129ff9480; ?>
<?php unset($__attributesOriginalad64fd8082a658cbdd3ae55129ff9480); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalad64fd8082a658cbdd3ae55129ff9480)): ?>
<?php $component = $__componentOriginalad64fd8082a658cbdd3ae55129ff9480; ?>
<?php unset($__componentOriginalad64fd8082a658cbdd3ae55129ff9480); ?>
<?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('trainer.layouts.app', ['title' => $module->title.' | MCARE Module Hub'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\modules\show.blade.php ENDPATH**/ ?>