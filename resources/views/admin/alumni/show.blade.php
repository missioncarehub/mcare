@extends('admin.layouts.app', ['title' => $alumni->name.' | Alumni Profile'])

@section('content')
    @php
        $rank = $profile?->rank ?? \App\Models\AlumniProfile::RANK_JUNIOR;
        $contactNumber = $alumni->contact_number ?: $enrollment?->contact_number;
        $address = collect([
            $enrollment?->street,
            $enrollment?->barangay,
            $enrollment?->city,
            $enrollment?->province,
            $enrollment?->region,
            $enrollment?->zip_code,
        ])->filter()->implode(', ');
        $applicationStatusStyles = [
            \App\Models\CareerInquiry::STATUS_PENDING => 'bg-amber-50 text-amber-800 ring-amber-100',
            \App\Models\CareerInquiry::STATUS_APPROVED => 'bg-emerald-50 text-emerald-800 ring-emerald-100',
            \App\Models\CareerInquiry::STATUS_REJECTED => 'bg-red-50 text-red-800 ring-red-100',
        ];
    @endphp

    <section class="space-y-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex min-w-0 items-center gap-4">
                <x-user-avatar :user="$alumni" :application="$enrollment" :use-enrollment-photo="true" class="grid h-16 w-16 shrink-0 place-items-center rounded-full bg-purple-100 text-xl font-black text-purple-800" />
                <div class="min-w-0">
                    <p class="dashboard-section-kicker">Alumni profile</p>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        <h1 class="text-2xl font-bold text-slate-950">{{ $alumni->name }}</h1>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $rank === \App\Models\AlumniProfile::RANK_SENIOR ? 'bg-purple-50 text-purple-800 ring-purple-100' : 'bg-slate-50 text-slate-700 ring-slate-200' }}">
                            {{ \App\Models\AlumniProfile::ranks()[$rank] }}
                        </span>
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $profile?->is_available_for_duty ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ $profile?->is_available_for_duty ? 'Available for Duty' : 'Unavailable' }}
                        </span>
                    </div>
                    <p class="mt-1 truncate text-sm text-slate-500">{{ $alumni->email }}</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.alumni.index') }}" class="secondary-action inline-flex items-center gap-2">
                    <x-dashboard-icon name="arrow-left" class="h-4 w-4" />
                    Back to alumni list
                </a>
                @if ($enrollment && ! $enrollment->is_historical_record)
                    <a href="{{ route('admin.learning.trainees.show', $enrollment) }}" class="secondary-action inline-flex items-center gap-2">
                        <x-dashboard-icon name="book-open" class="h-4 w-4" />
                        Trainee record
                    </a>
                @endif
                @if ($historicalClaim)
                    <a href="{{ route('admin.historical-alumni.show', $historicalClaim) }}" class="secondary-action inline-flex items-center gap-2">
                        <x-dashboard-icon name="file-text" class="h-4 w-4" />
                        Alumni claim
                    </a>
                @endif
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <article class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Career applications</p>
                    <p class="dashboard-stat-value">{{ $applications->count() }}</p>
                    <p class="dashboard-stat-help">Submitted through Career Hub</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700 ring-1 ring-sky-100"><x-dashboard-icon name="briefcase" class="h-5 w-5" /></span>
            </article>
            <article class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Awarded careers</p>
                    <p class="dashboard-stat-value">{{ $awardedCareers }}</p>
                    <p class="dashboard-stat-help">Placement certificate approved</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100"><x-dashboard-icon name="award" class="h-5 w-5" /></span>
            </article>
            <article class="dashboard-stat">
                <div>
                    <p class="dashboard-stat-label">Availability updated</p>
                    <p class="dashboard-stat-value text-lg">{{ $profile?->availability_updated_at?->format('M d, Y') ?? '—' }}</p>
                    <p class="dashboard-stat-help">{{ $profile?->availability_updated_at?->diffForHumans() ?? 'Not updated yet' }}</p>
                </div>
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-violet-50 text-violet-700 ring-1 ring-violet-100"><x-dashboard-icon name="clock" class="h-5 w-5" /></span>
            </article>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Contact & account</p>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        'Email' => $alumni->email,
                        'Email status' => $alumni->hasVerifiedEmail() ? 'Verified' : 'Pending verification',
                        'Contact number' => $contactNumber,
                        'Address' => $address ?: '—',
                    ] as $label => $value)
                        <div @class(['sm:col-span-2' => in_array($label, ['Address'], true)])>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 font-semibold {{ $label === 'Email status' && ! $alumni->hasVerifiedEmail() ? 'text-amber-700' : 'text-slate-900' }}">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Personal details</p>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    @foreach ([
                        'Full name' => $enrollment ? trim($enrollment->first_name.' '.$enrollment->middle_name.' '.$enrollment->last_name.' '.$enrollment->extension_name) : $alumni->name,
                        'Birth date' => $enrollment?->birth_date?->format('M d, Y'),
                        'Gender' => $enrollment?->gender,
                        'Educational attainment' => $enrollment?->educational_attainment,
                        'School' => $enrollment?->school_name,
                        'Year graduated' => $enrollment?->yearGraduatedLabel(),
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-500">MCARE training record</p>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ([
                        'Program' => $enrollment?->program,
                        'Batch' => $enrollment?->batch ? $enrollment->batch->name.' '.$enrollment->batch->year : ($historicalClaim?->historical_batch_name ?: 'Historical graduate'),
                        'Schedule' => $enrollment?->schedule_preference,
                        'Learning status' => $enrollment?->learningStatusLabel(),
                        'Graduated' => $enrollment?->learning_status_changed_at?->format('M d, Y'),
                        'Source' => $enrollment?->is_historical_record ? 'Historical alumni claim' : 'In-system graduate',
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $value ?: '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
                @if ($historicalClaim)
                    <dl class="mt-4 grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ([
                            'Completion year' => $historicalClaim->training_completion_year,
                            'Evidence type' => str($historicalClaim->evidence_type)->headline(),
                            'COTC number' => $historicalClaim->certificate_number,
                        ] as $label => $value)
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</dt>
                                <dd class="mt-1 font-semibold text-slate-900">{{ $value ?: '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                @endif
            </section>
        </div>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white">
            <div class="border-b border-slate-100 px-6 py-4">
                <h2 class="text-sm font-bold text-slate-950">Career Hub applications</h2>
                <p class="mt-1 text-xs text-slate-500">Applications submitted by this alumni, including certificate review status.</p>
            </div>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Career</th>
                        <th class="px-4 py-3">Application</th>
                        <th class="px-4 py-3">Certificate</th>
                        <th class="px-4 py-3">Submitted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($applications as $application)
                        <tr>
                            <td class="px-4 py-4">
                                <p class="font-bold text-slate-900">{{ $application->opportunity?->title ?? 'Removed posting' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $application->opportunity?->location ?: 'Location not listed' }}</p>
                            </td>
                            <td class="px-4 py-4">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $applicationStatusStyles[$application->status] ?? 'bg-slate-50 text-slate-700 ring-slate-200' }}">
                                    {{ $application->statusLabel() }}
                                </span>
                            </td>
                            <td class="px-4 py-4">
                                @if ($application->certificateStatusLabel())
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold {{ $application->isCareerAwarded() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $application->certificateStatusLabel() }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-4 text-slate-500">{{ $application->created_at?->format('M d, Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-slate-500">No career applications yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </section>
@endsection
