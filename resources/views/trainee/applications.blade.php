@extends('trainee.layouts.app', ['title' => 'My Applications | MCARE Graduate'])

@section('content')
    <section class="space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <p class="dashboard-section-kicker">Career applications</p>
            <h1 class="text-2xl font-black text-slate-950 sm:text-3xl">My Applications</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Track the status of your career applications. Once MCARE approves your application, it will also appear in your Career History.</p>
        </header>

        @php
            $pending = $applications->where('status', \App\Models\CareerInquiry::STATUS_PENDING)->count();
            $approved = $applications->where('status', \App\Models\CareerInquiry::STATUS_APPROVED)->count();
            $rejected = $applications->where('status', \App\Models\CareerInquiry::STATUS_REJECTED)->count();
        @endphp

        <div class="grid gap-3 sm:grid-cols-3">
            <div class="dashboard-panel flex items-center gap-4 p-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-amber-700">
                    <x-dashboard-icon name="clock" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-2xl font-black text-slate-950">{{ $pending }}</p>
                    <p class="text-xs font-semibold text-slate-500">Pending</p>
                </div>
            </div>
            <div class="dashboard-panel flex items-center gap-4 p-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-700">
                    <x-dashboard-icon name="circle-check" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-2xl font-black text-slate-950">{{ $approved }}</p>
                    <p class="text-xs font-semibold text-slate-500">Approved</p>
                </div>
            </div>
            <div class="dashboard-panel flex items-center gap-4 p-4">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-red-50 text-red-700">
                    <x-dashboard-icon name="xmark" class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-2xl font-black text-slate-950">{{ $rejected }}</p>
                    <p class="text-xs font-semibold text-slate-500">Not approved</p>
                </div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            @forelse ($applications as $application)
                @php $job = $application->opportunity; @endphp
                <article class="dashboard-panel">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-purple-700">Career application</p>
                            <h2 class="mt-1 text-xl font-black text-slate-950">{{ $job?->listingTitle() ?? 'Career opportunity' }}</h2>
                            <p class="mt-1 text-sm font-bold text-purple-700">{{ $job?->listingEmployer() ?? 'MCARE-Coordinated Placement' }}</p>
                        </div>
                        @if ($application->isApproved())
                            <span class="dashboard-pill bg-emerald-50 text-emerald-800 ring-emerald-200">Approved</span>
                        @elseif ($application->isRejected())
                            <span class="dashboard-pill bg-red-50 text-red-800 ring-red-200">Not approved</span>
                        @elseif ($application->isPending())
                            <span class="dashboard-pill bg-amber-50 text-amber-800 ring-amber-200">Pending</span>
                        @else
                            <span class="dashboard-pill bg-slate-50 text-slate-800 ring-slate-200">{{ $application->statusLabel() }}</span>
                        @endif
                    </div>

                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        @if ($job?->estimated_salary)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Salary</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $job->estimated_salary }}</dd>
                        </div>
                        @endif
                        @if ($job?->estimated_start_date)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Start</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $job->estimated_start_date->format('M d, Y') }}</dd>
                        </div>
                        @endif
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Applied</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $application->created_at?->format('M d, Y') }}</dd>
                        </div>
                        @if ($application->reviewed_at)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Reviewed</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $application->reviewed_at->format('M d, Y') }}</dd>
                        </div>
                        @endif
                    </dl>

                    @if ($application->admin_notes)
                        <div class="mt-4 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Admin remarks</p>
                            <p class="mt-1 text-sm leading-6 text-slate-700">{{ $application->admin_notes }}</p>
                        </div>
                    @endif

                    @if (is_array($application->credential_paths) && count($application->credential_paths) > 0)
                        <div class="mt-4 border-t border-slate-100 pt-4">
                            <p class="text-xs font-bold uppercase tracking-wide text-slate-500">Attached credentials</p>
                            <ul class="mt-2 space-y-1.5">
                                @foreach ($application->credential_paths as $idx => $credential)
                                    <li class="flex items-center gap-2 text-sm">
                                        <x-dashboard-icon name="file-text" class="h-4 w-4 shrink-0 text-purple-600" />
                                        <a href="{{ route('trainee.applications.credential', [$application, $idx]) }}" target="_blank" class="truncate font-semibold text-purple-700 underline decoration-purple-200 hover:text-purple-900">{{ data_get($credential, 'name', 'Credential '.($idx + 1)) }}</a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($job?->postingSummary())
                        <p class="mt-4 text-sm leading-6 text-slate-600">{{ $job->postingSummary() }}</p>
                    @endif
                </article>
            @empty
                <div class="dashboard-panel py-16 text-center lg:col-span-2">
                    <x-dashboard-icon name="clipboard-list" class="mx-auto h-9 w-9 text-slate-300" />
                    <h2 class="mt-4 text-lg font-bold text-slate-900">No applications yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Apply for a career from the Career Hub and your application will appear here so you can track its status.</p>
                    <a href="{{ route('trainee.career-hub') }}" class="primary-action mt-5 inline-flex">Open Career Hub</a>
                </div>
            @endforelse
        </div>

        @if ($applications->hasPages())
            <div>{{ $applications->links() }}</div>
        @endif
    </section>
@endsection
