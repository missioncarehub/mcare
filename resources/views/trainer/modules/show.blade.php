@extends('trainer.layouts.app', ['title' => $module->title.' | MCARE Module Hub'])

@section('content')
@php
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
@endphp

<div class="w-full space-y-6" data-trainer-module-hub>
    <div>
        <a href="{{ route('trainer.resources') }}" class="lms-module-back">
            <x-dashboard-icon name="chevron-left" class="h-4 w-4" />
            Back to Classwork
        </a>

        <header class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0 space-y-2">
                    <div class="flex flex-wrap items-center gap-2">
                        @if($module->module_code)
                            <span class="rounded bg-purple-100 px-2.5 py-0.5 text-xs font-mono font-bold text-purple-900 ring-1 ring-purple-300">
                                {{ $module->module_code }}
                            </span>
                        @endif
                        <span class="rounded bg-slate-100 px-2.5 py-0.5 text-xs font-bold text-slate-700">
                            {{ match($module->competency_category) {
                                'core' => 'Core',
                                'common' => 'Common',
                                'basic' => 'Basic',
                                default => 'Material',
                            } }}
                        </span>
                        @if($module->estimated_hours)
                            <span class="rounded bg-stone-100 px-2 py-0.5 text-xs text-stone-600">
                                {{ $module->estimated_hours }} hrs
                            </span>
                        @endif
                        <span class="rounded px-2.5 py-0.5 text-xs font-bold {{ $module->delivery_status === 'active' ? 'bg-emerald-100 text-emerald-800' : ($module->delivery_status === 'closed' ? 'bg-amber-100 text-amber-800' : 'bg-stone-200 text-stone-700') }}">
                            {{ match($module->delivery_status) {
                                'active' => 'Active',
                                'available' => 'Custom',
                                'closed' => 'Closed',
                                default => 'Draft',
                            } }}
                        </span>
                        <span class="rounded px-2.5 py-0.5 text-xs font-bold {{ $module->requiresEvaluation() ? 'bg-purple-100 text-purple-800' : 'bg-sky-100 text-sky-800' }}">
                            {{ $module->requiresEvaluation() ? 'Assessed' : 'Material' }}
                        </span>
                    </div>

                    <h1 class="text-2xl font-bold text-stone-950 sm:text-3xl">{{ $module->title }}</h1>

                    @if($module->topic)
                        <p class="text-sm font-semibold text-purple-800">{{ $module->topic }}</p>
                    @endif

                    <p class="text-xs text-stone-500">
                        <strong class="text-stone-800">{{ $isPrivate ? trim($module->targetTrainee?->first_name.' '.$module->targetTrainee?->last_name).' (Private)' : ($module->batch ? $module->batch->name.' '.$module->batch->year : 'Entire class') }}</strong>
                        @if($module->available_at) · Opens {{ $module->available_at->format('M d, Y') }} @endif
                        @if($module->due_at) · Due {{ $module->due_at->format('M d, Y') }} @endif
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    @if($module->delivery_status !== 'closed')
                    <form method="POST" action="{{ route('trainer.modules.update', $module) }}" data-confirm="{{ $module->delivery_status === 'active' ? 'Close this module to future enrollees? Existing assigned trainees will keep access.' : 'Publish this as the current active module? The previous active module will close to new enrollees.' }}">
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
                        <input type="hidden" name="_return_to_module" value="1">
                        <button class="secondary-action text-xs">
                            {{ $module->delivery_status === 'active' ? 'Close to new' : 'Publish' }}
                        </button>
                    </form>
                    @else
                        <span class="max-w-xs text-xs leading-5 text-amber-800">Closed. Assigned trainees keep access.</span>
                    @endif
                </div>
            </div>

            <nav class="mt-5 flex flex-wrap gap-2 border-t border-stone-100 pt-4" aria-label="Module Hub sections">
                <a href="{{ route('trainer.modules.show', ['module' => $module, 'tab' => 'materials']) }}#materials" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $activeTab === 'materials' ? 'bg-purple-700 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    Materials ({{ count($supplementaryList) + 1 }})
                </a>
                @if($module->requiresEvaluation())
                <a href="{{ route('trainer.modules.show', ['module' => $module, 'tab' => 'assessments']) }}#assessments" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $activeTab === 'assessments' ? 'bg-purple-700 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    Assessments ({{ $quizzes->count() }})
                </a>
                <a href="{{ route('trainer.modules.show', ['module' => $module, 'tab' => 'evaluations']) }}#evaluations" class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $activeTab === 'evaluations' ? 'bg-purple-700 text-white' : 'bg-stone-100 text-stone-700 hover:bg-stone-200' }}">
                    Grades ({{ $trainees->count() }})
                </a>
                @endif
            </nav>
        </header>
    </div>

    <!-- SECTION 1: LEARNING MATERIALS & PREVIEW -->
    @if($activeTab === 'materials')
    <section id="materials" class="space-y-4">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-stone-950">Primary Lesson Material</h2>
                    <p class="text-xs text-stone-500">{{ $module->original_file_name }} · {{ $module->fileTypeLabel() }} ({{ number_format(($module->file_size ?? 0) / 1048576, 2) }} MB)</p>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ $viewerUrl }}" target="_blank" rel="noopener" class="secondary-action text-xs">Open in New Tab</a>
                    <a href="{{ $downloadUrl }}" class="primary-action text-xs">Download File</a>
                </div>
            </div>

            <x-module-file-preview :module="$module" :viewer-url="$viewerUrl" />

            <!-- Supplementary Files List -->
            @if(count($supplementaryList) > 0)
                <div class="border-t border-stone-100 pt-4">
                    <h3 class="text-sm font-bold text-stone-900 mb-2">Supplementary Worksheets & Handouts ({{ count($supplementaryList) }})</h3>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($supplementaryList as $idx => $supp)
                            <div class="flex items-center justify-between rounded-xl border border-stone-200 bg-stone-50 p-3 text-xs">
                                <div class="min-w-0 pr-2">
                                    <p class="font-bold text-stone-900 truncate">{{ $supp['original_name'] }}</p>
                                    <p class="text-[11px] text-stone-500">{{ $supp['human_size'] ?? '' }}</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-1">
                                    <a href="{{ route('trainer.modules.supplementary.download', [$module, $idx]) }}" class="secondary-action text-xs py-1 px-2.5">
                                        Download
                                    </a>
                                    <form method="POST" action="{{ route('trainer.modules.supplementary.destroy', [$module, $idx]) }}" onsubmit="return confirm('Remove this supplementary file?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="rounded-lg border border-rose-200 bg-white px-2.5 py-1 text-xs font-bold text-rose-700 hover:bg-rose-50">Remove</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Add Supplementary Files Form -->
            <details class="rounded-xl border border-dashed border-stone-300 bg-stone-50/50 p-3 text-xs">
                <summary class="cursor-pointer font-bold text-purple-700 hover:text-purple-900 select-none">
                    + Attach More Supplementary Handouts / Worksheets
                </summary>
                <form method="POST" action="{{ route('trainer.modules.update', $module) }}" enctype="multipart/form-data" class="mt-3 space-y-3">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="module_code" value="{{ $module->module_code }}">
                    <input type="hidden" name="competency_category" value="{{ $module->competency_category }}">
                    <input type="hidden" name="completion_mode" value="{{ $module->completion_mode ?? 'assessed' }}">
                    <input type="hidden" name="title" value="{{ $module->title }}">
                    <input type="hidden" name="description" value="{{ $module->description }}">
                    <input type="hidden" name="topic" value="{{ $module->topic }}">
                    <input type="hidden" name="audience_type" value="{{ $isPrivate ? 'trainee' : 'batch' }}">
                    <input type="hidden" name="training_batch_id" value="{{ $module->training_batch_id }}">
                    <input type="hidden" name="target_enrollment_application_id" value="{{ $module->target_enrollment_application_id }}">
                    <input type="hidden" name="estimated_hours" value="{{ $module->estimated_hours }}">
                    <input type="hidden" name="position" value="{{ $module->position ?? 0 }}">
                    <input type="hidden" name="available_at" value="{{ $module->available_at?->format('Y-m-d\TH:i') }}">
                    <input type="hidden" name="due_at" value="{{ $module->due_at?->format('Y-m-d\TH:i') }}">
                    <input type="hidden" name="_return_to_module" value="1">
                    <div>
                        <label class="block text-xs font-semibold text-stone-700 mb-1">Select files to append</label>
                        <input name="supplementary_files[]" type="file" multiple accept="{{ \App\Support\TrainingModuleFiles::acceptAttribute() }}" class="form-field">
                        <p class="mt-1 text-[11px] text-stone-500">{{ count($supplementaryList) }} of {{ \App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_FILES }} slots used; {{ number_format(\App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_UPLOAD_KB / 1024) }} MB maximum per file.</p>
                    </div>
                    <button type="submit" class="primary-action text-xs">Upload Attachments</button>
                </form>
            </details>
        </div>
    </section>
    @endif

    <!-- SECTION 2: MODULE ASSESSMENTS -->
    @if($activeTab === 'assessments' && $module->requiresEvaluation())
    <section id="assessments" class="space-y-4">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-stone-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-stone-950">Module Assessments</h2>
                    <p class="text-xs text-stone-500">Quizzes attached directly to this module for knowledge evaluation.</p>
                </div>
                <button type="button" class="primary-action text-xs" data-dashboard-dialog-open="module-quiz-dialog">Create quiz</button>
            </div>

            <div class="space-y-3">
                @forelse($quizzes as $quiz)
                    <div class="flex flex-col gap-3 rounded-xl border border-stone-200 bg-stone-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-bold text-stone-950">{{ $quiz->title }}</h3>
                                <span class="rounded px-2 py-0.5 text-[10px] font-bold {{ $quiz->is_published ? 'bg-emerald-100 text-emerald-800' : 'bg-stone-200 text-stone-700' }}">
                                    {{ $quiz->is_published ? 'Active' : 'Draft' }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-stone-600">{{ $quiz->questions->count() }} Questions · Passing Score: {{ number_format($quiz->passing_score_percent, 0) }}% · Time Limit: {{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' mins' : 'Unlimited' }}</p>
                            @if($quiz->instructions)<p class="mt-1 text-xs text-stone-500 italic">{{ str($quiz->instructions)->limit(120) }}</p>@endif
                            <p class="mt-1 text-[11px] font-semibold {{ $quiz->trainingSubmodule ? 'text-purple-700' : 'text-amber-700' }}">{{ $quiz->trainingSubmodule?->title ?? 'Legacy module-wide assessment' }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('trainer.quizzes.edit', $quiz) }}" class="secondary-action text-xs py-1.5 px-3">
                                Edit Questions
                            </a>
                            <a href="{{ route('trainer.quizzes.results', $quiz) }}" class="primary-action text-xs py-1.5 px-3">
                                View Submissions
                            </a>
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-stone-200 bg-stone-50/50 p-6 text-center text-xs text-stone-500">
                        No quiz has been created for this module yet.
                    </div>
                @endforelse
            </div>

            <dialog id="module-quiz-dialog" data-dashboard-dialog data-auto-open="{{ old('_composer') === 'module-quiz' && $errors->any() ? 'true' : 'false' }}" class="lms-workflow-dialog is-landscape" aria-labelledby="module-quiz-title">
                <div class="lms-dialog-header">
                    <div><p class="lms-eyebrow">{{ $module->title }}</p><h2 id="module-quiz-title">Create module quiz</h2><p>Create the draft, then add the answer key and questions.</p></div>
                    <button type="button" data-dashboard-dialog-close class="lms-dialog-close" aria-label="Close quiz creator"><x-dashboard-icon name="xmark" /></button>
                </div>
                <form method="POST" action="{{ route('trainer.modules.quizzes.store', $module) }}" class="lms-form-grid lms-composer-form" data-dashboard-dialog-form data-submit-label="Creating quiz...">
                    @csrf
                    <input type="hidden" name="_composer" value="module-quiz">
                    <div class="lms-field lms-field-span-2">
                        <label class="block text-xs font-bold text-purple-900 mb-1">Assessment / Quiz Title</label>
                        <input name="title" required maxlength="160" placeholder="e.g. {{ $module->title }} - Mastery Assessment" class="form-field">
                    </div>
                    <div class="lms-field lms-field-span-2">
                        <label class="block text-xs font-bold text-purple-900 mb-1">Competency Submodule</label>
                        <select name="training_submodule_id" class="form-field" required>
                            <option value="">Choose the outcome this quiz measures</option>
                            @foreach($submodules as $submodule)
                                <option value="{{ $submodule->id }}" @selected((string) old('training_submodule_id') === (string) $submodule->id)>{{ $submodule->position }}. {{ $submodule->title }}</option>
                            @endforeach
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
                    <p class="lms-field-wide text-xs text-stone-600">The assessment starts as a draft. Add the answer key and questions before publishing it to trainees.</p>
                    <div class="lms-form-actions lms-field-wide">
                        <button type="button" data-dashboard-dialog-close class="secondary-action text-xs">Cancel</button>
                        <button type="submit" data-action-button class="primary-action text-xs">Create & Add Questions</button>
                    </div>
                </form>
            </dialog>
        </div>
    </section>
    @endif

    <!-- SECTION 3: LEARNER GRADES & EVALUATION MATRIX -->
    @if($activeTab === 'evaluations' && $module->requiresEvaluation())
    <section id="evaluations" class="space-y-4">
        <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm space-y-4">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-stone-100 pb-3">
                <div>
                    <h2 class="text-lg font-bold text-stone-950">Submodule Competency Evaluation</h2>
                    <p class="text-xs text-stone-500">Evaluate each competency outcome separately. The main module result is calculated automatically and has no manual dropdown.</p>
                </div>
                <div class="text-xs text-stone-600">
                    Batch: <strong>{{ $module->batch ? $module->batch->name.' '.$module->batch->year : 'Current Roster' }}</strong>
                </div>
            </div>

            <div class="space-y-5">
                @forelse($trainees as $trainee)
                    @php
                        $parentProgress = $progressByApp->get($trainee->id);
                        $traineeChildProgress = $submoduleProgressByApp->get($trainee->id, collect());
                        $traineeChildSummaries = $submoduleAssessmentSummaryByApp->get($trainee->id, collect());
                    @endphp
                    <article class="rounded-2xl border border-stone-200 bg-stone-50/60 p-4">
                        <div class="flex flex-col gap-3 border-b border-stone-200 pb-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-3">
                                <x-user-avatar :user="$trainee->user" :name="trim($trainee->first_name.' '.$trainee->last_name)" class="grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800" />
                                <div>
                                    <p class="font-bold text-stone-950">{{ $trainee->last_name }}, {{ $trainee->first_name }}</p>
                                    <p class="text-[11px] text-stone-500">{{ $trainee->email }}</p>
                                </div>
                            </div>
                            <div class="text-xs text-stone-600">
                                Main module: <strong class="{{ $parentProgress?->isTrainerValidated() ? 'text-emerald-700' : 'text-purple-800' }}">{{ $parentProgress?->workflowStatusLabel() ?? 'Not assigned' }}</strong>
                            </div>
                        </div>

                        <div class="mt-4 grid gap-4 xl:grid-cols-2">
                            @foreach($submodules as $submodule)
                                @php
                                    $childProgress = $traineeChildProgress->get($submodule->id);
                                    $childSummary = $traineeChildSummaries->get($submodule->id, []);
                                    $childAverage = $childSummary['average_score'] ?? null;
                                    $childHasQuiz = ($childSummary['required_count'] ?? 0) > 0;
                                    $childAllPassed = (bool) ($childSummary['all_passed'] ?? false);
                                    $childReadyForRemediation = (bool) ($childSummary['ready_for_remediation_evaluation'] ?? false);
                                    $canMarkCompetent = ! $childHasQuiz || $childAllPassed;
                                @endphp
                                <form method="POST" action="{{ route('trainer.modules.evaluate', $module) }}" class="space-y-3 rounded-xl border border-stone-200 bg-white p-4">
                                    @csrf
                                    <input type="hidden" name="enrollment_application_id" value="{{ $trainee->id }}">
                                    <input type="hidden" name="training_submodule_id" value="{{ $submodule->id }}">

                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-wider text-purple-700">Outcome {{ $submodule->position }}</p>
                                            <h3 class="mt-1 text-sm font-bold leading-5 text-stone-950">{{ $submodule->title }}</h3>
                                        </div>
                                        <span class="shrink-0 rounded px-2 py-1 text-[10px] font-bold {{ $childProgress?->isTrainerValidated() ? 'bg-emerald-100 text-emerald-800' : ($childProgress?->status === 'awaiting_evaluation' ? 'bg-purple-100 text-purple-800' : ($childProgress?->status === 'needs_remediation' ? 'bg-amber-100 text-amber-800' : 'bg-stone-100 text-stone-700')) }}">
                                            {{ $childProgress?->workflowStatusLabel() ?? 'Ready to start' }}
                                        </span>
                                    </div>

                                    <div class="grid gap-2 text-xs sm:grid-cols-2">
                                        <div class="rounded-lg bg-purple-50 p-2.5 text-purple-900">
                                            <span class="block text-[10px] font-semibold">Quiz / activity</span>
                                            <strong>{{ $childHasQuiz ? (($childSummary['passed_count'] ?? 0).' / '.($childSummary['required_count'] ?? 0).' passed') : 'No quiz assigned' }}</strong>
                                            @if($childAverage !== null)<span class="block text-[10px]">Average {{ number_format((float) $childAverage, 1) }}%</span>@endif
                                        </div>
                                        <div class="rounded-lg bg-stone-100 p-2.5 text-stone-800">
                                            <span class="block text-[10px] font-semibold">Face-to-face grade</span>
                                            <strong>{{ $childProgress?->evaluated_at ? $childProgress->evaluated_at->format('M d, g:i A') : 'Awaiting trainer evaluation' }}</strong>
                                        </div>
                                    </div>

                                    @if($childHasQuiz && ! $childAllPassed && ! $childReadyForRemediation)
                                        <p class="rounded-lg bg-amber-50 p-2 text-[11px] font-semibold leading-4 text-amber-800">Competent unlocks after assigned classwork is passed. You can still record Not yet competent from the face-to-face session.</p>
                                    @elseif($childReadyForRemediation)
                                        <p class="rounded-lg bg-amber-50 p-2 text-[11px] font-semibold leading-4 text-amber-800">Attempts are exhausted. You may record Not Yet Competent with remediation feedback.</p>
                                    @endif

                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-stone-700">Practical Rating</label>
                                            <select name="practical_rating" class="form-field">
                                                <option value="pending" @selected(!$childProgress || $childProgress->practical_rating === 'pending')>Pending</option>
                                                <option value="competent" @selected($childProgress?->practical_rating === 'competent')>Competent</option>
                                                <option value="not_yet_competent" @selected($childProgress?->practical_rating === 'not_yet_competent')>Not Yet Competent</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-semibold text-stone-700">Outcome</label>
                                            <select name="competency_outcome" class="form-field" required>
                                                <option value="in_progress" @selected(!$childProgress || $childProgress->competency_outcome === 'in_progress')>In Progress</option>
                                                <option value="competent" @selected($childProgress?->competency_outcome === 'competent')>Competent (Passed)</option>
                                                <option value="not_yet_competent" @selected($childProgress?->competency_outcome === 'not_yet_competent')>Not Yet Competent</option>
                                            </select>
                                            @unless($canMarkCompetent)
                                                <p class="mt-1 text-[10px] font-semibold leading-4 text-amber-700">Competent unlocks after assigned classwork is passed.</p>
                                            @endunless
                                        </div>
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-xs font-semibold text-stone-700">Trainer Remarks</label>
                                        <textarea name="evaluation_remarks" rows="2" placeholder="Practical demonstration or remediation feedback..." class="form-field">{{ $childProgress?->evaluation_remarks }}</textarea>
                                    </div>

                                    <button type="submit" class="primary-action w-full justify-center py-2 text-xs">Save Submodule Evaluation</button>
                                </form>
                            @endforeach
                        </div>
                    </article>
                @empty
                    <p class="rounded-xl bg-stone-50 p-6 text-center text-xs text-stone-500">No approved trainees are assigned to this delivery.</p>
                @endforelse
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
                        @forelse($trainees as $trainee)
                            @php
                                $progress = $progressByApp->get($trainee->id);
                                $isCompetent = $progress?->competency_outcome === 'competent';
                                $isNyc = $progress?->competency_outcome === 'not_yet_competent';
                                $assessmentSummary = $assessmentSummaryByApp->get($trainee->id, []);
                                $assessmentAverage = $assessmentSummary['average_score'] ?? null;
                                $readyForRemediation = (bool) ($assessmentSummary['ready_for_remediation_evaluation'] ?? false);
                                $canMarkCompetent = ($assessmentSummary['required_count'] ?? 0) === 0
                                    || (bool) ($assessmentSummary['all_passed'] ?? false);
                            @endphp
                            <tr class="align-middle">
                                <td>
                                    <div class="flex items-center gap-3">
                                        <x-user-avatar
                                            :user="$trainee->user"
                                            :name="trim($trainee->first_name.' '.$trainee->last_name)"
                                            class="grid h-9 w-9 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800"
                                        />
                                        <div class="min-w-0">
                                            <p class="font-bold text-stone-950">{{ $trainee->last_name }}, {{ $trainee->first_name }}</p>
                                            <p class="truncate text-[11px] text-stone-500">{{ $trainee->email }}</p>
                                            <x-graduate-batch-badge :application="$trainee" class="mt-1.5" />
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold {{ $progress?->isTrainerValidated() ? 'text-emerald-700' : ($progress?->status === 'awaiting_evaluation' ? 'text-purple-700' : 'text-stone-600') }}">
                                        {{ $progress?->workflowStatusLabel() ?? 'Not assigned' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="font-bold {{ $assessmentAverage !== null ? 'text-purple-900' : 'text-stone-400' }}">
                                        {{ $assessmentAverage !== null ? number_format((float)$assessmentAverage, 1).'%' : 'N/A' }}
                                    </span>
                                    <span class="mt-1 block text-[10px] text-stone-500">Quiz/activity only</span>
                                </td>
                                <td>
                                    <span class="rounded px-2 py-0.5 text-xs font-bold {{ $progress?->practical_rating === 'competent' ? 'bg-emerald-100 text-emerald-800' : ($progress?->practical_rating === 'not_yet_competent' ? 'bg-rose-100 text-rose-800' : 'bg-stone-100 text-stone-600') }}">
                                        {{ $progress?->practicalRatingLabel() ?? 'Pending' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-bold {{ $isCompetent ? 'bg-emerald-100 text-emerald-900 ring-1 ring-emerald-300' : ($isNyc ? 'bg-amber-100 text-amber-900 ring-1 ring-amber-300' : 'bg-stone-100 text-stone-700') }}">
                                        {{ $isCompetent ? '🟢 Competent' : ($isNyc ? '🟡 For Remediation' : 'In Progress') }}
                                    </span>
                                    @if($progress?->evaluation_remarks)
                                        <p class="mt-1 text-[10px] text-stone-500 italic max-w-xs truncate">"{{ $progress->evaluation_remarks }}"</p>
                                    @endif
                                </td>
                                <td>
                                    @if($progress?->status === 'locked')
                                        <span class="inline-flex max-w-44 rounded-lg bg-stone-100 px-3 py-2 text-xs font-semibold leading-5 text-stone-600">Evaluation opens after this trainee becomes competent in the previous assigned module.</span>
                                    @else
                                    <details class="relative">
                                        <summary class="cursor-pointer rounded-lg bg-stone-100 hover:bg-stone-200 px-3 py-1.5 text-xs font-bold text-stone-800 select-none">
                                            {{ $progress?->status === 'awaiting_evaluation' ? 'Evaluate Submission' : ($readyForRemediation ? 'Evaluate Remediation' : ($progress?->isTrainerValidated() ? 'View Completed Record' : 'Review')) }}
                                        </summary>
                                        <div class="absolute right-0 z-20 mt-2 w-80 rounded-2xl border border-stone-200 bg-white p-4 shadow-xl text-xs space-y-3">
                                            <div class="font-bold text-stone-900 border-b border-stone-100 pb-2">
                                                Grade: {{ $trainee->first_name }} {{ $trainee->last_name }}
                                            </div>
                                            @if($readyForRemediation)
                                                <p class="rounded-lg bg-amber-50 p-2 text-amber-800">The trainee used every attempt and still has failed classwork. You may record Not Yet Competent and remediation feedback from the face-to-face session.</p>
                                            @elseif(($assessmentSummary['required_count'] ?? 0) > 0 && ! ($assessmentSummary['all_passed'] ?? false))
                                                <p class="rounded-lg bg-amber-50 p-2 text-amber-800">Competent unlocks after assigned classwork is passed. You can still record Not yet competent from the face-to-face session.</p>
                                            @endif
                                            <form method="POST" action="{{ route('trainer.modules.evaluate', $module) }}" class="space-y-3">
                                                @csrf
                                                <input type="hidden" name="enrollment_application_id" value="{{ $trainee->id }}">

                                                <div class="rounded-lg border border-purple-100 bg-purple-50 p-3">
                                                    <span class="block font-semibold text-purple-900">Quiz & Activity Average</span>
                                                    <strong class="mt-1 block text-lg text-purple-950">{{ $assessmentAverage !== null ? number_format((float)$assessmentAverage, 1).'%' : 'No submitted score' }}</strong>
                                                    <span class="mt-1 block text-[10px] text-purple-700">Calculated from this module's classwork only. This is not the official overall course grade.</span>
                                                </div>

                                                <div>
                                                    <label class="block font-semibold text-stone-700 mb-1">Practical Demonstration Rating</label>
                                                    <select name="practical_rating" class="form-field">
                                                        <option value="competent" @selected($progress?->practical_rating === 'competent')>Competent (C) - Passed</option>
                                                        <option value="not_yet_competent" @selected($progress?->practical_rating === 'not_yet_competent')>Not Yet Competent (NYC)</option>
                                                        <option value="pending" @selected(!$progress || $progress->practical_rating === 'pending')>Pending Evaluation</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block font-semibold text-stone-700 mb-1">Overall Module Outcome</label>
                                                    <select name="competency_outcome" class="form-field" required>
                                                        <option value="competent" data-competent-outcome-option @selected($progress?->competency_outcome === 'competent')>Competent (Passed)</option>
                                                        <option value="not_yet_competent" @selected($progress?->competency_outcome === 'not_yet_competent')>Not Yet Competent (For Remediation)</option>
                                                        <option value="in_progress" @selected(!$progress || $progress->competency_outcome === 'in_progress')>In Progress</option>
                                                    </select>
                                                    @unless($canMarkCompetent)
                                                        <p class="mt-1 text-[10px] font-semibold leading-4 text-amber-700">Competent is selectable, but saving it requires passed assigned classwork.</p>
                                                    @endunless
                                                </div>

                                                <div>
                                                    <label class="block font-semibold text-stone-700 mb-1">Trainer Remarks</label>
                                                    <textarea name="evaluation_remarks" rows="2" placeholder="F2F demo feedback..." class="form-field">{{ $progress?->evaluation_remarks }}</textarea>
                                                </div>

                                                <button type="submit" class="primary-action w-full justify-center text-xs py-2">
                                                    Save Evaluation
                                                </button>
                                            </form>
                                        </div>
                                    </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-6 text-stone-500">No approved trainees assigned to this batch.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>
    @endif

    <x-classroom-comments :commentable="$module" :comments="$classroomComments" :private-recipients="$privateCommentRecipients" />
</div>
@endsection
