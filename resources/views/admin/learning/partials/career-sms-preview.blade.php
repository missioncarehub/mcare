@php
    $preview = ($opportunity ?? null)?->graduateSmsMessage()
        ?? \App\Models\CareerOpportunity::make()->graduateSmsMessage();
@endphp
<div class="rounded-lg border border-slate-200 bg-white p-3" data-career-sms-preview>
    <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400">SMS graduates will receive</p>
    <p class="mt-2 text-sm leading-6 text-slate-800" data-career-sms-preview-text>{{ $preview }}</p>
    <p class="mt-2 text-[11px] leading-4 text-slate-400"><span data-career-sms-preview-count>{{ mb_strlen($preview) }}</span> characters. This copy greets alumni with the job offer, salary, start date, and care details above.</p>
</div>
