<?php
    $editing = ($quiz?->exists ?? false);
    $existingQuestions = $quiz?->questions?->map(function ($question) {
        return [
            'id' => $question->id,
            'type' => $question->type,
            'prompt' => $question->prompt,
            'points' => $question->points,
            'correct_option' => $question->correct_option,
            'options' => collect($question->options ?? [])->values()->all(),
        ];
    })->values()->all() ?? [];
    $questionRows = old('questions', $existingQuestions);
    if (count($questionRows) === 0) {
        $questionRows = [[
            'id' => null,
            'type' => 'multiple_choice',
            'prompt' => '',
            'points' => 1,
            'correct_option' => 0,
            'options' => ['', '', '', ''],
        ]];
    }
    $isPrivate = filled(old('target_enrollment_application_id', $quiz?->target_enrollment_application_id));
    $isAdminQuiz = request()->routeIs('admin.learning.quizzes.*');
    $questionImportUrl = $isAdminQuiz
        ? route('admin.learning.quizzes.import')
        : route('trainer.quizzes.import');
    $questionTemplateUrl = $isAdminQuiz
        ? route('admin.learning.quizzes.import-template')
        : route('trainer.quizzes.import-template');
    $moduleReturnUrl = $isAdminQuiz
        ? ($quiz?->training_module_id
            ? route('admin.learning.modules.quizzes', $quiz->training_module_id)
            : route('admin.learning.modules'))
        : ($quiz?->training_module_id
            ? route('trainer.modules.show', $quiz->training_module_id).'#assessments'
            : route('trainer.resources'));
    $selectedModuleId = old('training_module_id', $quiz?->training_module_id);
    $selectedSubmoduleId = old('training_submodule_id', $quiz?->training_submodule_id);
    $selectedBatchId = old('training_batch_id', $quiz?->training_batch_id);
    $selectedTraineeId = old('target_enrollment_application_id', $quiz?->target_enrollment_application_id);
    $selectedModule = collect($modules ?? [])->firstWhere('id', (int) $selectedModuleId) ?? $quiz?->trainingModule;
    $selectedSubmodule = $selectedModule?->submodules?->firstWhere('id', (int) $selectedSubmoduleId) ?? $quiz?->trainingSubmodule;
    $selectedBatch = collect($batches ?? [])->firstWhere('id', (int) $selectedBatchId) ?? $quiz?->batch;
    $selectedTrainee = collect($trainees ?? [])->firstWhere('id', (int) $selectedTraineeId) ?? $quiz?->targetTrainee;
    $formatDateTime = static function ($value, string $empty = 'Not set'): string {
        if (blank($value)) {
            return $empty;
        }

        try {
            return \Illuminate\Support\Carbon::parse($value)->format('M d, Y g:i A');
        } catch (\Throwable) {
            return (string) $value;
        }
    };
    $availableAtValue = old('available_at', $quiz?->available_at?->format('Y-m-d\TH:i'));
    $dueAtValue = old('due_at', $quiz?->due_at?->format('Y-m-d\TH:i'));
    $timeLimitValue = old('time_limit_minutes', $quiz?->time_limit_minutes);
    $attemptLimitValue = old('attempt_limit', $quiz?->attempt_limit ?? 1);
    $passingScoreValue = old('passing_score_percent', $quiz?->passing_score_percent ?? 75);
    $isPublishedValue = filter_var(old('is_published', $quiz?->is_published ?? false), FILTER_VALIDATE_BOOLEAN);
    $moduleLabel = $selectedModule
        ? trim(($selectedModule->module_code ? '['.$selectedModule->module_code.'] ' : '').$selectedModule->title)
        : 'Not assigned';
    $submoduleLabel = $selectedSubmodule
        ? $selectedSubmodule->title
        : 'Module-wide assessment';
    $audienceLabel = $isPrivate
        ? trim(($selectedTrainee?->last_name ?? '').', '.($selectedTrainee?->first_name ?? '')).' — specific trainee'
        : trim(($selectedBatch?->name ?? 'Class').' '.($selectedBatch?->year ?? '')).' — entire class';
