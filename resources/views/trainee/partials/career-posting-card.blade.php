<article class="dashboard-panel flex h-full flex-col p-5 sm:p-6">
    <div class="flex items-start justify-between gap-4">
        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-700">
            <x-dashboard-icon name="briefcase" class="h-5 w-5" />
        </span>
        <span class="bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-700">Open opportunity</span>
    </div>

    <h3 class="mt-5 text-xl font-black text-slate-950">{{ $job->listingTitle() }}</h3>
    <p class="mt-2 text-sm font-bold text-purple-700">{{ $job->listingEmployer() }}</p>

    <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs font-semibold text-slate-500">
        @if ($job->estimated_salary)
            <span class="inline-flex items-center gap-1.5"><x-dashboard-icon name="banknote" class="h-3.5 w-3.5" />{{ $job->estimated_salary }}</span>
        @endif
        @if ($job->estimated_start_date)
            <span class="inline-flex items-center gap-1.5"><x-dashboard-icon name="calendar-days" class="h-3.5 w-3.5" />Start {{ $job->estimated_start_date->format('M d, Y') }}</span>
        @endif
        @if ($job->location)
            <span class="inline-flex items-center gap-1.5"><x-dashboard-icon name="location-dot" class="h-3.5 w-3.5" />{{ $job->location }}</span>
        @endif
        @if (filled($job->patient_gender))
            <span class="inline-flex items-center gap-1.5"><x-dashboard-icon name="users" class="h-3.5 w-3.5" />{{ $job->patientGenderLabel() }}</span>
        @endif
        @if (filled($job->mobility_status))
            <span class="inline-flex items-center gap-1.5"><x-dashboard-icon name="clipboard-list" class="h-3.5 w-3.5" />{{ $job->mobilityStatusLabel() }}</span>
        @endif
        @if ($job->patient_age !== null)
            <span class="inline-flex items-center gap-1.5">Age {{ $job->patient_age }}</span>
        @endif
    </div>

    @if ($job->postingSummary())
        <p class="mt-5 flex-1 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $job->postingSummary() }}</p>
    @endif

    @if ($job->specific_contraptions)
        <p class="mt-4 {{ $job->postingSummary() ? 'border-t border-slate-100 pt-4' : '' }} text-sm leading-6 text-slate-600"><span class="font-bold text-slate-900">Requirements:</span> {{ $job->specific_contraptions }}</p>
    @endif

    <div class="mt-6 {{ $job->postingSummary() || $job->specific_contraptions ? '' : 'flex-1' }} flex w-full flex-col gap-2 sm:flex-row sm:flex-wrap">
        @unless ($isAdminPreview ?? false)
            @if (in_array((int) $job->id, array_map('intval', $contactedJobIds ?? []), true))
                <span class="secondary-action inline-flex items-center text-sm">Inquiry sent</span>
            @else
                <button type="button" data-dashboard-dialog-open="career-contact-{{ $job->id }}" class="secondary-action inline-flex items-center text-sm">Contact MCARE for details</button>
            @endif
        @else
            <span class="secondary-action inline-flex items-center text-sm">Contact MCARE for details</span>
        @endunless
    </div>

    @unless ($isAdminPreview ?? false)
        @php
            $contactUser = auth()->user()?->loadMissing('enrollmentApplication');
            $contactErrors = $errors->getBag('careerContact');
            $openContactForm = $contactErrors->any() && (int) old('inquiry_job_id') === (int) $job->id;
        @endphp
        <dialog id="career-contact-{{ $job->id }}" data-dashboard-dialog data-auto-open="{{ $openContactForm ? 'true' : 'false' }}" class="m-auto max-h-[90vh] w-[min(96vw,32rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="career-contact-title-{{ $job->id }}">
            <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
                <div>
                    <h2 id="career-contact-title-{{ $job->id }}" class="font-display text-xl font-bold text-slate-900">Contact MCARE</h2>
                    <p class="mt-1 text-xs text-slate-500">This inquiry is saved for administration against {{ $job->listingTitle() }}.</p>
                </div>
                <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Close contact form"><x-dashboard-icon name="xmark" class="h-4 w-4" /></button>
            </div>
            <form method="POST" action="{{ route('trainee.career-hub.contact', $job) }}" class="grid gap-4 p-6" data-dashboard-dialog-form data-submit-label="Sending inquiry...">
                @csrf
                <input type="hidden" name="inquiry_job_id" value="{{ $job->id }}">
                <div>
                    <label for="career-contact-name-{{ $job->id }}" class="form-label">Full name</label>
                    <input id="career-contact-name-{{ $job->id }}" name="name" type="text" maxlength="120" required class="form-field" value="{{ old('name', $contactUser?->name) }}">
                    @if ($openContactForm) @error('name', 'careerContact')<p class="form-error">{{ $message }}</p>@enderror @endif
                </div>
                <div>
                    <label for="career-contact-email-{{ $job->id }}" class="form-label">Email</label>
                    <input id="career-contact-email-{{ $job->id }}" name="email" type="email" maxlength="255" required class="form-field" value="{{ old('email', $contactUser?->email) }}">
                    @if ($openContactForm) @error('email', 'careerContact')<p class="form-error">{{ $message }}</p>@enderror @endif
                </div>
                <div>
                    <label for="career-contact-number-{{ $job->id }}" class="form-label">Contact number</label>
                    <input id="career-contact-number-{{ $job->id }}" name="contact_number" type="text" maxlength="30" required class="form-field" value="{{ old('contact_number', $contactUser?->contact_number ?: $contactUser?->enrollmentApplication?->contact_number) }}">
                    @if ($openContactForm) @error('contact_number', 'careerContact')<p class="form-error">{{ $message }}</p>@enderror @endif
                </div>
                <div>
                    <label for="career-contact-message-{{ $job->id }}" class="form-label">Message</label>
                    <textarea id="career-contact-message-{{ $job->id }}" name="message" rows="4" maxlength="1000" required class="form-field" placeholder="Tell MCARE why you are interested in this career.">{{ $openContactForm ? old('message') : '' }}</textarea>
                    @if ($openContactForm) @error('message', 'careerContact')<p class="form-error">{{ $message }}</p>@enderror @endif
                </div>
                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                    <button type="submit" data-action-button class="primary-action">Send inquiry</button>
                </div>
            </form>
        </dialog>
    @endunless
</article>
