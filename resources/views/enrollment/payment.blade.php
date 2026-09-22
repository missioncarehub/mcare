<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-[#f3f2f6]">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Method | MCARE</title>
    <x-site-favicon />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="enrollment-page min-h-screen bg-[#f3f2f6] font-sans text-slate-900 antialiased" data-form-draft-clear="enrollment.create">
    @php
        $fullName = preg_replace('/\s+/', ' ', trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name)) ?: 'Applicant';
        $amount = 'PHP '.number_format((float) ($application->downpayment_amount ?: $application->payment_amount ?: 0), 2);
        $batch = $application->batch;
        $activeUser = auth()->user();
        $backUrl = match($activeUser?->role) {
            'trainee' => route('trainee.payments'),
            'trainer' => route('trainer.dashboard'),
            'admin' => route('admin.enrollments.index'),
            default => route('enrollment.create'),
        };
        $backLabel = match($activeUser?->role) {
            'trainee' => 'Trainee portal',
            'trainer' => 'Trainer portal',
            'admin' => 'Admin portal',
            default => 'Enrollment',
        };
    @endphp

    <div id="action-toast" class="fixed right-5 top-5 z-50 hidden max-w-sm rounded-lg border border-amber-200 bg-amber-50 px-5 py-4 text-sm font-semibold leading-6 text-amber-800">
        Too many actions. Please wait for the current request to finish.
    </div>

    <x-public-official-header
        masthead-aside="Caregiving NC II · Official payment"
        nav-label="Enrollment payment"
        :secondary-href="route('payments.show')"
        secondary-label="Payments"
        :secondary-compact-hide="false"
        :primary-href="route('login')"
        primary-label="Sign in"
    />

    <main class="enrollment-main mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-10">
        <article class="enrollment-sheet">
            <header class="enrollment-intro">
                <p class="enrollment-kicker">Step 2 of official enrollment</p>
                <h1>Choose your payment method</h1>
                <p class="enrollment-lede">Copy your enrollment number first if you are not ready to pay. You can return later on the payments page with that number. MCARE records the result when you return from checkout or when the cashier verifies the receipt.</p>
                <dl class="enrollment-status-row">
                    <div>
                        <dt>Applicant</dt>
                        <dd>{{ $fullName }}<span>{{ $application->email }}</span></dd>
                    </div>
                    <div>
                        <dt>Program</dt>
                        <dd>{{ $application->program }}<span>{{ $batch ? $batch->name.' '.$batch->year : 'Batch to be assigned' }}</span></dd>
                    </div>
                    <div>
                        <dt>Downpayment</dt>
                        <dd>{{ $amount }}<span>{{ $application->paymentStatusLabel() }}</span></dd>
                    </div>
                </dl>
            </header>

            <div class="enrollment-form-body space-y-6">
                @if (session('payment_notice'))
                    <p class="enrollment-notice enrollment-notice-ok">{{ session('payment_notice') }}</p>
                @endif

                @if ($errors->any())
                    <p class="enrollment-notice enrollment-notice-error">{{ $errors->first('payment') ?: 'Please choose a valid payment method.' }}</p>
                @endif

                @include('enrollment.partials.enrollment-number')
                @include('enrollment.partials.payment-methods')
            </div>
        </article>
    </main>

    <x-public-official-footer note="Official enrollment payment for TESDA-accredited Caregiving NC II." />

    @include('enrollment.partials.payment-scripts')
</body>
</html>