?>

<div class="lms-page" data-quiz-builder data-import-url="<?php echo e($questionImportUrl); ?>">
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Quiz builder</p>
            <h1><?php echo e($pageTitle); ?></h1>
            <p><?php echo e($pageDescription); ?></p>
        </div>
        <div class="lms-header-actions">
            <a href="<?php echo e($moduleReturnUrl); ?>" class="secondary-action">Back to module</a>
            <button type="submit" class="primary-action" form="quiz-builder-form"><?php echo e($submitLabel); ?></button>
        </div>
    </header>

    <form id="quiz-builder-form" method="POST" action="<?php echo e($formAction); ?>" class="lms-builder-form">
        <?php echo csrf_field(); ?>
        <?php if($formMethod !== 'POST'): ?> <?php echo method_field($formMethod); ?> <?php endif; ?>

        <section class="lms-builder-section lms-quiz-overview" aria-labelledby="quiz-details-heading" data-quiz-overview>
            <div class="lms-builder-section-heading">
                <span>1</span>
                <div>
                    <h2 id="quiz-details-heading">Quiz details</h2>
                    <p>Title, class, schedule, and scoring for this assessment.</p>
                </div>
            </div>

            <?php if($editing): ?>
                <input type="hidden" name="title" value="<?php echo e(old('title', $quiz?->title)); ?>">
                <input type="hidden" name="instructions" value="<?php echo e(old('instructions', $quiz?->instructions)); ?>">
                <input type="hidden" name="training_module_id" value="<?php echo e($selectedModuleId); ?>">
                <input type="hidden" name="training_submodule_id" value="<?php echo e($selectedSubmoduleId); ?>">
                <input type="hidden" name="audience_type" value="<?php echo e($isPrivate ? 'trainee' : 'batch'); ?>">
                <input type="hidden" name="training_batch_id" value="<?php echo e($selectedBatchId); ?>">
                <input type="hidden" name="target_enrollment_application_id" value="<?php echo e($selectedTraineeId); ?>">
                <input type="hidden" name="available_at" value="<?php echo e($availableAtValue); ?>">
                <input type="hidden" name="due_at" value="<?php echo e($dueAtValue); ?>">
                <input type="hidden" name="time_limit_minutes" value="<?php echo e($timeLimitValue); ?>">
                <input type="hidden" name="attempt_limit" value="<?php echo e($attemptLimitValue); ?>">
                <input type="hidden" name="passing_score_percent" value="<?php echo e($passingScoreValue); ?>">
                <input type="hidden" name="is_published" value="<?php echo e($isPublishedValue ? '1' : '0'); ?>">

                <dl class="lms-quiz-facts">
                    <div class="lms-quiz-fact-wide">
                        <dt>Title</dt>
                        <dd><?php echo e(old('title', $quiz?->title) ?: 'Untitled quiz'); ?></dd>
                    </div>
                    <div>
                        <dt>Learning module</dt>
                        <dd><?php echo e($moduleLabel); ?></dd>
                    </div>
                    <div>
                        <dt>Competency submodule</dt>
                        <dd><?php echo e($submoduleLabel); ?></dd>
                    </div>
                    <div class="lms-quiz-fact-wide">
                        <dt>Assign to</dt>
                        <dd><?php echo e($audienceLabel); ?></dd>
                    </div>
                    <div>
                        <dt>Available from</dt>
                        <dd><?php echo e($formatDateTime($availableAtValue, 'Opens immediately')); ?></dd>
                    </div>
                    <div>
                        <dt>Due date</dt>
                        <dd><?php echo e($formatDateTime($dueAtValue, 'No due date')); ?></dd>
                    </div>
                    <div>
                        <dt>Time limit</dt>
                        <dd><?php echo e(filled($timeLimitValue) ? $timeLimitValue.' minutes' : 'No time limit'); ?></dd>
                    </div>
                    <div>
                        <dt>Allowed attempts</dt>
                        <dd><?php echo e($attemptLimitValue); ?> <?php echo e(\Illuminate\Support\Str::plural('attempt', (int) $attemptLimitValue)); ?></dd>
                    </div>
                    <div>
                        <dt>Passing score</dt>
                        <dd><?php echo e(rtrim(rtrim(number_format((float) $passingScoreValue, 2), '0'), '.')); ?>%</dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd><?php echo e($isPublishedValue ? 'Published' : 'Draft'); ?></dd>
                    </div>
                </dl>
                <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="lms-field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php $__errorArgs = ['training_module_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="lms-field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php $__errorArgs = ['training_submodule_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="lms-field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                <?php $__errorArgs = ['due_at'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="lms-field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
            <?php else: ?>
                <div class="lms-form-grid">
                    <div class="lms-field lms-field-wide">
                        <label for="quiz-title">Title</label>
                        <input id="quiz-title" name="title" value="<?php echo e(old('title', $quiz?->title)); ?>" required maxlength="160" placeholder="Example: Infection Control Knowledge Check">
                        <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="lms-field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="lms-field">
                        <label for="quiz-module">Learning Module</label>
                        <select id="quiz-module" name="training_module_id" class="form-field" required>
                            <option value="">Choose a learning module</option>
                            <?php $__currentLoopData = ($modules ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($mod->id); ?>" <?php if((string) old('training_module_id', $quiz?->training_module_id) === (string) $mod->id): echo 'selected'; endif; ?>>
                                    <?php echo e($mod->module_code ? '['.$mod->module_code.'] ' : ''); ?><?php echo e($mod->title); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['training_module_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="lms-field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <div class="lms-field">
                        <label for="quiz-submodule">Competency Submodule</label>
                        <select id="quiz-submodule" name="training_submodule_id" class="form-field">
                            <option value="">Legacy module-wide assessment</option>
                            <?php $__currentLoopData = ($modules ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mod): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <?php $__currentLoopData = $mod->submodules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $submodule): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($submodule->id); ?>" data-parent-module="<?php echo e($mod->id); ?>" <?php if((string) old('training_submodule_id', $quiz?->training_submodule_id) === (string) $submodule->id): echo 'selected'; endif; ?>>
                                        <?php echo e($mod->title); ?> — <?php echo e($submodule->title); ?>

                                    </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php $__errorArgs = ['training_submodule_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="lms-field-error"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>
                    <fieldset class="lms-audience-fieldset lms-field-wide" data-audience-scope>
                        <legend>Assign to</legend>
                        <div class="lms-choice-grid">
                            <label class="lms-choice-card">
                                <span class="lms-choice-title"><input type="radio" name="audience_type" value="batch" data-audience-control <?php if(! $isPrivate): echo 'checked'; endif; ?>> Entire class</span>
                                <span class="lms-choice-help">Every active trainee in one batch</span>
                                <select name="training_batch_id" data-audience-batch aria-label="Choose class">
                                    <option value="">Choose class</option>
                                    <?php $__currentLoopData = $batches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($batch->id); ?>" <?php if((string) old('training_batch_id', $quiz?->training_batch_id) === (string) $batch->id): echo 'selected'; endif; ?>><?php echo e($batch->name); ?> <?php echo e($batch->year); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </label>
                            <label class="lms-choice-card">
                                <span class="lms-choice-title"><input type="radio" name="audience_type" value="trainee" data-audience-control <?php if($isPrivate): echo 'checked'; endif; ?>> Specific trainee</span>
                                <span class="lms-choice-help">Private assessment or retake</span>
                                <select name="target_enrollment_application_id" data-audience-trainee aria-label="Choose trainee">
                                    <option value="">Choose approved trainee</option>
                                    <?php $__currentLoopData = $trainees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $trainee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($trainee->id); ?>" <?php if((string) old('target_enrollment_application_id', $quiz?->target_enrollment_application_id) === (string) $trainee->id): echo 'selected'; endif; ?>><?php echo e($trainee->last_name); ?>, <?php echo e($trainee->first_name); ?> - <?php echo e($trainee->batch?->name ?? 'No class'); ?><?php echo e($trainee->learning_status === \App\Models\EnrollmentApplication::LEARNING_GRADUATED ? ' - Graduated in this batch' : ''); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                            </label>
                        </div>
                    </fieldset>
                    <div class="lms-field"><label for="quiz-available-at">Available from</label><input id="quiz-available-at" type="datetime-local" name="available_at" value="<?php echo e($availableAtValue); ?>"></div>
                    <div class="lms-field"><label for="quiz-due-at">Due date</label><input id="quiz-due-at" type="datetime-local" name="due_at" value="<?php echo e($dueAtValue); ?>"></div>
                    <div class="lms-field"><label for="quiz-time-limit">Time limit (minutes)</label><input id="quiz-time-limit" type="number" name="time_limit_minutes" value="<?php echo e($timeLimitValue ?? 30); ?>" min="1" max="240"></div>
                    <div class="lms-field"><label for="quiz-attempt-limit">Allowed attempts</label><input id="quiz-attempt-limit" type="number" name="attempt_limit" value="<?php echo e($attemptLimitValue); ?>" min="1" max="5"></div>
                    <div class="lms-field"><label for="quiz-passing-score">Passing score (%)</label><input id="quiz-passing-score" type="number" name="passing_score_percent" value="<?php echo e($passingScoreValue); ?>" min="1" max="100"></div>
                    <div class="lms-form-options">
                        <label class="lms-check">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" <?php if($isPublishedValue): echo 'checked'; endif; ?>>
                            <span>Publish after saving</span>
                        </label>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="lms-builder-section" aria-labelledby="quiz-questions-heading">
            <div class="lms-builder-section-heading">
                <span>2</span>
                <div><h2 id="quiz-questions-heading">Questions</h2><p>Add questions one at a time, or import them from a text, CSV, or JSON file.</p></div>
            </div>

            <div class="lms-question-import" data-quiz-import>
                <div>
                    <h3>Import questions</h3>
                    <p>Choose a <strong>.txt</strong>, <strong>.csv</strong>, or <strong>.json</strong> file. A clean file is added here so you can review it before saving. If any row is missing an answer or repeats a question, the file is listed and nothing is added until it is fixed. In a text file, leave a blank line between questions and mark the correct choice with <strong>*</strong> or <code>Answer: B</code>.</p>
                </div>
                <label class="sr-only" for="quiz-question-import">Question file</label>
                <input id="quiz-question-import" type="file" data-quiz-import-file accept=".txt,.text,.csv,.json,text/plain,text/csv,application/json">
                <p>CSV needs a header row: <code>type, prompt, points, answer, option_a, option_b</code>. <a href="<?php echo e($questionTemplateUrl); ?>">Download a sample text file</a>.</p>
                <p class="lms-import-status" data-quiz-import-status role="status"></p>
            </div>

            <div class="lms-question-list" data-quiz-question-list>
                <?php $__currentLoopData = $questionRows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $questionIndex => $question): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $questionType = data_get($question, 'type', 'multiple_choice');
                        $questionOptions = array_values(data_get($question, 'options', []));
                        if ($questionType === 'true_false') $questionOptions = ['True', 'False'];
                        while (count($questionOptions) < ($questionType === 'true_false' ? 2 : 4)) $questionOptions[] = '';
                    ?>
                    <article class="lms-question-card" data-quiz-question data-question-index="<?php echo e($questionIndex); ?>">
                        <header>
                            <span class="lms-question-number">Question <?php echo e($questionIndex + 1); ?></span>
                            <button type="button" class="lms-text-action is-danger" data-remove-question>Remove</button>
                        </header>
                        <input type="hidden" name="questions[<?php echo e($questionIndex); ?>][id]" value="<?php echo e(data_get($question, 'id')); ?>">
                        <div class="lms-form-grid">
                            <div class="lms-field">
                                <label for="question-<?php echo e($questionIndex); ?>-type">Question type</label>
                                <select id="question-<?php echo e($questionIndex); ?>-type" name="questions[<?php echo e($questionIndex); ?>][type]" data-question-type>
                                    <option value="multiple_choice" <?php if($questionType === 'multiple_choice'): echo 'selected'; endif; ?>>Multiple choice</option>
                                    <option value="true_false" <?php if($questionType === 'true_false'): echo 'selected'; endif; ?>>True or false</option>
                                    <option value="file_upload" <?php if($questionType === 'file_upload'): echo 'selected'; endif; ?>>Activity Document (PDF or image)</option>
                                    <option value="enumeration" <?php if($questionType === 'enumeration'): echo 'selected'; endif; ?>>Enumeration / Written Activity</option>
                                </select>
                            </div>
                            <div class="lms-field">
                                <label for="question-<?php echo e($questionIndex); ?>-points">Points</label>
                                <input id="question-<?php echo e($questionIndex); ?>-points" type="number" name="questions[<?php echo e($questionIndex); ?>][points]" value="<?php echo e(data_get($question, 'points', 1)); ?>" min="1" max="100" required>
                            </div>
                            <div class="lms-field lms-field-wide">
                                <label for="question-<?php echo e($questionIndex); ?>-prompt">Question / Activity Instructions</label>
                                <textarea id="question-<?php echo e($questionIndex); ?>-prompt" name="questions[<?php echo e($questionIndex); ?>][prompt]" rows="3" maxlength="2000" required placeholder="Example: Create a DOCX or PDF file answering: Tell me about yourself and your caregiving background."><?php echo e(data_get($question, 'prompt')); ?></textarea>
                            </div>
                        </div>
                        <fieldset class="lms-option-fieldset" <?php if(in_array($questionType, ['file_upload', 'enumeration'], true)): ?> hidden <?php endif; ?>>
                            <legend>Answer options</legend>
                            <div class="lms-option-list" data-quiz-option-list>
                                <?php $__currentLoopData = $questionOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionIndex => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="lms-option-row" data-quiz-option>
                                        <span class="lms-option-letter" aria-hidden="true"><?php echo e(chr(65 + $optionIndex)); ?></span>
                                        <label class="sr-only" for="question-<?php echo e($questionIndex); ?>-option-<?php echo e($optionIndex); ?>">Option <?php echo e($optionIndex + 1); ?></label>
                                        <input id="question-<?php echo e($questionIndex); ?>-option-<?php echo e($optionIndex); ?>" name="questions[<?php echo e($questionIndex); ?>][options][<?php echo e($optionIndex); ?>]" value="<?php echo e($option); ?>" <?php if(!in_array($questionType, ['file_upload', 'enumeration'], true)): ?> required <?php endif; ?> <?php if($questionType === 'true_false'): echo 'readonly'; endif; ?>>
                                        <button type="button" class="lms-option-remove" data-remove-option aria-label="Remove option <?php echo e($optionIndex + 1); ?>">x</button>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                            <button type="button" class="lms-text-action" data-add-option <?php if($questionType === 'true_false' || in_array($questionType, ['file_upload', 'enumeration'], true)): ?> hidden <?php endif; ?>>Add option</button>
                        </fieldset>
                        <div class="lms-field lms-correct-answer" <?php if(in_array($questionType, ['file_upload', 'enumeration'], true)): ?> hidden <?php endif; ?>>
                            <label for="question-<?php echo e($questionIndex); ?>-correct">Correct answer</label>
                            <select id="question-<?php echo e($questionIndex); ?>-correct" name="questions[<?php echo e($questionIndex); ?>][correct_option]" data-correct-option <?php if(in_array($questionType, ['file_upload', 'enumeration'], true)): ?> disabled <?php endif; ?>>
                                <?php $__currentLoopData = $questionOptions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionIndex => $option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($optionIndex); ?>" <?php if((int) data_get($question, 'correct_option', 0) === $optionIndex): echo 'selected'; endif; ?>>Option <?php echo e(chr(65 + $optionIndex)); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                    </article>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <button type="button" class="lms-add-question" data-add-question>
                <span aria-hidden="true">+</span>
                <span><strong>Add question / activity</strong><small>Multiple choice, true/false, document upload, or enumeration</small></span>
            </button>
        </section>

        <div class="lms-sticky-submit">
            <a href="<?php echo e($moduleReturnUrl); ?>" class="secondary-action">Cancel</a>
            <button type="submit" class="primary-action"><?php echo e($submitLabel); ?></button>
        </div>
    </form>

    <template data-quiz-question-template>
        <article class="lms-question-card" data-quiz-question data-question-index="__INDEX__">
            <header><span class="lms-question-number">Question __NUMBER__</span><button type="button" class="lms-text-action is-danger" data-remove-question>Remove</button></header>
            <input type="hidden" name="questions[__INDEX__][id]" value="">
            <div class="lms-form-grid">
                <div class="lms-field"><label for="question-__INDEX__-type">Question type</label><select id="question-__INDEX__-type" name="questions[__INDEX__][type]" data-question-type><option value="multiple_choice">Multiple choice</option><option value="true_false">True or false</option><option value="file_upload">Activity Document (PDF or image)</option><option value="enumeration">Enumeration / Written Activity</option></select></div>
                <div class="lms-field"><label for="question-__INDEX__-points">Points</label><input id="question-__INDEX__-points" type="number" name="questions[__INDEX__][points]" value="1" min="1" max="100" required></div>
                <div class="lms-field lms-field-wide"><label for="question-__INDEX__-prompt">Question / Activity Instructions</label><textarea id="question-__INDEX__-prompt" name="questions[__INDEX__][prompt]" rows="3" maxlength="2000" required placeholder="Example: Create a DOCX or PDF file answering: Tell me about yourself and your caregiving background."></textarea></div>
            </div>
            <fieldset class="lms-option-fieldset">
                <legend>Answer options</legend>
                <div class="lms-option-list" data-quiz-option-list>
                    <?php $__currentLoopData = range(0, 3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionIndex): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="lms-option-row" data-quiz-option><span class="lms-option-letter" aria-hidden="true"><?php echo e(chr(65 + $optionIndex)); ?></span><label class="sr-only" for="question-__INDEX__-option-<?php echo e($optionIndex); ?>">Option <?php echo e($optionIndex + 1); ?></label><input id="question-__INDEX__-option-<?php echo e($optionIndex); ?>" name="questions[__INDEX__][options][<?php echo e($optionIndex); ?>]" required><button type="button" class="lms-option-remove" data-remove-option aria-label="Remove option <?php echo e($optionIndex + 1); ?>">x</button></div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <button type="button" class="lms-text-action" data-add-option>Add option</button>
            </fieldset>
            <div class="lms-field lms-correct-answer"><label for="question-__INDEX__-correct">Correct answer</label><select id="question-__INDEX__-correct" name="questions[__INDEX__][correct_option]" data-correct-option><?php $__currentLoopData = range(0, 3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $optionIndex): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($optionIndex); ?>">Option <?php echo e(chr(65 + $optionIndex)); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
        </article>
    </template>
</div>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainer\quizzes\partials\form.blade.php ENDPATH**/ ?>