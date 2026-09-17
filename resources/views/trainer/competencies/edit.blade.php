@extends('trainer.layouts.app', ['title' => 'Trainee Competency Record | MCARE Trainer'])

@section('content')
@php
    $categoryLabels = ['basic' => 'Basic competencies', 'common' => 'Common competencies', 'core' => 'Core competencies', 'custom' => 'Institutional / Custom competencies'];
@endphp
<section class="w-full space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="dashboard-section-kicker">Evaluate trainee</p><h1 class="dashboard-section-title mt-2 text-3xl">{{ $trainee->first_name }} {{ $trainee->last_name }}</h1><p class="mt-2 text-sm text-slate-600">{{ $trainee->batch?->name }} {{ $trainee->batch?->year }} · {{ $trainee->schedule_preference }} class</p><x-graduate-batch-badge :application="$trainee" class="mt-2" /></div>
        <a class="secondary-action" href="{{ route('trainer.competencies.index') }}">Back to records</a>
    </header>

    @if($unitsByCategory->flatten()->isEmpty())
        <div class="border border-slate-200 bg-white px-6 py-14 text-center text-slate-500">
            <p class="font-semibold text-slate-700">No published modules for this trainee's batch yet.</p>
            <p class="mt-2 text-sm">Publish classwork from the trainer or admin Modules page and those units will appear here by category.</p>
        </div>
    @else
    <form method="POST" action="{{ route('trainer.competencies.update', $trainee) }}" class="space-y-6">
        @csrf
        @method('PATCH')
        @foreach($unitsByCategory as $category => $units)
            <section class="space-y-3">
                <div><p class="dashboard-section-kicker">{{ $categoryLabels[$category] ?? str($category)->headline() }}</p><p class="mt-1 text-sm text-slate-500">Progress uses the unit result; Achievement uses every learning outcome below it.</p></div>
                @foreach($units as $unit)
                    @php
                        $record = $recordsByUnit->get($unit->id);
                        $results = $record?->outcomeResults?->keyBy('competency_outcome_id') ?? collect();
                        $locked = (bool) $record?->locked_at;
                        // Path: resources/views/trainer/competencies/edit.blade.php | Label: Disable evaluation until "Mark as done"
                        $canEvaluate = ($evaluableUnitIds ?? collect())->contains($unit->id) || $locked;
                        $inputDisabled = $locked || ! $canEvaluate;
                    @endphp
                    <details class="dashboard-panel p-0 {{ ! $canEvaluate ? 'opacity-70' : '' }}" @if($record && $record->status !== 'not_assessed') open @endif>
                        <summary class="flex cursor-pointer list-none items-center justify-between gap-4 p-5">
                            <span><span class="block font-bold text-slate-950">{{ $unit->title }}</span><span class="mt-1 block text-xs text-slate-500">{{ $unit->code ?: str($category)->headline() }} · {{ $unit->outcomes->count() }} outcomes</span></span>
                            <span class="dashboard-pill {{ ! $canEvaluate ? 'bg-slate-100 text-slate-500 ring-slate-200' : ($record?->status === 'competent' ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : ($record?->status === 'not_yet_competent' ? 'bg-red-50 text-red-700 ring-red-100' : 'bg-slate-100 text-slate-600 ring-slate-200')) }}">{{ ! $canEvaluate ? 'Waiting for trainee' : $statuses[$record?->status ?? 'not_assessed'] }}</span>
                        </summary>
                        <div class="border-t border-slate-200 p-5">
                            <input type="hidden" name="records[{{ $unit->id }}][unit_id]" value="{{ $unit->id }}">
                            @if($locked)<div class="mb-4 border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-800">Locked after official document generation. An admin must reissue or resolve the official record.</div>@endif
                            @if(! $canEvaluate)
                                <div class="mb-4 border border-slate-200 bg-slate-50 p-3 text-sm font-semibold text-slate-700">
                                    The trainee has not marked the module linked to this competency as done yet. You can evaluate this unit once they submit the module for evaluation.
                                </div>
                            @endif
                            <div class="grid gap-4 md:grid-cols-[1fr_12rem]">
                                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Unit result</label><select class="form-field" name="records[{{ $unit->id }}][status]" @disabled($inputDisabled)>@foreach($statuses as $value => $statusLabel)<option value="{{ $value }}" @selected(old("records.{$unit->id}.status", $record?->status ?? 'not_assessed') === $value)>{{ $statusLabel }}</option>@endforeach</select></div>
                                <div><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Final score (%)</label><input class="form-field" type="number" min="0" max="100" step="0.01" name="records[{{ $unit->id }}][percentage_score]" value="{{ old("records.{$unit->id}.percentage_score", $record?->percentage_score) }}" @disabled($inputDisabled)></div>
                            </div>
                            <div class="mt-5 divide-y divide-slate-100 border-y border-slate-200">
                                @foreach($unit->outcomes as $outcome)
                                    @php $outcomeStatus = old("records.{$unit->id}.outcomes.{$outcome->id}", $results->get($outcome->id)?->status ?? 'not_assessed'); @endphp
                                    <div class="grid gap-3 py-3 sm:grid-cols-[1fr_12rem] sm:items-center"><label class="text-sm font-medium text-slate-800" for="outcome-{{ $outcome->id }}">{{ $outcome->title }}</label><select id="outcome-{{ $outcome->id }}" class="form-field" name="records[{{ $unit->id }}][outcomes][{{ $outcome->id }}]" @disabled($inputDisabled)>@foreach($statuses as $value => $statusLabel)<option value="{{ $value }}" @selected($outcomeStatus === $value)>{{ $statusLabel }}</option>@endforeach</select></div>
                                @endforeach
                            </div>
                            <div class="mt-4"><label class="mb-2 block text-xs font-bold uppercase text-slate-500">Trainer notes</label><textarea class="form-field min-h-24" name="records[{{ $unit->id }}][notes]" maxlength="1000" @disabled($inputDisabled)>{{ old("records.{$unit->id}.notes", $record?->notes) }}</textarea></div>
                            @if($locked)
                                <input type="hidden" name="records[{{ $unit->id }}][status]" value="{{ $record->status }}">
                                <input type="hidden" name="records[{{ $unit->id }}][percentage_score]" value="{{ $record->percentage_score }}">
                                <input type="hidden" name="records[{{ $unit->id }}][notes]" value="{{ $record->notes }}">
                                @foreach($unit->outcomes as $outcome)<input type="hidden" name="records[{{ $unit->id }}][outcomes][{{ $outcome->id }}]" value="{{ $results->get($outcome->id)?->status ?? 'not_assessed' }}">@endforeach
                            @elseif(! $canEvaluate)
                                {{-- Force the payload to remain "not_assessed" so the server-side gate never trips on this row --}}
                                <input type="hidden" name="records[{{ $unit->id }}][status]" value="not_assessed">
                                <input type="hidden" name="records[{{ $unit->id }}][percentage_score]" value="">
                                <input type="hidden" name="records[{{ $unit->id }}][notes]" value="">
                                @foreach($unit->outcomes as $outcome)<input type="hidden" name="records[{{ $unit->id }}][outcomes][{{ $outcome->id }}]" value="not_assessed">@endforeach
                            @endif
                        </div>
                    </details>
                @endforeach
            </section>
        @endforeach
        <div class="sticky bottom-4 flex justify-end border border-slate-200 bg-white p-4 shadow-lg"><button class="primary-action" type="submit">Save evaluation</button></div>
    </form>
    @endif
</section>
@endsection
