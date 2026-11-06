@extends('trainer.layouts.app', ['title' => 'Quiz Results | MCARE Trainer'])

@section('content')
@php
    $classSummary = $classSummary ?? ['average' => null, 'highest' => null, 'lowest' => null, 'passed' => 0];
    $audienceLabel = $quiz->targetTrainee
        ? trim($quiz->targetTrainee->first_name.' '.$quiz->targetTrainee->last_name)
        : ($quiz->batch ? $quiz->batch->name.' '.$quiz->batch->year : 'Assigned class');
@endphp

<div class="lms-page" data-quiz-results>
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Quiz results</p>
            <h1>{{ $quiz->title }}</h1>
            <p>{{ $audienceLabel }} - {{ $quiz->questions->count() }} questions - Passing score {{ number_format((float) $quiz->passing_score_percent) }}%</p>
        </div>
        <div class="lms-header-actions">
            <a href="{{ route('trainer.quizzes.edit', $quiz) }}" class="secondary-action">Edit quiz</a>
            <a href="{{ $quiz->training_module_id ? route('trainer.modules.show', ['module' => $quiz->training_module_id, 'tab' => 'assessments']).'#assessments' : route('trainer.resources') }}" class="secondary-action">Back to module</a>
        </div>
    </header>

    <section class="lms-metric-grid" aria-label="Quiz result summary">
        <article class="lms-metric-card">
            <span>Submissions</span>
            <strong>{{ $attempts->total() }}</strong>
            <small>Graded attempts</small>
        </article>
        <article class="lms-metric-card">
            <span>Average score</span>
            <strong>{{ $classSummary['average'] !== null ? number_format((float) $classSummary['average'], 1).'%' : '-' }}</strong>
            <small>Whole class</small>
        </article>
        <article class="lms-metric-card">
            <span>Highest / lowest</span>
            <strong>{{ $classSummary['highest'] !== null ? number_format((float) $classSummary['highest'], 1).'%' : '-' }} / {{ $classSummary['lowest'] !== null ? number_format((float) $classSummary['lowest'], 1).'%' : '-' }}</strong>
            <small>Whole class</small>
        </article>
        <article class="lms-metric-card">
            <span>Passed</span>
            <strong>{{ $classSummary['passed'] }}</strong>
            <small>Whole class</small>
        </article>
    </section>

    <section class="lms-results-panel" aria-labelledby="learner-results-title">
        <div class="lms-section-heading">
            <div>
                <p class="lms-eyebrow">Learner work</p>
                <h2 id="learner-results-title">Submitted attempts</h2>
            </div>
        </div>

        <div class="lms-responsive-table">
            <table>
                <thead>
                    <tr>
                        <th>Learner</th>
                        <th>Attempt</th>
                        <th>Submitted</th>
                        <th>Files / Activity</th>
                        <th>Points</th>
                        <th>Score</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($attempts as $attempt)
                        @php
                            $learner = $attempt->application;
                            $learnerName = $learner
                                ? trim($learner->first_name.' '.$learner->last_name)
                                : ($attempt->application?->user?->name ?? 'Trainee');
                            $submittedFiles = collect($attempt->answers ?? [])->filter(fn ($ans) => is_array($ans) && ($ans['type'] ?? null) === 'file');
                        @endphp
                        <tr>
                            <td data-label="Learner">
                                <span class="flex items-center gap-3">
                                    <x-user-avatar
                                        :user="$learner?->user"
                                        :name="$learnerName"
                                        class="grid h-9 w-9 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800"
                                    />
                                    <span class="min-w-0"><strong class="block truncate">{{ $learnerName }}</strong><small class="block truncate">{{ $learner?->email ?? $learner?->user?->email }}</small></span>
                                </span>
                            </td>
                            <td data-label="Attempt">#{{ $attempt->attempt_number }}</td>
                            <td data-label="Submitted">{{ $attempt->submitted_at?->format('M d, Y g:i A') ?? '-' }}</td>
                            <td data-label="Files / Activity">
                                @if($submittedFiles->isNotEmpty())
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($submittedFiles as $qId => $fileAnswer)
                                            <a href="{{ route('trainer.quizzes.attempts.download', ['quiz' => $quiz, 'attempt' => $attempt, 'question' => $qId]) }}" class="inline-flex items-center gap-1 rounded-md bg-purple-50 px-2 py-1 text-[11px] font-bold text-purple-700 hover:bg-purple-100 hover:text-purple-900" title="{{ $fileAnswer['original_name'] ?? 'Document' }}">
                                                <x-dashboard-icon name="document" class="h-3.5 w-3.5 text-purple-600" />
                                                <span>{{ str($fileAnswer['original_name'] ?? 'File')->limit(16) }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td data-label="Points">{{ number_format((float) $attempt->earned_points, 1) }} / {{ number_format((float) $attempt->total_points, 1) }}</td>
                            <td data-label="Score"><strong>{{ number_format((float) $attempt->score_percent, 1) }}%</strong></td>
                            <td data-label="Result">
                                <span class="lms-status-chip {{ $attempt->passed ? 'is-green' : 'is-red' }}">
                                    {{ $attempt->passed ? 'Passed' : 'Needs review' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7">
                                <div class="lms-empty-state">
                                    <x-dashboard-icon name="clipboard-list" />
                                    <h2>No submissions yet</h2>
                                    <p>Scores will appear here after trainees submit the quiz.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($attempts->hasPages())
            <div class="lms-pagination">{{ $attempts->links() }}</div>
        @endif
    </section>
</div>
@endsection
