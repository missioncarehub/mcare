@extends('admin.layouts.app', ['title' => 'Module Quizzes & Assessment Reports | MCARE Admin'])

@section('content')
    <section class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="dashboard-section-kicker">Learning module</p>
                <h1 class="dashboard-section-title mt-2 text-2xl">{{ $module->title }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                    Review and monitor quizzes authored by assigned trainers for this module. Administrators can inspect student answer submissions, passing rates, and summary score reports.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.learning.modules', ['tab' => 'submissions']) }}" class="secondary-action">
                    <x-dashboard-icon name="chart-bar" class="mr-1.5 h-4 w-4" />
                    All Submissions Ledger
                </a>
                <a href="{{ route('admin.learning.modules') }}" class="secondary-action">Back to modules</a>
            </div>
        </header>

        {{-- Trainer Assessment Overview Card --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Quizzes in Module</span>
                <strong class="mt-2 block text-2xl font-bold text-slate-900">{{ $module->quizzes->count() }}</strong>
                <small class="text-xs text-slate-500">Created by assigned trainer</small>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Published Assessments</span>
                <strong class="mt-2 block text-2xl font-bold text-emerald-700">{{ $module->quizzes->where('is_published', true)->count() }}</strong>
                <small class="text-xs text-slate-500">Available to enrolled trainees</small>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <span class="text-xs font-bold uppercase tracking-wider text-purple-700">Graded Submissions</span>
                <strong class="mt-2 block text-2xl font-bold text-purple-700">{{ $module->quizzes->sum('graded_attempts_count') }}</strong>
                <small class="text-xs text-slate-500">Completed student attempts</small>
            </article>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-950">Trainer Quizzes &amp; Student Summary</h2>
                    <p class="mt-0.5 text-xs text-slate-500">Quizzes are managed and published by the trainer. Click "View Results" to review individual student answers and scores.</p>
                </div>
            </div>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Quiz Title</th>
                        <th class="px-4 py-3">Competency Submodule</th>
                        <th class="px-4 py-3">Questions</th>
                        <th class="px-4 py-3">Submissions</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($module->quizzes as $quiz)
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-5 py-4">
                                <p class="font-bold text-slate-950">{{ $quiz->title }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    Passing score: <span class="font-semibold text-slate-700">{{ rtrim(rtrim(number_format((float) $quiz->passing_score_percent, 2), '0'), '.') }}%</span>
                                    @if($quiz->time_limit_minutes)
                                        · {{ $quiz->time_limit_minutes }} mins limit
                                    @endif
                                </p>
                            </td>
                            <td class="px-4 py-4 text-slate-700">
                                <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-medium text-slate-700">
                                    {{ $quiz->trainingSubmodule?->title ?? 'Module-wide assessment' }}
                                </span>
                            </td>
                            <td class="px-4 py-4 font-semibold text-slate-800">
                                {{ $quiz->questions_count }} questions
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-800">
                                    <span class="inline-block h-2 w-2 rounded-full {{ ($quiz->graded_attempts_count ?? 0) > 0 ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                    {{ $quiz->graded_attempts_count ?? 0 }} submitted
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold {{ $quiz->is_published ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $quiz->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.learning.quizzes.results', $quiz) }}" class="primary-action !min-h-9 !px-3.5 text-xs">
                                        <x-dashboard-icon name="chart-bar" class="mr-1 h-3.5 w-3.5" />
                                        View Results
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-slate-500">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <x-dashboard-icon name="clipboard-list" class="h-10 w-10 text-slate-300" />
                                    <p class="mt-2 text-sm font-semibold text-slate-700">No quizzes created by trainer yet</p>
                                    <p class="mt-1 text-xs text-slate-500">Once the assigned trainer publishes a quiz for this module, student submissions and results will appear here.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
