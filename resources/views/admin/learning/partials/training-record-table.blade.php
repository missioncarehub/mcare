@php
    $cotcType = \App\Models\OfficialDocument::TYPE_COTC;
    $torType = \App\Models\OfficialDocument::TYPE_TOR;
@endphp

<section class="space-y-3">
    @if($showHeading ?? true)
        <div>
            <p class="dashboard-section-kicker">{{ $kicker }}</p>
            <h2 class="mt-2 text-xl font-bold text-slate-950">{{ $heading }} <span class="text-sm font-semibold text-slate-500">({{ $records->count() }})</span></h2>
        </div>
    @endif

    <div class="dashboard-table-wrap overflow-x-auto">
        <table class="dashboard-table w-full min-w-[68rem]">
            <thead>
                <tr>
                    <th>Trainee</th>
                    <th>Batch / class</th>
                    <th>Status</th>
                    <th>Completion checks</th>
                    <th>COTC</th>
                    <th>TOR</th>
                </tr>
            </thead>
            <tbody>
                @forelse($records as $record)
                    @php
                        $cotc = $record->officialDocuments->first(fn ($document) => $document->type === $cotcType);
                        $tor = $record->officialDocuments->first(fn ($document) => $document->type === $torType);
                        $eligibility = $record->completion_eligibility;
                        $checks = collect($eligibility['checks'] ?? []);
                        $passedCount = $checks->where('passed', true)->count();
                        $pendingChecks = $checks->where('passed', false);
                    @endphp
                    <tr>
                        <td>
                            <p class="font-bold text-slate-950">{{ $record->last_name }}, {{ $record->first_name }} {{ $record->middle_name }}</p>
                            @if(filled($record->email))
                                <p class="mt-1 text-xs text-slate-500">{{ $record->email }}</p>
                            @endif
                        </td>
                        <td>
                            <p>{{ $record->batch ? $record->batch->name.' '.$record->batch->year : 'Unassigned' }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $record->schedule_preference }} class</p>
                        </td>
                        <td>
                            <span class="dashboard-pill {{ $eligibility['eligible'] ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }}">{{ $eligibility['eligible'] ? 'Eligible for issuance' : 'Completion checks pending' }}</span>
                            @if($record->learning_status === \App\Models\EnrollmentApplication::LEARNING_GRADUATED)
                                <p class="mt-2"><span class="dashboard-pill bg-indigo-50 text-indigo-700 ring-indigo-100">Graduated</span></p>
                            @else
                                <p class="mt-2"><span class="dashboard-pill bg-slate-100 text-slate-700 ring-slate-200">Not graduated</span></p>
                            @endif
                        </td>
                        <td>
                            <p class="font-semibold text-slate-800">{{ $passedCount }} of {{ $checks->count() }} passed</p>
                            @forelse($pendingChecks as $check)
                                <p class="mt-1 text-xs text-slate-500">WAIT — {{ $check['label'] }} ({{ $check['detail'] }})</p>
                            @empty
                                <p class="mt-1 text-xs text-emerald-700">All completion checks passed</p>
                            @endforelse
                        </td>
                        <td>
                            @include('admin.learning.partials.training-record-document-actions', [
                                'record' => $record,
                                'type' => $cotcType,
                                'document' => $cotc,
                                'eligibility' => $eligibility,
                            ])
                        </td>
                        <td>
                            @include('admin.learning.partials.training-record-document-actions', [
                                'record' => $record,
                                'type' => $torType,
                                'document' => $tor,
                                'eligibility' => $eligibility,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-14 text-center text-slate-500">{{ $empty }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
