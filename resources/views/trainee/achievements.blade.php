@extends('trainee.layouts.app', ['title' => 'Career History | MCARE Graduate'])

@section('content')
    <section class="space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <p class="dashboard-section-kicker">Career record</p>
            <h1 class="text-2xl font-black text-slate-950 sm:text-3xl">Career history</h1>
            <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">Approved careers appear here first as a pending record. Upload your placement certificate for MCARE review. Once the certificate is approved, the career is fully awarded in your history.</p>
        </header>

        <div class="grid gap-4 lg:grid-cols-2">
            @forelse ($achievements as $achievement)
                @php $job = $achievement->opportunity; @endphp
                <article class="dashboard-panel {{ $achievement->isCareerAwarded() ? 'ring-1 ring-emerald-100' : '' }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wide text-purple-700">
                                {{ $achievement->isCareerAwarded() ? 'Career awarded' : 'Career record' }}
                            </p>
                            <h2 class="mt-1 text-xl font-black text-slate-950">{{ $job?->listingTitle() ?? 'Career opportunity' }}</h2>
                        </div>
                        @if ($achievement->isCareerAwarded())
                            <span class="dashboard-pill bg-emerald-50 text-emerald-800 ring-emerald-200">Awarded</span>
                        @elseif ($achievement->certificateIsPendingReview())
                            <span class="dashboard-pill bg-amber-50 text-amber-800 ring-amber-200">Certificate under review</span>
                        @elseif ($achievement->certificate_status === \App\Models\CareerInquiry::CERT_REJECTED)
                            <span class="dashboard-pill bg-red-50 text-red-800 ring-red-200">Resubmit certificate</span>
                        @else
                            <span class="dashboard-pill bg-sky-50 text-sky-800 ring-sky-200">Certificate required</span>
                        @endif
                    </div>

                    <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Salary</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $job?->estimated_salary ?: 'See Career Hub' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Start</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $job?->estimated_start_date?->format('M d, Y') ?: 'To be announced' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">Placement</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ $job?->listingEmployer() ?? 'MCARE-Coordinated Placement' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wide text-slate-500">{{ $achievement->isCareerAwarded() ? 'Awarded' : 'Approved' }}</dt>
                            <dd class="mt-1 font-semibold text-slate-900">{{ ($achievement->isCareerAwarded() ? $achievement->certificate_reviewed_at : $achievement->reviewed_at)?->format('M d, Y') ?: $achievement->created_at?->format('M d, Y') }}</dd>
                        </div>
                    </dl>

                    @if ($achievement->needsCertificateUpload())
                        <div class="mt-4 rounded-lg border border-sky-100 bg-sky-50 p-4">
                            <p class="text-sm font-bold text-sky-950">Upload your placement certificate</p>
                            <p class="mt-1 text-sm leading-6 text-sky-900/80">Submit proof that you completed this career placement. MCARE will review it before this record is fully awarded.</p>
                            @if ($achievement->certificate_status === \App\Models\CareerInquiry::CERT_REJECTED && $achievement->certificate_admin_notes)
                                <div class="mt-3 rounded-lg border border-red-200 bg-white px-3 py-2 text-sm text-red-800">
                                    <span class="font-bold">Admin remarks:</span> {{ $achievement->certificate_admin_notes }}
                                </div>
                            @endif
                            <form method="POST" action="{{ route('trainee.achievements.upload-certificate', $achievement) }}" enctype="multipart/form-data" class="mt-4 space-y-3">
                                @csrf
                                <div>
                                    <label for="placement-certificate-{{ $achievement->id }}" class="form-label">Placement certificate</label>
                                    <input id="placement-certificate-{{ $achievement->id }}" name="placement_certificate" type="file" accept=".pdf,.jpg,.jpeg,.png" required class="form-field">
                                    <p class="mt-1 text-xs text-slate-500">PDF, JPG, or PNG. Maximum 5 MB.</p>
                                    @error('placement_certificate')<p class="form-error">{{ $message }}</p>@enderror
                                </div>
                                <button type="submit" data-action-button class="primary-action inline-flex items-center gap-2 text-sm">
                                    <x-dashboard-icon name="file-text" class="h-4 w-4" />
                                    Submit certificate
                                </button>
                            </form>
                        </div>
                    @elseif ($achievement->certificateIsPendingReview())
                        <div class="mt-4 rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-sm leading-6 text-amber-950">
                            Your placement certificate was submitted and is waiting for MCARE review.
                            @if ($achievement->placement_certificate)
                                <a href="{{ route('trainee.achievements.placement-certificate', $achievement) }}" target="_blank" class="mt-2 inline-flex items-center gap-1 font-bold text-amber-900 underline decoration-amber-300 hover:text-amber-950">
                                    <x-dashboard-icon name="file-text" class="h-4 w-4" />
                                    View submitted certificate
                                </a>
                            @endif
                        </div>
                    @elseif ($achievement->isCareerAwarded())
                        <div class="mt-4 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm leading-6 text-emerald-950">
                            This career is fully awarded in your MCARE career history.
                            @if ($achievement->placement_certificate)
                                <a href="{{ route('trainee.achievements.placement-certificate', $achievement) }}" target="_blank" class="mt-2 inline-flex items-center gap-1 font-bold text-emerald-900 underline decoration-emerald-300 hover:text-emerald-950">
                                    <x-dashboard-icon name="file-text" class="h-4 w-4" />
                                    View awarded certificate
                                </a>
                            @endif
                        </div>
                    @endif

                    @if ($job?->postingSummary())
                        <p class="mt-4 text-sm leading-6 text-slate-600">{{ $job->postingSummary() }}</p>
                    @endif
                </article>
            @empty
                <div class="dashboard-panel py-16 text-center lg:col-span-2">
                    <x-dashboard-icon name="award" class="mx-auto h-9 w-9 text-slate-300" />
                    <h2 class="mt-4 text-lg font-bold text-slate-900">No career history yet</h2>
                    <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Apply for a career from the Career Hub. Once MCARE approves your application, it will appear here so you can submit your placement certificate.</p>
                    <a href="{{ route('trainee.career-hub') }}" class="primary-action mt-5 inline-flex">Open Career Hub</a>
                </div>
            @endforelse
        </div>

        @if ($achievements->hasPages())
            <div>{{ $achievements->links() }}</div>
        @endif
    </section>
@endsection
