@extends('admin.layouts.app', ['title' => 'User Accounts | MCARE Admin'])

@section('content')
@php
    $trainerErrors = $errors->getBag('trainer');
    $traineeErrors = $errors->getBag('trainee');
    $currentRole = $roleFilter ?? 'all';
    $searchQuery = $search ?? '';
@endphp

<section class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <p class="max-w-3xl text-sm leading-6 text-slate-600">
            Manage verified staff access, assisted onsite trainees, and applicants released for review.
        </p>
        <div class="flex flex-wrap gap-2">
            <button type="button" data-dashboard-dialog-open="trainer-account-dialog" class="secondary-action inline-flex items-center justify-center gap-2">
                <x-dashboard-icon name="plus" class="h-4 w-4" />Add trainer
            </button>
            <button type="button" data-dashboard-dialog-open="trainee-account-dialog" class="secondary-action inline-flex items-center justify-center gap-2">
                <x-dashboard-icon name="plus" class="h-4 w-4" />Assisted trainee intake
            </button>
            <a href="{{ route('admin.historical-alumni.index') }}" class="primary-action inline-flex items-center justify-center gap-2">
                <x-dashboard-icon name="user-check" class="h-4 w-4" />Alumni claims
            </a>
        </div>
    </header>

    <!-- Role Filter Tabs & Search Bar -->
    <div class="flex flex-col gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap gap-1.5" role="tablist" aria-label="Filter accounts by role">
            <a href="{{ route('admin.accounts.index', ['role' => 'all', 'search' => $searchQuery]) }}"
               class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $currentRole === 'all' ? 'bg-purple-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                All Accounts ({{ $counts['all'] ?? 0 }})
            </a>
            <a href="{{ route('admin.accounts.index', ['role' => 'trainer', 'search' => $searchQuery]) }}"
               class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $currentRole === 'trainer' ? 'bg-indigo-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Trainers ({{ $counts['trainer'] ?? 0 }})
            </a>
            <a href="{{ route('admin.accounts.index', ['role' => 'trainee', 'search' => $searchQuery]) }}"
               class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $currentRole === 'trainee' ? 'bg-emerald-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Trainees ({{ $counts['trainee'] ?? 0 }})
            </a>
            <a href="{{ route('admin.accounts.index', ['role' => 'alumni', 'search' => $searchQuery]) }}"
               class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $currentRole === 'alumni' ? 'bg-purple-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Alumni ({{ $counts['alumni'] ?? 0 }})
            </a>
            <a href="{{ route('admin.accounts.index', ['role' => 'applicant', 'search' => $searchQuery]) }}"
               class="rounded-xl px-4 py-2 text-xs font-bold transition {{ $currentRole === 'applicant' ? 'bg-amber-700 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' }}">
                Applicants ({{ $counts['applicant'] ?? 0 }})
            </a>
        </div>

        <form method="GET" action="{{ route('admin.accounts.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="role" value="{{ $currentRole }}">
            <div class="relative">
                <input type="text" name="search" value="{{ $searchQuery }}" placeholder="Search name, email..."
                       class="form-field w-56 text-xs pl-8 pr-3 py-1.5">
                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400">
                    <x-dashboard-icon name="search" class="h-3.5 w-3.5" />
                </span>
            </div>
            <button type="submit" class="secondary-action text-xs py-1.5 px-3">Search</button>
            @if(filled($searchQuery))
                <a href="{{ route('admin.accounts.index', ['role' => $currentRole]) }}" class="text-xs text-slate-500 hover:text-slate-900 underline">Clear</a>
            @endif
        </form>
    </div>

    <!-- Accounts Table -->
    <div class="dashboard-table-wrap overflow-x-auto">
        <table class="dashboard-table w-full min-w-[66rem]">
            <thead>
                <tr>
                    <th>Account / Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Batch / Progress Status</th>
                    <th>Verification</th>
                    <th>Created</th>
                    <th class="text-right">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($accounts as $account)
                    @php
                        $app = $account->enrollmentApplication;
                        $roleBadge = match($account->role) {
                            'trainer' => 'bg-indigo-50 text-indigo-700 ring-indigo-100',
                            'trainee' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                            'applicant' => 'bg-amber-50 text-amber-800 ring-amber-100',
                            default => 'bg-purple-50 text-purple-700 ring-purple-100',
                        };
                        $isAlumni = $account->isGraduate();
                        if ($isAlumni) $roleBadge = 'bg-purple-50 text-purple-700 ring-purple-100';
                        $deleteTitle = $app?->accountDeletionTitle() ?: 'Delete this account?';
                        $deleteMessage = $app?->accountDeletionMessage() ?: "Permanently delete account for '".($account->name ?: $account->email)."' (".str($account->role)->headline().')? All related enrollment applications, payment records, uploaded documents, and learning history will be permanently deleted, allowing them to re-enroll if needed.';
                        $deleteDetail = $app?->accountDeletionDetail();
                        $deleteAction = $app?->accountDeletionAction() ?: 'Delete account';
                    @endphp
                    <tr>
                        <td>
                            <div class="flex items-center gap-3">
                                <x-user-avatar :user="$account" :application="$app" :use-enrollment-photo="true" class="grid h-10 w-10 place-items-center rounded-full bg-purple-100 text-sm font-black text-purple-800" />
                                <div class="min-w-0">
                                    <div class="font-bold text-slate-900">{{ $account->name ?: ($app ? $app->first_name.' '.$app->last_name : 'No name set') }}</div>
                                    @if(filled($account->google_id))
                                        <span class="inline-flex items-center gap-1 text-[10px] text-slate-500 font-medium">
                                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-blue-500"></span> Google Sign-in
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="font-mono text-xs text-slate-700">{{ $account->contact_email ?: $account->email }}</div>
                            @if($account->contact_number || $app?->contact_number)
                                <div class="text-[11px] text-slate-500">{{ $account->contact_number ?: $app?->contact_number }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="dashboard-pill {{ $roleBadge }}">
                                {{ $isAlumni ? 'Alumni' : str($account->role)->headline() }}
                            </span>
                            @if($account->role === 'trainee' || $account->trainee_status)
                                <div class="mt-1 text-[11px] font-semibold text-slate-500">{{ $account->traineeStatusLabel() }}</div>
                            @endif
                        </td>
                        <td>
                            @if($app)
                                <div class="text-xs font-semibold text-slate-900">
                                    {{ $app->is_historical_record ? 'Historical verified record' : ($app->batch ? $app->batch->name.' '.$app->batch->year : 'No batch assigned') }}
                                </div>
                                <div class="flex items-center gap-1 mt-0.5">
                                    <span class="rounded px-1.5 py-0.5 text-[10px] font-bold {{ $app->status === 'approved' ? 'bg-emerald-100 text-emerald-800' : ($app->status === 'denied' ? 'bg-red-100 text-red-800' : 'bg-stone-100 text-stone-700') }}">
                                        {{ $app->statusLabel() }}
                                    </span>
                                    <span class="text-[10px] text-slate-500">· {{ $app->paymentStatusLabel() }}</span>
                                </div>
                            @else
                                <span class="text-xs text-slate-400">Direct staff account</span>
                            @endif
                        </td>
                        <td>
                            <span class="dashboard-pill {{ $account->hasVerifiedEmail() ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }}">
                                {{ $account->hasVerifiedEmail() ? 'Verified' : 'Pending' }}
                            </span>
                        </td>
                        <td class="text-xs text-slate-600">
                            {{ $account->created_at?->format('M d, Y g:i A') }}
                        </td>
                        <td class="text-right">
                            <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}"
                                  data-confirm-title="{{ $deleteTitle }}"
                                  data-confirm="{{ $deleteMessage }}"
                                  @if($deleteDetail) data-confirm-detail="{{ $deleteDetail }}" @endif
                                  data-confirm-action="{{ $deleteAction }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-200 text-red-700 hover:bg-red-50 hover:border-red-300 transition"
                                        aria-label="Delete {{ $account->name ?: $account->email }}" title="{{ $deleteAction }}">
                                    <x-dashboard-icon name="trash-2" class="h-4 w-4" />
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-12 text-center text-slate-500">
                            No accounts found matching the selected filter.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($accounts->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">
                {{ $accounts->links() }}
            </div>
        @endif
    </div>

    <!-- DIALOG: ADD TRAINER -->
    <dialog id="trainer-account-dialog" data-dashboard-dialog data-auto-open="{{ $trainerErrors->any() ? 'true' : 'false' }}" class="m-auto max-h-[90vh] w-[min(94vw,42rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="trainer-account-dialog-title">
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div><h2 id="trainer-account-dialog-title" class="font-display text-xl font-bold text-slate-900">Add trainer</h2><p class="mt-1 text-xs text-slate-500">Enter the trainer’s name and email. A temporary password will be generated and sent to that address.</p></div>
            <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900" aria-label="Close trainer form" title="Close"><x-dashboard-icon name="xmark" class="h-4 w-4" /></button>
        </div>
        <form method="POST" action="{{ route('admin.accounts.trainers.store') }}" class="space-y-4 p-6" data-dashboard-dialog-form data-submit-label="Creating trainer...">
            @csrf
            <div><label for="trainer-name" class="form-label">Full name</label><input id="trainer-name" name="name" value="{{ old('name') }}" class="form-field" required autofocus>@error('name', 'trainer')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div><label for="trainer-email" class="form-label">Email</label><input id="trainer-email" name="email" type="email" value="{{ old('email') }}" class="form-field" required>@error('email', 'trainer')<p class="form-error">{{ $message }}</p>@enderror</div>
            <p class="rounded-lg border border-purple-100 bg-purple-50 px-3 py-2 text-xs leading-5 text-slate-600">MCARE will generate a unique temporary password and email it to this address. The trainer must verify the email before signing in.</p>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" data-action-button class="primary-action">Create trainer</button></div>
        </form>
    </dialog>

    <!-- DIALOG: ADD TRAINEE -->
    <dialog id="trainee-account-dialog" data-dashboard-dialog data-auto-open="{{ $traineeErrors->any() ? 'true' : 'false' }}" class="m-auto max-h-[90vh] w-[min(96vw,64rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="trainee-account-dialog-title">
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div><h2 id="trainee-account-dialog-title" class="font-display text-xl font-bold text-slate-900">Assisted trainee intake</h2><p class="mt-1 text-xs text-slate-500">For walk-ins whose original requirements and onsite payment are verified by an administrator. A temporary password is generated and emailed after intake.</p></div>
            <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900" aria-label="Close trainee form" title="Close"><x-dashboard-icon name="xmark" class="h-4 w-4" /></button>
        </div>
        <form method="POST" action="{{ route('admin.accounts.trainees.store') }}" class="space-y-5 p-6" data-dashboard-dialog-form data-submit-label="Creating trainee...">
            @csrf
            <div class="grid gap-4 md:grid-cols-3">
                <div><label for="trainee-first-name" class="form-label">First name</label><input id="trainee-first-name" name="first_name" value="{{ old('first_name') }}" class="form-field" required autofocus>@error('first_name', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-middle-name" class="form-label">Middle name</label><input id="trainee-middle-name" name="middle_name" value="{{ old('middle_name') }}" class="form-field">@error('middle_name', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-last-name" class="form-label">Last name</label><input id="trainee-last-name" name="last_name" value="{{ old('last_name') }}" class="form-field" required>@error('last_name', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label for="trainee-email" class="form-label">Email</label><input id="trainee-email" name="email" type="email" value="{{ old('email') }}" class="form-field" required>@error('email', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-contact-number" class="form-label">Contact number</label><input id="trainee-contact-number" name="contact_number" value="{{ old('contact_number') }}" class="form-field" required>@error('contact_number', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <div><label for="trainee-birth-date" class="form-label">Birth date</label><input id="trainee-birth-date" name="birth_date" type="date" value="{{ old('birth_date') }}" class="form-field" required>@error('birth_date', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-gender" class="form-label">Gender</label><select id="trainee-gender" name="gender" class="form-field" required><option value="">Select</option><option @selected(old('gender') === 'Male')>Male</option><option @selected(old('gender') === 'Female')>Female</option></select>@error('gender', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-schedule" class="form-label">Schedule</label><select id="trainee-schedule" name="schedule_preference" class="form-field" required><option value="AM" @selected(old('schedule_preference') === 'AM')>AM</option><option value="PM" @selected(old('schedule_preference') === 'PM')>PM</option></select>@error('schedule_preference', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            </div>
            @php
                $selectedIntakeBatch = $batches->firstWhere('id', (int) old('training_batch_id'));
                $selectedIntakeProgram = $selectedIntakeBatch?->program ?? $defaultProgram;
            @endphp
            <div><label for="trainee-batch" class="form-label">Program and batch</label><select id="trainee-batch" name="training_batch_id" class="form-field" required><option value="">Select batch</option>@foreach($batches as $batch)@php($batchProgram = $batch->program ?? $defaultProgram)<option value="{{ $batch->id }}" data-program="{{ $batchProgram?->name ?? 'Program not assigned' }}" data-downpayment="{{ $batchProgram?->downpayment_amount ?? 0 }}" data-total-fee="{{ $batchProgram?->total_program_fee ?? 0 }}" @selected((int)old('training_batch_id') === $batch->id)>{{ $batchProgram?->name ?? 'Program not assigned' }} — {{ $batch->name }} {{ $batch->year }}</option>@endforeach</select>@error('training_batch_id', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label for="trainee-street" class="form-label">Number and street</label><input id="trainee-street" name="street" value="{{ old('street') }}" class="form-field" required>@error('street', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-barangay" class="form-label">Barangay</label><input id="trainee-barangay" name="barangay" value="{{ old('barangay') }}" class="form-field" required>@error('barangay', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-city" class="form-label">City</label><input id="trainee-city" name="city" value="{{ old('city') }}" class="form-field" required>@error('city', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-province" class="form-label">Province</label><input id="trainee-province" name="province" value="{{ old('province') }}" class="form-field" required>@error('province', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-zip-code" class="form-label">ZIP code</label><input id="trainee-zip-code" name="zip_code" value="{{ old('zip_code') }}" class="form-field" required>@error('zip_code', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            </div>
            <div class="grid gap-4 md:grid-cols-3">
                <div><label for="trainee-education" class="form-label">Educational attainment</label><input id="trainee-education" name="educational_attainment" value="{{ old('educational_attainment') }}" class="form-field" required>@error('educational_attainment', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-school" class="form-label">School</label><input id="trainee-school" name="school_name" value="{{ old('school_name') }}" class="form-field" required>@error('school_name', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-year-graduated" class="form-label">Year graduated</label><input id="trainee-year-graduated" name="year_graduated" type="number" min="1950" max="{{ now()->year }}" value="{{ old('year_graduated') }}" class="form-field" required>@error('year_graduated', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            </div>
            <div class="rounded-xl border border-purple-200 bg-purple-50 p-4">
                <p class="text-xs font-black uppercase tracking-wide text-purple-700">Onsite requirements and consent</p>
                <div class="mt-3 grid gap-3 md:grid-cols-2">
                    @foreach(['birth_certificate_onsite' => 'Original birth certificate inspected', 'education_document_onsite' => 'Original Form 137/138 or diploma inspected', 'good_moral_onsite' => 'Original good moral certificate inspected', 'id_photo_onsite' => 'ID photo received and identity checked', 'signature_onsite' => 'Applicant signature received', 'privacy_consent_onsite' => 'Applicant signed the privacy consent'] as $field => $label)
                        <label class="flex items-start gap-3 rounded-lg border border-purple-100 bg-white p-3 text-xs leading-5 text-slate-700"><input type="checkbox" name="{{ $field }}" value="1" @checked(old($field)) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-600" required><span>{{ $label }}</span></label>
                    @endforeach
                </div>
            </div>
            <div class="grid gap-4 md:grid-cols-2">
                <div><label for="trainee-onsite-payment" class="form-label">Verified onsite payment</label><input id="trainee-onsite-payment" name="onsite_payment_amount" type="number" min="{{ $selectedIntakeProgram?->downpayment_amount ?? '0.01' }}" max="{{ $selectedIntakeProgram?->total_program_fee ?? '1000000' }}" step="0.01" value="{{ old('onsite_payment_amount', $selectedIntakeProgram?->downpayment_amount) }}" class="form-field" required><p id="trainee-payment-range" class="mt-1 text-xs text-slate-500">Select a batch to load its required downpayment and total fee.</p>@error('onsite_payment_amount', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
                <div><label for="trainee-or-number" class="form-label">Official receipt number</label><input id="trainee-or-number" name="onsite_or_number" value="{{ old('onsite_or_number') }}" class="form-field" required>@error('onsite_or_number', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            </div>
            <label class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-900"><input type="checkbox" name="onsite_payment_received" value="1" @checked(old('onsite_payment_received')) class="mt-1 h-4 w-4 rounded border-emerald-300 text-emerald-700 focus:ring-emerald-600" required><span><strong class="block">Payment and receipt verified</strong>I confirm the amount was received onsite and the official receipt number above is accurate.</span></label>
            <div><label for="trainee-onsite-notes" class="form-label">Verification notes</label><textarea id="trainee-onsite-notes" name="onsite_verification_notes" rows="3" class="form-field" required placeholder="Record who presented the originals, relevant document references, and payment context.">{{ old('onsite_verification_notes') }}</textarea>@error('onsite_verification_notes', 'trainee')<p class="form-error">{{ $message }}</p>@enderror</div>
            <p class="rounded-lg border border-purple-100 bg-purple-50 px-3 py-2 text-xs leading-5 text-slate-600">MCARE will generate a unique temporary password and email it to the trainee. They must verify the email before signing in.</p>
            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" data-action-button class="primary-action">Verify intake and create trainee</button></div>
        </form>
    </dialog>
    <script>
        (() => {
            const batch = document.getElementById('trainee-batch');
            const amount = document.getElementById('trainee-onsite-payment');
            const hint = document.getElementById('trainee-payment-range');
            if (!batch || !amount || !hint) return;

            const money = (value) => Number(value || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const syncProgramFees = (replaceAmount = false) => {
                const option = batch.selectedOptions[0];
                const downpayment = Number(option?.dataset.downpayment || 0);
                const totalFee = Number(option?.dataset.totalFee || 0);
                if (!option?.value || downpayment <= 0 || totalFee <= 0) {
                    hint.textContent = 'Select a batch to load its required downpayment and total fee.';
                    return;
                }

                amount.min = String(downpayment);
                amount.max = String(totalFee);
                if (replaceAmount || !amount.value) amount.value = downpayment.toFixed(2);
                hint.textContent = `${option.dataset.program}: PHP ${money(downpayment)} minimum, PHP ${money(totalFee)} total fee.`;
            };

            batch.addEventListener('change', () => syncProgramFees(true));
            syncProgramFees(false);
        })();
    </script>
</section>
@endsection
