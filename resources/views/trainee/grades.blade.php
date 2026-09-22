@extends('trainee.layouts.app', ['title' => 'Grades | MCARE Graduate'])

@section('content')
@php
    $competentCount = $gradeRecords->where('competency_outcome', \App\Models\ModuleProgress::OUTCOME_COMPETENT)->count();
    $notYetCompetentCount = $gradeRecords->where('competency_outcome', \App\Models\ModuleProgress::OUTCOME_NOT_YET_COMPETENT)->count();
@endphp

<section class="space-y-6">
    <header class="border-b border-slate-200 pb-6">
        <p class="dashboard-section-kicker">Graduate record</p>
        <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="dashboard-section-title text-2xl sm:text-3xl">Course grades</h1>
                <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Only module results formally evaluated by an MCARE trainer appear here. Uploaded lessons and learning files are no longer accessible after graduation.</p>
            </div>
            <span class="inline-flex self-start rounded-full bg-purple-50 px-3 py-1.5 text-xs font-bold text-purple-800 ring-1 ring-purple-200">Read-only official record</span>
        </div>
    </header>

    <div class="rounded-2xl border border-purple-200 bg-purple-50/70 p-5">
        <div class="flex items-start gap-4">
            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-purple-600 text-white">
                <x-dashboard-icon name="document" class="h-5 w-5" />
            </span>
            <div class="space-y-1">
                <h3 class="text-sm font-bold text-purple-950">Official Certificate and Transcript of Records (TOR) Notice</h3>
                <p class="text-xs leading-5 text-purple-900/80">
                    Your completion has been officially validated as <strong>Competent</strong>. Your <strong>Certificate of Training Completion (COTC)</strong> can be claimed and downloaded directly in your <a href="{{ route('trainee.documents') }}" class="font-bold underline text-purple-800 hover:text-purple-950">Documents section</a>. For official certified Transcript of Records (TOR) with physical dry seals and authentication, please visit the MCTC Administrative Office / Registrar.
                </p>
            </div>
        </div>
    </div>

    <article class="dashboard-panel overflow-hidden">
        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-xl bg-purple-100 text-purple-800">
                    <x-dashboard-icon name="award" class="h-6 w-6" />
                </span>
                <div>
                    <p class="text-xs font-black uppercase tracking-wide text-purple-700">Completed MCARE course</p>
                    <h2 class="mt-1 text-2xl font-black text-slate-950">{{ $application->program ?: 'Caregiving NC II' }}</h2>
                    <p class="mt-1 text-sm text-slate-500">{{ $batch ? $batch->name.' '.$batch->year : 'Verified graduate record' }}</p>
                </div>
            </div>
            <div class="grid w-full grid-cols-3 gap-2 text-center" data-trainee-grade-chips>
                <div class="rounded-xl bg-slate-50 px-3 py-3"><strong class="block text-xl text-slate-950">{{ $gradeRecords->count() ?: ($competencyRecords->count() ?? 0) }}</strong><span class="text-[10px] font-bold uppercase tracking-wide text-slate-500">Evaluated</span></div>
                <div class="rounded-xl bg-emerald-50 px-3 py-3"><strong class="block text-xl text-emerald-800">{{ $competentCount ?: ($competencyRecords->where('status', 'competent')->count() ?? 0) }}</strong><span class="text-[10px] font-bold uppercase tracking-wide text-emerald-700">Competent</span></div>
                <div class="rounded-xl bg-amber-50 px-3 py-3"><strong class="block text-xl text-amber-800">{{ $notYetCompetentCount }}</strong><span class="text-[10px] font-bold uppercase tracking-wide text-amber-700">NYC</span></div>
            </div>
        </div>
    </article>

    <section aria-labelledby="validated-grades-title">
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="dashboard-section-kicker">Trainer evaluations</p>
                <h2 id="validated-grades-title" class="mt-1 text-2xl font-black text-slate-950">Validated module grades</h2>
            </div>
            <p class="text-xs font-semibold text-slate-500">Unevaluated modules are not included.</p>
        </div>

        <div class="grid gap-4">
            @forelse($gradeRecords as $grade)
                @php
                    $module = $grade->module;
                    $isCompetent = $grade->competency_outcome === \App\Models\ModuleProgress::OUTCOME_COMPETENT;
                @endphp
                <article class="dashboard-panel">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                @if($module?->module_code)
                                    <span class="rounded bg-purple-100 px-2 py-0.5 font-mono text-xs font-bold text-purple-900 ring-1 ring-purple-200">{{ $module->module_code }}</span>
                                @endif
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $isCompetent ? 'bg-emerald-50 text-emerald-800 ring-1 ring-emerald-200' : 'bg-amber-50 text-amber-800 ring-1 ring-amber-200' }}">{{ $grade->competencyOutcomeLabel() }}</span>
                            </div>
                            <h3 class="mt-3 text-lg font-black text-slate-950">{{ $module?->title ?? 'Recorded Caregiving NC II module' }}</h3>
                            @if($module?->topic)<p class="mt-1 text-sm text-slate-600">{{ $module->topic }}</p>@endif
                        </div>
                        <div class="text-xs text-slate-500 xl:text-right">
                            <p>Evaluated by <strong class="text-slate-700">{{ $grade->evaluator?->name ?? 'MCARE trainer' }}</strong></p>
                            <p class="mt-1">{{ $grade->evaluated_at?->format('M d, Y g:i A') }}</p>
                        </div>
                    </div>

                    <dl class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Knowledge / Quiz</dt><dd class="mt-1 text-lg font-black text-slate-950">{{ filled($grade->quiz_score) ? number_format((float) $grade->quiz_score, 1).'%' : 'Not recorded' }}</dd></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Practical rating</dt><dd class="mt-1 text-lg font-black text-slate-950">{{ $grade->practicalRatingLabel() }}</dd></div>
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Overall outcome</dt><dd class="mt-1 text-lg font-black {{ $isCompetent ? 'text-emerald-700' : 'text-amber-700' }}">{{ $grade->competencyOutcomeLabel() }}</dd></div>
                    </dl>

                    @if($grade->evaluation_remarks)
                        <div class="mt-4 rounded-xl border border-purple-100 bg-purple-50/60 px-4 py-3 text-sm leading-6 text-purple-950"><strong>Trainer remarks:</strong> {{ $grade->evaluation_remarks }}</div>
                    @endif
                </article>
            @empty
                <div class="dashboard-panel py-14 text-center">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-full bg-slate-100 text-slate-500"><x-dashboard-icon name="chart-column" class="h-5 w-5" /></span>
                    <h3 class="mt-4 text-lg font-black text-slate-950">No trainer-validated grades yet</h3>
                    <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-600">No trainer-validated grades have been recorded for this course. Modules without a completed trainer evaluation stay hidden from the graduate record.</p>
                </div>
            @endforelse
        </div>
    </section>
</section>
@endsection
