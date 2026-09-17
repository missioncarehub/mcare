@extends('admin.layouts.app', ['title' => 'Review Application | MCARE Admin'])

@section('content')
    @php
        $badgeClasses = [
            'profile_submitted' => 'bg-sky-50 text-sky-700 ring-sky-100',
            'pre_enlistment' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'approved' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'denied' => 'bg-red-50 text-red-700 ring-red-100',
        ];

        $paymentBadgeClasses = [
            'not_selected' => 'bg-slate-50 text-slate-700 ring-slate-100',
            'onsite_pending' => 'bg-amber-50 text-amber-700 ring-amber-100',
            'online_pending' => 'bg-purple-50 text-purple-700 ring-purple-100',
            'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            'expired' => 'bg-red-50 text-red-700 ring-red-100',
        ];

        $defaultDecision = in_array($application->status, $reviewableStatuses, true)
            ? $application->status
            : 'pre_enlistment';

        $documentsReadyForApproval = $pendingDocumentApprovals === [];
        $paymentReadyForApproval = $application->hasEnrollmentPaymentClearance();
        $documentReviewSubmitted = $application->documents_reviewed_at !== null || filled($application->document_review);
        $documentsReviewed = $documentsReadyForApproval && $application->documents_reviewed_at !== null;
        $documentReviewRequiredNotice = $errors->has('status') && ! $documentsReviewed;

        $personalFields = [
            'Full name' => trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name),
            'Email' => $application->email,
            'Contact number' => $application->contact_number,
            'Birthdate' => $application->birth_date?->format('M d, Y'),
            'Birthplace' => collect([$application->birthplace_city, $application->birthplace_province, $application->birthplace_region])->filter()->join(', '),
            'Sex' => $application->gender,
            'Civil status' => $application->civil_status,
            'Nationality' => $application->nationality,
            'Employment status' => $application->employment_status,
            'Employment type' => $application->employment_type,
        ];

        $addressFields = [
            'Street' => $application->street,
            'Barangay' => $application->barangay,
            'City/Municipality' => $application->city,
            'Province' => $application->province,
            'Region' => $application->region,
            'ZIP code' => $application->zip_code,
        ];

        $educationFields = [
            'Educational attainment' => $application->educational_attainment,
            'School name' => $application->school_name,
            'Year graduated' => $application->year_graduated ?? 'N/A',
            'Guardian name' => $application->guardian_name,
            'Guardian address' => $application->guardian_address,
        ];

        $classificationFields = [
            'Client classification' => $application->classification,
            'Disability type' => $application->disability_type,
            'Disability cause' => $application->disability_cause,
            'Scholarship package' => $application->scholarship_type,
            'Signature name' => $application->signature_name,
            'Date accomplished' => $application->date_accomplished?->format('M d, Y'),
        ];
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('admin.enrollments.index') }}" class="secondary-action">
                Back to queue
            </a>
            <form method="POST" action="{{ route('admin.enrollments.destroy', $application) }}" data-confirm-title="{{ $application->accountDeletionTitle() }}" data-confirm="{{ $application->accountDeletionMessage() }}" @if($application->accountDeletionDetail()) data-confirm-detail="{{ $application->accountDeletionDetail() }}" @endif data-confirm-action="{{ $application->accountDeletionAction() }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-700 transition hover:bg-red-50" title="{{ $application->accountDeletionAction() }}">
                    <x-dashboard-icon name="trash-2" class="h-3.5 w-3.5" />
                    Delete
                </button>
            </form>
        </div>
        <span class="dashboard-pill {{ $badgeClasses[$application->status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
            {{ $application->statusLabel() }}
        </span>
    </div>

    <section class="grid grid-cols-1 gap-6 lg:grid-cols-[1fr_22rem]">
        <div class="space-y-6">
            <section class="border border-slate-200 bg-white p-6">
                <div class="flex items-center gap-4">
                    <x-user-avatar :user="$application->user" :application="$application" :use-enrollment-photo="true" class="grid h-16 w-16 place-items-center bg-purple-100 text-xl font-black text-purple-800" />
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase tracking-wider text-purple-700">{{ $application->program ?: 'Training program' }}</p>
                        <h1 class="mt-1 truncate text-2xl font-bold text-slate-950">{{ $application->last_name }}, {{ $application->first_name }}</h1>
                    </div>
                </div>
                <dl class="mt-6 grid gap-x-8 gap-y-4 sm:grid-cols-3">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Schedule</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $application->schedule_preference }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Submitted</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $application->created_at?->format('M d, Y g:i A') }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Account status</dt>
                        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ str($application->user?->applicant_status ?? 'No account')->headline() }}</dd>
                    </div>
                </dl>
            </section>

            <section class="border border-slate-200 bg-white p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-purple-700">Payment</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-950">Payment selection</h2>
                    </div>
                    <span class="dashboard-pill {{ $paymentBadgeClasses[$application->payment_status] ?? 'bg-slate-50 text-slate-700 ring-slate-100' }}">
                        {{ $application->paymentStatusLabel() }}
                    </span>
                </div>
                <dl class="mt-6 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                    @foreach ([
                        'Enrollment number' => $application->enrollment_number,
                        'Method' => $application->payment_method ? str($application->payment_method)->headline() : 'Not selected',
                        'Amount' => $application->payment_currency.' '.number_format((float) $application->payment_amount, 2),
                        'Batch' => $application->batch ? $application->batch->name.' '.$application->batch->year : 'Unassigned',
                        'Class schedule' => $application->batch?->scheduleLabelFor($application->schedule_preference),
                        'Room destination' => $application->batch?->roomFor($application->schedule_preference),
                        'Enrollment deadline' => $application->batch?->enrollment_ends_at?->format('M d, Y g:i A'),
                        'Reference' => $application->latestPaymentReference() ?: $application->payment_reference,
                        'Official Receipt (OR)' => $application->payment_receipt_number,
                        'Receipt expires' => $application->effectivePaymentDeadline()?->format('M d, Y g:i A'),
                        'PayMongo payment' => $application->paymongoPaymentId(),
                        'PayMongo checkout' => $application->paymongo_checkout_reference,
                    ] as $label => $value)
                        <div>
                            <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 break-all text-sm font-semibold text-slate-900">{{ filled($value) ? $value : '—' }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>

            @foreach ([
                'Personal information' => $personalFields,
                'Permanent mailing address' => $addressFields,
                'Education and guardian' => $educationFields,
                'TESDA classification' => $classificationFields,
            ] as $sectionTitle => $fields)
                <section class="border border-slate-200 bg-white p-6">
                    <h2 class="text-xl font-bold text-slate-950">{{ $sectionTitle }}</h2>
                    <dl class="mt-6 grid gap-x-8 gap-y-4 sm:grid-cols-2">
                        @foreach ($fields as $label => $value)
                            <div>
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">{{ $label }}</dt>
                                <dd class="mt-1 text-sm font-semibold text-slate-900">{{ filled($value) ? $value : '—' }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </section>
            @endforeach

            <section id="document-review" class="border border-slate-200 bg-white p-6">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-purple-700">Applicant documents</p>
                        <h2 class="mt-1 text-xl font-bold text-slate-950">Documents and feedback</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600">Open the document review page to preview files, record status and feedback, then return here to save the enrollment decision.</p>
                    </div>
                    <span class="dashboard-pill {{ $documentsReadyForApproval ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }}">
                        {{ $documentsReadyForApproval ? 'All accepted' : count($pendingDocumentApprovals).' pending' }}
                    </span>
                </div>
                <div class="mt-5">
                    <a href="{{ route('admin.enrollments.document-review', $application) }}" class="primary-action">
                        Review documents
                    </a>
                    @if($documentsReviewed)
                        <p class="mt-3 text-xs leading-5 text-slate-500">Last reviewed {{ $application->documents_reviewed_at->format('M d, Y g:i A') }} by {{ $application->documentReviewer?->name ?? 'Admin' }}. Open the review page again if you need to change a file decision.</p>
                    @elseif(! $documentsReadyForApproval)
                        <p class="mt-3 text-xs leading-5 text-amber-700">Pending: {{ implode(', ', $pendingDocumentApprovals) }}. Accept every required document before review can be completed.</p>
                    @endif
                </div>
            </section>
        </div>

        <aside class="space-y-6">
            <section class="border border-slate-200 bg-white p-6">
                <p class="text-xs font-bold uppercase tracking-wider text-purple-700">Official TESDA form</p>
                <h2 class="mt-2 text-xl font-bold text-slate-950">Registration Form MIS 03-01</h2>
                <p class="mt-3 text-sm leading-6 text-slate-600">Applicant answers, ID photo, and e-signature are placed on the original two-page TESDA form. The registrar name and signature come from <a href="{{ route('account.settings') }}#tesda-registrar" class="font-semibold text-purple-700 hover:text-purple-800">Settings</a>.</p>
                <div class="mt-5 grid grid-cols-1 gap-3">
                    <a href="{{ route('admin.enrollments.tesda-form', [$application, 'disposition' => 'inline']) }}" target="_blank" rel="noopener" class="primary-action w-full text-center">
                        Preview / Print form
                    </a>
                    <a href="{{ route('admin.enrollments.tesda-form', [$application, 'disposition' => 'attachment']) }}" class="secondary-action w-full text-center">
                        Download PDF
                    </a>
                </div>
            </section>

            <section class="border border-slate-200 bg-white p-6">
                <p class="text-xs font-bold uppercase tracking-wider text-purple-700">Review decision</p>
                <h2 class="mt-2 text-xl font-bold text-slate-950">Update application</h2>

                <dl class="mt-5 space-y-3 border-y border-slate-200 py-4 text-sm">
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-600">Document review</dt>
                        <dd class="text-right font-semibold {{ $documentsReviewed ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $documentsReviewed ? 'Completed' : ($documentReviewSubmitted ? 'Incomplete' : 'Not reviewed') }}
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-600">Required documents</dt>
                        <dd class="text-right font-semibold {{ $documentsReadyForApproval ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $documentsReadyForApproval ? 'All accepted' : count($pendingDocumentApprovals).' pending' }}
                        </dd>
                    </div>
                    <div class="flex items-start justify-between gap-3">
                        <dt class="text-slate-600">Enrollment payment</dt>
                        <dd class="text-right font-semibold {{ $paymentReadyForApproval ? 'text-emerald-700' : 'text-amber-700' }}">
                            {{ $paymentReadyForApproval ? 'Verified' : 'Not verified' }}
                        </dd>
                    </div>
                </dl>
                @if (! $documentsReviewed)
                    <p class="mt-3 text-xs leading-5 text-amber-700">
                        @if (! $documentsReadyForApproval)
                            Document review stays incomplete while required documents are pending. Accept every file before it can be completed.
                        @else
                            Review documents first, then return here to save a decision.
                        @endif
                    </p>
                @endif
                <p class="mt-3 text-xs leading-5 text-slate-500">Approved or denied decisions email a verification link. The enrollee can log in only after that email is verified.</p>

                <form method="POST" action="{{ route('admin.enrollments.update', $application) }}" class="mt-6 space-y-4" data-enrollment-decision-form data-documents-reviewed="{{ $documentsReviewed ? '1' : '0' }}" data-documents-review-submitted="{{ $documentReviewSubmitted ? '1' : '0' }}" data-documents-pending="{{ $documentsReadyForApproval ? '0' : '1' }}">
                    @csrf
                    @method('PATCH')

                    <div id="document-review-required-notice"
                        class="border border-amber-200 bg-amber-50 p-4"
                        role="alert"
                        tabindex="-1"
                        @unless($documentReviewRequiredNotice) hidden @endunless>
                        <p class="text-sm font-bold text-amber-950" data-document-review-notice-title>
                            @if (! $documentsReadyForApproval)
                                Document review cannot be completed while required documents are pending.
                            @else
                                Review the applicant documents first before saving a decision.
                            @endif
                        </p>
                        <p class="mt-2 text-sm leading-6 text-amber-900" data-document-review-notice-body>
                            @if (! $documentsReadyForApproval)
                                Pending: {{ implode(', ', $pendingDocumentApprovals) }}. Open the document review page and mark every required file as Accepted.
                            @else
                                Open the document review page, preview the files, then finish that review. Approval requires every required document to be accepted.
                            @endif
                        </p>
                        <a href="{{ route('admin.enrollments.document-review', $application) }}" class="primary-action mt-3">
                            Review documents
                        </a>
                    </div>

                    <div>
                        <label for="status" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Decision</label>
                        <select id="status" name="status" required class="w-full border border-slate-200 bg-white px-3 py-2 text-sm outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600">
                            @foreach ($reviewableStatuses as $status)
                                <option value="{{ $status }}" @selected(old('status', $defaultDecision) === $status)>{{ $statuses[$status] }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="admin_notes" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-500">Admin notes</label>
                        <textarea id="admin_notes" name="admin_notes" rows="6" maxlength="2000" class="min-h-28 w-full border border-slate-200 bg-white px-3 py-2 text-sm leading-6 outline-none focus:border-purple-600 focus:ring-1 focus:ring-purple-600" placeholder="Add document requirements, pre-enlistment instructions, or denial reason.">{{ old('admin_notes', $application->admin_notes) }}</textarea>
                        <p class="mt-2 text-xs leading-5 text-slate-500">A note is required when denying an application. Review documents first, then save the decision. Approval also requires verified payment and all five required documents accepted.</p>
                        @error('admin_notes') <p class="mt-2 text-sm font-semibold text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <button type="submit" class="primary-action w-full">
                        Save decision
                    </button>
                </form>
            </section>

            <section class="border border-slate-200 bg-white p-6">
                <p class="text-xs font-bold uppercase tracking-wider text-purple-700">Review trail</p>
                <dl class="mt-5 grid gap-4 text-sm">
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Reviewed by</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $application->reviewer?->name ?? 'Not reviewed yet' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Reviewed at</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $application->reviewed_at?->format('M d, Y g:i A') ?? 'Not reviewed yet' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Privacy consent</dt>
                        <dd class="mt-1 font-semibold text-slate-900">{{ $application->privacy_consent ? 'Accepted' : 'Not accepted' }}</dd>
                    </div>
                </dl>
            </section>
        </aside>
    </section>

    <script>
        (() => {
            const form = document.querySelector('[data-enrollment-decision-form]');
            const notice = document.getElementById('document-review-required-notice');
            if (!form || !notice) return;

            const message = form.dataset.documentsPending === '1'
                ? 'Document review cannot be completed while required documents are pending. Accept every required document first.'
                : 'Review the applicant documents first before saving a decision.';

            const showNotice = (text) => {
                const title = notice.querySelector('[data-document-review-notice-title]');
                const body = notice.querySelector('[data-document-review-notice-body]');
                if (title) title.textContent = text;
                if (body && form.dataset.documentsPending === '1') {
                    body.textContent = 'Open the document review page and mark every required file as Accepted before this can show as Completed.';
                }

                notice.hidden = false;
                notice.focus({ preventScroll: true });
                notice.scrollIntoView({ behavior: 'smooth', block: 'center' });

                if (document.querySelector('[data-document-review-toast]')) return;

                const toast = document.createElement('div');
                toast.className = 'dashboard-toast dashboard-toast-error';
                toast.dataset.documentReviewToast = '1';
                toast.setAttribute('role', 'alert');
                toast.style.position = 'fixed';
                toast.style.top = '1rem';
                toast.style.right = '1rem';
                toast.style.left = 'auto';
                toast.style.zIndex = '90';
                toast.style.width = 'min(24rem, calc(100vw - 2rem))';
                toast.style.maxWidth = 'min(24rem, calc(100vw - 2rem))';
                toast.style.pointerEvents = 'auto';

                const toastBody = document.createElement('div');
                toastBody.className = 'dashboard-toast-body';
                const paragraph = document.createElement('p');
                paragraph.textContent = text;
                toastBody.appendChild(paragraph);
                toast.appendChild(toastBody);
                document.body.appendChild(toast);

                window.setTimeout(() => toast.remove(), 8000);
            };

            form.addEventListener('submit', (event) => {
                const status = form.querySelector('#status')?.value;
                const reviewCompleted = form.dataset.documentsReviewed === '1';
                const reviewSubmitted = form.dataset.documentsReviewSubmitted === '1';

                if (status === 'approved' && ! reviewCompleted) {
                    event.preventDefault();
                    showNotice(message);
                    return;
                }

                if (! reviewSubmitted) {
                    event.preventDefault();
                    showNotice('Review the applicant documents first before saving a decision.');
                }
            });
        })();
    </script>
@endsection
