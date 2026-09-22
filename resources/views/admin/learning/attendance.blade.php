@extends('admin.layouts.app', ['title' => 'Batch Attendance Management | MCARE Admin'])

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="text-sm text-slate-600">Review, record, and export batch attendance records for institutional and TESDA compliance.</p>
        @if($selectedBatch)
            <div class="flex items-center gap-2">
                <a href="{{ route('admin.learning.attendance.export', $selectedBatch) }}" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800">
                    <i class="fa-solid fa-file-excel"></i>
                    <span>Export TESDA Workbook (.xlsx)</span>
                </a>
                <a href="{{ route('admin.learning.attendance.export', ['batch' => $selectedBatch, 'format' => 'csv']) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-xs font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50" title="Download raw CSV">
                    <i class="fa-solid fa-file-csv text-slate-500"></i>
                    <span>CSV</span>
                </a>
            </div>
        @endif
    </div>

    <!-- Controls Bar -->
    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.learning.attendance') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-4 items-end">
            <div>
                <label for="admin_batch_select" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Select Batch</label>
                <select id="admin_batch_select" name="batch_id" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-purple-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-600/20">
                    @foreach($batches as $b)
                        <option value="{{ $b->id }}" @selected($selectedBatch && $selectedBatch->id === $b->id)>
                            {{ $b->name }} ({{ $b->trainer?->name ?? 'No Trainer' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="admin_date_select" class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Attendance Date</label>
                <input type="date" id="admin_date_select" name="date" value="{{ $selectedDate->toDateString() }}" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-300 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium text-slate-800 focus:border-purple-600 focus:bg-white focus:outline-none focus:ring-2 focus:ring-purple-600/20">
            </div>

            <input type="hidden" name="tab" value="{{ $activeTab }}">

            <div class="sm:col-span-1 lg:col-span-2 flex items-center gap-2">
                <a href="{{ route('admin.learning.attendance', ['batch_id' => $selectedBatch?->id, 'date' => $selectedDate->toDateString(), 'tab' => 'sheet']) }}" class="flex-1 text-center rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ $activeTab === 'sheet' ? 'bg-purple-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Daily Sheet
                </a>
                <a href="{{ route('admin.learning.attendance', ['batch_id' => $selectedBatch?->id, 'date' => $selectedDate->toDateString(), 'tab' => 'summary']) }}" class="flex-1 text-center rounded-xl px-4 py-2.5 text-sm font-semibold transition {{ $activeTab === 'summary' ? 'bg-purple-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                    Summary & Compliance
                </a>
            </div>
        </form>
    </div>

    @if(! $selectedBatch)
        <div class="rounded-2xl border border-slate-200/80 bg-white p-12 text-center text-slate-500 shadow-sm">
            <p class="text-base font-semibold">No training batches found.</p>
        </div>
    @elseif($activeTab === 'sheet')
        <!-- Daily Attendance Sheet -->
        <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between bg-slate-50/50">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">
                        {{ $selectedBatch->name }} &mdash; {{ $selectedDate->format('F d, Y (l)') }}
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">Trainer: {{ $selectedBatch->trainer?->name ?? 'Unassigned' }} | Trainees enrolled on this date: {{ $trainees->count() }}</p>
                </div>
                @unless($isFutureDate)
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="adminMarkAllPresent()" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50">
                            <i class="fa-solid fa-check-double text-emerald-600"></i>
                            <span>Mark All as Present</span>
                        </button>
                        <button type="submit" form="admin_attendance_form" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-purple-800">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Attendance</span>
                        </button>
                    </div>
                @endunless
            </div>

            @if($isFutureDate)
                <div class="border-b border-slate-100 bg-slate-50 px-5 py-3 text-sm text-slate-600">
                    This date is in the future. Attendance status will be available on the session day.
                </div>
            @endif

            @if($trainees->isEmpty())
                <div class="p-12 text-center text-slate-500">
                    <p class="font-medium">No enrolled trainees for this date.</p>
                </div>
            @elseif($isFutureDate)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600">
                        <thead class="bg-slate-100/75 text-xs uppercase tracking-wider text-slate-600 border-b border-slate-200">
                            <tr>
                                <th scope="col" class="py-3.5 px-4 font-bold">#</th>
                                <th scope="col" class="py-3.5 px-4 font-bold">Trainee Name</th>
                                <th scope="col" class="py-3.5 px-4 font-bold">Schedule</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($trainees as $index => $trainee)
                                <tr class="hover:bg-slate-50/60 transition">
                                    <td class="py-3.5 px-4 font-medium text-slate-400">{{ $index + 1 }}</td>
                                    <td class="py-3.5 px-4">
                                        <div class="font-bold text-slate-900">{{ $trainee->full_name }}</div>
                                        <div class="text-xs text-slate-500">{{ $trainee->email ?? $trainee->user?->email }}</div>
                                    </td>
                                    <td class="py-3.5 px-4">
                                        <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 uppercase">
                                            {{ $trainee->schedule_preference ?: 'AM' }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <form id="admin_attendance_form" method="POST" action="{{ route('admin.learning.attendance.store') }}">
                    @csrf
                    <input type="hidden" name="batch_id" value="{{ $selectedBatch->id }}">
                    <input type="hidden" name="date" value="{{ $selectedDate->toDateString() }}">

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-100/75 text-xs uppercase tracking-wider text-slate-600 border-b border-slate-200">
                                <tr>
                                    <th scope="col" class="py-3.5 px-4 font-bold">#</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold">Trainee Name</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold">Schedule</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold text-center">Attendance Status</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold">Recorded Check-In</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold">Notes / Remarks</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($trainees as $index => $trainee)
                                    @php
                                        $att = $existingAttendances->get($trainee->id);
                                        $currentStatus = $att?->status ?? 'present';
                                    @endphp
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3.5 px-4 font-medium text-slate-400">{{ $index + 1 }}</td>
                                        <td class="py-3.5 px-4">
                                            <div class="font-bold text-slate-900">{{ $trainee->full_name }}</div>
                                            <div class="text-xs text-slate-500">{{ $trainee->email ?? $trainee->user?->email }}</div>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 uppercase">
                                                {{ $trainee->schedule_preference ?: 'AM' }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <div class="flex items-center justify-center gap-2 sm:gap-4">
                                                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-emerald-700">
                                                    <input type="radio" name="records[{{ $trainee->id }}][status]" value="present" class="admin-status-radio text-emerald-600 focus:ring-emerald-500" @checked($currentStatus === 'present')>
                                                    <span>Present</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-amber-700">
                                                    <input type="radio" name="records[{{ $trainee->id }}][status]" value="late" class="admin-status-radio text-amber-600 focus:ring-amber-500" @checked($currentStatus === 'late')>
                                                    <span>Late</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-rose-700">
                                                    <input type="radio" name="records[{{ $trainee->id }}][status]" value="absent" class="admin-status-radio text-rose-600 focus:ring-rose-500" @checked($currentStatus === 'absent')>
                                                    <span>Absent</span>
                                                </label>
                                                <label class="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-blue-700">
                                                    <input type="radio" name="records[{{ $trainee->id }}][status]" value="excused" class="admin-status-radio text-blue-600 focus:ring-blue-500" @checked($currentStatus === 'excused')>
                                                    <span>Excused</span>
                                                </label>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 text-xs text-slate-500">
                                            @if($att?->timed_in_at)
                                                <span class="font-semibold text-slate-700">{{ $att->timed_in_at->format('g:i A') }}</span>
                                                <span class="text-[10px] text-slate-400 block">({{ $att->check_in_type === 'activity_time_in' ? 'Activity Time-In' : 'Class Sheet' }})</span>
                                            @else
                                                <span class="text-slate-400">&mdash;</span>
                                            @endif
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <input type="text" name="records[{{ $trainee->id }}][notes]" value="{{ $att?->notes }}" placeholder="Optional remarks..." class="w-full rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-800 placeholder-slate-400 focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/50 p-4">
                        <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm hover:bg-purple-800">
                            <i class="fa-solid fa-floppy-disk"></i>
                            <span>Save Attendance</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    @else
        <!-- Summary Tab -->
        @if($summary)
            <div class="space-y-6">
                <!-- Summary Stats Cards -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Recorded Sessions</p>
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700 ring-1 ring-sky-100"><x-dashboard-icon name="calendar-days" /></span>
                        </div>
                        <p class="mt-2 text-3xl font-extrabold text-slate-900">{{ $summary['total_days'] }}</p>
                        <p class="mt-1 text-xs text-slate-500">Training days recorded to date</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Batch Average Attendance</p>
                            @php($avgRate = $summary['average_rate'])
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg {{ $avgRate === null ? 'bg-slate-50 text-slate-600 ring-slate-100' : ($avgRate >= 80 ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100') }} ring-1"><x-dashboard-icon name="chart-column" /></span>
                        </div>
                        <p class="mt-2 text-3xl font-extrabold {{ $avgRate === null ? 'text-slate-500' : ($avgRate >= 80 ? 'text-emerald-700' : 'text-amber-700') }}">
                            {{ $avgRate === null ? '—' : $avgRate.'%' }}
                        </p>
                        <p class="mt-1 text-xs text-slate-500">TESDA standard benchmark: 80%</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Trainees in Roster</p>
                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100"><x-dashboard-icon name="users" /></span>
                        </div>
                        <p class="mt-2 text-3xl font-extrabold text-purple-700">{{ count($summary['trainees']) }}</p>
                        <p class="mt-1 text-xs text-slate-500">Approved active enrolled learners</p>
                    </div>
                </div>

                <!-- Summary Roster Table -->
                <div class="rounded-2xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                    <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between bg-slate-50/50">
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">TESDA Attendance Compliance Roster</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Learners must maintain at least 80% attendance to qualify for certification.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('admin.learning.attendance.export', $selectedBatch) }}" class="inline-flex items-center gap-2 rounded-xl bg-purple-700 px-4 py-2 text-xs font-bold text-white shadow-sm hover:bg-purple-800">
                                <i class="fa-solid fa-file-excel"></i>
                                <span>Download Excel Workbook (.xlsx)</span>
                            </a>
                            <a href="{{ route('admin.learning.attendance.export', ['batch' => $selectedBatch, 'format' => 'csv']) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-sm hover:bg-slate-50">
                                <i class="fa-solid fa-file-csv text-slate-500"></i>
                                <span>CSV</span>
                            </a>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-100/75 text-xs uppercase tracking-wider text-slate-600 border-b border-slate-200">
                                <tr>
                                    <th scope="col" class="py-3.5 px-4 font-bold">Trainee Name</th>
                                    <th scope="col" class="py-3.5 px-3 font-bold text-center">Schedule</th>
                                    <th scope="col" class="py-3.5 px-3 font-bold text-center">Present (P)</th>
                                    <th scope="col" class="py-3.5 px-3 font-bold text-center">Late (L)</th>
                                    <th scope="col" class="py-3.5 px-3 font-bold text-center">Absent (A)</th>
                                    <th scope="col" class="py-3.5 px-3 font-bold text-center">Excused (E)</th>
                                    <th scope="col" class="py-3.5 px-3 font-bold text-center">Sessions</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold text-center">Attendance %</th>
                                    <th scope="col" class="py-3.5 px-4 font-bold text-center">TESDA Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($summary['trainees'] as $t)
                                    <tr class="hover:bg-slate-50/60 transition">
                                        <td class="py-3.5 px-4">
                                            <div class="font-bold text-slate-900">{{ $t['name'] }}</div>
                                            <div class="text-xs text-slate-500">{{ $t['email'] }}</div>
                                        </td>
                                        <td class="py-3.5 px-3 text-center">
                                            <span class="inline-flex rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                                {{ $t['schedule'] }}
                                            </span>
                                        </td>
                                        <td class="py-3.5 px-3 text-center font-semibold text-emerald-700">{{ $t['present'] }}</td>
                                        <td class="py-3.5 px-3 text-center font-semibold text-amber-700">{{ $t['late'] }}</td>
                                        <td class="py-3.5 px-3 text-center font-semibold text-rose-700">{{ $t['absent'] }}</td>
                                        <td class="py-3.5 px-3 text-center font-semibold text-blue-700">{{ $t['excused'] }}</td>
                                        <td class="py-3.5 px-3 text-center font-medium text-slate-500">{{ $t['total_sessions'] }}</td>
                                        <td class="py-3.5 px-4 text-center font-bold {{ $t['attendance_rate'] === null ? 'text-slate-500' : ($t['attendance_rate'] >= 80 ? 'text-emerald-700' : 'text-amber-700') }}">
                                            {{ $t['attendance_rate'] === null ? '—' : $t['attendance_rate'].'%' }}
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            @if($t['is_compliant'] === null)
                                                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">
                                                    Newly enrolled
                                                </span>
                                            @elseif($t['is_compliant'])
                                                <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-800">
                                                    Compliant
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">
                                                    At Risk (&lt;80%)
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="p-8 text-center text-slate-500">No trainees enrolled.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

<script>
    function adminMarkAllPresent() {
        document.querySelectorAll('input.admin-status-radio[value="present"]').forEach(function(radio) {
            radio.checked = true;
        });
    }
</script>
@endsection
