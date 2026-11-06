@extends('admin.layouts.app', ['title' => 'Module Quizzes | MCARE Admin'])

@section('content')
    <section class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="dashboard-section-kicker">Learning module</p>
                <h1 class="dashboard-section-title mt-2 text-2xl">{{ $module->title }}</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Create the quiz here and import its questions from a text, CSV, or JSON file. The assigned trainer can keep editing the same quiz.</p>
            </div>
            <a href="{{ route('admin.learning.modules') }}" class="secondary-action">Back to modules</a>
        </header>

        @if($module->requiresEvaluation())
            <form method="POST" action="{{ route('admin.learning.modules.quizzes.store', $module) }}" enctype="multipart/form-data" class="dashboard-panel grid gap-4 md:grid-cols-2">
                @csrf
                <div class="md:col-span-2">
                    <h2 class="text-lg font-bold text-slate-950">New quiz</h2>
                    <p class="mt-1 text-sm text-slate-600">The quiz stays a draft until its questions are saved and a trainer publishes it.</p>
                </div>
                <div>
                    <label for="admin-quiz-title" class="mb-1 block text-sm font-bold text-slate-800">Title</label>
                    <input id="admin-quiz-title" name="title" value="{{ old('title') }}" required maxlength="160" class="form-field" placeholder="Example: Infection control check">
                    @error('title')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="admin-quiz-submodule" class="mb-1 block text-sm font-bold text-slate-800">Competency submodule</label>
                    <select id="admin-quiz-submodule" name="training_submodule_id" class="form-field" required>
                        <option value="">Choose the outcome this quiz measures</option>
                        @foreach($module->submodules as $submodule)
                            <option value="{{ $submodule->id }}" @selected((string) old('training_submodule_id') === (string) $submodule->id)>{{ $submodule->position }}. {{ $submodule->title }}</option>
                        @endforeach
                    </select>
                    @error('training_submodule_id')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="admin-quiz-time" class="mb-1 block text-sm font-bold text-slate-800">Time limit (minutes)</label>
                    <input id="admin-quiz-time" type="number" name="time_limit_minutes" value="{{ old('time_limit_minutes', 20) }}" min="1" max="180" class="form-field">
                </div>
                <div>
                    <label for="admin-quiz-pass" class="mb-1 block text-sm font-bold text-slate-800">Passing score (%)</label>
                    <input id="admin-quiz-pass" type="number" name="passing_score_percent" value="{{ old('passing_score_percent', 75) }}" min="50" max="100" class="form-field">
                </div>
                <div class="md:col-span-2">
                    @include('trainer.quizzes.partials.question-file', [
                        'inputId' => 'admin-quiz-question-file',
                        'templateUrl' => route('admin.learning.quizzes.import-template'),
                    ])
                    @error('quiz')<p class="mt-1 text-sm text-red-700">{{ $message }}</p>@enderror
                </div>
                <div class="md:col-span-2">
                    <button type="submit" class="primary-action">Create quiz</button>
                </div>
            </form>
        @else
            <p class="dashboard-panel text-sm text-slate-600">This module is learning material only, so it does not take a required quiz.</p>
        @endif

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
                    @forelse($module->quizzes as $quiz)
                        <tr>
                            <td class="px-4 py-4">
                                <p class="font-bold text-slate-950">{{ $quiz->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">Passing score {{ rtrim(rtrim(number_format((float) $quiz->passing_score_percent, 2), '0'), '.') }}%</p>
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $quiz->trainingSubmodule?->title ?? 'Module-wide assessment' }}</td>
                            <td class="px-4 py-4 font-semibold text-slate-800">{{ $quiz->questions_count }}</td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $quiz->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $quiz->is_published ? 'Published' : 'Draft' }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                <a href="{{ route('admin.learning.quizzes.edit', $quiz) }}" class="secondary-action inline-flex h-9 items-center text-xs">Edit questions</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-12 text-center text-slate-500">No quiz has been created for this module yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
