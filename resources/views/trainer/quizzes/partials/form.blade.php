@php
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
@endphp

<div class="lms-page" data-quiz-builder data-import-url="{{ $questionImportUrl }}">
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Quiz builder</p>
            <h1>{{ $pageTitle }}</h1>
            <p>{{ $pageDescription }}</p>
        </div>
        <div class="lms-header-actions">
            <a href="{{ $moduleReturnUrl }}" class="secondary-action">Back to module</a>
            <button type="submit" class="primary-action" form="quiz-builder-form">{{ $submitLabel }}</button>
        </div>
    </header>

    <form id="quiz-builder-form" method="POST" action="{{ $formAction }}" class="lms-builder-form">
        @csrf
        @if($formMethod !== 'POST') @method($formMethod) @endif

        <section class="lms-builder-section lms-quiz-overview" aria-labelledby="quiz-details-heading" data-quiz-overview>
            <div class="lms-builder-section-heading">
                <span>1</span>
                <div>
                    <h2 id="quiz-details-heading">Quiz details</h2>
                    <p>Title, class, schedule, and scoring for this assessment.</p>
                </div>
            </div>

            @if($editing)
                <input type="hidden" name="title" value="{{ old('title', $quiz?->title) }}">
                <input type="hidden" name="instructions" value="{{ old('instructions', $quiz?->instructions) }}">
                <input type="hidden" name="training_module_id" value="{{ $selectedModuleId }}">
                <input type="hidden" name="training_submodule_id" value="{{ $selectedSubmoduleId }}">
                <input type="hidden" name="audience_type" value="{{ $isPrivate ? 'trainee' : 'batch' }}">
                <input type="hidden" name="training_batch_id" value="{{ $selectedBatchId }}">
                <input type="hidden" name="target_enrollment_application_id" value="{{ $selectedTraineeId }}">
                <input type="hidden" name="available_at" value="{{ $availableAtValue }}">
                <input type="hidden" name="due_at" value="{{ $dueAtValue }}">
                <input type="hidden" name="time_limit_minutes" value="{{ $timeLimitValue }}">
                <input type="hidden" name="attempt_limit" value="{{ $attemptLimitValue }}">
                <input type="hidden" name="passing_score_percent" value="{{ $passingScoreValue }}">
                <input type="hidden" name="is_published" value="{{ $isPublishedValue ? '1' : '0' }}">

                <dl class="lms-quiz-facts">
                    <div class="lms-quiz-fact-wide">
                        <dt>Title</dt>
                        <dd>{{ old('title', $quiz?->title) ?: 'Untitled quiz' }}</dd>
                    </div>
                    <div>
                        <dt>Learning module</dt>
                        <dd>{{ $moduleLabel }}</dd>
                    </div>
                    <div>
                        <dt>Competency submodule</dt>
                        <dd>{{ $submoduleLabel }}</dd>
                    </div>
                    <div class="lms-quiz-fact-wide">
                        <dt>Assign to</dt>
                        <dd>{{ $audienceLabel }}</dd>
                    </div>
                    <div>
                        <dt>Available from</dt>
                        <dd>{{ $formatDateTime($availableAtValue, 'Opens immediately') }}</dd>
                    </div>
                    <div>
                        <dt>Due date</dt>
                        <dd>{{ $formatDateTime($dueAtValue, 'No due date') }}</dd>
                    </div>
                    <div>
                        <dt>Time limit</dt>
                        <dd>{{ filled($timeLimitValue) ? $timeLimitValue.' minutes' : 'No time limit' }}</dd>
                    </div>
                    <div>
                        <dt>Allowed attempts</dt>
                        <dd>{{ $attemptLimitValue }} {{ \Illuminate\Support\Str::plural('attempt', (int) $attemptLimitValue) }}</dd>
                    </div>
                    <div>
                        <dt>Passing score</dt>
                        <dd>{{ rtrim(rtrim(number_format((float) $passingScoreValue, 2), '0'), '.') }}%</dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd>{{ $isPublishedValue ? 'Published' : 'Draft' }}</dd>
                    </div>
                </dl>
                @error('title')<p class="lms-field-error">{{ $message }}</p>@enderror
                @error('training_module_id')<p class="lms-field-error">{{ $message }}</p>@enderror
                @error('training_submodule_id')<p class="lms-field-error">{{ $message }}</p>@enderror
                @error('due_at')<p class="lms-field-error">{{ $message }}</p>@enderror
            @else
                <div class="lms-form-grid">
                    <div class="lms-field lms-field-wide">
                        <label for="quiz-title">Title</label>
                        <input id="quiz-title" name="title" value="{{ old('title', $quiz?->title) }}" required maxlength="160" placeholder="Example: Infection Control Knowledge Check">
                        @error('title')<p class="lms-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="lms-field">
                        <label for="quiz-module">Learning Module</label>
                        <select id="quiz-module" name="training_module_id" class="form-field" required>
                            <option value="">Choose a learning module</option>
                            @foreach(($modules ?? []) as $mod)
                                <option value="{{ $mod->id }}" @selected((string) old('training_module_id', $quiz?->training_module_id) === (string) $mod->id)>
                                    {{ $mod->module_code ? '['.$mod->module_code.'] ' : '' }}{{ $mod->title }}
                                </option>
                            @endforeach
                        </select>
                        @error('training_module_id')<p class="lms-field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="lms-field">
                        <label for="quiz-submodule">Competency Submodule</label>
                        <select id="quiz-submodule" name="training_submodule_id" class="form-field">
                            <option value="">Legacy module-wide assessment</option>
                            @foreach(($modules ?? []) as $mod)
                                @foreach($mod->submodules as $submodule)
                                    <option value="{{ $submodule->id }}" data-parent-module="{{ $mod->id }}" @selected((string) old('training_submodule_id', $quiz?->training_submodule_id) === (string) $submodule->id)>
                                        {{ $mod->title }} — {{ $submodule->title }}
                                    </option>
                                @endforeach
                            @endforeach
                        </select>
                        @error('training_submodule_id')<p class="lms-field-error">{{ $message }}</p>@enderror
                    </div>
                    <fieldset class="lms-audience-fieldset lms-field-wide" data-audience-scope>
                        <legend>Assign to</legend>
                        <div class="lms-choice-grid">
                            <label class="lms-choice-card">
                                <span class="lms-choice-title"><input type="radio" name="audience_type" value="batch" data-audience-control @checked(! $isPrivate)> Entire class</span>
                                <span class="lms-choice-help">Every active trainee in one batch</span>
                                <select name="training_batch_id" data-audience-batch aria-label="Choose class">
                                    <option value="">Choose class</option>
                                    @foreach($batches as $batch)
                                        <option value="{{ $batch->id }}" @selected((string) old('training_batch_id', $quiz?->training_batch_id) === (string) $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="lms-choice-card">
                                <span class="lms-choice-title"><input type="radio" name="audience_type" value="trainee" data-audience-control @checked($isPrivate)> Specific trainee</span>
                                <span class="lms-choice-help">Private assessment or retake</span>
                                <select name="target_enrollment_application_id" data-audience-trainee aria-label="Choose trainee">
                                    <option value="">Choose approved trainee</option>
                                    @foreach($trainees as $trainee)
                                        <option value="{{ $trainee->id }}" @selected((string) old('target_enrollment_application_id', $quiz?->target_enrollment_application_id) === (string) $trainee->id)>{{ $trainee->last_name }}, {{ $trainee->first_name }} - {{ $trainee->batch?->name ?? 'No class' }}{{ $trainee->learning_status === \App\Models\EnrollmentApplication::LEARNING_GRADUATED ? ' - Graduated in this batch' : '' }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </fieldset>
                    <div class="lms-field"><label for="quiz-available-at">Available from</label><input id="quiz-available-at" type="datetime-local" name="available_at" value="{{ $availableAtValue }}"></div>
                    <div class="lms-field"><label for="quiz-due-at">Due date</label><input id="quiz-due-at" type="datetime-local" name="due_at" value="{{ $dueAtValue }}"></div>
                    <div class="lms-field"><label for="quiz-time-limit">Time limit (minutes)</label><input id="quiz-time-limit" type="number" name="time_limit_minutes" value="{{ $timeLimitValue ?? 30 }}" min="1" max="240"></div>
                    <div class="lms-field"><label for="quiz-attempt-limit">Allowed attempts</label><input id="quiz-attempt-limit" type="number" name="attempt_limit" value="{{ $attemptLimitValue }}" min="1" max="5"></div>
                    <div class="lms-field"><label for="quiz-passing-score">Passing score (%)</label><input id="quiz-passing-score" type="number" name="passing_score_percent" value="{{ $passingScoreValue }}" min="1" max="100"></div>
                    <div class="lms-form-options">
                        <label class="lms-check">
                            <input type="hidden" name="is_published" value="0">
                            <input type="checkbox" name="is_published" value="1" @checked($isPublishedValue)>
                            <span>Publish after saving</span>
                        </label>
                    </div>
                </div>
            @endif
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
                <p>CSV needs a header row: <code>type, prompt, points, answer, option_a, option_b</code>. <a href="{{ $questionTemplateUrl }}">Download a sample text file</a>.</p>
                <p class="lms-import-status" data-quiz-import-status role="status"></p>
            </div>

            <div class="lms-question-list" data-quiz-question-list>
                @foreach($questionRows as $questionIndex => $question)
                    @php
                        $questionType = data_get($question, 'type', 'multiple_choice');
                        $questionOptions = array_values(data_get($question, 'options', []));
                        if ($questionType === 'true_false') $questionOptions = ['True', 'False'];
                        while (count($questionOptions) < ($questionType === 'true_false' ? 2 : 4)) $questionOptions[] = '';
                    @endphp
                    <article class="lms-question-card" data-quiz-question data-question-index="{{ $questionIndex }}">
                        <header>
                            <span class="lms-question-number">Question {{ $questionIndex + 1 }}</span>
                            <button type="button" class="lms-text-action is-danger" data-remove-question>Remove</button>
                        </header>
                        <input type="hidden" name="questions[{{ $questionIndex }}][id]" value="{{ data_get($question, 'id') }}">
                        <div class="lms-form-grid">
                            <div class="lms-field">
                                <label for="question-{{ $questionIndex }}-type">Question type</label>
                                <select id="question-{{ $questionIndex }}-type" name="questions[{{ $questionIndex }}][type]" data-question-type>
                                    <option value="multiple_choice" @selected($questionType === 'multiple_choice')>Multiple choice</option>
                                    <option value="true_false" @selected($questionType === 'true_false')>True or false</option>
                                    <option value="file_upload" @selected($questionType === 'file_upload')>Activity Document (PDF or image)</option>
                                    <option value="enumeration" @selected($questionType === 'enumeration')>Enumeration / Written Activity</option>
                                </select>
                            </div>
                            <div class="lms-field">
                                <label for="question-{{ $questionIndex }}-points">Points</label>
                                <input id="question-{{ $questionIndex }}-points" type="number" name="questions[{{ $questionIndex }}][points]" value="{{ data_get($question, 'points', 1) }}" min="1" max="100" required>
                            </div>
                            <div class="lms-field lms-field-wide">
                                <label for="question-{{ $questionIndex }}-prompt">Question / Activity Instructions</label>
                                <textarea id="question-{{ $questionIndex }}-prompt" name="questions[{{ $questionIndex }}][prompt]" rows="3" maxlength="2000" required placeholder="Example: Create a DOCX or PDF file answering: Tell me about yourself and your caregiving background.">{{ data_get($question, 'prompt') }}</textarea>
                            </div>
                        </div>
                        <fieldset class="lms-option-fieldset" @if(in_array($questionType, ['file_upload', 'enumeration'], true)) hidden @endif>
                            <legend>Answer options</legend>
                            <div class="lms-option-list" data-quiz-option-list>
                                @foreach($questionOptions as $optionIndex => $option)
                                    <div class="lms-option-row" data-quiz-option>
                                        <span class="lms-option-letter" aria-hidden="true">{{ chr(65 + $optionIndex) }}</span>
                                        <label class="sr-only" for="question-{{ $questionIndex }}-option-{{ $optionIndex }}">Option {{ $optionIndex + 1 }}</label>
                                        <input id="question-{{ $questionIndex }}-option-{{ $optionIndex }}" name="questions[{{ $questionIndex }}][options][{{ $optionIndex }}]" value="{{ $option }}" @if(!in_array($questionType, ['file_upload', 'enumeration'], true)) required @endif @readonly($questionType === 'true_false')>
                                        <button type="button" class="lms-option-remove" data-remove-option aria-label="Remove option {{ $optionIndex + 1 }}">x</button>
                                    </div>
                                @endforeach
                            </div>
                            <button type="button" class="lms-text-action" data-add-option @if($questionType === 'true_false' || in_array($questionType, ['file_upload', 'enumeration'], true)) hidden @endif>Add option</button>
                        </fieldset>
                        <div class="lms-field lms-correct-answer" @if(in_array($questionType, ['file_upload', 'enumeration'], true)) hidden @endif>
                            <label for="question-{{ $questionIndex }}-correct">Correct answer</label>
                            <select id="question-{{ $questionIndex }}-correct" name="questions[{{ $questionIndex }}][correct_option]" data-correct-option @if(in_array($questionType, ['file_upload', 'enumeration'], true)) disabled @endif>
                                @foreach($questionOptions as $optionIndex => $option)
                                    <option value="{{ $optionIndex }}" @selected((int) data_get($question, 'correct_option', 0) === $optionIndex)>Option {{ chr(65 + $optionIndex) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </article>
                @endforeach
            </div>

            <button type="button" class="lms-add-question" data-add-question>
                <span aria-hidden="true">+</span>
                <span><strong>Add question / activity</strong><small>Multiple choice, true/false, document upload, or enumeration</small></span>
            </button>
        </section>

        <div class="lms-sticky-submit">
            <a href="{{ $moduleReturnUrl }}" class="secondary-action">Cancel</a>
            <button type="submit" class="primary-action">{{ $submitLabel }}</button>
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
                    @foreach(range(0, 3) as $optionIndex)
                        <div class="lms-option-row" data-quiz-option><span class="lms-option-letter" aria-hidden="true">{{ chr(65 + $optionIndex) }}</span><label class="sr-only" for="question-__INDEX__-option-{{ $optionIndex }}">Option {{ $optionIndex + 1 }}</label><input id="question-__INDEX__-option-{{ $optionIndex }}" name="questions[__INDEX__][options][{{ $optionIndex }}]" required><button type="button" class="lms-option-remove" data-remove-option aria-label="Remove option {{ $optionIndex + 1 }}">x</button></div>
                    @endforeach
                </div>
                <button type="button" class="lms-text-action" data-add-option>Add option</button>
            </fieldset>
            <div class="lms-field lms-correct-answer"><label for="question-__INDEX__-correct">Correct answer</label><select id="question-__INDEX__-correct" name="questions[__INDEX__][correct_option]" data-correct-option>@foreach(range(0, 3) as $optionIndex)<option value="{{ $optionIndex }}">Option {{ chr(65 + $optionIndex) }}</option>@endforeach</select></div>
        </article>
    </template>
</div>
