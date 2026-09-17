@php
    $label = $type === \App\Models\OfficialDocument::TYPE_COTC ? 'COTC' : 'TOR';
    $isCotc = $type === \App\Models\OfficialDocument::TYPE_COTC;
    $isTor = $type === \App\Models\OfficialDocument::TYPE_TOR;
    $checksComplete = (bool) ($eligibility['eligible'] ?? false);
    $isGraduated = (bool) ($eligibility['graduated'] ?? false)
        || $record->learning_status === \App\Models\EnrollmentApplication::LEARNING_GRADUATED;
    $canIssueTor = (bool) ($eligibility['can_issue_tor'] ?? ($isGraduated && $checksComplete));
    $canAct = $isTor ? $canIssueTor : $checksComplete;
    $torBlockers = [];
    if ($isTor && ! $canIssueTor) {
        if (! $isGraduated) {
            $torBlockers[] = 'Graduate the trainee first';
        }
        if (! $checksComplete) {
            $torBlockers[] = 'Finish all completion checks';
        }
    }
    $textAction = 'training-record-text-action';
@endphp

<div class="training-record-actions">
    @if($document)
        <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">{{ str($document->status)->headline() }}</span>
    @endif

    @if($isTor && ! $canIssueTor)
        <p class="max-w-[14rem] text-[11px] font-medium leading-4 text-amber-800">
            {{ implode(' · ', $torBlockers) }}.
        </p>
    @endif

    @if(!$document)
        <form method="POST" action="{{ route('admin.learning.documents.generate', [$record, $type]) }}">
            @csrf
            <button class="{{ $textAction }}" @disabled(! $canAct) title="{{ $isTor && ! $canAct ? implode('. ', $torBlockers) : '' }}">Generate {{ $label }}</button>
        </form>
    @elseif($document->status === 'queued')
        <form method="POST" action="{{ route('admin.learning.documents.generate', [$record, $type]) }}">
            @csrf
            <button class="{{ $textAction }}" @disabled(! $canAct)>Generate {{ $label }} now</button>
        </form>
    @elseif($isCotc && $document->status === 'generated')
        <a class="{{ $textAction }}" href="{{ route('admin.learning.documents.preview', $document) }}">Review PDF</a>
        <form method="POST" action="{{ route('admin.learning.documents.release', $document) }}">
            @csrf
            @method('PATCH')
            <button class="{{ $textAction }}">Release to trainee</button>
        </form>
    @elseif($isCotc && in_array($document->status, ['released', 'downloaded']))
        <a class="{{ $textAction }}" href="{{ route('admin.learning.documents.download', $document) }}">Admin copy</a>
        <button type="button" data-dashboard-dialog-open="reissue-{{ $type }}-{{ $record->id }}" class="{{ $textAction }}">Reissue</button>
    @elseif($isTor && in_array($document->status, ['generated', 'released', 'downloaded']))
        @if($canIssueTor)
            <a class="{{ $textAction }}" href="{{ route('admin.learning.documents.preview', $document) }}">Preview</a>
            <a class="{{ $textAction }}" href="{{ route('admin.learning.documents.download', $document) }}">Download</a>
            <button type="button" data-dashboard-dialog-open="reissue-{{ $type }}-{{ $record->id }}" class="{{ $textAction }}">Reissue</button>
        @else
            <button type="button" class="{{ $textAction }}" disabled>Preview</button>
            <button type="button" class="{{ $textAction }}" disabled>Download</button>
        @endif
    @elseif($document->status === 'failed')
        @if($canAct)
            <button type="button" data-dashboard-dialog-open="reissue-{{ $type }}-{{ $record->id }}" class="{{ $textAction }}">Retry as new version</button>
        @else
            <button type="button" class="{{ $textAction }}" disabled>Retry as new version</button>
        @endif
    @else
        <span class="text-sm text-slate-500">Queue status: {{ str($document->status)->headline() }}</span>
    @endif
</div>
