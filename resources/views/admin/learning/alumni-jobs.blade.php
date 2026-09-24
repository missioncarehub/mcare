@extends('admin.layouts.app', ['title' => 'Alumni Job Board | MCARE Admin'])

@section('content')
@php
    $careerCreateErrors = $errors->getBag('careerCreate');
@endphp

<section class="space-y-6">
    <style>
        .career-form-dialog {
            width: min(96vw, 72rem);
            max-width: 72rem;
            max-height: 92vh;
            overflow: hidden;
        }
        .career-form-dialog[open] {
            display: flex;
            flex-direction: column;
        }
        .career-form-dialog > form {
            min-height: 0;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .career-form-dialog .career-form-layout {
            display: grid;
            grid-template-columns: minmax(22rem, 1.15fr) minmax(20rem, 0.85fr);
            align-items: stretch;
            gap: 1.25rem;
        }
        @media (max-width: 860px) {
            .career-form-dialog .career-form-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <header class="flex flex-wrap justify-end gap-2">
        <div class="flex flex-wrap gap-2">
            <button type="button" data-dashboard-dialog-open="career-opportunity-dialog" class="primary-action inline-flex items-center justify-center gap-2 text-sm font-bold">
                <x-dashboard-icon name="plus" class="h-4 w-4" />
                Add career
            </button>
            <a href="{{ route('admin.learning.alumni-jobs.preview') }}" class="secondary-action inline-flex items-center justify-center gap-2 text-sm font-bold">
                <x-dashboard-icon name="briefcase" class="h-4 w-4" />
                Preview alumni board
            </a>
        </div>
    </header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Alumni accounts</p><p class="dashboard-stat-value">{{ $alumniAccounts }}</p><p class="dashboard-stat-help">Dedicated Career Hub access</p></div><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-purple-50 text-purple-700 ring-1 ring-purple-100"><x-dashboard-icon name="users" class="h-5 w-5" /></span></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Available for Duty</p><p class="dashboard-stat-value">{{ $availableAlumni }}</p><p class="dashboard-stat-help">Alumni accepting placement</p></div><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 ring-1 ring-emerald-100"><x-dashboard-icon name="circle-check" class="h-5 w-5" /></span></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Published careers</p><p class="dashboard-stat-value">{{ $publishedJobs }}</p><p class="dashboard-stat-help">Visible on the alumni board</p></div><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-sky-50 text-sky-700 ring-1 ring-sky-100"><x-dashboard-icon name="briefcase" class="h-5 w-5" /></span></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Draft careers</p><p class="dashboard-stat-value">{{ $draftJobs }}</p><p class="dashboard-stat-help">Awaiting privacy review</p></div><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-amber-50 text-amber-700 ring-1 ring-amber-100"><x-dashboard-icon name="pencil" class="h-5 w-5" /></span></article>
        <article class="dashboard-stat"><div><p class="dashboard-stat-label">Pending inquiries</p><p class="dashboard-stat-value">{{ $pendingInquiryCount }}</p><p class="dashboard-stat-help">Alumni contact forms to review</p></div><span class="grid h-10 w-10 shrink-0 place-items-center rounded-lg bg-violet-50 text-violet-700 ring-1 ring-violet-100"><x-dashboard-icon name="message-circle" class="h-5 w-5" /></span></article>
    </div>

    <dialog id="career-opportunity-dialog" data-dashboard-dialog data-auto-open="{{ $careerCreateErrors->any() ? 'true' : 'false' }}" class="career-form-dialog m-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="career-opportunity-form-title">
        <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div>
                <h2 id="career-opportunity-form-title" class="font-display text-xl font-bold text-slate-900">Add a career</h2>
                <p class="mt-1 text-xs text-slate-500">Use only the client-approved care summary.</p>
            </div>
            <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-900" aria-label="Close career form" title="Close"><x-dashboard-icon name="xmark" class="h-4 w-4" /></button>
        </div>

        <form method="POST" action="{{ route('admin.learning.alumni-jobs.store') }}" class="p-6" data-dashboard-dialog-form data-career-sms-form data-submit-label="Saving career...">
            @csrf
            <div class="career-form-layout">
                <div class="space-y-4">
                    <div><label for="career-title" class="form-label">Career title</label><input id="career-title" name="title" type="text" maxlength="160" value="{{ old('title') }}" required class="form-field" placeholder="Example: Live-in caregiver, Iriga City" autofocus data-career-sms-field="title">@error('title', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div><label for="career-salary" class="form-label">Estimated salary</label><input id="career-salary" name="estimated_salary" type="text" maxlength="80" value="{{ old('estimated_salary') }}" required class="form-field" placeholder="Example: ₱18,000 / month" data-career-sms-field="salary">@error('estimated_salary', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
                        <div><label for="career-start-date" class="form-label">Estimated start date</label><input id="career-start-date" name="estimated_start_date" type="date" min="{{ now()->toDateString() }}" value="{{ old('estimated_start_date') }}" required class="form-field" data-career-sms-field="start">@error('estimated_start_date', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
                    </div>
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div><label for="career-patient-age" class="form-label">Patient age <span class="font-normal text-slate-400">(optional)</span></label><input id="career-patient-age" name="patient_age" type="number" min="0" max="120" value="{{ old('patient_age') }}" class="form-field" placeholder="Example: 72" data-career-sms-field="age">@error('patient_age', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
                        <div><label for="career-patient-gender" class="form-label">Patient gender</label><select id="career-patient-gender" name="patient_gender" required class="form-field" data-career-sms-field="gender"><option value="">Select gender</option>@foreach ($patientGenders as $value => $label)<option value="{{ $value }}" @selected(old('patient_gender') === $value)>{{ $label }}</option>@endforeach</select>@error('patient_gender', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
                        <div><label for="career-mobility" class="form-label">Mobility status</label><select id="career-mobility" name="mobility_status" required class="form-field" data-career-sms-field="mobility"><option value="">Select mobility</option>@foreach ($mobilityStatuses as $value => $label)<option value="{{ $value }}" @selected(old('mobility_status') === $value)>{{ $label }}</option>@endforeach</select>@error('mobility_status', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
                    </div>
                    <div><label for="career-contraptions" class="form-label">Specific contraptions <span class="font-normal text-slate-400">(optional)</span></label><input id="career-contraptions" name="specific_contraptions" value="{{ old('specific_contraptions') }}" maxlength="255" class="form-field" placeholder="Example: wheelchair, oxygen concentrator">@error('specific_contraptions', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div><label for="career-condition" class="form-label">Condition summary <span class="font-normal text-slate-400">(optional)</span></label><textarea id="career-condition" name="condition_summary" rows="3" maxlength="500" class="form-field" placeholder="Short care-relevant context only">{{ old('condition_summary') }}</textarea>@error('condition_summary', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900"><span class="font-bold">Privacy boundary:</span> Do not enter patient names, exact addresses, contact details, identification numbers, medical histories, or upload patient documents.</div>
                </div>
                <div class="space-y-4">
                    <label class="flex items-start gap-3 rounded-lg border border-purple-100 bg-purple-50 p-4 text-sm text-slate-700"><input type="checkbox" name="is_published" value="1" @checked(old('is_published')) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-500"><span><span class="block font-bold text-slate-900">Publish now</span><span class="mt-1 block leading-5">Alumni receive an in-app notification when this career becomes visible.</span></span></label>
                    <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <p class="text-sm font-bold text-slate-900">Graduate SMS</p>
                        <p class="text-xs leading-5 text-slate-500">Send immediately reaches senior alumni only. A scheduled text reaches both junior and senior alumni. Only alumni with a valid contact number are included.</p>
                        @include('admin.learning.partials.career-sms-preview')
                        <label class="flex items-start gap-3 text-sm text-slate-700"><input type="checkbox" name="sms_send_immediately" value="1" @checked(old('sms_send_immediately')) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-500"><span><span class="block font-bold text-slate-900">Send SMS immediately</span><span class="mt-1 block leading-5">Text senior alumni as soon as this career is published. Leave this unchecked and set a schedule to text junior and senior alumni.</span></span></label>
                        <div><label for="career-sms-scheduled-at" class="form-label">Or schedule SMS date and time</label><input id="career-sms-scheduled-at" name="sms_scheduled_at" type="datetime-local" min="{{ now()->format('Y-m-d\\TH:i') }}" value="{{ old('sms_scheduled_at') }}" class="form-field">@error('sms_scheduled_at', 'careerCreate')<p class="form-error">{{ $message }}</p>@enderror</div>
                    </div>
                </div>
            </div>
            <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" data-action-button class="primary-action inline-flex items-center justify-center gap-2"><x-dashboard-icon name="briefcase" class="h-4 w-4" />Save career</button></div>
        </form>
    </dialog>

    <section class="dashboard-panel" aria-labelledby="career-opportunities-title">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="dashboard-section-kicker">Centralized postings</p><h2 id="career-opportunities-title" class="dashboard-section-title text-xl">Career opportunities</h2></div>
            <span class="text-sm text-slate-500">{{ $jobs->total() }} total records</span>
        </div>

        <div class="mt-5 divide-y divide-slate-100">
            @forelse ($jobs as $job)
                <article class="flex flex-col gap-4 py-5 first:pt-0 last:pb-0 lg:flex-row lg:items-center lg:justify-between">
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="font-bold text-slate-950">{{ $job->listingTitle() }}</h3>
                            <span class="rounded-full px-2.5 py-1 text-[11px] font-bold {{ $job->is_published ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $job->is_published ? 'Published' : 'Draft' }}</span>
                            @if (! $job->estimated_start_date)<span class="rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-bold text-amber-700">Privacy review required</span>@endif
                        </div>
                        <p class="mt-2 text-sm text-slate-600">{{ $job->estimated_salary ?: 'Salary not set' }}<span class="mx-2 text-slate-300">|</span>{{ $job->estimated_start_date?->format('M d, Y') ?? 'Start date not set' }}<span class="mx-2 text-slate-300">|</span>{{ $job->patientGenderLabel() }}<span class="mx-2 text-slate-300">|</span>{{ $job->mobilityStatusLabel() }}</p>
                        <p class="mt-1 text-xs font-semibold {{ filled($job->sms_last_error) && ! $job->sms_sent_at ? 'text-rose-600' : 'text-slate-500' }}">{{ $job->smsStatusLabel() }}@if ($job->inquiries_count) · {{ $job->inquiries_count }} {{ str('inquiry')->plural($job->inquiries_count) }}@endif</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" data-dashboard-dialog-open="career-edit-{{ $job->id }}" class="secondary-action inline-flex items-center gap-2"><x-dashboard-icon name="pencil" class="h-4 w-4" />Edit</button>
                        <form method="POST" action="{{ route('admin.learning.alumni-jobs.destroy', $job) }}" data-confirm="Remove this career?">@csrf @method('DELETE')<button type="submit" class="secondary-action border-red-200 text-red-700 hover:bg-red-50">Remove</button></form>
                    </div>
                </article>

                <dialog id="career-edit-{{ $job->id }}" data-dashboard-dialog class="career-form-dialog m-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="career-edit-title-{{ $job->id }}">
                    <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4"><div><h2 id="career-edit-title-{{ $job->id }}" class="font-display text-xl font-bold text-slate-900">Edit career</h2><p class="mt-1 text-xs text-slate-500">Only privacy-approved care details are retained.</p></div><button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Close edit form"><x-dashboard-icon name="xmark" class="h-4 w-4" /></button></div>
                    <form method="POST" action="{{ route('admin.learning.alumni-jobs.update', $job) }}" class="p-6" data-dashboard-dialog-form data-career-sms-form data-submit-label="Updating career...">
                        @csrf @method('PATCH')
                        <div class="career-form-layout">
                            <div class="space-y-4">
                                <div><label for="job-title-{{ $job->id }}" class="form-label">Career title</label><input id="job-title-{{ $job->id }}" name="title" type="text" maxlength="160" value="{{ $job->title }}" required class="form-field" data-career-sms-field="title"></div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <div><label for="job-salary-{{ $job->id }}" class="form-label">Estimated salary</label><input id="job-salary-{{ $job->id }}" name="estimated_salary" type="text" maxlength="80" value="{{ $job->estimated_salary }}" required class="form-field" data-career-sms-field="salary"></div>
                                    <div><label for="job-start-{{ $job->id }}" class="form-label">Estimated start date</label><input id="job-start-{{ $job->id }}" name="estimated_start_date" type="date" min="{{ now()->toDateString() }}" value="{{ $job->estimated_start_date?->toDateString() }}" required class="form-field" data-career-sms-field="start"></div>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-3">
                                    <div><label for="job-age-{{ $job->id }}" class="form-label">Patient age <span class="font-normal text-slate-400">(optional)</span></label><input id="job-age-{{ $job->id }}" name="patient_age" type="number" min="0" max="120" value="{{ $job->patient_age }}" class="form-field" data-career-sms-field="age"></div>
                                    <div><label for="job-gender-{{ $job->id }}" class="form-label">Patient gender</label><select id="job-gender-{{ $job->id }}" name="patient_gender" required class="form-field" data-career-sms-field="gender"><option value="">Select gender</option>@foreach ($patientGenders as $value => $label)<option value="{{ $value }}" @selected($job->patient_gender === $value)>{{ $label }}</option>@endforeach</select></div>
                                    <div><label for="job-mobility-{{ $job->id }}" class="form-label">Mobility status</label><select id="job-mobility-{{ $job->id }}" name="mobility_status" required class="form-field" data-career-sms-field="mobility"><option value="">Select mobility</option>@foreach ($mobilityStatuses as $value => $label)<option value="{{ $value }}" @selected($job->mobility_status === $value)>{{ $label }}</option>@endforeach</select></div>
                                </div>
                                <div><label for="job-contraptions-{{ $job->id }}" class="form-label">Specific contraptions <span class="font-normal text-slate-400">(optional)</span></label><input id="job-contraptions-{{ $job->id }}" name="specific_contraptions" value="{{ $job->specific_contraptions }}" maxlength="255" class="form-field"></div>
                                <div><label for="job-condition-{{ $job->id }}" class="form-label">Condition summary <span class="font-normal text-slate-400">(optional)</span></label><textarea id="job-condition-{{ $job->id }}" name="condition_summary" rows="3" maxlength="500" class="form-field">{{ $job->condition_summary }}</textarea></div>
                            </div>
                            <div class="space-y-4">
                                <label class="flex items-start gap-3 rounded-lg border border-purple-100 bg-purple-50 p-4 text-sm text-slate-700"><input type="checkbox" name="is_published" value="1" @checked($job->is_published) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-500"><span><span class="block font-bold text-slate-900">Published for alumni</span><span class="mt-1 block leading-5">Uncheck to return this career to draft review.</span></span></label>
                                <div class="space-y-3 rounded-lg border border-slate-200 bg-slate-50 p-4">
                                    <p class="text-sm font-bold text-slate-900">Graduate SMS</p>
                                    <p class="text-xs leading-5 {{ filled($job->sms_last_error) && ! $job->sms_sent_at ? 'font-semibold text-rose-600' : 'text-slate-500' }}">{{ $job->smsStatusLabel() }}</p>
                                    @include('admin.learning.partials.career-sms-preview', ['opportunity' => $job])
                                    @unless($job->sms_sent_at)
                                        <label class="flex items-start gap-3 text-sm text-slate-700"><input type="checkbox" name="sms_send_immediately" value="1" @checked($job->sms_mode === \App\Models\CareerOpportunity::SMS_IMMEDIATE) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-purple-700 focus:ring-purple-500"><span class="font-bold text-slate-900">Send SMS immediately</span></label>
                                        <div><label for="job-sms-{{ $job->id }}" class="form-label">Or schedule SMS date and time</label><input id="job-sms-{{ $job->id }}" name="sms_scheduled_at" type="datetime-local" min="{{ now()->format('Y-m-d\\TH:i') }}" value="{{ $job->sms_scheduled_at?->format('Y-m-d\\TH:i') }}" class="form-field"></div>
                                    @endunless
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button><button type="submit" class="primary-action">Save changes</button></div>
                    </form>
                </dialog>
            @empty
                <div class="py-10 text-center"><x-dashboard-icon name="briefcase" class="mx-auto h-8 w-8 text-slate-300" /><p class="mt-3 font-bold text-slate-800">No careers yet</p><p class="mt-1 text-sm text-slate-500">Add the first privacy-reviewed career to start the board.</p></div>
            @endforelse
        </div>

        @if ($jobs->hasPages())<div class="mt-6 border-t border-slate-100 pt-5">{{ $jobs->links() }}</div>@endif
    </section>

    <section class="dashboard-panel" aria-labelledby="career-inquiries-title">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="dashboard-section-kicker">Alumni contact</p><h2 id="career-inquiries-title" class="dashboard-section-title text-xl">Career inquiries</h2></div>
            <span class="text-sm text-slate-500">{{ $inquiries->total() }} total records</span>
        </div>
        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-3 font-bold">Alumni</th>
                        <th class="px-3 py-3 font-bold">Career</th>
                        <th class="px-3 py-3 font-bold">Contact</th>
                        <th class="px-3 py-3 font-bold">Status</th>
                        <th class="px-3 py-3 font-bold">Submitted</th>
                        <th class="px-3 py-3 font-bold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($inquiries as $inquiry)
                        <tr>
                            <td class="px-3 py-4">
                                <p class="font-bold text-slate-900">{{ $inquiry->name }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $inquiry->email }}</p>
                            </td>
                            <td class="px-3 py-4 font-semibold text-slate-800">{{ $inquiry->opportunity?->listingTitle() ?? 'Removed career' }}</td>
                            <td class="px-3 py-4 text-slate-600">{{ $inquiry->contact_number }}</td>
                            <td class="px-3 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $inquiry->isPending() ? 'bg-amber-50 text-amber-700' : ($inquiry->status === \App\Models\CareerInquiry::STATUS_CLOSED ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700') }}">{{ $inquiry->statusLabel() }}</span></td>
                            <td class="px-3 py-4 text-slate-500">{{ $inquiry->created_at?->format('M d, Y g:i A') }}</td>
                            <td class="px-3 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button type="button" data-dashboard-dialog-open="career-inquiry-{{ $inquiry->id }}" class="secondary-action inline-flex items-center gap-2"><x-dashboard-icon name="pencil" class="h-4 w-4" />Review</button>
                                    <form method="POST" action="{{ route('admin.learning.alumni-jobs.inquiries.destroy', $inquiry) }}" data-confirm="Remove this inquiry?">@csrf @method('DELETE')<button type="submit" class="secondary-action border-red-200 text-red-700 hover:bg-red-50">Remove</button></form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-10 text-center text-slate-500">No alumni inquiries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @foreach ($inquiries as $inquiry)
            <dialog id="career-inquiry-{{ $inquiry->id }}" data-dashboard-dialog class="m-auto max-h-[90vh] w-[min(96vw,36rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="career-inquiry-title-{{ $inquiry->id }}">
                <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
                    <div>
                        <h2 id="career-inquiry-title-{{ $inquiry->id }}" class="font-display text-xl font-bold text-slate-900">Inquiry review</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $inquiry->opportunity?->listingTitle() ?? 'Removed career' }}</p>
                    </div>
                    <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Close inquiry"><x-dashboard-icon name="xmark" class="h-4 w-4" /></button>
                </div>
                <form method="POST" action="{{ route('admin.learning.alumni-jobs.inquiries.update', $inquiry) }}" class="grid gap-4 p-6" data-dashboard-dialog-form data-submit-label="Saving inquiry...">
                    @csrf @method('PATCH')
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                        <p><span class="font-bold text-slate-900">Alumni:</span> {{ $inquiry->name }}</p>
                        <p class="mt-1"><span class="font-bold text-slate-900">Email:</span> {{ $inquiry->email }}</p>
                        <p class="mt-1"><span class="font-bold text-slate-900">Contact number:</span> {{ $inquiry->contact_number }}</p>
                        <p class="mt-3 whitespace-pre-line">{{ $inquiry->message }}</p>
                    </div>
                    <div>
                        <label for="inquiry-status-{{ $inquiry->id }}" class="form-label">Status</label>
                        <select id="inquiry-status-{{ $inquiry->id }}" name="status" required class="form-field">
                            @foreach ($inquiryStatuses as $value => $label)
                                <option value="{{ $value }}" @selected($inquiry->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="inquiry-notes-{{ $inquiry->id }}" class="form-label">Admin notes <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea id="inquiry-notes-{{ $inquiry->id }}" name="admin_notes" rows="3" maxlength="1000" class="form-field">{{ $inquiry->admin_notes }}</textarea>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                        <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                        <button type="submit" data-action-button class="primary-action">Save review</button>
                    </div>
                </form>
            </dialog>
        @endforeach
        @if ($inquiries->hasPages())<div class="mt-5 border-t border-slate-100 pt-5">{{ $inquiries->links() }}</div>@endif
    </section>

    <section class="dashboard-panel" aria-labelledby="alumni-availability-title">
        <div class="border-b border-slate-200 pb-5"><p class="dashboard-section-kicker">Caregiver connectivity</p><h2 id="alumni-availability-title" class="dashboard-section-title text-xl">Alumni availability</h2></div>
        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase text-slate-500"><tr><th class="px-3 py-3 font-bold">Alumni</th><th class="px-3 py-3 font-bold">Account</th><th class="px-3 py-3 font-bold">Duty status</th><th class="px-3 py-3 font-bold">Updated</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($alumniRoster as $alumni)
                        @php($profile = $alumni->alumniProfile)
                        <tr><td class="px-3 py-4 font-bold text-slate-900">{{ $alumni->name }}</td><td class="px-3 py-4 text-slate-600">{{ $alumni->email }}</td><td class="px-3 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $profile?->is_available_for_duty ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $profile?->is_available_for_duty ? 'Available for Duty' : 'Unavailable' }}</span></td><td class="px-3 py-4 text-slate-500">{{ $profile?->availability_updated_at?->diffForHumans() ?? 'Not updated' }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="px-3 py-10 text-center text-slate-500">No alumni accounts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($alumniRoster->hasPages())<div class="mt-5 border-t border-slate-100 pt-5">{{ $alumniRoster->links() }}</div>@endif
    </section>
</section>
@endsection
