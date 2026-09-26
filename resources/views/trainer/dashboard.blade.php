@extends('trainer.layouts.app', ['title' => 'Teaching Day | MCARE Trainer'])

@section('content')
@php
    $trainerDisplayName = trim(auth()->user()?->name ?? 'Trainer');
    $followUpCount = $learnerFollowUps->where('needs_action', true)->count();
@endphp

<div class="space-y-7">
    <header class="flex flex-col gap-5 border-b border-stone-200 pb-7 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="dashboard-section-kicker">Teaching day</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight text-stone-950 sm:text-4xl">Welcome back, {{ $trainerDisplayName }}</h1>
            <p class="mt-2 text-base text-stone-600">Here is your complete teaching day, organized around the live administrator schedule.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3 text-sm">
            <span class="inline-flex items-center gap-2 font-medium text-stone-700">
                <x-dashboard-icon name="calendar" class="text-violet-700" />
                {{ now()->format('l, F j, Y') }}
            </span>
            <a href="{{ route('trainer.sessions') }}" class="secondary-action inline-flex items-center justify-center gap-2 text-sm font-bold">
                Full calendar
            </a>
        </div>
    </header>

    <section class="dashboard-panel" aria-labelledby="delivery-snapshot-title">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="dashboard-section-kicker">Learning delivery</p>
                <h2 id="delivery-snapshot-title" class="mt-2 text-xl font-bold text-stone-950">Delivery snapshot</h2>
                <p class="mt-1 text-sm text-stone-600">Manage learning files and audiences from Resources.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('trainer.resources') }}" class="primary-action inline-flex items-center justify-center text-sm font-bold">Manage resources</a>
                <a href="{{ route('trainer.trainees') }}" class="secondary-action inline-flex items-center justify-center text-sm font-bold">View trainees</a>
            </div>
        </div>
        <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Published modules</p>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100">
                        <x-dashboard-icon name="book-open" />
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-stone-950">{{ $moduleCount }}</p>
                <a href="{{ route('trainer.resources') }}" class="mt-2 block text-xs font-bold text-purple-700 hover:text-purple-900">View modules →</a>
            </div>
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Quizzes & Assessments</p>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-100">
                        <x-dashboard-icon name="clipboard-list" />
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-amber-700">{{ $quizCount ?? 0 }}</p>
                <a href="{{ route('trainer.resources') }}#assessments-hub" class="mt-2 block text-xs font-bold text-amber-800 hover:text-amber-950">View quizzes →</a>
            </div>
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Assigned learners</p>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700 ring-1 ring-sky-100">
                        <x-dashboard-icon name="users" />
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-stone-950">{{ $stats['total_trainees'] ?? 0 }}</p>
                <a href="{{ route('trainer.trainees') }}" class="mt-2 block text-xs font-bold text-stone-700 hover:text-stone-900">View roster →</a>
            </div>
            <div class="rounded-lg border border-stone-200 bg-stone-50 p-4">
                <div class="flex items-start justify-between gap-3">
                    <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Sessions today</p>
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100">
                        <x-dashboard-icon name="calendar-days" />
                    </span>
                </div>
                <p class="mt-2 text-2xl font-bold text-stone-950">{{ $stats['sessions_today'] ?? 0 }}</p>
                <a href="{{ route('trainer.sessions') }}" class="mt-2 block text-xs font-bold text-stone-700 hover:text-stone-900">Open calendar →</a>
            </div>
        </div>
    </section>

    <section class="grid items-start gap-7 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <div class="min-w-0">
            <div class="mb-5 flex items-end justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-stone-950">Today's schedule</h2>
                    <p class="mt-1 text-sm text-stone-600">Every session stays visible. Only the status rail animates when a class is currently in progress.</p>
                </div>
                <span class="text-sm font-medium text-stone-500">{{ $teachingTimeline->count() }} {{ \Illuminate\Support\Str::plural('session', $teachingTimeline->count()) }}</span>
            </div>

            <ol class="trainer-day-agenda">
                @forelse ($teachingTimeline as $item)
                    <li class="trainer-day-session is-{{ $item['state'] }}">
                        <span class="trainer-day-status-dot" aria-hidden="true"></span>
                        <div class="flex min-w-0 flex-1 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-3">
                                    <time class="text-sm font-bold text-stone-950">{{ $item['time'] }}</time>
                                    <span class="trainer-day-state is-{{ $item['state'] }}">{{ $item['label'] }}</span>
                                </div>
                                <h3 class="mt-2 font-bold text-stone-950">{{ $item['title'] }}</h3>
                                <p class="mt-1 text-sm text-stone-600">{{ $item['duration'] }} &middot; {{ $item['training'] }}</p>
                            </div>
                            <div class="min-w-0 sm:max-w-52 sm:text-right">
                                <p class="text-xs font-bold uppercase tracking-wide text-stone-500">Room</p>
                                <p class="mt-1 text-sm font-semibold text-stone-900">{{ $item['room'] }}</p>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="trainer-day-session is-empty">
                        <span class="trainer-day-status-dot" aria-hidden="true"></span>
                        <div>
                            <p class="font-bold text-stone-950">No session scheduled today.</p>
                            <p class="mt-2 text-sm text-stone-600">Open the calendar to review the full month generated from the admin schedule.</p>
                        </div>
                    </li>
                @endforelse
            </ol>
        </div>

        <aside class="dashboard-panel divide-y divide-stone-200 p-0">
            <section class="p-5" id="learner-follow-up">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-stone-950">Learner follow-up</h2>
                        <p class="mt-1 text-sm text-stone-600">Actions that keep delivery moving.</p>
                    </div>
                    <span class="flex h-8 min-w-8 items-center justify-center rounded-lg bg-amber-100 px-2 text-sm font-bold text-amber-900">{{ $followUpCount }}</span>
                </div>

                @if ($learnerFollowUps->isNotEmpty())
                    <ul class="mt-4 divide-y divide-stone-200">
                        @foreach ($learnerFollowUps->take(5) as $learner)
                            <li class="py-4 first:pt-0 last:pb-0">
                                <div class="flex gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-stone-900 text-sm font-bold text-white">{{ $learner['initial'] }}</span>
                                    <div class="min-w-0">
                                        <p class="truncate font-bold text-stone-950">{{ $learner['name'] }}</p>
                                        @if($learner['is_graduate'] ?? false)
                                            <span class="mt-1 inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-200">{{ $learner['graduate_label'] ?? 'Verified graduate' }}</span>
                                        @endif
                                        <p class="mt-1 text-sm text-stone-600">{{ $learner['action'] }}</p>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm leading-6 text-stone-600">No learners need follow-up yet.</p>
                @endif
            </section>

            <section class="p-5" aria-labelledby="system-notifications-title">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-violet-700">From admin</p>
                        <h2 id="system-notifications-title" class="mt-1 text-lg font-bold text-stone-950">System notifications</h2>
                    </div>
                    <x-dashboard-icon name="bell" class="mt-1 text-violet-700" />
                </div>

                @if ($systemNotifications->isNotEmpty())
                    <ul class="mt-4 space-y-3">
                        @foreach ($systemNotifications as $notification)
                            <li class="border-l-2 border-violet-300 pl-3">
                                <p class="text-sm font-semibold leading-5 text-stone-900">{{ $notification['title'] }}</p>
                                <p class="mt-1 text-xs text-stone-500">{{ $notification['actor'] }} &middot; {{ $notification['occurred_at'] }}</p>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="mt-4 text-sm leading-6 text-stone-600">No new schedule, enrollment, or module notices.</p>
                @endif
            </section>
        </aside>
    </section>
</div>
@endsection
