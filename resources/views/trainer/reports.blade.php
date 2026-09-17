@extends('trainer.layouts.app', ['title' => 'Reports | MCARE Trainer'])

@section('content')
{{-- Path: resources/views/trainer/reports.blade.php | Label: Detailed trainer reports --}}
@php
    use App\Models\EnrollmentApplication;
    use App\Models\ModuleProgress;
    use App\Models\TraineeAttendance;

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

    $kpiCards = [
        ['key' => 'trainees', 'label' => 'Approved trainees', 'icon' => 'users', 'tone' => 'bg-purple-50 text-purple-700 ring-purple-100'],
        ['key' => 'active_learners', 'label' => 'Actively learning', 'icon' => 'user-check', 'tone' => 'bg-sky-50 text-sky-700 ring-sky-100'],
        ['key' => 'graduated', 'label' => 'Graduated', 'icon' => 'award', 'tone' => 'bg-indigo-50 text-indigo-700 ring-indigo-100'],
        ['key' => 'modules', 'label' => 'Published modules', 'icon' => 'book-open', 'tone' => 'bg-violet-50 text-violet-700 ring-violet-100'],
        ['key' => 'module_completions', 'label' => 'Module completions', 'icon' => 'circle-check', 'tone' => 'bg-emerald-50 text-emerald-700 ring-emerald-100'],
        ['key' => 'awaiting_evaluation', 'label' => 'Awaiting evaluation', 'icon' => 'clipboard-list', 'tone' => 'bg-amber-50 text-amber-700 ring-amber-100'],
        ['key' => 'paid', 'label' => 'Payments verified', 'icon' => 'credit-card', 'tone' => 'bg-teal-50 text-teal-700 ring-teal-100'],
        ['key' => 'payment_pending', 'label' => 'Payment pending', 'icon' => 'clock', 'tone' => 'bg-rose-50 text-rose-700 ring-rose-100'],
    ];
@endphp

<div class="w-full space-y-7">
    <header class="flex flex-wrap items-end justify-between gap-3 border-b border-stone-200 pb-6">
        <div>
            <p class="text-sm font-bold uppercase tracking-[0.16em] text-violet-700">Reports</p>
            <h1 class="mt-2 text-3xl font-bold text-stone-950">Training delivery snapshot</h1>
            <p class="mt-2 text-stone-600">
                @if($activeBatch)
                    Live figures for <span class="font-bold text-stone-900">{{ $activeBatch->name }} {{ $activeBatch->year }}</span>.
                @else
                    You are not yet assigned to a batch — data appears once your batch is set.
                @endif
            </p>
        </div>
        <p class="text-xs text-stone-500">Generated {{ $generatedAt->format('M d, Y g:i A') }}</p>
    </header>

    {{-- KPI cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach($kpiCards as $card)
            <div class="border border-stone-200 bg-white p-5">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-sm text-stone-500">{{ $card['label'] }}</p>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg ring-1 {{ $card['tone'] }}">
                        <x-dashboard-icon :name="$card['icon']" />
                    </span>
                </div>
                <p class="mt-2 text-3xl font-bold text-stone-950">{{ number_format($stats[$card['key']] ?? 0) }}</p>
                @if($card['key'] === 'modules')
                    <p class="mt-1 text-xs text-stone-500">{{ number_format($stats['total_modules']) }} in catalog for this batch</p>
                @elseif($card['key'] === 'trainees')
                    <p class="mt-1 text-xs text-stone-500">{{ number_format($stats['am']) }} AM · {{ number_format($stats['pm']) }} PM</p>
                @elseif($card['key'] === 'paid')
                    <p class="mt-1 text-xs text-stone-500">{{ number_format($stats['partial_paid']) }} partially paid</p>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Module progression + attendance --}}
    <div class="grid gap-6 lg:grid-cols-2">
        <section class="border border-stone-200 bg-white p-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-bold text-stone-950">Module progression</h2>
                <p class="text-xs text-stone-500">{{ number_format($moduleProgressTotal) }} learner-module records</p>
            </div>
            <p class="mt-1 text-sm text-stone-600">Where your trainees currently stand across all their assigned modules.</p>

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
        </section>

        <section class="border border-stone-200 bg-white p-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-bold text-stone-950">Attendance snapshot</h2>
                <p class="text-xs text-stone-500">Last 30 days · since {{ $attendance['window_start']->format('M d, Y') }}</p>
            </div>
            <p class="mt-1 text-sm text-stone-600">Your logged attendance for this batch.</p>

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
                    <p class="text-xs uppercase tracking-wide text-stone-500">Attendance rate</p>
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
                {{ number_format($attendance['recent_competency_evaluations']) }} competency evaluations updated in the same window.
            </p>
        </section>
    </div>

    {{-- Per-trainee table --}}
    <section class="border border-stone-200 bg-white p-6">
        <div class="flex flex-wrap items-baseline justify-between gap-2">
            <div>
                <h2 class="text-lg font-bold text-stone-950">Trainee progress</h2>
                <p class="text-sm text-stone-600">Ranked by modules marked competent.</p>
            </div>
            <p class="text-xs text-stone-500">{{ $traineeRows->count() }} {{ Str::plural('trainee', $traineeRows->count()) }}</p>
        </div>

        <div class="dashboard-table-wrap mt-4 overflow-x-auto">
            <table class="dashboard-table w-full min-w-[52rem]">
                <thead>
                    <tr>
                        <th>Trainee</th>
                        <th>Schedule</th>
                        <th class="text-right">Completed</th>
                        <th class="text-right">In-progress</th>
                        <th>Progress</th>
                        <th>Payment</th>
                        <th>State</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($traineeRows as $row)
                    <tr>
                        <td class="font-bold text-slate-950">{{ $row['name'] }}</td>
                        <td>{{ $row['schedule'] ?: '—' }}</td>
                        <td class="text-right font-bold text-emerald-700">{{ number_format($row['completed']) }}</td>
                        <td class="text-right">{{ number_format($row['in_progress']) }}</td>
                        <td class="min-w-[140px]">
                            <div class="flex items-center gap-2">
                                <div class="h-2 w-24 overflow-hidden rounded-full bg-stone-100">
                                    <div class="h-full rounded-full bg-violet-500" style="width: {{ min(100, $row['progress_percent']) }}%"></div>
                                </div>
                                <span class="text-xs font-bold text-stone-700">{{ $row['progress_percent'] }}%</span>
                            </div>
                        </td>
                        <td class="text-xs">{{ $row['payment_status'] }}</td>
                        <td>
                            <span class="dashboard-pill {{ $row['learning_status'] === EnrollmentApplication::LEARNING_GRADUATED ? 'bg-indigo-50 text-indigo-700 ring-indigo-100' : 'bg-sky-50 text-sky-700 ring-sky-100' }}">
                                {{ $row['learning_status_label'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-sm text-slate-500">No approved trainees yet for this batch.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="border border-stone-200 bg-white p-6">
        <h2 class="text-lg font-bold text-stone-950">Reporting scope</h2>
        <p class="mt-2 text-stone-600">These figures update from the same enrollment, payment, module, progress, attendance, and competency records used by the admin and trainee portals — so any change on those pages appears here immediately.</p>
    </section>
</div>
@endsection
