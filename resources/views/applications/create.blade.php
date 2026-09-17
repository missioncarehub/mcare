<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Application | MCARE</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="enrollment-page application-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased">
    <x-public-official-header
        masthead-aside="Caregiving NC II · Official application"
        nav-label="Application"
        :secondary-href="route('applications.status')"
        secondary-label="Check status"
        :primary-href="route('login')"
        primary-label="Sign in"
    />

    <main class="enrollment-main mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <article class="enrollment-sheet">
            <header class="enrollment-intro">
                <p class="enrollment-kicker">Before enrollment</p>
                <h1>Submit a training application</h1>
                <p class="enrollment-lede">This is not the enrollment form. MCARE reviews this application first, then issues an application number. After approval, use that number on the enrollment page to open the TESDA applicant profile.</p>
                <ol class="alumni-process">
                    <li><span>1</span><span><strong>Apply.</strong> Submit your name, Gmail, and contact details.</span></li>
                    <li><span>2</span><span><strong>Keep your number.</strong> Check status anytime with the application number.</span></li>
                    <li><span>3</span><span><strong>Admin review.</strong> MCARE approves or denies the application.</span></li>
                    <li><span>4</span><span><strong>Enroll.</strong> Enter the approved number to fill out the enrollment form.</span></li>
                </ol>
            </header>

            <div class="enrollment-form-body">
                {{-- Path: resources/views/applications/create.blade.php | Label: Applicant-facing error handler --}}
                @if (session('application_error'))
                    <p class="enrollment-notice enrollment-notice-error" role="alert" data-auto-dismiss="8000">
                        {{ session('application_error') }}
                    </p>
                @endif

                @if ($programs->isEmpty())
                    <p class="enrollment-notice enrollment-notice-amber" role="alert">
                        MCARE has not published any active training program right now. Applications will reopen once the administrator activates a program.
                        You can <a href="{{ route('applications.status') }}" class="underline underline-offset-2">check the status of a previous application</a> in the meantime.
                    </p>
                @endif

                @if ($errors->first('email') === \App\Models\AdmissionApplication::EMAIL_IN_USE_MESSAGE)
                    <p class="enrollment-notice enrollment-notice-error" role="alert">
                        This Gmail has already been used for a pending or approved MCARE application.
                        <a href="{{ route('applications.status') }}" class="underline underline-offset-2">Check status</a>
                        with the application number sent to this email.
                    </p>
                @elseif ($errors->any())
                    <div class="enrollment-notice enrollment-notice-error" role="alert">
                        <p class="font-bold">We couldn't submit your application yet.</p>
                        <p class="mt-1">Please review the highlighted fields below and try again. Common issues:</p>
                        <ul class="mt-2 list-disc pl-5 text-sm">
                            <li>Use a valid Gmail address ending in <span class="font-mono">@gmail.com</span>.</li>
                            <li>Enter a working PH contact number (digits, spaces, and <span class="font-mono">+-()</span> only).</li>
                            <li>Do not include special characters like <span class="font-mono">&lt; &gt; " ' ` ; { } | \</span> in your name or notes.</li>
                            <li>Tick the privacy consent checkbox before submitting.</li>
                        </ul>
                        @if ($errors->hasBag('default'))
                            <details class="mt-3 text-xs">
                                <summary class="cursor-pointer font-bold">Show detailed error list</summary>
                                <ul class="mt-2 space-y-1">
                                    @foreach ($errors->all() as $error)
                                        <li>· {{ $error }}</li>
                                    @endforeach
                                </ul>
                            </details>
                        @endif
                    </div>
                @endif

                @php
                    $applicationCanSubmit = $programs->isNotEmpty()
                        && filled(old('first_name'))
                        && filled(old('last_name'))
                        && filled(old('email'))
                        && filled(old('contact_number'))
                        && filled(old('educational_attainment'))
                        && (string) old('privacy_consent') === '1';
                @endphp

                <form method="POST" action="{{ route('applications.store') }}" class="enrollment-form space-y-10" data-application-form>
                    @csrf
                    <section>
                        <div class="enrollment-section-heading border-b border-slate-200 pb-3">
                            <p>Applicant details</p>
                            <h2>Identity and contact</h2>
                        </div>
                        <div class="mt-5 grid gap-4 md:grid-cols-3">
                            <div>
                                <label class="form-label" for="first_name">First name</label>
                                <input class="form-field" id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                                @error('first_name')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label" for="middle_name">Middle name</label>
                                <input class="form-field" id="middle_name" name="middle_name" value="{{ old('middle_name') }}">
                                @error('middle_name')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label" for="last_name">Last name</label>
                                <input class="form-field" id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                                @error('last_name')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label" for="email">Gmail address</label>
                                <input class="form-field" id="email" name="email" type="email" value="{{ old('email') }}" required>
                                <p class="mt-1 text-xs text-slate-500">Use the same Gmail later for enrollment and Google sign-in.</p>
                                @error('email')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label" for="contact_number">Contact number</label>
                                <input class="form-field" id="contact_number" name="contact_number" value="{{ old('contact_number') }}" required>
                                @error('contact_number')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label" for="training_program_id">Program</label>
                                @if ($programs->isNotEmpty())
                                    <select class="form-field" id="training_program_id" name="training_program_id" required>
                                        @foreach ($programs as $program)
                                            <option value="{{ $program->id }}" @selected((int) old('training_program_id', $selectedProgramId) === $program->id)>{{ $program->name }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-slate-500">Choose the TESDA program you are applying for.</p>
                                @else
                                    <p class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">No training programs are open for application right now. Please contact MCARE or try again later.</p>
                                @endif
                                @error('training_program_id')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="form-label" for="schedule_preference">Preferred schedule</label>
                                <select class="form-field" id="schedule_preference" name="schedule_preference">
                                    <option value="">No preference yet</option>
                                    <option value="AM" @selected(old('schedule_preference') === 'AM')>AM</option>
                                    <option value="PM" @selected(old('schedule_preference') === 'PM')>PM</option>
                                    <option value="Weekend" @selected(old('schedule_preference') === 'Weekend')>Weekend</option>
                                </select>
                                @error('schedule_preference')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="md:col-span-2">
                                <label class="form-label" for="educational_attainment">Highest educational attainment</label>
                                <select class="form-field" id="educational_attainment" name="educational_attainment" required>
                                    <option value="">Select</option>
                                    @foreach (\App\Models\AdmissionApplication::educationalAttainmentOptions() as $option)
                                        <option value="{{ $option }}" @selected(old('educational_attainment') === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @error('educational_attainment')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                            <div class="md:col-span-3">
                                <label class="form-label" for="notes">Additional note (optional)</label>
                                <textarea class="form-field min-h-24" id="notes" name="notes" maxlength="500">{{ old('notes') }}</textarea>
                                @error('notes')<p class="form-error">{{ $message }}</p>@enderror
                            </div>
                        </div>
                    </section>

                    <section>
                        <label class="flex items-start gap-3 text-sm leading-6 text-slate-700">
                            <input class="mt-1" type="checkbox" name="privacy_consent" value="1" @checked(old('privacy_consent')) required>
                            <span>I confirm that these details are mine and that MCARE may use them to review this application and contact me about enrollment.</span>
                        </label>
                        @error('privacy_consent')<p class="form-error">{{ $message }}</p>@enderror
                    </section>

                    <div class="enrollment-submit-row">
                        <button type="submit" class="primary-action" data-application-submit @disabled(! $applicationCanSubmit || $programs->isEmpty())>Submit application</button>
                        <a href="{{ route('applications.status') }}" class="secondary-action application-number-link-in-card">I already have an application number</a>
                    </div>
                </form>
            </div>
        </article>
        <p class="application-number-link-after-card">
            <a href="{{ route('applications.status') }}" class="secondary-action">I already have an application number</a>
        </p>
    </main>

    <x-public-official-footer note="Application numbers are issued after this form is submitted. Enrollment opens only after administrator approval." />
</body>
</html>
