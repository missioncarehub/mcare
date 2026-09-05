@extends('trainee.layouts.app', ['title' => $module->title.' | MCARE Learning'])

@section('content')
@php
    $previewKind = $module->previewKind();
    $viewerUrl = route('trainee.modules.content', $module);
    $downloadUrl = route('trainee.modules.download', $module);
    $traineeName = trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name);
    $watermarkImageUrl = \App\Support\WatermarkedFpdi::publicImageUrl();
    $supplementaryList = $module->supplementaryList();
    $isCompetent = $progress?->competency_outcome === 'competent';
    $assessmentAverage = $assessmentSummary['average_score'] ?? null;
    $unpassedQuizzes = $quizzes->reject(function ($q) use ($quizAttempts) {
        $attempts = $quizAttempts->get($q->id);
        return $attempts && $attempts->contains('passed', true);
    });
    $submoduleAccess = $submoduleAccess ?? collect();
@endphp

<div class="lms-page" data-lms-classwork data-lms-role="trainee" data-protected-module-viewer data-security-event-url="{{ route('trainee.modules.security-event', $module) }}">
    <a href="{{ route('trainee.modules.index') }}" class="lms-module-back">
        <x-dashboard-icon name="chevron-left" class="h-4 w-4" />
        Back to Classwork
    </a>

    <article class="lms-module-workspace">
        <header class="lms-module-workspace-header">
            <div class="lms-module-workspace-meta">
                <p class="lms-eyebrow">Protected learning viewer</p>
                @if($module->module_code)
                    <span class="lms-status-chip is-purple font-mono">{{ $module->module_code }}</span>
                @endif
                <span class="lms-status-chip">{{ $module->categoryLabel() }}</span>
                @if($module->estimated_hours)
                    <span class="lms-status-chip">{{ $module->estimated_hours }} hours</span>
                @endif
                <span class="lms-status-chip {{ $isCompetent ? 'is-green' : ($progress?->status === 'awaiting_evaluation' ? 'is-purple' : ($progress?->status === 'needs_remediation' ? 'is-amber' : '')) }}">
                    {{ $progress->workflowStatusLabel() }}
                </span>
            </div>

            <div class="lms-module-workspace-title">
                <div>
                    <h1>{{ $module->title }}</h1>
                    @if($module->topic)
                        <p class="lms-module-topic">Learning Outcome / Topic: {{ $module->topic }}</p>
                    @endif
                    <p class="lms-module-byline">
                        <x-user-avatar :user="$module->trainer" :name="$module->trainer?->name ?? 'MCARE Trainer'" class="lms-module-trainer-avatar" />
                        Trainer: <strong>{{ $module->trainer?->name ?? 'MCARE Trainer' }}</strong>
                        @if($module->due_at)
                            <span>· Due {{ $module->due_at->format('M d, Y g:i A') }}</span>
                        @endif
                    </p>
                </div>
                @if(!$module->requiresEvaluation())
                    <p class="lms-module-guide">Learning material only — no Mark as Done required</p>
                @else
                    <a href="#submodules" class="lms-module-guide is-link">
                        {{ $progress->isTrainerValidated() ? 'All required submodules completed' : ($progress->needsRemediation() ? 'Remediate Not yet competent outcomes below ↓' : 'Face-to-face outcomes are listed below ↓') }}
                    </a>
                @endif
            </div>
        </header>

        @if ($errors->any() || session('error') || session('alert'))
            <div class="lms-module-banner is-danger" role="alert">
                <strong>Module Submission Notice.</strong>
                {{ $errors->first('action') ?: ($errors->first() ?: (session('error') ?: session('alert'))) }}
                @if(isset($unpassedQuizzes) && $unpassedQuizzes->isNotEmpty())
                    <a href="#assessments">Jump to required {{ \Illuminate\Support\Str::plural('quiz', $unpassedQuizzes->count()) }} below</a>
                @endif
            </div>
        @endif

        @if($progress->isTrainerValidated())
            <div class="lms-module-banner is-success">
                <strong>Competency unit evaluated and completed.</strong>
                Validated by {{ $progress->evaluator?->name ?? 'MCARE Trainer' }}
                on {{ $progress->evaluated_at?->format('M d, Y g:i A') ?? $progress->completed_at?->format('M d, Y') ?? 'recorded date' }}.
                The protected lesson document stays available below. Use Show or Hide to review it.
            </div>
        @elseif($progress->status === \App\Models\ModuleProgress::STATUS_AWAITING_EVALUATION)
            <div class="lms-module-banner is-review">
                <strong>Submitted for Trainer Evaluation.</strong>
                All required submodules are submitted. The main module result is calculated automatically while the trainer reviews each competency outcome.
            </div>
        @elseif($progress->needsRemediation())
            <div class="lms-module-banner is-danger" role="status">
                <strong>Not yet competent.</strong>
                Remediate the marked submodule(s) below. The next classwork module stays locked until this unit is Competent.
            </div>
        @else
            <div class="lms-module-banner is-notice">
                <strong>Protected Content.</strong>
                This learning material is watermarked for {{ $traineeName }} ({{ $application->email }}). Unauthorized copying or redistribution is strictly monitored.
            </div>
        @endif

        @php $lessonDocumentOpen = ! $progress->isTrainerValidated(); @endphp
        <div data-lesson-document data-lesson-document-open="{{ $lessonDocumentOpen ? 'true' : 'false' }}">
            <div class="lms-lesson-document-toolbar">
                <div>
                    <p class="lms-eyebrow">Protected content</p>
                    <p class="lms-lesson-document-copy">Watermarked lesson document for {{ $traineeName }}</p>
                </div>
                <button type="button" class="lms-lesson-document-toggle" data-lesson-document-toggle aria-controls="lesson-document-panel" aria-expanded="{{ $lessonDocumentOpen ? 'true' : 'false' }}">
                    <x-dashboard-icon name="book-open" class="h-4 w-4" />
                    <span data-lesson-document-toggle-label>{{ $lessonDocumentOpen ? 'Hide lesson document' : 'Show lesson document' }}</span>
                </button>
            </div>

            <div id="lesson-document-panel" data-lesson-document-panel @unless($lessonDocumentOpen) hidden @endunless>
                <section class="lms-module-viewer protected-module-content" data-protected-module-viewer>
                    @unless($previewKind === 'pdf')
                        <div class="lms-module-watermark" aria-hidden="true">
                            <x-learning-watermark :src="$watermarkImageUrl" />
                        </div>
                    @endunless

                    @if($previewKind === 'video')
                        <video class="lms-module-media" controls controlsList="nodownload noremoteplayback" disablePictureInPicture preload="metadata">
                            <source src="{{ $viewerUrl }}" type="{{ $module->mime_type }}">
                            Your browser cannot play this video.
                        </video>
                    @elseif($previewKind === 'audio')
                        <div class="lms-module-fallback">
                            <p>{{ $module->original_file_name }}</p>
                            <audio controls preload="metadata"><source src="{{ $viewerUrl }}" type="{{ $module->mime_type }}"></audio>
                        </div>
                    @elseif($previewKind === 'image')
                        <div class="lms-module-image">
                            <img src="{{ $viewerUrl }}" alt="{{ $module->title }}" draggable="false">
                        </div>
                    @elseif($previewKind === 'pdf')
                        <div class="lms-module-pdf" data-pdf-canvas-viewer data-pdf-url="{{ $viewerUrl }}">
                            <div class="lms-module-pdf-toolbar" aria-label="Document controls">
                                <div>
                                    <button type="button" data-pdf-prev disabled title="Previous Page">&larr; Prev</button>
                                    <span>Page <span data-pdf-current-page>1</span> of <span data-pdf-total-pages>-</span></span>
                                    <button type="button" data-pdf-next disabled title="Next Page">Next &rarr;</button>
                                </div>
                                <div>
                                    <button type="button" data-pdf-zoom-out title="Zoom Out">&minus;</button>
                                    <span data-pdf-zoom-level>125%</span>
                                    <button type="button" data-pdf-zoom-in title="Zoom In">+</button>
                                    <button type="button" data-pdf-fit-width title="Fit Width">Fit Width</button>
                                </div>
                            </div>
                            <div class="lms-module-pdf-stage" data-pdf-canvas-container>
                                <div data-pdf-scroll-sizer>
                                    <div data-pdf-page-wrapper>
                                        <canvas class="block bg-white" data-pdf-canvas></canvas>
                                    </div>
                                </div>
                                <div class="lms-module-pdf-loading" data-pdf-loading>
                                    Rendering lesson document...
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="lms-module-fallback">
                            <p>{{ $module->fileTypeLabel() }}</p>
                            <p>This document format is available for download and review.</p>
                            <a href="{{ $downloadUrl }}" class="primary-action">Download Lesson Document</a>
                        </div>
                    @endif
                </section>

                @if(count($supplementaryList) > 0)
                    <section class="lms-module-section">
                        <h2>Supplementary handouts</h2>
                        <ul class="lms-module-file-list">
                            @foreach($supplementaryList as $idx => $supp)
                                <li>
                                    <div>
                                        <strong>{{ $supp['original_name'] }}</strong>
                                        <span>{{ $supp['human_size'] ?? '' }}</span>
                                    </div>
                                    <a href="{{ route('trainee.modules.supplementary.download', [$module, $idx]) }}">Download</a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </div>

        @if($module->requiresEvaluation())
            <section id="submodules" class="lms-module-section">
                <div class="lms-module-section-heading">
                    <div>
                        <p class="lms-eyebrow">Competency outcomes</p>
                        <h2>Required Submodules</h2>
                        <p>These outcomes are demonstrated in face-to-face class. Your trainer records Competent or Not yet competent. A Not yet competent outcome must be remediated before later outcomes or the next classwork module can open.</p>
                    </div>
                    <span class="lms-status-chip is-purple">
                        {{ $submoduleProgressById->filter(fn ($item) => $item->isTrainerValidated())->count() }} / {{ $submodules->where('is_required', true)->count() }} competent
                    </span>
                </div>

                <div class="lms-module-outcome-list">
                    @forelse($submodules as $submodule)
                        @php
                            $childProgress = $submoduleProgressById->get($submodule->id);
                            $childSummary = $submoduleAssessmentSummaries->get($submodule->id, []);
                            $childQuizzes = $childSummary['quizzes'] ?? collect();
                            $childHasClasswork = ($childSummary['required_count'] ?? 0) > 0;
                            $childCompleted = $childProgress?->isTrainerValidated() ?? false;
                            $childAwaiting = $childProgress?->status === \App\Models\TrainingSubmoduleProgress::STATUS_AWAITING_EVALUATION;
                            $childNyc = $childProgress?->needsRemediation() ?? false;
                            $childAccess = $submoduleAccess[$submodule->id] ?? ['can_work' => true, 'blocker' => null];
                            $childLocked = ! ($childAccess['can_work'] ?? true);
                            $childBlocker = $childAccess['blocker'] ?? null;
                        @endphp
                        <article class="lms-module-outcome {{ $childCompleted ? 'is-complete' : ($childNyc ? 'is-remediation' : ($childLocked ? 'is-locked' : '')) }}">
                            <div class="lms-module-outcome-top">
                                <div>
                                    <p class="lms-eyebrow">Submodule {{ $submodule->position }}</p>
                                    <h3>{{ $submodule->title }}</h3>
                                </div>
                                <span class="lms-status-chip {{ $childCompleted ? 'is-green' : ($childAwaiting ? 'is-purple' : ($childNyc ? 'is-amber' : ($childLocked ? 'is-red' : ''))) }}">
                                    {{ $childLocked ? 'Locked' : ($childProgress?->workflowStatusLabel() ?? 'Ready to start') }}
                                </span>
                            </div>

                            <p class="lms-module-outcome-copy">
                                @if($childLocked)
                                    Locked until {{ $childBlocker?->title ?? 'the previous submodule' }} is Competent. You can still take this outcome's quiz.
                                @elseif($childNyc)
                                    Not yet competent. Your trainer will reassess this outcome in a face-to-face session.
                                @elseif($childCompleted)
                                    Trainer validated this outcome as Competent.
                                @elseif($childHasClasswork)
                                    Optional classwork: {{ $childSummary['passed_count'] ?? 0 }} / {{ $childSummary['required_count'] ?? 0 }} passed
                                    @if(($childSummary['average_score'] ?? null) !== null)
                                        · Best average {{ number_format((float) $childSummary['average_score'], 1) }}%
                                    @endif
                                @else
                                    Await your trainer's face-to-face evaluation. There is no Mark as Done on this outcome.
                                @endif
                            </p>

                            @if($childQuizzes->isNotEmpty())
                                <div class="lms-module-inline-actions">
                                    @foreach($childQuizzes as $childQuiz)
                                        <a href="{{ route('trainee.quizzes.show', $childQuiz) }}" class="secondary-action px-3 py-1.5 text-[11px]">{{ $childQuiz->title }}</a>
                                    @endforeach
                                </div>
                            @endif

                            @if($childProgress?->evaluation_remarks)
                                <p class="lms-module-outcome-copy"><strong>Trainer feedback:</strong> {{ $childProgress->evaluation_remarks }}</p>
                            @endif

                            <div class="lms-module-outcome-actions">
                                @if($childLocked)
                                    <span>Finish remediation on the Not yet competent outcome first.</span>
                                @elseif($childCompleted)
                                    <span>Trainer validated as Competent</span>
                                @elseif($childNyc)
                                    <span>Await face-to-face reassessment from your trainer.</span>
                                @else
                                    <span>Your trainer records this grade after the face-to-face session.</span>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p class="lms-module-banner is-notice">No competency outcomes are attached to this module yet.</p>
                    @endforelse
                </div>
            </section>
        @endif

        <section id="assessments" class="lms-module-section">
            <h2>Module Assessments & Performance Outcome</h2>

            <div class="lms-module-stat-row">
                <div>
                    <span>Quiz & Activity Average (This Module)</span>
                    <strong>{{ $assessmentAverage !== null ? number_format((float) $assessmentAverage, 1).'%' : 'No submitted score yet' }}</strong>
                    <small>Separate from the official overall course grade.</small>
                </div>
                <div>
                    <span>Practical Demonstration Rating</span>
                    <strong>{{ $progress?->practicalRatingLabel() ?? 'Pending F2F Demo' }}</strong>
                </div>
                <div>
                    <span>Module Competency Outcome</span>
                    <strong>{{ $isCompetent ? 'Competent (Passed)' : ($progress?->competency_outcome === 'not_yet_competent' ? 'For Remediation' : 'In Progress') }}</strong>
                </div>
            </div>

            @if($progress?->evaluation_remarks)
                <p class="lms-module-outcome-copy"><strong>Trainer Feedback:</strong> "{{ $progress->evaluation_remarks }}"
                    @if($progress->evaluator) · {{ $progress->evaluator->name }} @endif
                </p>
            @endif

            @if($quizzes->isNotEmpty())
                <h3 class="lms-module-subheading">Online Assessments ({{ $quizzes->count() }})</h3>
                <ul class="lms-module-quiz-list">
                    @foreach($quizzes as $quiz)
                        @php
                            $attempts = $quizAttempts->get($quiz->id) ?? collect();
                            $bestAttempt = $attempts->sortByDesc('score_percent')->first();
                            $hasPassed = $bestAttempt && $bestAttempt->score_percent >= $quiz->passing_score_percent;
                        @endphp
                        <li>
                            <div>
                                <strong>{{ $quiz->title }}</strong>
                                <span>{{ $quiz->questions->count() }} Questions · Passing Score: {{ number_format($quiz->passing_score_percent, 0) }}% · Time Limit: {{ $quiz->time_limit_minutes ? $quiz->time_limit_minutes.' mins' : 'Unlimited' }}</span>
                                @if($bestAttempt)
                                    <span class="{{ $hasPassed ? 'is-pass' : 'is-retry' }}">Best Score: {{ number_format($bestAttempt->score_percent, 1) }}% ({{ $hasPassed ? 'Passed' : 'Needs Retake' }})</span>
                                @endif
                            </div>
                            <div class="lms-module-inline-actions">
                                @if($bestAttempt)
                                    <a href="{{ route('trainee.quiz-attempts.result', $bestAttempt) }}" class="secondary-action text-xs py-1.5 px-3">View Score</a>
                                @endif
                                @if(!$progress->isTrainerValidated() && $progress->status !== \App\Models\ModuleProgress::STATUS_AWAITING_EVALUATION)
                                    <a href="{{ route('trainee.quizzes.show', $quiz) }}" class="primary-action text-xs py-1.5 px-3.5">
                                        {{ $bestAttempt ? 'Retake Quiz' : 'Take Quiz' }}
                                    </a>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <x-classroom-comments :commentable="$module" :comments="$classroomComments" :private-recipients="$privateCommentRecipients" />
    </article>
</div>
@endsection
