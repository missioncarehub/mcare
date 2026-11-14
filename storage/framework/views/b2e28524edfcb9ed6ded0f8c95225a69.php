<?php $__env->startSection('content'); ?>
    <section class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="dashboard-section-kicker">Learning module</p>
                <h1 class="dashboard-section-title mt-2 text-2xl"><?php echo e($module->title); ?></h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Create the quiz here and import its questions from a text, CSV, or JSON file. The assigned trainer can keep editing the same quiz.</p>
            </div>
            <a href="<?php echo e(route('admin.learning.modules')); ?>" class="secondary-action">Back to modules</a>
        </header>

        <?php if($module->requiresEvaluation()): ?>
            <form method="POST" action="<?php echo e(route('admin.learning.modules.quizzes.store', $module)); ?>" enctype="multipart/form-data" class="dashboard-panel grid gap-4 md:grid-cols-2">
                <?php echo csrf_field(); ?>
                <div class="md:col-span-2">
                    <h2 class="text-lg font-bold text-slate-950">New quiz</h2>
                    <p class="mt-1 text-sm text-slate-600">The quiz stays a draft until its questions are saved and a trainer publishes it.</p>
                </div>
                <div>
                    <label for="admin-quiz-title" class="mb-1 block text-sm font-bold text-slate-800">Title</label>
                    <input id="admin-quiz-title" name="title" value="<?php echo e(old('title')); ?>" required maxlength="160" class="form-field" placeholder="Example: Infection control check">
                    <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-red-700"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="admin-quiz-submodule" class="mb-1 block text-sm font-bold text-slate-800">Competency submodule</label>
                    <select id="admin-quiz-submodule" name="training_submodule_id" class="form-field" required>
                        <option value="">Choose the outcome this quiz measures</option>
                        <?php $__currentLoopData = $module->submodules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $submodule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($submodule->id); ?>" <?php if((string) old('training_submodule_id') === (string) $submodule->id): echo 'selected'; endif; ?>><?php echo e($submodule->position); ?>. <?php echo e($submodule->title); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <?php $__errorArgs = ['training_submodule_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-red-700"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div>
                    <label for="admin-quiz-time" class="mb-1 block text-sm font-bold text-slate-800">Time limit (minutes)</label>
                    <input id="admin-quiz-time" type="number" name="time_limit_minutes" value="<?php echo e(old('time_limit_minutes', 20)); ?>" min="1" max="180" class="form-field">
                </div>
                <div>
                    <label for="admin-quiz-pass" class="mb-1 block text-sm font-bold text-slate-800">Passing score (%)</label>
                    <input id="admin-quiz-pass" type="number" name="passing_score_percent" value="<?php echo e(old('passing_score_percent', 75)); ?>" min="50" max="100" class="form-field">
                </div>
                <div class="md:col-span-2">
                    <?php echo $__env->make('trainer.quizzes.partials.question-file', [
                        'inputId' => 'admin-quiz-question-file',
                        'templateUrl' => route('admin.learning.quizzes.import-template'),
                    ], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    <?php $__errorArgs = ['quiz'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-red-700"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="primary-action">Create quiz</button>
                </div>
            </form>
        <?php else: ?>
            <p class="dashboard-panel text-sm text-slate-600">This module is learning material only, so it does not take a required quiz.</p>
        <?php endif; ?>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Quiz</th>
                        <th class="px-4 py-3">Submodule</th>
                        <th class="px-4 py-3">Questions</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php $__empty_1 = true; $__currentLoopData = $module->quizzes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $quiz): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <tr>
                            <td class="px-4 py-4">
                                <p class="font-bold text-slate-950"><?php echo e($quiz->title); ?></p>
                                <p class="mt-1 text-xs text-slate-500">Passing score <?php echo e(rtrim(rtrim(number_format((float) $quiz->passing_score_percent, 2), '0'), '.')); ?>%</p>
                            </td>
                            <td class="px-4 py-4 text-slate-700"><?php echo e($quiz->trainingSubmodule?->title ?? 'Module-wide assessment'); ?></td>
                            <td class="px-4 py-4 font-semibold text-slate-800"><?php echo e($quiz->questions_count); ?></td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold <?php echo e($quiz->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'); ?>">
                                    <?php echo e($quiz->is_published ? 'Published' : 'Draft'); ?>

                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <a href="<?php echo e(route('admin.learning.quizzes.edit', $quiz)); ?>" class="secondary-action inline-flex h-9 items-center text-xs">Edit questions</a>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-slate-500">No quiz has been created for this module yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', ['title' => 'Module Quizzes | MCARE Admin'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\admin\learning\quizzes.blade.php ENDPATH**/ ?>