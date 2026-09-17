@extends('admin.layouts.app', ['title' => 'Review Documents | MCARE Admin'])

@section('content')
    @php
        $documentsReadyForApproval = $pendingDocumentApprovals === [];
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('admin.enrollments.show', $application) }}" class="secondary-action">
            Back to application
        </a>
        <span class="dashboard-pill {{ $documentsReadyForApproval ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-amber-50 text-amber-700 ring-amber-100' }}">
            {{ $documentsReadyForApproval ? 'All accepted' : count($pendingDocumentApprovals).' pending' }}
        </span>
    </div>

    <section class="border border-slate-200 bg-white p-6">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-purple-700">{{ $application->last_name }}, {{ $application->first_name }}</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-950">Review documents</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">Preview each file and mark every required document as Accepted. Document review stays incomplete while any file is still pending.</p>
        </div>

        @error('documents')
            <div class="mt-6 border border-amber-200 bg-amber-50 p-4" role="alert">
                <p class="text-sm font-bold text-amber-950">{{ $message }}</p>
            </div>
        @enderror

        <form method="POST" action="{{ route('admin.enrollments.documents.review', $application) }}" class="mt-6" data-document-review-form>
            @csrf
            @method('PATCH')
            @foreach($documents as $key => $document)
                @php
                    $storedReview = data_get($application->document_review, $key, []);
                    $defaultDocumentStatus = $document['path'] ? 'unreviewed' : 'missing';
                @endphp
                <div id="document-card-{{ $key }}" class="grid items-start gap-4 border-t border-slate-200 py-4 md:grid-cols-[minmax(14rem,0.8fr)_minmax(18rem,1.2fr)]">
                    <div>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <label for="review-{{ $key }}" class="text-sm font-bold text-slate-900">{{ $document['label'] }}</label>
                            @if ($document['path'])
                                <button type="button"
                                    class="document-preview-trigger secondary-action !px-3 !py-1.5 text-xs"
                                    data-document-key="{{ $key }}"
                                    data-document-label="{{ $document['label'] }}"
                                    data-document-mime="{{ $document['mime'] ?? '' }}"
                                    data-document-url="{{ route('admin.enrollments.documents.content', [$application, $key]) }}"
                                    aria-haspopup="dialog">
                                    Preview
                                </button>
                            @else
                                <span class="text-xs font-bold text-red-600">Missing</span>
                            @endif
                        </div>
                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ $document['path'] ? 'Private preview; access is logged.' : 'Applicant must upload this file.' }}</p>
                    </div>
                    <div>
                        <label for="review-{{ $key }}" class="sr-only">Review status for {{ $document['label'] }}</label>
                        <select id="review-{{ $key }}" name="documents[{{ $key }}][status]" class="w-full border border-slate-200 bg-white px-3 py-2 text-sm focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                            <option value="unreviewed" @selected(old("documents.$key.status", $storedReview['status'] ?? $defaultDocumentStatus) === 'unreviewed')>Not reviewed</option>
                            <option value="accepted" @selected(old("documents.$key.status", $storedReview['status'] ?? $defaultDocumentStatus) === 'accepted')>Accepted</option>
                            <option value="replace" @selected(old("documents.$key.status", $storedReview['status'] ?? $defaultDocumentStatus) === 'replace')>Needs replacement</option>
                            <option value="missing" @selected(old("documents.$key.status", $storedReview['status'] ?? $defaultDocumentStatus) === 'missing')>Missing</option>
                        </select>
                        <label for="note-{{ $key }}" class="mt-3 block text-xs font-bold uppercase tracking-wider text-slate-500">Feedback or problem found</label>
                        <textarea id="note-{{ $key }}" name="documents[{{ $key }}][note]" rows="2" maxlength="500" class="mt-2 w-full border border-slate-200 bg-white px-3 py-2 text-sm focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600" placeholder="Example: Image is blurry; upload a clear copy showing all corners.">{{ old("documents.$key.note", $storedReview['note'] ?? '') }}</textarea>
                    </div>
                </div>
            @endforeach
            <div class="flex flex-col gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <button type="submit" class="primary-action">Done for review</button>
                @if($documentsReadyForApproval && $application->documents_reviewed_at)
                    <p class="text-xs text-slate-500">Last reviewed {{ $application->documents_reviewed_at->format('M d, Y g:i A') }} by {{ $application->documentReviewer?->name ?? 'Admin' }}</p>
                @elseif(! $documentsReadyForApproval)
                    <p class="text-xs text-amber-700">Accept every required document before this review can be completed.</p>
                @endif
            </div>
        </form>
    </section>

    {{-- Path: resources/views/admin/enrollments/document-review.blade.php | Label: Request revisions block --}}
    <section class="mt-6 border border-amber-200 bg-amber-50/40 p-6">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h2 class="text-sm font-bold uppercase tracking-wider text-amber-900">Request revisions from the applicant</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-amber-900/90">
                    First mark each problem document above as <strong>Needs replacement</strong> and add a short note explaining what to correct. Then click the button below to email the applicant a direct link to re-upload the flagged files.
                </p>
            </div>
            @error('documents_request')
                <p class="text-xs font-bold text-red-700">{{ $message }}</p>
            @enderror
        </div>

        <form method="POST" action="{{ route('admin.enrollments.documents.request-revisions', $application) }}" class="mt-4 space-y-3">
            @csrf
            <label for="revise-remark" class="block text-xs font-bold uppercase tracking-wider text-amber-900">Overall remark (optional)</label>
            <textarea id="revise-remark" name="remark" rows="3" maxlength="2000" class="w-full border border-amber-200 bg-white px-3 py-2 text-sm focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500" placeholder="Example: Two of your documents were unclear. Please re-upload sharp, complete scans.">{{ old('remark') }}</textarea>
            @error('remark')<p class="text-xs font-bold text-red-700">{{ $message }}</p>@enderror
            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="primary-action">Email revision request</button>
                <p class="text-xs text-amber-900/80">The email includes a link that opens the applicant's document revision page so they can replace only the files marked Needs replacement.</p>
            </div>
        </form>
    </section>

    <div id="document-preview-modal"
        class="fixed inset-0 z-[100] hidden"
        role="dialog"
        aria-modal="true"
        aria-labelledby="document-preview-title"
        aria-hidden="true">
        <div class="absolute inset-0 bg-slate-950/75 backdrop-blur-sm" data-document-modal-close></div>
        <div class="relative mx-auto flex h-full w-full max-w-6xl flex-col p-3 sm:p-6">
            <div class="flex min-h-0 flex-1 flex-col overflow-hidden rounded-2xl border border-white/15 bg-slate-950 shadow-2xl">
                <header class="flex shrink-0 items-center justify-between gap-4 border-b border-white/10 px-4 py-3 text-white sm:px-6">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-[0.16em] text-purple-300">Private document preview</p>
                        <h2 id="document-preview-title" class="truncate text-base font-bold sm:text-lg">Document</h2>
                    </div>
                    <button type="button" data-document-modal-close class="inline-flex shrink-0 items-center justify-center rounded-full border border-white/20 px-4 py-2 text-sm font-bold text-white transition hover:bg-white/10 focus:outline-none focus:ring-4 focus:ring-purple-300/40">
                        Close
                    </button>
                </header>
                <div class="relative min-h-0 flex-1 overflow-hidden bg-slate-900">
                    <div class="pointer-events-none absolute inset-0 z-20 grid grid-cols-2 grid-rows-4 overflow-hidden opacity-[0.12]" aria-hidden="true">
                        @for($i = 0; $i < 8; $i++)
                            <span class="grid -rotate-12 place-items-center whitespace-nowrap text-xs font-black uppercase tracking-widest text-white sm:text-sm">ADMIN REVIEW · {{ $application->email }} · {{ now()->format('Y-m-d H:i') }}</span>
                        @endfor
                    </div>
                    <iframe id="document-preview-frame" class="relative z-10 hidden h-full min-h-[70vh] w-full bg-white" title="Document preview"></iframe>
                    <div id="document-preview-image-wrap" class="relative z-10 hidden h-full overflow-auto bg-slate-100 p-4 sm:p-8">
                        <img id="document-preview-image" src="" alt="" class="mx-auto h-auto max-w-full select-none object-contain" draggable="false">
                    </div>
                    <div id="document-preview-unavailable" class="relative z-10 hidden h-full min-h-[70vh] place-items-center bg-white p-8 text-center">
                        <p class="max-w-md font-semibold leading-6 text-slate-700">This file type cannot be previewed in the browser. Ask the applicant to upload a PDF, JPG, or PNG.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const modal = document.getElementById('document-preview-modal');
            if (!modal) return;

            const frame = document.getElementById('document-preview-frame');
            const imageWrap = document.getElementById('document-preview-image-wrap');
            const image = document.getElementById('document-preview-image');
            const unavailable = document.getElementById('document-preview-unavailable');
            const title = document.getElementById('document-preview-title');
            let activeTrigger = null;
            let previousScrollY = 0;

            const resetViewer = () => {
                frame.classList.add('hidden');
                imageWrap.classList.add('hidden');
                unavailable.classList.add('hidden');
                frame.removeAttribute('src');
                image.removeAttribute('src');
                image.removeAttribute('alt');
            };

            const closeModal = () => {
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
                resetViewer();
                window.scrollTo({ top: previousScrollY, behavior: 'auto' });
                activeTrigger?.focus({ preventScroll: true });
            };

            const openModal = (trigger) => {
                activeTrigger = trigger;
                previousScrollY = window.scrollY;
                const label = trigger.dataset.documentLabel || 'Document';
                const url = trigger.dataset.documentUrl;
                const mime = trigger.dataset.documentMime || '';
                title.textContent = label;
                resetViewer();

                if (mime === 'application/pdf' || url.toLowerCase().endsWith('.pdf')) {
                    frame.src = `${url}#toolbar=0&navpanes=0&scrollbar=1&view=FitH`;
                    frame.title = `${label} preview`;
                    frame.classList.remove('hidden');
                } else if (mime.startsWith('image/')) {
                    image.src = url;
                    image.alt = `${label} preview`;
                    imageWrap.classList.remove('hidden');
                } else {
                    unavailable.classList.remove('hidden');
                    unavailable.classList.add('grid');
                }

                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
                modal.querySelector('[data-document-modal-close]')?.focus();
            };

            document.querySelectorAll('.document-preview-trigger').forEach((trigger) => {
                trigger.addEventListener('click', () => openModal(trigger));
            });
            modal.querySelectorAll('[data-document-modal-close]').forEach((button) => {
                button.addEventListener('click', closeModal);
            });
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });
        })();
    </script>
@endsection
