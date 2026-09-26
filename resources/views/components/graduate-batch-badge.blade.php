@props(['application'])

@if($application?->learning_status === \App\Models\EnrollmentApplication::LEARNING_GRADUATED)
    @php
        $graduatedBatch = trim(($application->batch->name ?? '').' '.($application->batch->year ?? ''));
    @endphp
    <span {{ $attributes->class(['inline-flex w-fit items-center rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-bold text-emerald-800 ring-1 ring-emerald-200']) }}>
        {{ $graduatedBatch !== '' ? 'Graduated in '.$graduatedBatch : 'Verified graduate' }}
    </span>
@endif
