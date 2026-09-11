@extends('trainee.layouts.app', ['title' => 'Classwork | MCARE Trainee'])

@section('content')
@php
    $classworkAccess = $classworkAccess ?? collect();
    $categories = collect($modules)->groupBy(function ($module) {
        return match($module->competency_category) {
            'core' => '1. Core Competencies',
            'common' => '2. Common Competencies',
            'basic' => '3. Basic Competencies',
            default => '4. Learning Materials',
        };
    })->sortKeys();

    $completedCount = collect($modules)->filter(
        fn ($module) => ($progressByModule->get($module->id)?->status === 'completed' || $progressByModule->get($module->id)?->competency_outcome === 'competent')
    )->count();
@endphp

<div class="lms-page" data-lms-classwork data-lms-role="trainee">
    <header class="lms-class-header">
        <div class="min-w-0">
            <p class="lms-eyebrow">Classwork</p>
            <h1>Modules</h1>
            <p>Complete units in order.</p>
        </div>
        <div class="lms-compact-progress" aria-label="{{ $completedCount }} of {{ collect($modules)->count() }} modules completed">
            <strong>{{ $completedCount }}/{{ collect($modules)->count() }}</strong>
            <span>completed</span>
        </div>
    </header>

    <div class="lms-topic-list">
        @forelse($categories as $categoryName => $catModules)
            <section class="lms-topic-section">
                <header class="lms-topic-heading">
                    <h2>{{ $categoryName }}</h2>
                    <span>{{ $catModules->count() }} {{ str('unit')->plural($catModules->count()) }}</span>
                </header>
                <div class="lms-trainee-classwork-list">
                    @foreach($catModules as $module)
                        @php
                            $moduleProgress = $progressByModule->get($module->id);
                            $access = $classworkAccess[$module->id] ?? ['accessible' => true, 'blocker' => null];
                            $isLocked = ! ($access['accessible'] ?? true);
                            $blocker = $access['blocker'] ?? null;
                            $progressValue = $isLocked ? 0 : (int) ($moduleProgress?->displayProgressPercent() ?? 0);
                            $isCompetent = $moduleProgress?->competency_outcome === 'competent';
                            $isCompleted = $moduleProgress?->status === 'completed' || $isCompetent;
                            $isMaterialOnly = !$module->requiresEvaluation();
                            $isDeferred = (bool) ($moduleProgress?->is_deferred ?? false);
                            $blockerLabel = $blocker ? ($blocker->module_code ?: $blocker->title) : null;
                            $blockerNeedsRemediation = $blocker
                                ? (bool) ($progressByModule->get($blocker->id)?->needsRemediation())
                                : false;
                            if ($isDeferred && $isLocked) {
                                $lockLabel = $blocker
                                    ? ($blockerNeedsRemediation
                                        ? 'Locked — finish '.$blockerLabel
                                        : 'Locked — '.$blockerLabel)
                                    : 'Locked — finish current modules';
                            } elseif ($blockerNeedsRemediation) {
                                $lockLabel = 'Locked — finish '.$blockerLabel;
                            } else {
                                $lockLabel = $blocker
                                    ? 'Locked — '.$blockerLabel
                                    : ($moduleProgress?->workflowStatusLabel() ?: 'Locked');
                            }
                        @endphp
                        <article class="lms-trainee-classwork-card{{ $isLocked ? ' is-locked' : '' }}{{ $isDeferred ? ' is-deferred' : '' }}" @if($isDeferred) data-classwork-deferred="1" @endif>
                            <span class="lms-classwork-icon"><x-dashboard-icon name="book-open" /></span>
                            <div class="lms-classwork-main">
                                <div class="lms-classwork-title-line">
                                    <div class="flex flex-wrap items-center gap-2">
                                        @if($module->module_code)
                                            <span class="rounded bg-purple-100 px-2 py-0.5 text-xs font-mono font-bold text-purple-900 ring-1 ring-purple-300">
                                                {{ $module->module_code }}
                                            </span>
                                        @endif
                                        @if($isDeferred && ! $isCompleted)
                                            <span class="rounded bg-amber-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wide text-amber-900 ring-1 ring-amber-300">
                                                Catch-up
                                            </span>
                                        @endif
                                        <h3>
                                            @if($isLocked)
                                                {{ $module->title }}
                                            @else
                                                <a href="{{ route('trainee.modules.show', $module) }}" class="hover:text-purple-700 transition">
                                                    {{ $module->title }}
                                                </a>
                                            @endif
                                        </h3>
                                    </div>
                                    <span class="lms-status-chip {{ $isLocked ? 'is-red' : ($isCompleted ? 'is-green' : ($isMaterialOnly ? 'is-purple' : ($moduleProgress ? 'is-amber' : 'is-neutral'))) }}">
                                        {{ $isLocked ? $lockLabel : ($isCompleted ? 'Completed' : ($isMaterialOnly ? 'Material' : ($moduleProgress ? $moduleProgress->workflowStatusLabel() : 'Not started'))) }}
                                    </span>
                                </div>

                                @if($module->topic)
                                    <p class="lms-classwork-topic">{{ $module->topic }}</p>
                                @endif

                                <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                                    <span class="inline-flex items-center gap-1.5">
                                        <x-user-avatar :user="$module->trainer" :name="$module->trainer?->name ?? 'MCARE Trainer'" class="grid h-6 w-6 place-items-center rounded-full bg-purple-100 text-[9px] font-black text-purple-800" />
                                        {{ $module->trainer?->name ?? 'MCARE Trainer' }}
                                    </span>
                                    @if($module->due_at)<span>Due {{ $module->due_at->format('M d') }}</span>@endif
                                </div>

                                <div class="lms-progress-track" role="progressbar" aria-label="{{ $progressValue }} percent complete" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $progressValue }}">
                                    @if($progressValue > 0)
                                        <span style="width: {{ $progressValue }}%"></span>
                                    @endif
                                </div>
                            </div>
                            @if($isLocked)
                                <span class="secondary-action text-xs py-2 px-4 shrink-0" aria-disabled="true">Locked</span>
                            @else
                                <a href="{{ route('trainee.modules.show', $module) }}" class="{{ $isCompleted ? 'secondary-action' : 'primary-action' }} text-xs py-2 px-4 shrink-0">
                                    {{ $isCompleted ? 'Review' : 'Open' }}
                                </a>
                            @endif
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <div class="lms-empty-state">
                <x-dashboard-icon name="book-open" />
                <h2>No classwork yet</h2>
                <p>Published learning materials will appear here when your trainer shares them.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
