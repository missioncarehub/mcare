@extends('admin.layouts.app', ['title' => 'Learning Reports | MCARE Admin'])

@section('content')
{{-- Path: resources/views/admin/learning/reports.blade.php | Label: Detailed learning reports page --}}
@php
    use App\Models\EnrollmentApplication;
    use App\Models\ModuleProgress;
    use App\Models\TraineeAttendance;

    $peso = fn ($v) => '₱'.number_format((float) $v, 2);
    $pct = fn ($num, $den) => $den > 0 ? round(($num / $den) * 100, 1) : 0;

    $progressLabels = [
        ModuleProgress::STATUS_NOT_STARTED => ['label' => 'Not started', 'tone' => 'bg-slate-100 text-slate-700 ring-slate-200'],
        ModuleProgress::STATUS_LOCKED => ['label' => 'Locked', 'tone' => 'bg-stone-100 text-stone-700 ring-stone-200'],
        ModuleProgress::STATUS_IN_PROGRESS => ['label' => 'In progress', 'tone' => 'bg-sky-50 text-sky-700 ring-sky-100'],
        ModuleProgress::STATUS_AWAITING_EVALUATION => ['label' => 'Awaiting evaluation', 'tone' => 'bg-amber-50 text-amber-700 ring-amber-100'],
        ModuleProgress::STATUS_NEEDS_REMEDIATION => ['label' => 'Needs remediation', 'tone' => 'bg-rose-50 text-rose-700 ring-rose-100'],
        ModuleProgress::STATUS_COMPLETED => ['label' => 'Completed', 'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
    ];

    $attendanceLabels = [
        TraineeAttendance::STATUS_PRESENT => ['label' => 'Present', 'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
        TraineeAttendance::STATUS_LATE => ['label' => 'Late', 'tone' => 'bg-amber-50 text-amber-700 ring-amber-100'],
        TraineeAttendance::STATUS_ABSENT => ['label' => 'Absent', 'tone' => 'bg-rose-50 text-rose-700 ring-rose-100'],
        TraineeAttendance::STATUS_EXCUSED => ['label' => 'Excused', 'tone' => 'bg-slate-100 text-slate-700 ring-slate-200'],
    ];
@endphp

<section class="space-y-8">
    <header class="flex flex-wrap items-end justify-between gap-4 border-b border-stone-200 pb-6">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-violet-700">Reports</p>
            <h1 class="mt-2 text-3xl font-bold text-stone-950">Learning system overview</h1>
            <p class="mt-2 max-w-3xl text-stone-600">Applications, learners, modules, attendance, and payments — pulled live from the same records the rest of MCARE uses.</p>
        </div>
        <p class="text-xs text-stone-500">Generated {{ $generatedAt->format('M d, Y g:i A') }}</p>
    </header>

    {{-- Top-line KPIs --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $kpiCards = [
                ['label' => 'Total applications', 'value' => number_format($kpis['total_applications']), 'sub' => number_format($kpis['pending_applications']).' awaiting review', 'icon' => 'clipboard-list', 'tone' => 'bg-purple-50 text-purple-700 ring-purple-100'],
                ['label' => 'Approved trainees', 'value' => number_format($kpis['approved_trainees']), 'sub' => number_format($kpis['active_learners']).' actively learning', 'icon' => 'user-check', 'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
                ['label' => 'Graduated trainees', 'value' => number_format($kpis['graduated_trainees']), 'sub' => 'Cumulative graduates on record', 'icon' => 'award', 'tone' => 'bg-indigo-50 text-indigo-700 ring-indigo-100'],
                ['label' => 'Published modules', 'value' => number_format($kpis['published_modules']), 'sub' => number_format($kpis['total_modules']).' modules in catalog', 'icon' => 'book-open', 'tone' => 'bg-violet-50 text-violet-700 ring-violet-100'],
            ];
        @endphp
        @foreach($kpiCards as $card)
            <div class="border border-stone-200 bg-white p-5">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm text-stone-500">{{ $card['label'] }}</p>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg ring-1 {{ $card['tone'] }}">
                        <x-dashboard-icon :name="$card['icon']" />
                    </span>
                </div>
                <p class="mt-2 text-3xl font-bold text-stone-950">{{ $card['value'] }}</p>
                <p class="mt-1 text-xs text-stone-500">{{ $card['sub'] }}</p>
            </div>
        @endforeach
    </div>

    {{-- Module progress mix + attendance snapshot --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="border border-stone-200 bg-white p-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-bold text-stone-950">Module progression</h2>
                <p class="text-xs text-stone-500">{{ number_format($moduleProgressTotal) }} learner-module records</p>
            </div>
            <p class="mt-1 text-sm text-stone-600">Where trainees currently stand across every module of every batch.</p>

            <div class="mt-4 space-y-3">
                @foreach($progressLabels as $status => $meta)
                    @php
                        $count = (int) ($moduleProgress[$status] ?? 0);
                        $percent = $pct($count, $moduleProgressTotal);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-xs">
                            <span class="inline-flex items-center gap-2 rounded-full px-2 py-0.5 text-[11px] font-bold ring-1 {{ $meta['tone'] }}">{{ $meta['label'] }}</span>
                            <span class="font-bold text-stone-700">{{ number_format($count) }} <span class="font-normal text-stone-400">({{ $percent }}%)</span></span>
                        </div>
                        <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-stone-100">
                            <div class="h-full rounded-full bg-violet-500" style="width: {{ min(100, $percent) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-5 grid grid-cols-2 gap-3 border-t border-stone-100 pt-4 text-sm">
                <div>
                    <p class="text-xs uppercase tracking-wide text-stone-500">Completed</p>
                    <p class="mt-0.5 text-2xl font-bold text-emerald-700">{{ number_format($kpis['completed_modules']) }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-wide text-stone-500">Awaiting evaluation</p>
                    <p class="mt-0.5 text-2xl font-bold text-amber-700">{{ number_format($kpis['awaiting_evaluation']) }}</p>
                </div>
            </div>
        </section>

        <section class="border border-stone-200 bg-white p-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-bold text-stone-950">Attendance snapshot</h2>
                <p class="text-xs text-stone-500">Last 30 days · since {{ $attendance['window_start']->format('M d, Y') }}</p>
            </div>
            <p class="mt-1 text-sm text-stone-600">Trainer-logged classroom attendance across all active batches.</p>

            <div class="mt-4 grid grid-cols-2 gap-3">
                @foreach($attendanceLabels as $status => $meta)
                    @php $count = (int) ($attendance['counts'][$status] ?? 0); @endphp
                    <div class="rounded-lg border border-stone-100 bg-stone-50 p-3">
                        <p class="text-xs uppercase tracking-wide text-stone-500">{{ $meta['label'] }}</p>
                        <p class="mt-0.5 text-2xl font-bold text-stone-950">{{ number_format($count) }}</p>
                    </div>
                @endforeach
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t border-stone-100 pt-4">
                <div>
                    <p class="text-xs uppercase tracking-wide text-stone-500">Overall attendance rate</p>
                    <p class="mt-0.5 text-3xl font-bold text-stone-950">
                        {{ $attendance['rate'] === null ? '—' : $attendance['rate'].'%' }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs uppercase tracking-wide text-stone-500">Records logged</p>
                    <p class="mt-0.5 text-3xl font-bold text-stone-950">{{ number_format($attendance['logged']) }}</p>
                </div>
            </div>

            <p class="mt-3 text-xs text-stone-500">
                {{ number_format($attendance['recent_competency_evaluations']) }} competency evaluations were updated in the same window.
            </p>
        </section>
    </div>

    {{-- Payments summary --}}
    <section class="border border-stone-200 bg-white p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <h2 class="text-lg font-bold text-stone-950">Payments & revenue</h2>
            <p class="text-xs text-stone-500">Verified transactions only</p>
        </div>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-lg border border-stone-100 bg-emerald-50/40 p-4">
                <p class="text-xs uppercase tracking-wide text-stone-500">Total verified revenue</p>
                <p class="mt-1 text-2xl font-bold text-emerald-800">{{ $peso($kpis['revenue_verified']) }}</p>
            </div>
            <div class="rounded-lg border border-stone-100 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-stone-500">This month</p>
                <p class="mt-1 text-2xl font-bold text-stone-950">{{ $peso($kpis['revenue_this_month']) }}</p>
            </div>
            <div class="rounded-lg border border-stone-100 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-stone-500">Fully paid trainees</p>
                <p class="mt-1 text-2xl font-bold text-stone-950">{{ number_format($kpis['fully_paid']) }}</p>
            </div>
            <div class="rounded-lg border border-stone-100 bg-white p-4">
                <p class="text-xs uppercase tracking-wide text-stone-500">Partially paid</p>
                <p class="mt-1 text-2xl font-bold text-stone-950">{{ number_format($kpis['partially_paid']) }}</p>
            </div>
            <div class="rounded-lg border border-stone-100 bg-amber-50/40 p-4">
                <p class="text-xs uppercase tracking-wide text-stone-500">Awaiting verification</p>
                <p class="mt-1 text-2xl font-bold text-amber-800">{{ number_format($kpis['pending_payments']) }}</p>
            </div>
        </div>
    </section>

    {{-- Per-batch comparison --}}
    <section class="border border-stone-200 bg-white p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <div>
                <h2 class="text-lg font-bold text-stone-950">Per-batch breakdown</h2>
                <p class="text-sm text-stone-600">Compare enrollment, schedule mix, approvals, payments, and published modules side by side.</p>
            </div>
            <p class="text-xs text-stone-500">{{ $batches->count() }} {{ Str::plural('batch', $batches->count()) }}</p>
        </div>

        <div class="dashboard-table-wrap mt-4 overflow-x-auto">
            <table class="dashboard-table w-full min-w-[80rem]">
                <thead>
                    <tr>
                        <th>Batch</th>
                        <th>Enrollment</th>
                        <th>Training</th>
                        <th class="text-right">Apps</th>
                        <th class="text-right">Pending</th>
                        <th class="text-right">Approved</th>
                        <th class="text-right">Denied</th>
                        <th class="text-right">AM</th>
                        <th class="text-right">PM</th>
                        <th class="text-right">Paid</th>
                        <th class="text-right">Partial</th>
                        <th class="text-right">Active</th>
                        <th class="text-right">Graduated</th>
                        <th class="text-right">Modules</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td>
                            <p class="font-bold text-slate-950">{{ $batch->name }} {{ $batch->year }}</p>
                            <p class="mt-1 text-xs text-slate-500">
                                {{ $batch->is_continuous_enrollment ? 'Continuous enrollment' : 'Deadline '.optional($batch->enrollment_ends_at)->format('M d, Y') }}
                            </p>
                        </td>
                        <td>
                            <span class="dashboard-pill {{ $batch->acceptsEnrollment() ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-100 text-slate-700 ring-slate-200' }}">
                                {{ $batch->enrollmentStateLabel() }}
                            </span>
                        </td>
                        <td class="text-xs text-slate-600">{{ $batch->trainingStateLabel() }}</td>
                        <td class="text-right font-bold">{{ number_format($batch->applications_count) }}</td>
                        <td class="text-right">{{ number_format($batch->pending_count) }}</td>
                        <td class="text-right text-emerald-700">{{ number_format($batch->approved_count) }}</td>
                        <td class="text-right text-rose-600">{{ number_format($batch->denied_count) }}</td>
                        <td class="text-right">{{ number_format($batch->am_count) }}</td>
                        <td class="text-right">{{ number_format($batch->pm_count) }}</td>
                        <td class="text-right">{{ number_format($batch->paid_count) }}</td>
                        <td class="text-right">{{ number_format($batch->partial_paid_count) }}</td>
                        <td class="text-right">{{ number_format($batch->active_learners_count) }}</td>
                        <td class="text-right text-indigo-700">{{ number_format($batch->graduated_count) }}</td>
                        <td class="text-right">
                            {{ number_format($batch->modules_count) }}
                            <span class="text-xs text-slate-400">({{ number_format($batch->published_modules_count) }} live)</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="14" class="py-14 text-center text-sm text-slate-500">Create a batch to begin reporting.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    {{-- Top trainees by module completion --}}
    <section class="border border-stone-200 bg-white p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <div>
                <h2 class="text-lg font-bold text-stone-950">Learners leading module completion</h2>
                <p class="text-sm text-stone-600">Top approved trainees by modules marked competent.</p>
            </div>
            <p class="text-xs text-stone-500">Top {{ $topTrainees->count() }}</p>
        </div>

        <div class="dashboard-table-wrap mt-4 overflow-x-auto">
            <table class="dashboard-table w-full min-w-[52rem]">
                <thead>
                    <tr>
                        <th>Trainee</th>
                        <th>Batch</th>
                        <th>Schedule</th>
                        <th class="text-right">Completed modules</th>
                        <th class="text-right">In-progress</th>
                        <th>Learning state</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($topTrainees as $trainee)
                    <tr>
                        <td class="font-bold text-slate-950">{{ $trainee->last_name }}, {{ $trainee->first_name }}</td>
                        <td>{{ $trainee->batch ? $trainee->batch->name.' '.$trainee->batch->year : '—' }}</td>
                        <td>{{ $trainee->schedule_preference ?: '—' }}</td>
                        <td class="text-right font-bold text-emerald-700">{{ number_format($trainee->completed_modules) }}</td>
                        <td class="text-right">{{ number_format($trainee->in_progress_modules) }}</td>
                        <td>
                            <span class="dashboard-pill {{ $trainee->learning_status === EnrollmentApplication::LEARNING_GRADUATED ? 'bg-indigo-50 text-indigo-700 ring-indigo-100' : 'bg-sky-50 text-sky-700 ring-sky-100' }}">
                                {{ $trainee->learningStatusLabel() }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-sm text-slate-500">No approved learners yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>
</section>
@endsection
