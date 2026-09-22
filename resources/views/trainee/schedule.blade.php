@extends('trainee.layouts.app', ['title' => 'Schedule | MCARE Trainee'])

@section('content')
@php
    $scheduleLabel = $batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule to be confirmed';
    $roomLabel = $batch?->roomFor($application->schedule_preference) ?: 'Room TBA';
    $deadline = $application->effectivePaymentDeadline() ?: $batch?->enrollment_ends_at;
@endphp

<section class="space-y-6">
    @if ($isGraduate ?? false)
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
            <div><p class="dashboard-section-kicker">Graduate calendar</p><h1 class="dashboard-section-title mt-2 text-2xl sm:text-3xl">Career opportunities calendar</h1><p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">This calendar shows estimated start dates for privacy-reviewed caregiving duties.</p></div>
            <a href="{{ route('trainee.career-hub') }}" class="secondary-action inline-flex w-full items-center justify-center gap-2 sm:w-auto"><x-dashboard-icon name="briefcase" class="h-4 w-4" />Career Hub</a>
        </header>
        <x-training-calendar
            :month="$calendarMonth"
            :sessions="$calendarSessions"
            :selected-date="$calendarSelectedDate"
            :month-route="route('trainee.schedule')"
            :show-batch="false"
            eyebrow="Read-only career calendar"
            :heading="$calendarMonth->format('F Y').' opportunities'"
            description="Career dates are estimates. Open the Career Hub for the privacy-reviewed duty details."
            empty-message="No career opportunity starts on this date."
        />
    @else
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <p class="dashboard-section-kicker">My schedule</p>
            <h1 class="dashboard-section-title mt-2 text-2xl sm:text-3xl">Class calendar</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Your calendar only includes the {{ $application->schedule_preference }} sessions assigned to your approved batch.</p>
        </div>
        <div class="flex flex-wrap gap-2 text-xs font-bold">
            <span class="rounded-lg bg-purple-50 px-3 py-2 text-purple-700 ring-1 ring-purple-100">{{ $application->schedule_preference }} class</span>
            <span class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">{{ $batch ? $batch->name.' '.$batch->year : 'Batch pending' }}</span>
        </div>
    </header>

    @if ($batch)
        <x-training-calendar
            :month="$calendarMonth"
            :sessions="$calendarSessions"
            :selected-date="$calendarSelectedDate"
            :month-route="route('trainee.schedule')"
            :show-batch="false"
            eyebrow="Read-only class calendar"
            :heading="$calendarMonth->format('F Y').' schedule'"
            description="Choose a date to review the complete class time and room. Schedule changes made by the administrator appear here on your next visit."
            empty-message="You have no class scheduled on this date."
        />
    @else
        <section class="dashboard-panel text-center">
            <x-dashboard-icon name="calendar-days" class="mx-auto h-9 w-9 text-slate-400" />
            <h2 class="mt-4 text-xl font-bold text-slate-900">Batch schedule pending</h2>
            <p class="mt-2 text-sm text-slate-600">The calendar will appear after the administrator assigns your approved enrollment to a batch.</p>
        </section>
    @endif

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="dashboard-panel">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <div class="rounded-xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Regular schedule</p>
                    <p class="mt-2 text-lg font-black text-slate-900">{{ $scheduleLabel }}</p>
                </div>
                <div class="rounded-xl bg-slate-50 p-5">
                    <p class="text-xs font-black uppercase tracking-wide text-slate-500">Classroom</p>
                    <p class="mt-2 text-lg font-black text-slate-900">{{ $roomLabel }}</p>
                </div>
                <div class="rounded-xl bg-purple-50 p-5 ring-1 ring-purple-100">
                    <p class="text-xs font-black uppercase tracking-wide text-purple-700">Enrollment/payment deadline</p>
                    <p class="mt-2 text-lg font-black text-slate-900">{{ $deadline?->format('M d, Y g:i A') ?? 'Deadline TBA' }}</p>
                </div>
            </div>
        </section>

        <aside class="dashboard-panel">
            <p class="text-xs font-black uppercase tracking-wide text-amber-600">Announcements</p>
            <h2 class="mt-2 font-display text-xl font-black text-slate-900">Trainer notices</h2>
            <div class="mt-5 space-y-3">
                @forelse ($announcements as $announcement)
                    <article class="rounded-xl border border-slate-100 bg-slate-50 p-4">
                        <p class="font-bold text-slate-900">{{ $announcement->title }}</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $announcement->message }}</p>
                        <p class="mt-2 text-xs font-bold text-amber-700">{{ $announcement->posted_at?->format('M d, Y g:i A') ?? 'Recently posted' }}</p>
                    </article>
                @empty
                    <p class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-sm text-slate-600">No class announcements yet.</p>
                @endforelse
            </div>
        </aside>
    </div>
    @endif
</section>
@endsection
