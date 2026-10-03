@extends('trainee.layouts.app', ['title' => 'Apply for ' . $job->listingTitle() . ' | MCARE Graduate'])

@section('content')
    @php
        $contactErrors = $errors->getBag('careerApply');
    @endphp

    <section class="mx-auto max-w-3xl space-y-6">
        <header class="border-b border-slate-200 pb-6">
            <a href="{{ route('trainee.career-hub') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-purple-700 hover:text-purple-900">
                <x-dashboard-icon name="arrow-left" class="h-4 w-4" />
                Back to Career Hub
            </a>
            <h1 class="mt-4 text-2xl font-black text-slate-950 sm:text-3xl">Apply for this career</h1>
            <p class="mt-2 text-sm text-slate-600">Submit your application and credentials for MCARE review.</p>
        </header>

        @if ($existingApplication)
            <div class="dashboard-panel space-y-4 text-center">
                <x-dashboard-icon name="circle-check" class="mx-auto h-10 w-10 text-emerald-600" />
                <h2 class="text-lg font-bold text-slate-900">You already applied for this career</h2>
                <p class="text-sm text-slate-600">MCARE administration will review your application. You can track the status in your applications page.</p>
                <div class="flex flex-wrap justify-center gap-3">
                    <a href="{{ route('trainee.applications') }}" class="primary-action inline-flex items-center gap-2">
                        <x-dashboard-icon name="clipboard-list" class="h-4 w-4" />
                        View My Applications
                    </a>
                    <a href="{{ route('trainee.career-hub') }}" class="secondary-action">Back to Career Hub</a>
                </div>
            </div>
        @else
            <article class="dashboard-panel">
                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-purple-50 text-purple-700">
                        <x-dashboard-icon name="briefcase" class="h-5 w-5" />
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-xl font-black text-slate-950">{{ $job->listingTitle() }}</h2>
                        <p class="mt-1 text-sm font-bold text-purple-700">{{ $job->listingEmployer() }}</p>
                    </div>
                </div>

                <div class="mt-4 flex flex-wrap gap-x-4 gap-y-2 text-xs font-semibold text-slate-500">
                    @if ($job->estimated_salary)
                        <span class="inline-flex items-center gap-1.5"><x-dashboard-icon name="banknote" class="h-3.5 w-3.5" />{{ $job->estimated_salary }}</span>
                    @endif
                    @if ($job->estimated_start_date)
                        <span class="inline-flex items-center gap-1.5"><x-dashboard-icon name="calendar-days" class="h-3.5 w-3.5" />Start {{ $job->estimated_start_date->format('M d, Y') }}</span>
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
                    <p class="mt-4 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $job->postingSummary() }}</p>
                @endif

                @if ($job->specific_contraptions)
                    <p class="mt-3 text-sm leading-6 text-slate-600"><span class="font-bold text-slate-900">Requirements:</span> {{ $job->specific_contraptions }}</p>
                @endif
            </article>

            <form method="POST" action="{{ route('trainee.career-hub.apply', $job) }}" enctype="multipart/form-data" class="dashboard-panel space-y-5">
                @csrf

                <h2 class="text-lg font-bold text-slate-900">Application details</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="apply-name" class="form-label">Full name</label>
                        <input id="apply-name" name="name" type="text" maxlength="120" required class="form-field" value="{{ old('name', $graduate->name) }}">
                        @error('name', 'careerApply')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="apply-email" class="form-label">Email</label>
                        <input id="apply-email" name="email" type="email" maxlength="255" required class="form-field" value="{{ old('email', $graduate->email) }}">
                        @error('email', 'careerApply')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div>
                    <label for="apply-contact" class="form-label">Contact number</label>
                    <input id="apply-contact" name="contact_number" type="text" maxlength="30" required class="form-field" value="{{ old('contact_number', $graduate->contact_number ?: $graduate->enrollmentApplication?->contact_number) }}">
                    @error('contact_number', 'careerApply')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="apply-message" class="form-label">Why are you interested in this career?</label>
                    <textarea id="apply-message" name="message" rows="4" maxlength="1000" required class="form-field" placeholder="Tell MCARE why you are a good fit for this career.">{{ old('message') }}</textarea>
                    @error('message', 'careerApply')<p class="form-error">{{ $message }}</p>@enderror
                </div>

                <div class="border-t border-slate-200 pt-5">
                    <h3 class="text-sm font-bold text-slate-900">Attach credentials <span class="font-normal text-slate-400">(optional)</span></h3>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Upload up to 5 files to support your application (certificates, training records, IDs, etc.). Accepted formats: PDF, JPG, PNG. Maximum 5 MB per file.</p>

                    <div class="mt-3">
                        <input id="apply-credentials" name="credentials[]" type="file" multiple accept=".pdf,.jpg,.jpeg,.png" class="form-field" data-credential-input>
                        @error('credentials', 'careerApply')<p class="form-error">{{ $message }}</p>@enderror
                        @error('credentials.*', 'careerApply')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <ul data-credential-list class="mt-3 space-y-2 text-sm" hidden></ul>
                </div>

                <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                    <a href="{{ route('trainee.career-hub') }}" class="secondary-action text-center">Cancel</a>
                    <button type="submit" data-action-button class="primary-action inline-flex items-center justify-center gap-2">
                        <x-dashboard-icon name="briefcase" class="h-4 w-4" />
                        Submit application
                    </button>
                </div>
            </form>
        @endif
    </section>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const input = document.querySelector('[data-credential-input]');
            const list = document.querySelector('[data-credential-list]');
            if (!input || !list) return;

            input.addEventListener('change', function () {
                list.innerHTML = '';
                const files = Array.from(input.files || []);
                list.hidden = files.length === 0;

                files.forEach(function (file) {
                    const li = document.createElement('li');
                    li.className = 'flex items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2';

                    const icon = file.type === 'application/pdf' ? 'PDF' : 'IMG';
                    const size = (file.size / 1024 / 1024).toFixed(2);

                    li.innerHTML =
                        '<span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded bg-purple-100 text-[10px] font-black text-purple-700">' + icon + '</span>' +
                        '<span class="min-w-0 flex-1 truncate font-semibold text-slate-700">' + file.name + '</span>' +
                        '<span class="text-xs text-slate-400">' + size + ' MB</span>';
                    list.appendChild(li);
                });

                if (files.length > 5) {
                    const warning = document.createElement('li');
                    warning.className = 'text-xs font-semibold text-red-600';
                    warning.textContent = 'Maximum 5 files allowed. Please remove some files.';
                    list.appendChild(warning);
                }
            });
        });
    </script>
@endsection
