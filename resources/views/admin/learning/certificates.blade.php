@extends('admin.layouts.app', ['title' => 'Training Records | MCARE Admin'])

@section('content')
@php
    $cotcType = \App\Models\OfficialDocument::TYPE_COTC;
    $torType = \App\Models\OfficialDocument::TYPE_TOR;
    $activeTab = ($activeTab ?? 'active') === 'graduates' ? 'graduates' : 'active';
    $isGraduatesTab = $activeTab === 'graduates';
    $visibleRecords = $isGraduatesTab ? $graduates : $activeTrainees;
    $tabQuery = fn (string $tab): array => array_filter([
        'tab' => $tab === 'active' ? null : $tab,
        'batch_id' => $filters['batch_id'] ?? null,
        'schedule' => $filters['schedule'] ?? null,
        'eligibility' => $filters['eligibility'] ?? null,
    ], fn ($value) => filled($value));
@endphp
<section class="space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.learning.certificate-templates') }}" class="secondary-action">Certificate templates</a>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-dashboard-dialog-open="competency-excel-dialog" class="secondary-action inline-flex items-center gap-2"><x-dashboard-icon name="file-excel" class="h-4 w-4" />Export competency Excel</button>
            <button type="button" data-dashboard-dialog-open="batch-tor-dialog" class="primary-action inline-flex items-center gap-2"><x-dashboard-icon name="folder-open" class="h-4 w-4" />Prepare batch TOR ZIP</button>
        </div>
    </header>

    <form method="GET" class="dashboard-panel grid gap-4 md:grid-cols-4">
        <input type="hidden" name="tab" value="{{ $activeTab }}">
        <div><label class="form-label">Batch</label><select name="batch_id" class="form-field"><option value="">All batches</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected((int)($filters['batch_id'] ?? 0) === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>@endforeach</select></div>
        <div><label class="form-label">Class</label><select name="schedule" class="form-field"><option value="">AM and PM</option><option value="AM" @selected(($filters['schedule'] ?? '') === 'AM')>AM</option><option value="PM" @selected(($filters['schedule'] ?? '') === 'PM')>PM</option></select></div>
        <div><label class="form-label">Completion gate</label><select name="eligibility" class="form-field"><option value="">All states</option><option value="eligible" @selected(($filters['eligibility'] ?? '') === 'eligible')>Eligible</option><option value="blocked" @selected(($filters['eligibility'] ?? '') === 'blocked')>Blocked</option></select></div>
        <div class="flex items-end gap-2"><button class="primary-action">Apply</button><a class="secondary-action" href="{{ route('admin.learning.certificates') }}">Reset</a></div>
    </form>

    <nav class="lms-context-tabs" aria-label="Training record groups">
        <a href="{{ route('admin.learning.certificates', $tabQuery('active')) }}" class="{{ $isGraduatesTab ? '' : 'is-active' }}" @unless($isGraduatesTab) aria-current="page" @endunless>Active trainees ({{ $activeTrainees->count() }})</a>
        <a href="{{ route('admin.learning.certificates', $tabQuery('graduates')) }}" class="{{ $isGraduatesTab ? 'is-active' : '' }}" @if($isGraduatesTab) aria-current="page" @endif>Graduates ({{ $graduates->count() }})</a>
    </nav>

    <p class="text-sm leading-6 text-slate-600">Generate COTC and Generate TOR use the active certificate template. Change the title, statement, logo, or signatory from <a href="{{ route('admin.learning.certificate-templates') }}" class="font-bold text-purple-700 hover:text-purple-900">Certificate templates</a>. Enrollment ID, document number, and the watermark stay on every file.</p>

    @include('admin.learning.partials.training-record-table', [
        'showHeading' => false,
        'records' => $visibleRecords,
        'empty' => $isGraduatesTab
            ? 'No graduates match these filters.'
            : 'No active trainees match these filters.',
    ])

    @foreach($visibleRecords as $record)
        @foreach([$cotcType => 'COTC', $torType => 'TOR'] as $type => $typeLabel)
            <dialog id="reissue-{{ $type }}-{{ $record->id }}" data-dashboard-dialog class="m-auto w-[min(94vw,34rem)] rounded-xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/45">
                <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4"><div><h2 class="text-xl font-bold text-slate-950">Reissue {{ $typeLabel }}</h2><p class="mt-1 text-xs text-slate-500">The current version will be revoked and retained in the audit history.</p></div><button type="button" data-dashboard-dialog-close class="secondary-action" aria-label="Close">Close</button></div>
                <form method="POST" action="{{ route('admin.learning.documents.reissue', [$record, $type]) }}" class="space-y-4 p-6">@csrf<label class="form-label" for="reason-{{ $type }}-{{ $record->id }}">Reason for reissue</label><textarea id="reason-{{ $type }}-{{ $record->id }}" class="form-field min-h-28" name="reason" minlength="10" maxlength="1000" required></textarea><div class="flex justify-end gap-2"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button class="primary-action">Queue new version</button></div></form>
            </dialog>
        @endforeach
    @endforeach

    <section class="space-y-3">
        <div><p class="dashboard-section-kicker">Batch exports</p><h2 class="mt-2 text-xl font-bold text-slate-950">Recent TOR archives</h2></div>
        <div class="dashboard-table-wrap overflow-x-auto"><table class="dashboard-table w-full min-w-[44rem]"><thead><tr><th>Batch</th><th>Status</th><th>Progress</th><th>Expires</th><th class="text-right">File</th></tr></thead><tbody>@forelse($exports as $export)<tr><td>{{ $export->batch?->name }} {{ $export->batch?->year }}</td><td>{{ str($export->status)->headline() }}</td><td>{{ $export->processed_records }} / {{ $export->total_records }}</td><td>{{ $export->expires_at?->format('M j, Y g:i A') ?? '-' }}</td><td class="text-right">@if($export->isDownloadable())<a class="secondary-action" href="{{ route('admin.learning.batch-exports.download', $export) }}">Download ZIP</a>@elseif($export->failure_reason)<span class="text-xs text-red-700">Failed</span>@else<span class="text-xs text-slate-500">Processing</span>@endif</td></tr>@empty<tr><td colspan="5" class="py-10 text-center text-slate-500">No batch exports requested.</td></tr>@endforelse</tbody></table></div>
    </section>

    <dialog id="batch-tor-dialog" data-dashboard-dialog class="m-auto w-[min(94vw,34rem)] rounded-xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/45">
        <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4"><div><h2 class="text-xl font-bold text-slate-950">Prepare batch TOR ZIP</h2><p class="mt-1 text-xs text-slate-500">The queue worker generates eligible TORs in chunks and creates one expiring archive.</p></div><button type="button" data-dashboard-dialog-close class="secondary-action" aria-label="Close">Close</button></div>
        <form method="POST" action="{{ route('admin.learning.batch-exports.store') }}" class="space-y-4 p-6">@csrf<div><label class="form-label" for="export-batch">Training batch</label><select id="export-batch" class="form-field" name="training_batch_id" required><option value="">Select batch</option>@foreach($batches as $batch)<option value="{{ $batch->id }}">{{ $batch->name }} {{ $batch->year }}</option>@endforeach</select></div><div class="flex justify-end gap-2"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button class="primary-action">Queue export</button></div></form>
    </dialog>

    <dialog id="competency-excel-dialog" data-dashboard-dialog class="m-auto w-[min(94vw,34rem)] rounded-xl border border-slate-200 bg-white p-0 shadow-2xl backdrop:bg-slate-950/45">
        <div class="flex items-start justify-between border-b border-slate-200 px-6 py-4"><div><h2 class="text-xl font-bold text-slate-950">Export competency Excel</h2><p class="mt-1 text-xs text-slate-500">Download a read-only progress matrix, achievement outcomes, and status guide.</p></div><button type="button" data-dashboard-dialog-close class="secondary-action" aria-label="Close">Close</button></div>
        <form method="GET" action="{{ route('admin.learning.competency-workbooks.download') }}" class="space-y-4 p-6">
            <div><label class="form-label" for="competency-export-batch">Training batch</label><select id="competency-export-batch" class="form-field" name="batch_id" required><option value="">Select batch</option>@foreach($batches as $batch)<option value="{{ $batch->id }}" @selected((int)($filters['batch_id'] ?? 0) === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>@endforeach</select></div>
            <div><label class="form-label" for="competency-export-schedule">Class</label><select id="competency-export-schedule" class="form-field" name="schedule"><option value="">AM and PM</option><option value="AM" @selected(($filters['schedule'] ?? '') === 'AM')>AM</option><option value="PM" @selected(($filters['schedule'] ?? '') === 'PM')>PM</option></select></div>
            <div class="flex justify-end gap-2"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button class="primary-action"><x-dashboard-icon name="save" class="h-4 w-4" />Download Excel</button></div>
        </form>
    </dialog>
</section>
@endsection
