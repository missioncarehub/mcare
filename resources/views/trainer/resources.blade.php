@extends('trainer.layouts.app', ['title' => 'Classwork | MCARE Trainer'])

@section('content')
@php
    $activeBatch = $assignedBatch ?? $batches->firstWhere('is_active', true);
    $moduleList = collect($modules);
    $quizList = collect($quizzes ?? []);
    $publishedModuleCount = $moduleList->where('is_published', true)->count();
    $activeQuizCount = $quizList->where('is_published', true)->count();
@endphp

<div class="lms-page" data-lms-classwork>
    <div class="lms-header-actions lms-classwork-toolbar">
        <button type="button" class="secondary-action" data-dashboard-dialog-open="quiz-creator-dialog">
            <x-dashboard-icon name="list-check" class="h-4 w-4" />
            Create quiz
        </button>
        <button type="button" class="primary-action" data-dashboard-dialog-open="module-creator-dialog">
            <x-dashboard-icon name="plus" class="h-4 w-4" />
            Create module
        </button>
    </div>

    <!-- Content Type View Switcher Filter -->
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white p-3 shadow-sm">
        <div class="flex flex-wrap gap-1.5" role="tablist" aria-label="Classwork view filter">
            <button type="button" class="rounded-xl px-4 py-2 text-xs font-bold transition lms-filter-btn" data-classwork-tab="modules">
                Learning Modules ({{ $moduleList->count() }})
            </button>
            <button type="button" class="rounded-xl px-4 py-2 text-xs font-bold transition lms-filter-btn" data-classwork-tab="assessments">
                Quizzes & Assessments ({{ $quizList->count() }})
            </button>
        </div>
        <div class="text-xs text-stone-500 font-medium">
            Active Batch: <strong class="text-stone-800">{{ $activeBatch ? $activeBatch->name.' '.$activeBatch->year : 'No batch assigned' }}</strong>
        </div>
    </div>

    <dialog id="module-creator-dialog" data-dashboard-dialog data-auto-open="{{ old('_composer') === 'module' && $errors->any() ? 'true' : 'false' }}" class="lms-workflow-dialog" aria-labelledby="module-creator-title">
        <div class="lms-dialog-header">
            <div>
                <p class="lms-eyebrow">New classwork</p>
                <h2 id="module-creator-title">Create learning module</h2>
                <p>Set the lesson, audience, schedule, and protected learning files.</p>
            </div>
            <button type="button" data-dashboard-dialog-close class="lms-dialog-close" aria-label="Close module creator"><x-dashboard-icon name="xmark" /></button>
        </div>

        <form method="POST" action="{{ route('trainer.modules.store') }}" enctype="multipart/form-data" class="lms-form-grid lms-composer-form" data-dashboard-dialog-form data-submit-label="Creating module...">
            @csrf
            <input type="hidden" name="_composer" value="module">

            <!-- Caregiving NC II Core Preset Selector -->
            <div class="lms-field lms-field-wide">
                <label for="module-preset-select" class="font-bold text-purple-900">Caregiving NC II Course Module Preset</label>
                <x-module-preset-select
                    id="module-preset-select"
                    :units="$catalogUnits"
                    data-module-preset-select
                    class="form-field border-purple-300 focus:border-purple-600"
                />
                <small class="text-xs text-slate-500">These options come from the admin LMS modules catalog. Choosing one fills the official module code, title, category, hours, and suggested subtopics.</small>
            </div>

            <div class="lms-field">
                <label for="module-code">Module Code</label>
                <input id="module-code" name="module_code" value="{{ old('module_code') }}" maxlength="50" class="font-mono font-bold" placeholder="e.g. HCS323301">
                @error('module_code')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="lms-field">
                <label for="module-category">Competency Category</label>
                <select id="module-category" name="competency_category" class="form-field">
                    <option value="core" @selected(old('competency_category') === 'core')>Core Competency</option>
                    <option value="common" @selected(old('competency_category') === 'common')>Common Competency</option>
                    <option value="basic" @selected(old('competency_category') === 'basic')>Basic Competency</option>
                    <option value="custom" @selected(old('competency_category') === 'custom')>Institutional / Custom</option>
                </select>
                @error('competency_category')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="lms-field lms-field-wide">
                <label for="module-completion-mode">Module Requirement</label>
                <select id="module-completion-mode" name="completion_mode" class="form-field" required>
                    <option value="assessed" @selected(old('completion_mode', 'assessed') === 'assessed')>Assessed module — classwork and trainer evaluation required</option>
                    <option value="material_only" @selected(old('completion_mode') === 'material_only')>Learning material only — no Mark as Done or competency evaluation</option>
                </select>
                <small class="text-xs text-slate-500">Choose learning material only for references or lessons that should not block rolling module progression.</small>
                @error('completion_mode')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="lms-field">
                <label for="module-title">Module Title</label>
                <input id="module-title" name="title" value="{{ old('title') }}" required maxlength="160" placeholder="e.g. Provide Care and Support to Infants and Toddlers">
                @error('title')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="lms-field">
                <label for="module-topic">Sub-topic / Learning Outcome</label>
                <input id="module-topic" name="topic" list="subtopics-list" value="{{ old('topic') }}" maxlength="120" placeholder="e.g. Comfort infants and toddlers">
                <datalist id="subtopics-list"></datalist>
            </div>

            <div class="lms-field lms-field-wide" data-submodule-builder>
                <label>Submodules / Competency Outcomes</label>
                <p class="mb-2 text-xs text-slate-500">Client presets automatically load every official outcome. For a custom assessed module, enter each outcome the trainer will evaluate separately.</p>
                <div class="space-y-2" data-submodule-list>
                    @foreach(old('submodule_titles', ['']) as $submoduleTitle)
                        <input name="submodule_titles[]" value="{{ $submoduleTitle }}" maxlength="255" class="form-field" placeholder="Submodule or competency outcome">
                    @endforeach
                </div>
                <button type="button" class="secondary-action mt-2 text-xs" data-add-submodule>Add custom submodule</button>
                @error('submodule_titles')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="lms-field">
                <label for="module-hours">Nominal / Estimated Hours</label>
                <input id="module-hours" type="number" name="estimated_hours" value="{{ old('estimated_hours', 40) }}" min="1" max="500" placeholder="e.g. 40">
            </div>

            <div class="lms-field">
                <label for="module-position">Display Order</label>
                <input id="module-position" type="number" name="position" value="{{ old('position', 0) }}" min="0" max="999">
            </div>

            <div class="lms-field lms-field-wide">
                <label for="module-description">Instructions & Overview</label>
                <textarea id="module-description" name="description" required maxlength="5000" rows="3" placeholder="Tell trainees what to review, learning elements covered, and practical objectives.">{{ old('description') }}</textarea>
                @error('description')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <fieldset class="lms-audience-fieldset lms-field-wide" data-audience-scope>
                <legend>Assign to</legend>
                <div class="lms-choice-grid">
                    <label class="lms-choice-card">
                        <span class="lms-choice-title"><input type="radio" name="audience_type" value="batch" data-audience-control @checked(old('audience_type', 'batch') === 'batch')> Entire class</span>
                        <span class="lms-choice-help">Everyone in one approved batch</span>
                        <select name="training_batch_id" data-audience-batch aria-label="Choose class">
                            <option value="">Choose class</option>
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" @selected((string) old('training_batch_id', $activeBatch?->id) === (string) $batch->id)>
                                    {{ $batch->name }} {{ $batch->year }}{{ $batch->is_active ? ' - Active' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="lms-choice-card">
                        <span class="lms-choice-title"><input type="radio" name="audience_type" value="trainee" data-audience-control @checked(old('audience_type') === 'trainee')> Specific trainee</span>
                        <span class="lms-choice-help">Private support or remediation material</span>
                        <select name="target_enrollment_application_id" data-audience-trainee aria-label="Choose trainee">
                            <option value="">Choose approved trainee</option>
                            @foreach($trainees as $trainee)
                                <option value="{{ $trainee->id }}" @selected((string) old('target_enrollment_application_id') === (string) $trainee->id)>
                                    {{ $trainee->last_name }}, {{ $trainee->first_name }} - {{ $trainee->batch?->name ?? 'No class' }}{{ $trainee->learning_status === \App\Models\EnrollmentApplication::LEARNING_GRADUATED ? ' - Graduated in this batch' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
                @error('audience_type')<p class="lms-field-error">{{ $message }}</p>@enderror
            </fieldset>

            <div class="lms-field">
                <label for="module-available-at">Available from</label>
                <input id="module-available-at" type="datetime-local" name="available_at" value="{{ old('available_at') }}">
            </div>
            <div class="lms-field">
                <label for="module-due-at">Due date</label>
                <input id="module-due-at" type="datetime-local" name="due_at" value="{{ old('due_at') }}">
            </div>

            <!-- Primary Lesson File Upload -->
            <div class="lms-field lms-field-wide">
                <label for="module-file">Primary Lesson File ({{ \App\Support\TrainingModuleFiles::humanLabel() }})</label>
                <div class="lms-file-picker">
                    <input id="module-file" name="module_file" type="file" required accept="{{ \App\Support\TrainingModuleFiles::acceptAttribute() }}" data-lms-file-input data-file-preview-input="module-file" data-watermark-preview-url="{{ route('trainer.modules.preview-watermark') }}">
                    <div class="lms-file-preview" data-lms-file-preview aria-live="polite">
                        <x-dashboard-icon name="cloud-arrow-up" />
                        <span><strong>Choose primary lesson file</strong><small>{{ \App\Support\TrainingModuleFiles::humanLabel() }} - maximum 38MB</small></span>
                    </div>
                </div>
                {{-- Path: resources/views/trainer/resources.blade.php | Label: Primary lesson file preview + remove --}}
                <div class="mt-3 hidden rounded-xl border border-slate-200 bg-slate-50 p-3" data-file-preview-panel="module-file">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-slate-500">Preview</p>
                            <p class="mt-0.5 truncate text-sm font-bold text-slate-900" data-file-preview-name>—</p>
                            <p class="text-[11px] text-slate-500" data-file-preview-size></p>
                        </div>
                        <button type="button" class="secondary-action text-xs" data-file-preview-remove="module-file">Remove / replace</button>
                    </div>
                    <div class="mt-3" data-file-preview-body></div>
                </div>
                @error('module_file')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <!-- Supplementary Attachments Upload -->
            <div class="lms-field lms-field-wide">
                <label for="module-supplementary-files">Supplementary Handouts / Worksheets (Optional, Multiple)</label>
                <input id="module-supplementary-files" name="supplementary_files[]" type="file" multiple accept="{{ \App\Support\TrainingModuleFiles::acceptAttribute() }}" class="form-field file:mr-4 file:rounded-lg file:border-0 file:bg-purple-50 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-purple-700 hover:file:bg-purple-100">
                <small class="mt-1 block text-xs text-slate-500">Up to {{ \App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_FILES }} worksheets, reference documents, or activity rubrics; {{ number_format(\App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_UPLOAD_KB / 1024) }} MB maximum per file.</small>
                @error('supplementary_files')<p class="lms-field-error">{{ $message }}</p>@enderror
                @error('supplementary_files.*')<p class="lms-field-error">{{ $message }}</p>@enderror
            </div>

            <div class="lms-form-options lms-field-wide">
                <label class="lms-check">
                    <input type="hidden" name="is_published" value="0">
                    <input type="checkbox" name="is_published" value="1" @checked(old('is_published', true))>
                    <span data-module-publication-copy>Make this the active batch module. The previous active delivery closes only to future enrollees.</span>
                </label>
            </div>
            <div class="lms-form-actions lms-field-wide">
                <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                <button class="primary-action" data-action-button>Create & Open Module Hub</button>
            </div>
        </form>
    </dialog>

    <dialog id="quiz-creator-dialog" data-dashboard-dialog data-auto-open="{{ old('_composer') === 'quiz' && $errors->any() ? 'true' : 'false' }}" class="lms-workflow-dialog is-landscape" aria-labelledby="quiz-creator-title">
        <div class="lms-dialog-header">
            <div>
                <p class="lms-eyebrow">New assessment</p>
                <h2 id="quiz-creator-title">Create quiz</h2>
                <p>Create the draft here, then continue in the full question builder.</p>
            </div>
            <button type="button" data-dashboard-dialog-close class="lms-dialog-close" aria-label="Close quiz creator"><x-dashboard-icon name="xmark" /></button>
        </div>

        <form method="POST" action="{{ route('trainer.quizzes.store') }}" class="lms-form-grid lms-composer-form" data-dashboard-dialog-form data-submit-label="Creating quiz...">
            @csrf
            <input type="hidden" name="_composer" value="quiz">

            <div class="lms-field lms-field-span-2">
                <label for="quick-quiz-title" class="font-bold text-amber-950">Quiz Title</label>
                <input id="quick-quiz-title" name="title" required maxlength="160" placeholder="e.g. Provide Infant Care - Unit Assessment" class="form-field border-amber-300">
            </div>

            <div class="lms-field lms-field-span-2">
                <label for="quick-quiz-module" class="font-bold text-amber-950">Parent Learning Module</label>
                <select id="quick-quiz-module" name="training_module_id" class="form-field border-amber-300" required>
                    <option value="">-- Choose target learning module --</option>
                    @foreach($modules->filter->requiresEvaluation() as $mod)
                        <option value="{{ $mod->id }}">
                            {{ $mod->module_code ? '['.$mod->module_code.'] ' : '' }}{{ $mod->title }} ({{ $mod->categoryLabel() }})
                        </option>
                    @endforeach
                </select>
                <small class="text-xs text-stone-500">Every assessment belongs to a specific learning module.</small>
            </div>

            <div class="lms-field lms-field-span-2">
                <label for="quick-quiz-submodule" class="font-bold text-amber-950">Competency Submodule</label>
                <select id="quick-quiz-submodule" name="training_submodule_id" class="form-field border-amber-300" required>
                    <option value="">-- Choose the outcome this quiz evaluates --</option>
                    @foreach($modules->filter->requiresEvaluation() as $mod)
                        @foreach($mod->submodules as $submodule)
                            <option value="{{ $submodule->id }}" data-parent-module="{{ $mod->id }}">{{ $mod->title }} — {{ $submodule->title }}</option>
                        @endforeach
                    @endforeach
                </select>
                <small class="text-xs text-stone-500">New assessments are tracked against one submodule. Existing legacy quizzes remain module-wide.</small>
            </div>

            <div class="lms-field lms-field-span-2">
                <label for="quick-quiz-instructions" class="font-bold text-stone-900">Instructions (Optional)</label>
                <textarea id="quick-quiz-instructions" name="instructions" rows="3" placeholder="Instructions for trainees before taking the quiz..." class="form-field"></textarea>
            </div>

            <fieldset class="lms-audience-fieldset lms-field-wide" data-audience-scope>
                <legend>Assign Assessment to</legend>
                <div class="lms-choice-grid">
                    <label class="lms-choice-card">
                        <span class="lms-choice-title"><input type="radio" name="audience_type" value="batch" data-audience-control checked> Entire class</span>
                        <span class="lms-choice-help">Everyone in the assigned batch</span>
                        <select name="training_batch_id" data-audience-batch aria-label="Choose class">
                            @foreach($batches as $batch)
                                <option value="{{ $batch->id }}" @selected((string) $activeBatch?->id === (string) $batch->id)>
                                    {{ $batch->name }} {{ $batch->year }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                    <label class="lms-choice-card">
                        <span class="lms-choice-title"><input type="radio" name="audience_type" value="trainee" data-audience-control> Specific trainee</span>
                        <span class="lms-choice-help">Remediation or individual retake</span>
                        <select name="target_enrollment_application_id" data-audience-trainee aria-label="Choose trainee">
                            <option value="">Choose approved trainee</option>
                            @foreach($trainees as $trainee)
                                <option value="{{ $trainee->id }}">
                                    {{ $trainee->last_name }}, {{ $trainee->first_name }} - {{ $trainee->batch?->name ?? 'No class' }}{{ $trainee->learning_status === \App\Models\EnrollmentApplication::LEARNING_GRADUATED ? ' - Graduated in this batch' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </fieldset>

            <div class="lms-field">
                <label for="quick-quiz-time">Time Limit (Minutes)</label>
                <input id="quick-quiz-time" type="number" name="time_limit_minutes" value="20" min="1" max="240" class="form-field">
            </div>

            <div class="lms-field">
                <label for="quick-quiz-pass">Passing Score (%)</label>
                <input id="quick-quiz-pass" type="number" name="passing_score_percent" value="75" min="1" max="100" class="form-field">
            </div>

            <div class="lms-field">
                <label for="quick-quiz-attempts">Allowed Attempts</label>
                <input id="quick-quiz-attempts" type="number" name="attempt_limit" value="2" min="1" max="5" class="form-field">
            </div>

            <div class="lms-field">
                <label for="quick-quiz-due">Due Date (Optional)</label>
                <input id="quick-quiz-due" type="datetime-local" name="due_at" class="form-field">
            </div>

            <!-- Initial starter question to satisfy validation and allow adding questions right away -->
            <input type="hidden" name="questions[0][type]" value="multiple_choice">
            <input type="hidden" name="questions[0][prompt]" value="Starter question (click Edit to change prompt and options)">
            <input type="hidden" name="questions[0][options][0]" value="Option A">
            <input type="hidden" name="questions[0][options][1]" value="Option B">
            <input type="hidden" name="questions[0][correct_option]" value="0">
            <input type="hidden" name="questions[0][points]" value="1">
            <input type="hidden" name="is_published" value="0">

            <div class="lms-form-actions lms-field-wide">
                <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                <button class="primary-action" data-action-button>Create Assessment & Build Questions</button>
            </div>
        </form>
    </dialog>

    <!-- SECTION: LEARNING MATERIALS & MODULES -->
    <section aria-labelledby="classwork-list-title" class="lms-content-section" data-section-kind="modules">
        <div class="lms-section-heading">
            <div>
                <p class="lms-eyebrow">Course Modules</p>
                <h2 id="classwork-list-title">Learning Modules ({{ $moduleList->count() }})</h2>
            </div>
            <div class="lms-heading-stats">
                <span>{{ $moduleList->count() }} total</span>
                <span>{{ $publishedModuleCount }} published</span>
            </div>
        </div>

        <div class="lms-module-grid">
            @forelse($modules as $module)
                @php
                    $isPrivate = filled($module->target_enrollment_application_id);
                    $assignedCount = $module->progressRecords->count();
                    $competentCount = $module->progressRecords->where('competency_outcome', 'competent')->count();
                @endphp
                <article class="lms-module-card" data-module-card data-module-title="{{ str($module->title)->lower() }}">
                    <div class="lms-module-accent" aria-hidden="true"></div>
                    <header>
                        <div class="lms-module-icon"><x-dashboard-icon name="book-open" /></div>
                        <div class="lms-module-statuses">
                            <span class="lms-status-chip {{ $module->delivery_status === 'active' ? 'is-green' : ($module->delivery_status === 'closed' ? 'is-amber' : 'is-neutral') }}">{{ $module->deliveryStatusLabel() }}</span>
                            @if($isPrivate)<span class="lms-status-chip is-purple">Private</span>@endif
                        </div>
                    </header>
                    <div class="lms-module-content">
                        @if($module->topic)<p class="lms-module-topic">{{ $module->topic }}</p>@endif
                        <h3 class="font-bold text-slate-950 text-base">
                            <a href="{{ route('trainer.modules.show', $module) }}" class="hover:text-purple-700 transition">
                                {{ $module->title }}
                            </a>
                        </h3>
                        <p class="line-clamp-2">{{ str($module->description)->limit(150) }}</p>
                    </div>

                    <dl class="lms-module-meta">
                        <div><dt>Audience</dt><dd>{{ $isPrivate ? ($module->targetTrainee?->first_name.' '.$module->targetTrainee?->last_name) : 'Entire class' }}</dd></div>
                        <div><dt>Due</dt><dd>{{ $module->due_at?->format('M d, Y g:i A') ?? 'No due date' }}</dd></div>
                    </dl>
                    <div class="lms-progress-summary flex justify-between items-center text-xs">
                        <span><strong>{{ $assignedCount }}</strong> assigned</span>
                        <span class="font-bold text-emerald-700">{{ $competentCount }} Competent (Passed)</span>
                    </div>
                    <footer class="lms-card-footer">
                        <a href="{{ route('trainer.modules.show', $module) }}" class="primary-action text-xs">Open Module Hub</a>
                        <div class="lms-card-actions">
                            @if($module->requiresEvaluation())
                                <button type="button" class="secondary-action text-xs" data-dashboard-dialog-open="quiz-creator-dialog" data-quiz-module-id="{{ $module->id }}">Add quiz</button>
                            @else
                                <span class="text-[11px] font-semibold text-sky-700">Material only</span>
                            @endif
                            <details class="lms-action-menu">
                                <summary class="secondary-action text-xs">More</summary>
                                <div class="lms-action-menu-popover">
                                    @if($module->delivery_status !== 'closed')
                                    <form method="POST" action="{{ route('trainer.modules.update', $module) }}" data-confirm="{{ $module->isSupplemental() ? ($module->is_published ? 'Remove this supplemental module from the class?' : 'Make this supplemental module available to everyone in the assigned class?') : ($module->delivery_status === 'active' ? 'Close this delivery to future enrollees?' : 'Publish this as the current active module?') }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="module_code" value="{{ $module->module_code }}">
                                        <input type="hidden" name="competency_category" value="{{ $module->competency_category }}">
                                        <input type="hidden" name="completion_mode" value="{{ $module->completion_mode ?? 'assessed' }}">
                                        <input type="hidden" name="title" value="{{ $module->title }}">
                                        <input type="hidden" name="description" value="{{ $module->description }}">
                                        <input type="hidden" name="topic" value="{{ $module->topic }}">
                                        <input type="hidden" name="estimated_hours" value="{{ $module->estimated_hours }}">
                                        <input type="hidden" name="position" value="{{ $module->position ?? 0 }}">
                                        <input type="hidden" name="audience_type" value="{{ $isPrivate ? 'trainee' : 'batch' }}">
                                        <input type="hidden" name="training_batch_id" value="{{ $module->training_batch_id }}">
                                        <input type="hidden" name="target_enrollment_application_id" value="{{ $module->target_enrollment_application_id }}">
                                        <input type="hidden" name="available_at" value="{{ $module->available_at?->format('Y-m-d\TH:i') }}">
                                        <input type="hidden" name="due_at" value="{{ $module->due_at?->format('Y-m-d\TH:i') }}">
                                        <input type="hidden" name="is_published" value="{{ $module->is_published ? 0 : 1 }}">
                                        <button>{{ $module->isSupplemental() ? ($module->is_published ? 'Remove from class' : 'Make available to class') : ($module->delivery_status === 'active' ? 'Close to new enrollees' : 'Publish as active') }}</button>
                                    </form>
                                    @endif
                                    <form method="POST" action="{{ route('trainer.modules.destroy', $module) }}" data-confirm="Delete '{{ $module->title }}' and its recorded learner progress?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="is-danger">Delete</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </footer>
                </article>
            @empty
                <div class="lms-empty-state lms-grid-empty">
                    <x-dashboard-icon name="book-open" />
                    <h2>No learning modules yet</h2>
                    <p>Create a learning material or Core Competency unit to begin organizing the classwork library.</p>
                </div>
            @endforelse
        </div>

        @if(method_exists($modules, 'hasPages') && $modules->hasPages())
            <div class="lms-pagination">{{ $modules->links() }}</div>
        @endif
    </section>

    <!-- SECTION: QUIZZES & ASSESSMENTS HUB -->
    <section id="assessments-hub" aria-labelledby="assessments-list-title" class="lms-content-section" data-section-kind="assessments">
        <div class="lms-section-heading">
            <div>
                <p class="lms-eyebrow">Classwork Assessments</p>
                <h2 id="assessments-list-title">All Quizzes & Assessments ({{ $quizList->count() }})</h2>
            </div>
            <div class="lms-heading-stats">
                <span>{{ $quizList->count() }} total</span>
                <span>{{ $activeQuizCount }} active</span>
            </div>
        </div>

        <div class="lms-quiz-grid">
            @forelse($quizzes as $quiz)
                @php
                    $isQuizPrivate = filled($quiz->target_enrollment_application_id);
                    $attemptCount = $quiz->attempts->count();
                    $gradedAttemptCount = $quiz->attempts->where('status', 'graded')->count();
                @endphp
                <article class="flex flex-col justify-between rounded-2xl border border-stone-200 bg-white p-5 shadow-sm hover:shadow-md transition">
                    <div>
                        <div class="flex items-start justify-between gap-2 mb-3">
                            <span class="rounded px-2 py-0.5 text-xs font-bold {{ $quiz->is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-700' }}">
                                {{ $quiz->is_published ? 'Active' : 'Draft' }}
                            </span>
                            @if($isQuizPrivate)
                                <span class="rounded bg-purple-100 px-2 py-0.5 text-xs font-bold text-purple-900">Private Trainee</span>
                            @endif
                        </div>

                        @if($quiz->trainingModule)
                            <div class="flex items-center gap-1.5 mb-2">
                                <a href="{{ route('trainer.modules.show', $quiz->trainingModule) }}" class="text-xs text-purple-800 hover:underline font-semibold truncate">
                                    {{ $quiz->trainingModule->title }}
                                </a>
                            </div>
                        @endif

                        <h3 class="font-bold text-stone-950 text-base mb-1.5">
                            <a href="{{ route('trainer.quizzes.edit', $quiz) }}" class="hover:text-purple-700 transition">
                                {{ $quiz->title }}
                            </a>
                        </h3>

                        @if($quiz->instructions)
                            <p class="text-xs text-stone-600 italic line-clamp-2 mb-3">{{ $quiz->instructions }}</p>
                        @endif

                        <dl class="space-y-1 text-xs text-stone-600 border-t border-stone-100 pt-3 mb-4">
                            <div class="flex justify-between">
                                <dt class="text-stone-500">Questions:</dt>
                                <dd class="font-bold text-stone-900">{{ $quiz->questions->count() }} questions</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-stone-500">Passing Score:</dt>
                                <dd class="font-bold text-emerald-700">{{ number_format((float)$quiz->passing_score_percent, 0) }}%</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-stone-500">Time Limit:</dt>
                                <dd class="font-semibold text-stone-800">{{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' mins' : 'Unlimited' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-stone-500">Attempts Limit:</dt>
                                <dd class="font-semibold text-stone-800">{{ $quiz->attempt_limit }} {{ str('attempt')->plural($quiz->attempt_limit) }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-stone-500">Due Date:</dt>
                                <dd class="text-stone-700">{{ $quiz->due_at?->format('M d, Y g:i A') ?? 'No due date' }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-stone-500">Audience:</dt>
                                <dd class="text-stone-800 truncate max-w-[12rem]">{{ $isQuizPrivate ? ($quiz->targetTrainee?->first_name.' '.$quiz->targetTrainee?->last_name) : ($quiz->batch ? $quiz->batch->name.' '.$quiz->batch->year : 'Entire class') }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="border-t border-stone-100 pt-3 space-y-2">
                        <div class="flex items-center justify-between text-xs mb-2">
                            <span class="font-semibold text-stone-600">Submissions:</span>
                            <span class="font-bold text-purple-900">{{ $gradedAttemptCount }} graded / {{ $attemptCount }} total</span>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-1.5">
                                <a href="{{ route('trainer.quizzes.edit', $quiz) }}" class="secondary-action text-xs py-1.5 px-3">
                                    Edit Questions
                                </a>
                                <a href="{{ route('trainer.quizzes.results', $quiz) }}" class="primary-action text-xs py-1.5 px-3">
                                    Results ({{ $attemptCount }})
                                </a>
                            </div>
                            <details class="lms-action-menu">
                                <summary class="secondary-action text-xs">More</summary>
                                <div class="lms-action-menu-popover">
                                    <form method="POST" action="{{ route('trainer.quizzes.publication', $quiz) }}">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="is_published" value="{{ $quiz->is_published ? 0 : 1 }}">
                                        <button>{{ $quiz->is_published ? 'Unpublish' : 'Publish' }}</button>
                                    </form>
                                    <form method="POST" action="{{ route('trainer.quizzes.destroy', $quiz) }}" data-confirm="Delete '{{ $quiz->title }}'?">
                                        @csrf
                                        @method('DELETE')
                                        <button class="is-danger">Delete</button>
                                    </form>
                                </div>
                            </details>
                        </div>
                    </div>
                </article>
            @empty
                <div class="col-span-full rounded-2xl border border-stone-200 bg-white p-8 text-center">
                    <x-dashboard-icon name="list-check" class="mx-auto h-10 w-10 text-stone-400 mb-2" />
                    <h3 class="text-base font-bold text-stone-900">No assessments authored yet</h3>
                    <p class="text-xs text-stone-500 mt-1 max-w-sm mx-auto">Create a quiz using the composer above or inside any learning module to evaluate your trainees' knowledge.</p>
                </div>
            @endforelse
        </div>
    </section>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Module Preset Selector Script
        const presetSelect = document.querySelector('[data-module-preset-select]');
        const codeInput = document.getElementById('module-code');
        const categorySelect = document.getElementById('module-category');
        const titleInput = document.getElementById('module-title');
        const topicInput = document.getElementById('module-topic');
        const hoursInput = document.getElementById('module-hours');
        const datalist = document.getElementById('subtopics-list');
        const submoduleList = document.querySelector('[data-submodule-list]');
        const addSubmoduleButton = document.querySelector('[data-add-submodule]');
        const publicationCopy = document.querySelector('[data-module-publication-copy]');

        const renderSubmodules = (outcomes, locked = false) => {
            if (!submoduleList) return;
            submoduleList.innerHTML = '';
            const titles = outcomes.length ? outcomes : [''];
            titles.forEach((outcome) => {
                const input = document.createElement('input');
                input.name = 'submodule_titles[]';
                input.value = outcome;
                input.maxLength = 255;
                input.className = 'form-field';
                input.placeholder = 'Submodule or competency outcome';
                input.readOnly = locked;
                submoduleList.appendChild(input);
            });
        };

        const applyCategoryBehavior = () => {
            const custom = categorySelect?.value === 'custom';
            if (publicationCopy) {
                publicationCopy.textContent = custom
                    ? 'Make this supplemental module available to everyone in the assigned class. It will not replace the active module.'
                    : 'Make this the active batch module. The previous active delivery closes only to future enrollees.';
            }
            const traineeAudience = document.querySelector('#module-creator-dialog input[name="audience_type"][value="trainee"]');
            if (traineeAudience) {
                traineeAudience.disabled = custom;
                if (custom) {
                    const batchAudience = document.querySelector('#module-creator-dialog input[name="audience_type"][value="batch"]');
                    if (batchAudience) batchAudience.checked = true;
                }
            }
        };

        addSubmoduleButton?.addEventListener('click', () => {
            const input = document.createElement('input');
            input.name = 'submodule_titles[]';
            input.maxLength = 255;
            input.className = 'form-field';
            input.placeholder = 'Submodule or competency outcome';
            submoduleList?.appendChild(input);
        });
        categorySelect?.addEventListener('change', () => {
            applyCategoryBehavior();
            if (categorySelect.value === 'custom' && submoduleList?.querySelector('input[readonly]')) {
                renderSubmodules([], false);
            }
        });

        // Path: resources/views/trainer/resources.blade.php | Label: Lock category dropdown after preset selection
        const lockCategoryFromPreset = (locked) => {
            if (!categorySelect) return;
            categorySelect.dataset.presetLocked = locked ? '1' : '0';
            categorySelect.classList.toggle('pointer-events-none', locked);
            categorySelect.classList.toggle('opacity-70', locked);
            categorySelect.setAttribute('aria-readonly', locked ? 'true' : 'false');
            categorySelect.setAttribute('tabindex', locked ? '-1' : '0');
        };

        if (presetSelect) {
            presetSelect.addEventListener('change', () => {
                const selectedOption = presetSelect.selectedOptions[0];
                if (!selectedOption || !selectedOption.value) {
                    // Cleared preset — reopen the category dropdown so trainer can pick again
                    lockCategoryFromPreset(false);
                    return;
                }

                const code = selectedOption.dataset.code || '';
                const category = selectedOption.dataset.category || 'core';
                const title = selectedOption.dataset.title || '';
                const hours = selectedOption.dataset.hours || '40';
                let outcomes = [];

                try {
                    outcomes = JSON.parse(selectedOption.dataset.outcomes || '[]');
                } catch (e) {}

                if (codeInput && code) codeInput.value = code;
                if (categorySelect && category) categorySelect.value = category;
                if (titleInput && title) titleInput.value = title;
                if (hoursInput && hours) hoursInput.value = hours;

                if (datalist) {
                    datalist.innerHTML = '';
                    outcomes.forEach(outcome => {
                        const opt = document.createElement('option');
                        opt.value = outcome;
                        datalist.appendChild(opt);
                    });
                }

                renderSubmodules(outcomes, true);

                if (topicInput && outcomes.length > 0 && !topicInput.value) {
                    topicInput.value = outcomes[0];
                }

                lockCategoryFromPreset(true);
            });
        }
        applyCategoryBehavior();

        // Path: resources/views/trainer/resources.blade.php | Label: Primary lesson file inline preview
        document.querySelectorAll('[data-file-preview-input]').forEach((input) => {
            const key = input.dataset.filePreviewInput;
            const panel = document.querySelector('[data-file-preview-panel="' + key + '"]');
            if (!panel) return;
            const nameEl = panel.querySelector('[data-file-preview-name]');
            const sizeEl = panel.querySelector('[data-file-preview-size]');
            const bodyEl = panel.querySelector('[data-file-preview-body]');
            const removeBtn = document.querySelector('[data-file-preview-remove="' + key + '"]');

            const formatBytes = (b) => {
                if (!b) return '';
                if (b < 1024) return b + ' B';
                if (b < 1024*1024) return (b/1024).toFixed(1) + ' KB';
                return (b/1024/1024).toFixed(2) + ' MB';
            };

            const showFile = (file, url) => {
                bodyEl.innerHTML = '';
                if (file.type.startsWith('image/')) {
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = file.name;
                    img.className = 'max-h-72 w-auto rounded-lg border border-slate-200 bg-white';
                    bodyEl.appendChild(img);
                } else if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
                    const embed = document.createElement('iframe');
                    embed.src = url;
                    embed.title = file.name;
                    embed.className = 'h-72 w-full rounded-lg border border-slate-200 bg-white';
                    bodyEl.appendChild(embed);
                } else {
                    const note = document.createElement('p');
                    note.className = 'text-xs text-slate-500';
                    note.textContent = 'Preview not available for this file type — the file will still be uploaded.';
                    bodyEl.appendChild(note);
                }
            };

            const renderPreview = async (file) => {
                if (!file) {
                    panel.classList.add('hidden');
                    if (bodyEl) bodyEl.innerHTML = '';
                    return;
                }
                panel.classList.remove('hidden');
                if (nameEl) nameEl.textContent = file.name;
                if (sizeEl) sizeEl.textContent = formatBytes(file.size);
                if (!bodyEl) return;
                const localUrl = URL.createObjectURL(file);
                const canStamp = file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf') || file.type.startsWith('image/');
                const previewUrl = input.dataset.watermarkPreviewUrl;
                if (!canStamp || !previewUrl) {
                    showFile(file, localUrl);
                    return;
                }
                bodyEl.innerHTML = '<p class="text-xs font-semibold text-slate-500">Embedding watermark...</p>';
                try {
                    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const body = new FormData();
                    body.append('module_file', file);
                    const response = await fetch(previewUrl, {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/pdf, image/*, application/json' },
                        body,
                    });
                    if (!response.ok) throw new Error('watermark failed');
                    const stamped = URL.createObjectURL(await response.blob());
                    showFile(file, stamped);
                } catch (error) {
                    showFile(file, localUrl);
                }
            };

            input.addEventListener('change', () => {
                renderPreview(input.files && input.files[0] ? input.files[0] : null);
            });
            if (removeBtn) {
                removeBtn.addEventListener('click', () => {
                    input.value = '';
                    renderPreview(null);
                });
            }
        });

        const quickQuizModule = document.getElementById('quick-quiz-module');
        const quickQuizSubmodule = document.getElementById('quick-quiz-submodule');
        const filterQuickSubmodules = () => {
            if (!quickQuizSubmodule) return;
            const moduleId = quickQuizModule?.value || '';
            Array.from(quickQuizSubmodule.options).forEach((option, index) => {
                if (index === 0) return;
                option.hidden = option.dataset.parentModule !== moduleId;
            });
            if (quickQuizSubmodule.selectedOptions[0]?.hidden) quickQuizSubmodule.value = '';
        };
        quickQuizModule?.addEventListener('change', filterQuickSubmodules);
        filterQuickSubmodules();

        // Filter Sub-Tabs Switching (All / Modules / Assessments)
        const filterButtons = document.querySelectorAll('[data-classwork-tab]');
        const moduleSection = document.querySelector('[data-section-kind="modules"]');
        const assessmentSection = document.querySelector('[data-section-kind="assessments"]');

        function setActiveTab(tab) {
            const activeTab = tab === 'assessments' ? 'assessments' : 'modules';
            filterButtons.forEach(btn => {
                const isCurrent = btn.dataset.classworkTab === activeTab;
                btn.className = isCurrent
                    ? 'rounded-xl px-4 py-2 text-xs font-bold transition bg-purple-700 text-white shadow-sm'
                    : 'rounded-xl px-4 py-2 text-xs font-bold transition bg-stone-100 text-stone-700 hover:bg-stone-200';
                btn.setAttribute('aria-selected', isCurrent ? 'true' : 'false');
            });

            if (moduleSection && assessmentSection) {
                if (activeTab === 'modules') {
                    moduleSection.style.display = 'block';
                    assessmentSection.style.display = 'none';
                } else {
                    moduleSection.style.display = 'none';
                    assessmentSection.style.display = 'block';
                }
            }
        }

        filterButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                setActiveTab(btn.dataset.classworkTab);
            });
        });

        // Initialize based on URL hash or parameter
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        const hash = window.location.hash;

        if (tabParam === 'assessments' || hash === '#assessments' || hash === '#assessments-hub') {
            setActiveTab('assessments');
        } else if (tabParam === 'modules') {
            setActiveTab('modules');
        } else setActiveTab('modules');

        const quizModuleSelect = document.getElementById('quick-quiz-module');
        document.querySelectorAll('[data-quiz-module-id]').forEach(button => {
            button.addEventListener('click', () => {
                if (quizModuleSelect) quizModuleSelect.value = button.dataset.quizModuleId || '';
                filterQuickSubmodules();
            });
        });
    });
</script>
@endsection
