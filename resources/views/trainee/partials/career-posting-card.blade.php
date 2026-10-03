<article class="dashboard-panel flex h-full flex-col p-5 sm:p-6">
    <div class="flex items-start justify-between gap-4">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-700">
            <x-dashboard-icon name="briefcase" class="h-5 w-5" />
        </span>
        <span class="bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Open opportunity</span>
    </div>

    <h3 class="mt-5 text-xl font-black text-slate-950">{{ $job->listingTitle() }}</h3>
    <p class="mt-2 text-sm font-bold text-purple-700">{{ $job->listingEmployer() }}</p>

    <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs font-semibold text-slate-500">
        @if ($job->estimated_salary)
            <span class="inline-flex items-center gap-1.5"><x-dashboard-icon name="banknote" class="h-3.5 w-3.5" />{{ $job->estimated_salary }}</span>
        @endif
        @if ($job->estimated_start_date)
            <span class="inline-flex items-center gap-1.5"><x-dashboard-icon name="calendar-days" class="h-3.5 w-3.5" />Start {{ $job->estimated_start_date->format('M d, Y') }}</span>
        @endif
        @if ($job->location)
            <span class="inline-flex items-center gap-1.5"><x-dashboard-icon name="location-dot" class="h-3.5 w-3.5" />{{ $job->location }}</span>
        @endif
        @if (filled($job->patient_gender))
            <span class="inline-flex items-center gap-1.5"><x-dashboard-icon name="users" class="h-3.5 w-3.5" />{{ $job->patientGenderLabel() }}</span>
        @endif
        @if (filled($job->mobility_status))
            <span class="inline-flex items-center gap-1.5"><x-dashboard-icon name="clipboard-list" class="h-3.5 w-3.5" />{{ $job->mobilityStatusLabel() }}</span>
        @endif
        @if ($job->patient_age !== null)
            <span class="inline-flex items-center gap-1.5">Age {{ $job->patient_age }}</span>
        @endif
    </div>

    @if ($job->postingSummary())
        <p class="mt-5 flex-1 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $job->postingSummary() }}</p>
    @endif

    @if ($job->specific_contraptions)
        <p class="mt-4 {{ $job->postingSummary() ? 'border-t border-slate-100 pt-4' : '' }} text-sm leading-6 text-slate-600"><span class="font-bold text-slate-900">Requirements:</span> {{ $job->specific_contraptions }}</p>
    @endif

    @php
        $applicationStatus = $applicationStatuses[$job->id] ?? null;
    @endphp

    <div class="mt-6 {{ $job->postingSummary() || $job->specific_contraptions ? '' : 'flex-1' }} flex w-full flex-col gap-2 sm:flex-row sm:flex-wrap">
        @unless ($isAdminPreview ?? false)
            @if ($applicationStatus === 'approved')
                <span class="inline-flex items-center gap-2 rounded-lg bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-700 ring-1 ring-emerald-200"><x-dashboard-icon name="circle-check" class="h-4 w-4" />Approved</span>
            @elseif ($applicationStatus === 'rejected')
                <span class="inline-flex items-center gap-2 rounded-lg bg-red-50 px-4 py-2 text-sm font-bold text-red-700 ring-1 ring-red-200"><x-dashboard-icon name="xmark" class="h-4 w-4" />Not approved</span>
            @elseif ($applicationStatus === 'pending')
                <span class="inline-flex items-center gap-2 rounded-lg bg-amber-50 px-4 py-2 text-sm font-bold text-amber-700 ring-1 ring-amber-200"><x-dashboard-icon name="clock" class="h-4 w-4" />Application pending</span>
            @else
                <a href="{{ route('trainee.career-hub.apply-form', $job) }}" class="primary-action inline-flex items-center gap-2 text-sm"><x-dashboard-icon name="briefcase" class="h-4 w-4" />Apply for this career</a>
            @endif
        @else
            <span class="secondary-action inline-flex items-center text-sm">Apply for this career</span>
        @endunless
    </div>
</article>
