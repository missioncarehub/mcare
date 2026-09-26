@extends('trainee.layouts.app', ['title' => 'Achievements | MCARE Graduate'])

@section('content')
    <section class="space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <p class="dashboard-section-kicker">Career record</p>
            <h1 class="text-2xl font-black text-slate-950 sm:text-3xl">Achievements history</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Careers you take from the Career Hub are saved here, including the role, schedule, and the date you took it.</p>
        </header>

        <div class="grid gap-4 lg:grid-cols-2">
            @forelse ($achievements as $achievement)
                @php $job = $achievement->opportunity; @endphp
                <article class="dashboard-panel">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-purple-700">Career taken</p>
                            <h2 class="mt-1 text-xl font-black text-slate-950">{{ $job?->listingTitle() ?? 'Career opportunity' }}</h2>
                        </div>
                        <span class="dashboard-pill bg-emerald-50 text-emerald-800 ring-emerald-200">{{ $achievement->statusLabel() }}</span>
                    </div>
                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Salary</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $job?->estimated_salary ?: 'See Career Hub' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Start</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $job?->estimated_start_date?->format('M d, Y') ?: 'To be announced' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Placement</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $job?->listingEmployer() ?? 'MCARE-Coordinated Placement' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Taken</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $achievement->created_at?->format('M d, Y') }}</dd>
                        </div>
                    </dl>
                    @if ($job?->postingSummary())
                        <p class="mt-4 text-sm leading-6 text-slate-600">{{ $job->postingSummary() }}</p>
                    @endif
                </article>
            @empty
                <div class="dashboard-panel py-16 text-center lg:col-span-2">
                    <x-dashboard-icon name="award" class="mx-auto h-9 w-9 text-slate-300" />
                    <h2 class="mt-4 text-lg font-bold text-slate-900">No achievements yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Take a career from the Career Hub and it will be saved in this history.</p>
                    <a href="{{ route('trainee.career-hub') }}" class="primary-action mt-5 inline-flex">Open Career Hub</a>
                </div>
            @endforelse
        </div>

        @if ($achievements->hasPages())
            <div>{{ $achievements->links() }}</div>
        @endif
    </section>
@endsection
