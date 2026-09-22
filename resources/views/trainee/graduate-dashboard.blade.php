@extends('trainee.layouts.app', ['title' => 'Graduate Home | MCARE'])

@section('content')
<section class="space-y-7">
    <header class="border-b border-slate-200 pb-6">
        <p class="dashboard-section-kicker">Graduate account</p>
        <div class="mt-3 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
            <div><h1 class="dashboard-section-title">Welcome back, {{ $application->first_name }}.</h1><p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">Your MCARE training history, completion records, and graduate opportunities stay in the same account.</p></div>
            <a href="{{ route('trainee.career-hub') }}" class="primary-action inline-flex w-full items-center justify-center gap-2 sm:w-auto sm:self-start"><x-dashboard-icon name="briefcase" class="h-4 w-4" />Open Career Hub</a>
        </div>
    </header>

    <div class="grid grid-cols-2 gap-3 xl:grid-cols-3" data-trainee-stat-cards>
        <article class="dashboard-panel flex items-start justify-between gap-3" data-trainee-stat-card>
            <div class="min-w-0" data-trainee-stat-body>
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Training status</p>
                <p class="mt-2 text-xl font-black text-slate-950">{{ $application->learningStatusLabel() }}</p>
                <p class="mt-1 text-sm text-slate-500">Your account keeps its training record.</p>
            </div>
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100" data-trainee-stat-icon><x-dashboard-icon name="award" /></span>
        </article>
        <article class="dashboard-panel flex items-start justify-between gap-3" data-trainee-stat-card>
            <div class="min-w-0" data-trainee-stat-body>
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Course grades</p>
                <p class="mt-2 text-xl font-black text-slate-950">{{ $application->program ?: 'Caregiving NC II' }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $evaluatedGradeCount }} trainer-validated {{ str('grade')->plural($evaluatedGradeCount) }}</p>
                <a href="{{ route('trainee.grades') }}" class="mt-2 inline-flex text-sm font-bold text-purple-700 hover:text-purple-900">View Grades →</a>
            </div>
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100" data-trainee-stat-icon><x-dashboard-icon name="chart-column" /></span>
        </article>
        <article class="dashboard-panel flex items-start justify-between gap-3" data-trainee-stat-card>
            <div class="min-w-0" data-trainee-stat-body>
                <p class="text-xs font-black uppercase tracking-wide text-slate-500">Documents</p>
                <p class="mt-2 text-xl font-black text-slate-950">{{ $stats['documents'] }} files</p>
                <a href="{{ route('trainee.documents') }}" class="mt-1 inline-flex text-sm font-bold text-purple-700 hover:text-purple-900">View certificates and records</a>
            </div>
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700 ring-1 ring-sky-100" data-trainee-stat-icon><x-dashboard-icon name="file-text" /></span>
        </article>
    </div>

    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="dashboard-panel" aria-labelledby="graduate-history-title">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div class="min-w-0"><p class="dashboard-section-kicker">Completed training</p><h2 id="graduate-history-title" class="mt-1 text-2xl font-black text-slate-950">Your MCARE history</h2></div><span class="self-start rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">{{ $batch ? 'Graduated in this batch' : 'Verified graduate' }}</span></div>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-500">Program</p><p class="mt-1 font-bold text-slate-950">{{ $application->program }}</p></div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="text-xs font-bold uppercase text-slate-500">Batch</p><p class="mt-1 font-bold text-slate-950">{{ $batch ? $batch->name.' '.$batch->year : 'Recorded training batch' }}</p></div>
            </div>
            <p class="mt-5 text-sm leading-6 text-slate-600">Uploaded learning modules, quizzes, progress controls, and classroom posting close after graduation. Only grades formally evaluated by a trainer remain visible under Grades, while certificates and official records stay under Documents.</p>
        </section>
        <section class="dashboard-panel" aria-labelledby="graduate-jobs-title">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"><h2 id="graduate-jobs-title" class="text-xl font-black text-slate-950">Latest opportunities</h2><a href="{{ route('trainee.career-hub') }}" class="text-sm font-bold text-purple-700">View all</a></div>
            <div class="mt-4 space-y-3">
                @forelse($careerJobs as $job)
                    <div class="rounded-xl border border-slate-100 bg-slate-50 p-4"><p class="font-bold text-slate-950">{{ $job->listingTitle() }}</p><p class="mt-1 text-sm text-slate-600">{{ $job->estimated_salary ?: 'Salary not listed' }} · Start {{ $job->estimated_start_date->format('M d, Y') }}</p></div>
                @empty
                    <p class="text-sm leading-6 text-slate-500">No open careers yet.</p>
                @endforelse
            </div>
        </section>
    </div>
</section>
@endsection
