@extends('admin.layouts.app', ['title' => 'Quiz Results | MCARE Admin'])

@section('content')
@php
    $classSummary = $classSummary ?? ['average' => null, 'highest' => null, 'lowest' => null, 'passed' => 0];
    $audienceLabel = $quiz->targetTrainee
        ? trim($quiz->targetTrainee->first_name.' '.$quiz->targetTrainee->last_name)
        : ($quiz->batch ? $quiz->batch->name.' '.$quiz->batch->year : 'Assigned class');
@endphp

<div class="lms-page space-y-6" data-quiz-results>
    <header class="flex flex-col gap-3 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
        <div class="min-w-0">
            <p class="dashboard-section-kicker">Learning assessments · {{ $quiz->trainingModule?->title ?? 'Module' }}</p>
            <h1 class="dashboard-section-title mt-1 text-2xl font-bold sm:text-3xl">{{ $quiz->title }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $audienceLabel }} · {{ $quiz->questions->count() }} questions · Passing score {{ number_format((float) $quiz->passing_score_percent) }}%</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.learning.quizzes.edit', $quiz) }}" class="secondary-action">Edit quiz</a>
            <a href="{{ route('admin.learning.modules', ['tab' => 'submissions']) }}" class="secondary-action">Back to all submissions</a>
        </div>
    </header>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Quiz result summary">
        <article class="dashboard-panel p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Submissions</span>
            <strong class="mt-1 block text-3xl font-bold text-slate-950">{{ $attempts->total() }}</strong>
            <small class="text-xs text-slate-500">Graded attempts</small>
        </article>
        <article class="dashboard-panel p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-sky-700">Average score</span>
            <strong class="mt-1 block text-3xl font-bold text-sky-950">{{ $classSummary['average'] !== null ? number_format((float) $classSummary['average'], 1).'%' : '—' }}</strong>
            <small class="text-xs text-slate-500">Whole class</small>
        </article>
        <article class="dashboard-panel p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-purple-700">Highest / lowest</span>
            <strong class="mt-1 block text-3xl font-bold text-purple-950">{{ $classSummary['highest'] !== null ? number_format((float) $classSummary['highest'], 1).'%' : '—' }} / {{ $classSummary['lowest'] !== null ? number_format((float) $classSummary['lowest'], 1).'%' : '—' }}</strong>
            <small class="text-xs text-slate-500">Whole class</small>
        </article>
        <article class="dashboard-panel p-5">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Passed</span>
            <strong class="mt-1 block text-3xl font-bold text-emerald-950">{{ $classSummary['passed'] }}</strong>
            <small class="text-xs text-slate-500">Passing score met</small>
        </article>
    </section>

    <section class="dashboard-table-wrap overflow-x-auto" aria-labelledby="learner-results-title">
        <div class="flex flex-col gap-2 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="learner-results-title" class="text-base font-bold text-slate-950">Submitted attempts</h2>
                <p class="mt-1 text-sm text-slate-600">All submissions graded for this specific quiz.</p>
            </div>
        </div>

        <table class="dashboard-table w-full min-w-[54rem]">
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
                        <td>
                            <div class="flex items-center gap-3">
                                <x-user-avatar
                                    :user="$learner?->user"
                                    :name="$learnerName"
                                    class="grid h-9 w-9 place-items-center rounded-full bg-purple-100 text-xs font-black text-purple-800"
                                />
                                <div class="min-w-0">
                                    <strong class="block truncate font-bold text-slate-950">{{ $learnerName }}</strong>
                                    <small class="block truncate text-slate-500">{{ $learner?->email ?? $learner?->user?->email }}</small>
                                </div>
                            </div>
                        </td>
                        <td>#{{ $attempt->attempt_number }}</td>
                        <td class="text-slate-600 whitespace-nowrap">{{ $attempt->submitted_at?->format('M d, Y g:i A') ?? '—' }}</td>
                        <td>
                            @if($submittedFiles->isNotEmpty())
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($submittedFiles as $qId => $fileAnswer)
                                        <a href="{{ route('admin.learning.quizzes.attempts.download', ['quiz' => $quiz, 'attempt' => $attempt, 'question' => $qId]) }}" class="inline-flex items-center gap-1 rounded-md bg-purple-50 px-2 py-1 text-[11px] font-bold text-purple-700 hover:bg-purple-100 hover:text-purple-900" title="{{ $fileAnswer['original_name'] ?? 'Document' }}">
                                            <x-dashboard-icon name="document" class="h-3.5 w-3.5 text-purple-600" />
                                            <span>{{ str($fileAnswer['original_name'] ?? 'File')->limit(16) }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-xs text-slate-400">None</span>
                            @endif
                        </td>
                        <td>{{ $attempt->earned_points }} / {{ $attempt->total_points }} pts</td>
                        <td class="font-bold text-slate-950">{{ number_format((float) $attempt->score_percent, 1) }}%</td>
                        <td>
                            @if($attempt->passed)
                                <span class="dashboard-pill bg-emerald-50 text-emerald-800 ring-emerald-200">
                                    Passed
                                </span>
                            @else
                                <span class="dashboard-pill bg-rose-50 text-rose-800 ring-rose-200">
                                    Did not pass
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-500">
                            No learner submissions recorded for this quiz yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($attempts->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $attempts->links() }}</div>
        @endif
    </section>
</div>
@endsection
