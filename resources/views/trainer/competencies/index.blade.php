@extends('trainer.layouts.app', ['title' => 'Competency Records | MCARE Trainer'])

@section('content')
@php
    $categoryLabels = ['basic' => 'Basic competencies', 'common' => 'Common competencies', 'core' => 'Core competencies', 'custom' => 'Institutional / Custom competencies'];
    $statusSymbols = ['not_assessed' => '-', 'in_progress' => 'IP', 'competent' => 'C', 'not_yet_competent' => 'NYC'];
    $selectedTraineeIds = collect(old('trainee_ids', []))->map(fn ($id) => (int) $id);
@endphp

<section class="mx-auto max-w-none space-y-5" data-competency-board>
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 xl:flex-row xl:items-end xl:justify-between">
        <div><p class="dashboard-section-kicker">Training records</p><h1 class="dashboard-section-title mt-2 text-3xl">Batch grading board</h1></div>
        @if($selectedBatch)
            <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
                <span><strong class="text-slate-950">{{ $summary['trainees'] }}</strong> <span class="text-slate-500">trainees</span></span>
                <span><strong class="text-slate-950">{{ $summary['competent'] }}</strong><span class="text-slate-500"> / {{ $summary['possible'] }} competent</span></span>
                <span><strong class="text-violet-700">{{ $summary['percent'] }}%</strong> <span class="text-slate-500">batch completion</span></span>
            </div>
        @endif
    </header>

    <form method="GET" action="{{ route('trainer.competencies.index') }}" class="grid gap-3 border-b border-slate-200 pb-5 md:grid-cols-[minmax(13rem,1fr)_minmax(12rem,1fr)_10rem_auto] md:items-end">
        <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="competency-search">Search trainee</label><input id="competency-search" class="form-field" name="search" value="{{ $filters['search'] ?? '' }}" maxlength="100" placeholder="Name or email"></div>
        <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="competency-batch">Batch</label><select id="competency-batch" class="form-field" name="batch_id">@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected((int)($filters['batch_id'] ?? 0) === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>@endforeach</select></div>
        <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="competency-schedule">Class</label><select id="competency-schedule" class="form-field" name="schedule"><option value="">AM and PM</option><option value="AM" @selected(($filters['schedule'] ?? '') === 'AM')>AM</option><option value="PM" @selected(($filters['schedule'] ?? '') === 'PM')>PM</option></select></div>
        <div class="flex gap-2"><button class="primary-action min-h-11 flex-1 md:flex-none" type="submit">Apply</button><a class="secondary-action min-h-11 flex-1 md:flex-none" href="{{ route('trainer.competencies.index') }}">Reset</a></div>
    </form>

    @if(! $selectedBatch)
        <div class="border border-slate-200 bg-white px-6 py-14 text-center text-slate-500">Create a training batch before recording competencies.</div>
    @else
        <div class="competency-board-toolbar">
            <div class="min-w-0">
                <p class="truncate text-sm font-bold text-slate-950">{{ $selectedBatch->name }} {{ $selectedBatch->year }}{{ ! empty($filters['schedule']) ? ' | '.$filters['schedule'].' class' : '' }}</p>
                <p class="mt-1 text-xs font-medium text-slate-500">Evaluate only after the trainee marks the module as done. Locked and in-progress cells stay closed.</p>
                <div class="mt-2 flex flex-wrap gap-2 text-xs font-semibold text-slate-600" aria-label="Competency status legend"><span class="competency-legend is-competent">C Competent</span><span class="competency-legend is-progress">IP In progress</span><span class="competency-legend is-not-yet">NYC Not yet competent</span><span class="competency-legend is-empty">Evaluate Ready</span><span class="competency-legend is-waiting">In progress Closed</span><span class="competency-legend is-locked">Locked Closed</span></div>
            </div>
            <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                @if($summary['trainees'] > 0 && $unitsByCategory->flatten()->isNotEmpty())
                    <span class="text-xs font-bold text-violet-700" data-selected-trainee-count>0 selected</span>
                    <button type="button" data-dashboard-dialog-open="bulk-competency-dialog" data-bulk-update-open class="primary-action gap-2" disabled><x-dashboard-icon name="layer-group" class="h-4 w-4" />Bulk update</button>
                @endif
                @if($unitsByCategory->flatten()->isNotEmpty())
                <div class="flex border border-slate-200 bg-white" aria-label="Scroll competency columns"><button type="button" class="competency-scroll-button" data-competency-scroll="left" aria-label="Scroll competencies left" title="Scroll left"><x-dashboard-icon name="chevron-left" /></button><button type="button" class="competency-scroll-button border-l border-slate-200" data-competency-scroll="right" aria-label="Scroll competencies right" title="Scroll right"><x-dashboard-icon name="chevron-right" /></button></div>
                @endif
                <a class="secondary-action gap-2" href="{{ route('trainer.competencies.export', ['trainingBatch' => $selectedBatch, 'schedule' => $filters['schedule'] ?? null]) }}"><x-dashboard-icon name="file-excel" class="h-4 w-4" />Export Excel</a>
                <a class="secondary-action gap-2" href="{{ route('trainer.competencies.chart', ['trainingBatch' => $selectedBatch, 'chart' => 'progress', 'schedule' => $filters['schedule'] ?? null]) }}"><x-dashboard-icon name="table-columns" class="h-4 w-4" />Progress</a>
                <a class="secondary-action gap-2" href="{{ route('trainer.competencies.chart', ['trainingBatch' => $selectedBatch, 'chart' => 'achievement', 'schedule' => $filters['schedule'] ?? null]) }}"><x-dashboard-icon name="list-check" class="h-4 w-4" />Achievement</a>
            </div>
        </div>

        @if($traineeLimitReached)<div class="border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">The board is showing the first 100 matching trainees. Narrow the class or search filter before a bulk update.</div>@endif

        @if($unitsByCategory->flatten()->isEmpty())
            <div class="border border-slate-200 bg-white px-6 py-14 text-center text-slate-500">
                <p class="font-semibold text-slate-700">No published modules for this batch yet.</p>
                <p class="mt-2 text-sm">Create classwork from the trainer or admin Modules page. Only those units appear here, grouped by the admin catalog category.</p>
            </div>
        @else
        <div class="competency-matrix-shell" data-competency-scroller tabindex="0" aria-label="Batch competency grading matrix">
            <table class="competency-matrix">
                <thead>
                    <tr><th class="competency-select-column" rowspan="2"><input type="checkbox" data-select-all-trainees aria-label="Select all visible trainees" title="Select all visible trainees"></th><th class="competency-trainee-column" rowspan="2">Trainee</th>@foreach($unitsByCategory as $category => $units)<th class="competency-category-heading is-{{ $category }}" colspan="{{ $units->count() }}">{{ $categoryLabels[$category] ?? str($category)->headline() }}</th>@endforeach</tr>
                    <tr>@foreach($unitsByCategory as $category => $units)@foreach($units as $unit)<th class="competency-unit-heading is-{{ $category }}" title="{{ $unit->title }}"><span>{{ $unit->code ?: 'U'.str_pad((string)$unit->sort_order, 2, '0', STR_PAD_LEFT) }}</span><small>{{ $unit->title }}</small></th>@endforeach @endforeach</tr>
                </thead>
                <tbody>
                @forelse($trainees as $trainee)
                    <tr>
                        <td class="competency-select-column"><input type="checkbox" name="trainee_ids[]" value="{{ $trainee->id }}" form="bulk-competency-form" data-trainee-selector @checked($selectedTraineeIds->contains($trainee->id)) aria-label="Select {{ $trainee->first_name }} {{ $trainee->last_name }}"></td>
                        <th scope="row" class="competency-trainee-column">
                            <span class="flex items-center gap-2">
                                <x-user-avatar
                                    :user="$trainee->user"
                                    :name="trim($trainee->first_name.' '.$trainee->last_name)"
                                    class="grid h-8 w-8 place-items-center rounded-full bg-purple-100 text-[10px] font-black text-purple-800"
                                />
                                <span class="min-w-0">
                                    <span class="block truncate">{{ $trainee->last_name }}, {{ $trainee->first_name }}</span>
                                    <small>{{ $trainee->schedule_preference }} | {{ $trainee->email }}</small>
                                    <x-graduate-batch-badge :application="$trainee" class="mt-1.5" />
                                    <a href="{{ route('trainer.competencies.edit', $trainee) }}" class="competency-evaluate-link">Evaluate record</a>
                                </span>
                            </span>
                        </th>
                        @foreach($unitsByCategory as $category => $units)
                            @foreach($units as $unit)
                                @php
                                    $record = $recordsByTrainee->get($trainee->id)?->get($unit->id);
                                    $status = $record?->status ?? 'not_assessed';
                                    $results = $record?->outcomeResults?->keyBy('competency_outcome_id') ?? collect();
                                    $gate = ($evaluationByTrainee ?? collect())->get($trainee->id)?->get($unit->id) ?? ['evaluable' => false, 'reason' => 'locked'];
                                    $canEvaluate = (bool) ($gate['evaluable'] ?? false) || (bool) $record?->locked_at;
                                    $waitReason = $gate['reason'] ?? 'locked';
                                    $cellState = ! $canEvaluate
                                        ? ($waitReason === 'in_progress' ? 'waiting' : 'module-locked')
                                        : str($status)->replace('_', '-');
                                    $waitLabel = $waitReason === 'in_progress' ? 'In progress' : 'Locked';
                                    $waitTitle = $waitReason === 'in_progress'
                                        ? 'Trainee is still in progress. Evaluation opens after they mark this module as done.'
                                        : 'This module is locked. Evaluation opens after the trainee marks it as done.';
                                    $payload = [
                                        'trainee_name' => trim($trainee->first_name.' '.$trainee->last_name),
                                        'unit_id' => $unit->id,
                                        'unit_code' => $unit->code ?: 'Unit '.$unit->sort_order,
                                        'unit_title' => $unit->title,
                                        'status' => $status,
                                        'score' => $record?->percentage_score,
                                        'notes' => $record?->notes,
                                        'locked' => (bool) $record?->locked_at,
                                        'update_url' => route('trainer.competencies.update', $trainee),
                                        'full_url' => route('trainer.competencies.edit', $trainee),
                                        'outcomes' => $unit->outcomes->map(fn ($outcome) => ['id' => $outcome->id, 'title' => $outcome->title, 'status' => $results->get($outcome->id)?->status ?? 'not_assessed'])->values()->all(),
                                    ];
                                    $encodedPayload = base64_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
                                @endphp
                                <td class="competency-record-cell is-{{ $cellState }}">
                                    @if($canEvaluate)
                                        <button type="button" data-competency-cell data-record-payload="{{ $encodedPayload }}" aria-label="{{ $status === 'not_assessed' ? 'Evaluate' : 'Update evaluation for' }} {{ $unit->title }} for {{ $trainee->first_name }} {{ $trainee->last_name }}" title="{{ $status === 'not_assessed' ? 'Evaluate this competency' : ($statuses[$status].($record?->percentage_score ? ' | '.$record->percentage_score.'%' : '')) }}">
                                            @if($status === 'not_assessed')
                                                <span>Evaluate</span>
                                            @else
                                                <span>{{ $statusSymbols[$status] }}</span>
                                                @if($record?->percentage_score)<small>{{ number_format((float) $record->percentage_score, 0) }}</small>@endif
                                            @endif
                                            @if($record?->locked_at)<x-dashboard-icon name="lock" class="competency-lock-icon" />@endif
                                        </button>
                                    @else
                                        <button type="button" disabled aria-disabled="true" title="{{ $waitTitle }}" aria-label="{{ $waitLabel }} {{ $unit->title }} for {{ $trainee->first_name }} {{ $trainee->last_name }}">
                                            <span>{{ $waitLabel }}</span>
                                        </button>
                                    @endif
                                </td>
                            @endforeach
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ $unitsByCategory->flatten()->count() + 2 }}" class="px-6 py-14 text-center text-sm text-slate-500">No approved trainees match this batch and class.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @endif
    @endif

    @if($selectedBatch && $summary['trainees'] > 0 && $unitsByCategory->flatten()->isNotEmpty())
        <dialog id="bulk-competency-dialog" data-dashboard-dialog data-auto-open="{{ $errors->any() ? 'true' : 'false' }}" class="m-auto max-h-[92vh] w-[min(94vw,40rem)] overflow-y-auto rounded-lg border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="bulk-competency-title">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4"><div><p class="dashboard-section-kicker">Batch action</p><h2 id="bulk-competency-title" class="mt-1 text-xl font-bold text-slate-950">Update selected trainees</h2><p class="mt-1 text-sm text-slate-500" data-bulk-dialog-count>0 trainees selected</p></div><button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Close bulk update" title="Close"><x-dashboard-icon name="xmark" /></button></div>
            <form id="bulk-competency-form" method="POST" action="{{ route('trainer.competencies.bulk-update') }}" class="space-y-5 p-6" data-dashboard-dialog-form data-submit-label="Updating records...">
                @csrf @method('PATCH')<input type="hidden" name="batch_id" value="{{ $selectedBatch->id }}">
                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="bulk-unit">Competency</label><select id="bulk-unit" class="form-field" name="unit_id" required><option value="">Select competency</option>@foreach($unitsByCategory as $category => $units)<optgroup label="{{ $categoryLabels[$category] ?? str($category)->headline() }}">@foreach($units as $unit)<option value="{{ $unit->id }}" @selected((int)old('unit_id') === $unit->id)>{{ $unit->code ?: 'Unit '.$unit->sort_order }} | {{ $unit->title }}</option>@endforeach</optgroup>@endforeach</select></div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="bulk-status">Result</label><select id="bulk-status" class="form-field" name="status" data-bulk-status required>@foreach($statuses as $value => $label)<option value="{{ $value }}" @selected(old('status', 'in_progress') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div data-bulk-score-wrap @class(['hidden' => old('status', 'in_progress') !== 'competent'])><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="bulk-score">Shared score (%)</label><input id="bulk-score" class="form-field" type="number" name="percentage_score" min="75" max="100" step="0.01" value="{{ old('percentage_score') }}" data-bulk-score></div>
                </div>
                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="bulk-notes">Batch note</label><textarea id="bulk-notes" class="form-field min-h-24" name="notes" maxlength="1000">{{ old('notes') }}</textarea></div>
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" data-action-button class="primary-action gap-2"><x-dashboard-icon name="check" class="h-4 w-4" />Update selected</button></div>
            </form>
        </dialog>
    @endif

    <div class="competency-drawer-backdrop" data-competency-drawer-backdrop hidden></div>
    <aside class="competency-record-drawer" data-competency-drawer aria-hidden="true" aria-labelledby="competency-drawer-title">
        <form method="POST" data-competency-drawer-form class="flex h-full flex-col">
            @csrf @method('PATCH')
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6"><div class="min-w-0"><p class="dashboard-section-kicker">Evaluate competency</p><p class="mt-1 text-[11px] font-bold uppercase tracking-wide text-violet-700" data-drawer-unit-code>Competency</p><h2 id="competency-drawer-title" class="mt-1 truncate text-xl font-bold text-slate-950" data-drawer-trainee>Trainee</h2><p class="mt-1 text-sm text-slate-500" data-drawer-unit-title></p></div><button type="button" data-competency-drawer-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Close evaluation" title="Close"><x-dashboard-icon name="xmark" /></button></div>
            <div class="flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                <div data-drawer-lock-notice class="hidden border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-800">Locked after official document generation.</div>
                <input type="hidden" data-drawer-unit-id>
                <div class="grid gap-4 sm:grid-cols-2"><div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="drawer-status">Unit result</label><select id="drawer-status" class="form-field" data-drawer-status>@foreach($statuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div><div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="drawer-score">Final score (%)</label><input id="drawer-score" class="form-field" type="number" min="0" max="100" step="0.01" data-drawer-score></div></div>
                <section><p class="mb-2 text-xs font-bold uppercase text-slate-500">Achievement outcomes</p><div class="divide-y divide-slate-100 border-y border-slate-200" data-drawer-outcomes></div></section>
                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500" for="drawer-notes">Trainer notes</label><textarea id="drawer-notes" class="form-field min-h-24" maxlength="1000" data-drawer-notes></textarea></div>
            </div>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:flex-row sm:justify-between sm:px-6"><a class="secondary-action" href="#" data-drawer-full-record>Full trainee record</a><button type="submit" class="primary-action gap-2" data-drawer-save><x-dashboard-icon name="check" class="h-4 w-4" />Save evaluation</button></div>
        </form>
    </aside>
</section>
@endsection
