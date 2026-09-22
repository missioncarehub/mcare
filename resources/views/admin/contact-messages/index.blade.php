@extends('admin.layouts.app', ['title' => 'Contact admin | MCARE Admin'])

@section('content')
<section class="space-y-6">
    <header class="flex flex-col gap-4 border-b border-slate-200 pb-6 lg:flex-row lg:items-end lg:justify-between">
        <p class="max-w-3xl text-sm leading-6 text-slate-600">
            Review help-desk messages from trainees, trainers, and alumni. Reply here so the sender can see the update on their Help page.
        </p>
        <span class="text-sm font-semibold text-slate-500">{{ $pendingCount }} pending</span>
    </header>

    <section class="dashboard-panel" aria-labelledby="contact-messages-title">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-5 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="dashboard-section-kicker">Help desk</p>
                <h2 id="contact-messages-title" class="dashboard-section-title text-xl">Contact admin</h2>
            </div>
            <form method="GET" action="{{ route('admin.contact-messages.index') }}" class="flex flex-wrap items-center gap-2">
                <label for="contact-status-filter" class="sr-only">Filter by status</label>
                <select id="contact-status-filter" name="status" class="form-field w-auto min-w-[10rem]" onchange="this.form.submit()">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $value => $label)
                        <option value="{{ $value }}" @selected($currentStatus === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-3 font-bold">Sender</th>
                        <th class="px-3 py-3 font-bold">Topic</th>
                        <th class="px-3 py-3 font-bold">Subject</th>
                        <th class="px-3 py-3 font-bold">Status</th>
                        <th class="px-3 py-3 font-bold">Submitted</th>
                        <th class="px-3 py-3 font-bold"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($messages as $message)
                        <tr>
                            <td class="px-3 py-4">
                                <p class="font-bold text-slate-900">{{ $message->user?->name ?? 'Removed account' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $message->user?->email }}</p>
                            </td>
                            <td class="px-3 py-4 font-semibold text-slate-800">{{ $message->topicLabel() }}</td>
                            <td class="px-3 py-4 text-slate-700">{{ $message->subject }}</td>
                            <td class="px-3 py-4">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $message->isPending() ? 'bg-amber-50 text-amber-700' : ($message->status === \App\Models\AdminContactMessage::STATUS_CLOSED ? 'bg-slate-100 text-slate-600' : 'bg-emerald-50 text-emerald-700') }}">{{ $message->statusLabel() }}</span>
                            </td>
                            <td class="px-3 py-4 text-slate-500">{{ $message->created_at?->format('M d, Y g:i A') }}</td>
                            <td class="px-3 py-4">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <button type="button" data-dashboard-dialog-open="contact-message-{{ $message->id }}" class="secondary-action inline-flex items-center gap-2"><x-dashboard-icon name="pencil" class="h-4 w-4" />Review</button>
                                    <form method="POST" action="{{ route('admin.contact-messages.destroy', $message) }}" data-confirm="Remove this contact message?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="secondary-action border-red-200 text-red-700 hover:bg-red-50">Remove</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-3 py-10 text-center text-slate-500">No contact messages yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @foreach ($messages as $message)
            <dialog id="contact-message-{{ $message->id }}" data-dashboard-dialog class="m-auto max-h-[90vh] w-[min(96vw,36rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45" aria-labelledby="contact-message-title-{{ $message->id }}">
                <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
                    <div>
                        <h2 id="contact-message-title-{{ $message->id }}" class="font-display text-xl font-bold text-slate-900">Contact review</h2>
                        <p class="mt-1 text-xs text-slate-500">{{ $message->topicLabel() }}</p>
                    </div>
                    <button type="button" data-dashboard-dialog-close class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50" aria-label="Close contact review"><x-dashboard-icon name="xmark" class="h-4 w-4" /></button>
                </div>
                <form method="POST" action="{{ route('admin.contact-messages.update', $message) }}" class="grid gap-4 p-6" data-dashboard-dialog-form data-submit-label="Saving reply...">
                    @csrf
                    @method('PATCH')
                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm leading-6 text-slate-700">
                        <p><span class="font-bold text-slate-900">Sender:</span> {{ $message->user?->name ?? 'Removed account' }}</p>
                        <p class="mt-1"><span class="font-bold text-slate-900">Email:</span> {{ $message->user?->email }}</p>
                        <p class="mt-1"><span class="font-bold text-slate-900">Subject:</span> {{ $message->subject }}</p>
                        <p class="mt-3 whitespace-pre-line">{{ $message->message }}</p>
                    </div>
                    <div>
                        <label for="contact-status-{{ $message->id }}" class="form-label">Status</label>
                        <select id="contact-status-{{ $message->id }}" name="status" required class="form-field">
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}" @selected($message->status === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="contact-reply-{{ $message->id }}" class="form-label">Reply to sender <span class="font-normal text-slate-400">(optional)</span></label>
                        <textarea id="contact-reply-{{ $message->id }}" name="admin_reply" rows="4" maxlength="2000" class="form-field" placeholder="This reply appears on the sender's Help page.">{{ $message->admin_reply }}</textarea>
                    </div>
                    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end">
                        <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
                        <button type="submit" data-action-button class="primary-action">Save review</button>
                    </div>
                </form>
            </dialog>
        @endforeach

        @if ($messages->hasPages())
            <div class="mt-5 border-t border-slate-100 pt-5">{{ $messages->links() }}</div>
        @endif
    </section>
</section>
@endsection
