<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application received | MCARE</title>
    <x-dashboard-theme-head />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="enrollment-page application-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased" data-form-draft-clear="applications.create">
    <x-public-official-header
        masthead-aside="Caregiving NC II · Official application"
        nav-label="Application received"
        :secondary-href="route('applications.status')"
        secondary-label="Check status"
        :primary-href="route('login')"
        primary-label="Sign in"
    />

    <main class="enrollment-main mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <article class="enrollment-sheet">
            <header class="enrollment-intro">
                <p class="enrollment-kicker">Application submitted</p>
                <h1>Keep this application number</h1>
                <p class="enrollment-lede">MCARE received your application. Save this number. You will need it to check your status and, after approval, to open the enrollment form.</p>
            </header>
            <div class="enrollment-form-body space-y-6">
                <p class="enrollment-notice enrollment-notice-ok" role="status">
                    <strong>{{ $admission->fullName() }}</strong>, your application was submitted. A copy of this number was also sent to {{ $admission->email }}.
                </p>
                <div class="rounded-xl border border-purple-200 bg-purple-50 px-5 py-6 text-center">
                    <p class="text-xs font-bold uppercase tracking-wider text-purple-700">Application number</p>
                    <p class="mt-2 font-display text-3xl font-extrabold tracking-wide text-slate-950">{{ $admission->application_number }}</p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('applications.status', ['application_number' => $admission->application_number]) }}" class="primary-action">Check status</a>
                    <a href="{{ route('landing') }}" class="secondary-action">Return to public site</a>
                </div>
            </div>
        </article>
    </main>

    <x-public-official-footer />
</body>
</html>
