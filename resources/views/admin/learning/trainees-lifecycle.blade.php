@extends('admin.layouts.app', ['title' => 'Trainee Records | MCARE Admin'])

@section('content')
    @php
        $statusStyles = [
            \App\Models\EnrollmentApplication::LEARNING_ACTIVE => 'bg-emerald-50 text-emerald-800 ring-emerald-200',
            \App\Models\EnrollmentApplication::LEARNING_PAUSED => 'bg-amber-50 text-amber-900 ring-amber-200',
            \App\Models\EnrollmentApplication::LEARNING_PENDING_GRADUATE => 'bg-indigo-50 text-indigo-800 ring-indigo-200',
            \App\Models\EnrollmentApplication::LEARNING_GRADUATED => 'bg-purple-50 text-purple-800 ring-purple-200',
            \App\Models\EnrollmentApplication::LEARNING_WITHDRAWN => 'bg-slate-100 text-slate-700 ring-slate-200',
        ];
        $hasActiveFilters = collect($filters)->contains(fn ($value) => filled($value));
        $isGraduatedTab = $isGraduatedTab ?? false;
        $graduatedCount = (int) ($statusCounts[\App\Models\EnrollmentApplication::LEARNING_GRADUATED] ?? 0);
        $currentCount = max(0, (int) collect($statusCounts)->sum() - $graduatedCount);
        $rosterBaseQuery = collect($filters)->except(['learning_status'])->filter(fn ($value) => filled($value))->all();
    @endphp

    <section class="space-y-6">
        <p class="max-w-3xl text-sm leading-6 text-slate-600">Use the roster table to scan payment, module completion, and assessment status. Open View details for the full trainee record.</p>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
            @foreach ($learningStatuses as $value => $label)
                @php
                    $statusSelected = $value === \App\Models\EnrollmentApplication::LEARNING_GRADUATED
                        ? $isGraduatedTab
                        : (! $isGraduatedTab && ($filters['learning_status'] ?? '') === $value);
                @endphp
                <a href="{{ route('admin.learning.trainees', $rosterBaseQuery + [
                    'tab' => $value === \App\Models\EnrollmentApplication::LEARNING_GRADUATED ? 'graduated' : 'current',
                    'learning_status' => $value,
                ]) }}" class="dashboard-stat min-h-0 p-4 {{ $statusSelected ? 'border-purple-300 ring-2 ring-purple-100' : '' }}">
                    <div>
                        <p class="dashboard-stat-label">{{ $label }}</p>
                        <p class="dashboard-stat-value text-2xl">{{ $statusCounts[$value] ?? 0 }}</p>
                    </div>
                    @php
                        $statusIcons = ['active' => 'users', 'paused' => 'circle-minus', 'pending_graduate' => 'clock', 'graduated' => 'award', 'withdrawn' => 'xmark'];
                    @endphp
                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg ring-1 {{ $statusStyles[$value] }}"><x-dashboard-icon :name="$statusIcons[$value] ?? 'circle-question'" /></span>
                </a>
            @endforeach
        </div>

        <details class="dashboard-panel trainee-filter-panel" @if($hasActiveFilters) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between gap-4 outline-none">
                <div>
                    <p class="dashboard-section-kicker">Roster filters</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">Search or narrow the summarized trainee list</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-purple-700">
                    <x-dashboard-icon name="chevron-down" class="trainee-accordion-chevron h-4 w-4 transition-transform" />
                </span>
            </summary>

            <form method="GET" data-auto-filter class="mt-5 grid gap-4 border-t border-slate-200 pt-5 md:grid-cols-2 xl:grid-cols-8">
                <input type="hidden" name="tab" value="{{ $isGraduatedTab ? 'graduated' : 'current' }}">
                <div class="md:col-span-2 xl:col-span-2">
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Search trainee</label>
                    <input name="search" value="{{ $filters['search'] ?? '' }}" class="form-field" placeholder="Name or email">
                </div>
                <div class="md:col-span-2 xl:col-span-2">
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch</label>
                    <select name="batch_id" class="form-field">
                        <option value="">All batches</option>
                        @foreach ($batches as $batch)
                            <option value="{{ $batch->id }}" @selected((int) ($filters['batch_id'] ?? 0) === $batch->id)>{{ $batch->name }} {{ $batch->year }} · {{ $batch->approved_trainees_count }} trainees</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Schedule</label>
                    <select name="schedule" class="form-field"><option value="">AM and PM</option><option value="AM" @selected(($filters['schedule'] ?? '') === 'AM')>AM</option><option value="PM" @selected(($filters['schedule'] ?? '') === 'PM')>PM</option></select>
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Learner status</label>
                    @if ($isGraduatedTab)
                        <input type="hidden" name="learning_status" value="{{ \App\Models\EnrollmentApplication::LEARNING_GRADUATED }}">
                        <p class="form-field bg-slate-50 font-semibold text-slate-700">Graduated</p>
                    @else
                        <select name="learning_status" class="form-field">
                            <option value="">All current statuses</option>
                            @foreach($learningStatuses as $value => $label)
                                @continue($value === \App\Models\EnrollmentApplication::LEARNING_GRADUATED)
                                <option value="{{ $value }}" @selected(($filters['learning_status'] ?? '') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endif
                </div>
                <div>
                    <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch progress</label>
                    <select name="training_state" class="form-field"><option value="">Any progress</option><option value="not_started" @selected(($filters['training_state'] ?? '') === 'not_started')>Not started</option><option value="in_progress" @selected(($filters['training_state'] ?? '') === 'in_progress')>In progress</option><option value="completed" @selected(($filters['training_state'] ?? '') === 'completed')>Completed</option></select>
                </div>
                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Joined from</label><input name="joined_from" type="date" value="{{ $filters['joined_from'] ?? '' }}" class="form-field"></div>
                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Joined to</label><input name="joined_to" type="date" value="{{ $filters['joined_to'] ?? '' }}" class="form-field"></div>
                <div class="flex flex-wrap items-end gap-2 md:col-span-2 xl:col-span-6"><button class="primary-action">Filter trainees</button><a href="{{ route('admin.learning.trainees', ['tab' => $isGraduatedTab ? 'graduated' : 'current']) }}" class="secondary-action">Reset</a><a href="{{ route('admin.learning.trainees.export', request()->query()) }}" class="secondary-action">Export Excel CSV</a></div>
            </form>
        </details>

        <nav class="lms-context-tabs" aria-label="Trainee roster sections">
            <a href="{{ route('admin.learning.trainees', $rosterBaseQuery + ['tab' => 'current']) }}" class="{{ $isGraduatedTab ? '' : 'is-active' }}" @unless($isGraduatedTab) aria-current="page" @endunless>Current trainees ({{ $currentCount }})</a>
            <a href="{{ route('admin.learning.trainees', $rosterBaseQuery + ['tab' => 'graduated']) }}" class="{{ $isGraduatedTab ? 'is-active' : '' }}" @if($isGraduatedTab) aria-current="page" @endif>Graduates ({{ $graduatedCount }})</a>
            <a href="{{ route('admin.learning.trainees.archive') }}">Archive</a>
        </nav>

        <div class="dashboard-table-wrap overflow-x-auto" data-trainee-roster>
            <table class="dashboard-table w-full min-w-[72rem]">
                <thead>
                    <tr>
                        <th>Trainee</th>
                        <th>Batch</th>
                        <th>Payment</th>
                        <th>Modules</th>
                        <th>Assessment</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trainees as $trainee)
                        @php
                            $summary = $traineeSummaries->get($trainee->id, [
                                'total_modules' => 0,
                                'completed_modules' => 0,
                                'in_progress_modules' => 0,
                                'progress_percent' => 0,
                                'last_activity' => null,
                                'assessment_ready' => false,
                            ]);
                        @endphp
                        <tr data-trainee-card data-trainee-row="{{ $trainee->id }}">
                            <td>
                                <div class="flex min-w-0 items-center gap-3">
                                    <x-user-avatar :user="$trainee->user" :application="$trainee" :use-enrollment-photo="true" class="grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-sm font-black text-purple-800" />
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="font-bold text-slate-950">{{ $trainee->last_name }}, {{ $trainee->first_name }}</p>
                                            <span class="dashboard-pill {{ $statusStyles[$trainee->learning_status] ?? $statusStyles[\App\Models\EnrollmentApplication::LEARNING_ACTIVE] }}">{{ $trainee->learningStatusLabel() }}</span>
                                        </div>
                                        <p class="mt-1 truncate text-xs text-slate-500">{{ $trainee->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : 'Unassigned batch' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $trainee->schedule_preference ?: 'Schedule pending' }}</p>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $trainee->paymentStatusLabel() }}</p>
                            </td>
                            <td>
                                <p class="font-semibold text-slate-800">{{ $summary['completed_modules'] }} of {{ $summary['total_modules'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $summary['progress_percent'] }}% complete</p>
                            </td>
                            <td>
                                <p class="font-semibold {{ $summary['assessment_ready'] ? 'text-emerald-700' : 'text-amber-800' }}">{{ $summary['assessment_ready'] ? 'Ready' : 'Pending' }}</p>
                            </td>
                            <td>
                                <div class="flex flex-wrap items-center justify-end gap-2">
                                    <form method="POST" action="{{ route('admin.learning.trainees.destroy', $trainee) }}" class="inline-flex" data-confirm-title="{{ $trainee->archiveTitle() }}" data-confirm="{{ $trainee->archiveMessage() }}" data-confirm-action="Archive trainee">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex h-10 items-center gap-2 rounded-lg border border-red-200 bg-white px-3 text-xs font-bold uppercase tracking-wide text-red-700 hover:bg-red-50" aria-label="Archive {{ $trainee->first_name }} {{ $trainee->last_name }}">
                                            <x-dashboard-icon name="trash-2" class="h-4 w-4" />
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                    <a href="{{ route('admin.learning.trainees.show', $trainee) }}" class="secondary-action inline-flex h-10 items-center gap-2">
                                        <span>View details</span>
                                        <x-dashboard-icon name="arrow-right" class="h-4 w-4" />
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-14 text-center">
                                <p class="text-lg font-bold text-slate-950">{{ $isGraduatedTab ? 'No graduates found' : 'No trainees found' }}</p>
                                <p class="mt-2 text-sm text-slate-500">{{ $isGraduatedTab ? 'No graduated trainees match the current filters.' : 'No current trainees match the current filters.' }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            @if ($trainees->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">{{ $trainees->links() }}</div>
            @endif
        </div>
    </section>
@endsection
