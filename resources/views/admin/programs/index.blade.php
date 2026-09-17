@extends('admin.layouts.app', ['title' => 'Programs | MCARE Admin'])

@section('content')
    @php
        $program = $editingProgram;
        $programFormHasErrors = $errors->program->any();
        $formAction = $program ? route('admin.training-programs.update', $program) : route('admin.training-programs.store');
        $useOld = $programFormHasErrors;
        $isLastProgram = $programs->count() <= 1;
    @endphp

    <div class="space-y-6">
        <header class="flex flex-col gap-4 border-b border-slate-200 pb-5 md:flex-row md:items-end md:justify-between">
            <p class="text-sm text-slate-600">Manage the public name, fee, and required downpayment used by program batches.</p>
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('admin.batches.index') }}" class="inline-flex w-fit items-center justify-center gap-2 rounded-xl border border-purple-200 bg-white px-5 py-2.5 text-sm font-semibold text-purple-700 transition hover:bg-purple-50">
                    Manage batches
                </a>
                <button type="button" data-program-dialog-open class="inline-flex w-fit items-center justify-center gap-2 rounded-xl bg-purple-700 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-purple-800">
                    <x-dashboard-icon name="plus" class="h-4 w-4" />
                    Add program
                </button>
            </div>
        </header>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-display text-lg font-bold text-slate-900">Training program catalog</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Programs provide the public name, fee, and required downpayment used by their batches.</p>
                </div>
                <span class="w-fit rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700">{{ $programs->count() }} configured</span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Program</th>
                            <th class="px-4 py-3">Code</th>
                            <th class="px-4 py-3">Total fee</th>
                            <th class="px-4 py-3">Downpayment</th>
                            <th class="px-4 py-3">Batches</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($programs as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-slate-900">{{ $item->name }}</p>
                                    @if ($item->description)
                                        <p class="mt-1 max-w-xs text-xs text-slate-500 line-clamp-2">{{ $item->description }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-medium uppercase text-slate-700">{{ $item->code }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-700">₱{{ number_format((float) $item->total_program_fee, 2) }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-slate-700">₱{{ number_format((float) $item->downpayment_amount, 2) }}</td>
                                <td class="px-4 py-3 text-slate-700">{{ $item->batches_count }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $item->is_active ? 'bg-emerald-50 text-emerald-700 ring-emerald-100' : 'bg-slate-100 text-slate-600 ring-slate-200' }}">
                                        {{ $item->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @php
                                        $programHasRelatedRecords = ($item->batches_count + $item->applications_count + $item->admission_applications_count) > 0;
                                        $cannotDeleteProgram = $isLastProgram || $programHasRelatedRecords;
                                    @endphp
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('admin.training-programs.edit', $item) }}" data-dashboard-prefetch class="inline-flex items-center gap-2 rounded-lg border border-purple-200 bg-white px-3 py-1.5 text-xs font-bold text-purple-700 hover:bg-purple-50">
                                            <x-dashboard-icon name="pencil" class="h-3.5 w-3.5" />
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.training-programs.destroy', $item) }}" data-confirm="{{ $isLastProgram ? 'The last training program cannot be deleted.' : ($programHasRelatedRecords ? 'This program has related records and cannot be deleted.' : 'Delete this training program? This cannot be undone.') }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" @disabled($cannotDeleteProgram) class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-1.5 text-xs font-bold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40" title="{{ $isLastProgram ? 'Delete is disabled because this is the last program.' : ($programHasRelatedRecords ? 'Delete is disabled while related records exist.' : 'Delete training program') }}">
                                                <x-dashboard-icon name="trash-2" class="h-3.5 w-3.5" />
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-14 text-center text-slate-500">No training programs configured yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <dialog
        id="program-editor"
        data-program-dialog
        data-auto-open="{{ ($program || $programFormHasErrors) ? 'true' : 'false' }}"
        data-cancel-url="{{ $program ? route('admin.training-programs.index') : '' }}"
        class="m-auto max-h-[90vh] w-[min(94vw,40rem)] overflow-y-auto rounded-xl border border-slate-200 bg-white p-0 text-slate-900 shadow-2xl backdrop:bg-slate-950/45"
        aria-labelledby="program-dialog-title"
    >
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
            <div>
                <p class="dashboard-section-kicker">Program catalog</p>
                <h2 id="program-dialog-title" class="mt-1 font-display text-xl font-bold text-slate-900">{{ $program ? 'Edit program' : 'Add program' }}</h2>
                <p class="mt-1 text-xs text-slate-500">Public name, fee, and required downpayment used by batches.</p>
            </div>
            @if ($program)
                <a href="{{ route('admin.training-programs.index') }}" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900" aria-label="Close program editor" title="Close">
                    <x-dashboard-icon name="xmark" class="h-4 w-4" />
                </a>
            @else
                <button type="button" data-program-dialog-close class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50 hover:text-slate-900" aria-label="Close program editor" title="Close">
                    <x-dashboard-icon name="xmark" class="h-4 w-4" />
                </button>
            @endif
        </div>

        <div class="px-6 pb-6">
            <form method="POST" action="{{ $formAction }}" class="mt-5 space-y-4" data-single-action data-program-form>
                @csrf
                @if ($program)
                    @method('PATCH')
                @endif
                <input type="hidden" name="program_id" value="{{ $program->id ?? '' }}">

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="program_name" class="mb-1 block text-xs font-semibold text-slate-700">Program name</label>
                        <input id="program_name" name="program_name" value="{{ $useOld ? old('program_name') : ($program->name ?? '') }}" required placeholder="Caregiving NC III" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                        @error('program_name', 'program') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="program_code" class="mb-1 block text-xs font-semibold text-slate-700">Program code</label>
                        <input id="program_code" name="program_code" value="{{ $useOld ? old('program_code') : ($program->code ?? '') }}" required placeholder="CAREGIVING-NC-III" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm uppercase text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                        @error('program_code', 'program') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="program_total_fee" class="mb-1 block text-xs font-semibold text-slate-700">Total fee</label>
                        <input id="program_total_fee" name="program_total_fee" type="number" min="1" step="0.01" value="{{ $useOld ? old('program_total_fee', '22000.00') : ($program->total_program_fee ?? '22000.00') }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                        @error('program_total_fee', 'program') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="program_downpayment" class="mb-1 block text-xs font-semibold text-slate-700">Required downpayment</label>
                        <input id="program_downpayment" name="program_downpayment" type="number" min="1" step="0.01" value="{{ $useOld ? old('program_downpayment', '2000.00') : ($program->downpayment_amount ?? '2000.00') }}" required class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">
                        @error('program_downpayment', 'program') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label for="program_description" class="mb-1 block text-xs font-semibold text-slate-700">Short public description</label>
                    <textarea id="program_description" name="program_description" rows="3" placeholder="Optional" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-900 transition focus:border-purple-600 focus:outline-none focus:ring-1 focus:ring-purple-600">{{ $useOld ? old('program_description') : ($program->description ?? '') }}</textarea>
                    @error('program_description', 'program') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                @php
                    // Path: resources/views/admin/programs/index.blade.php | Label: Publish requires at least one batch
                    $programHasBatch = $program && (($program->batches_count ?? null) !== null
                        ? $program->batches_count > 0
                        : $program->batches()->exists());
                    $canPublish = $program !== null && $programHasBatch;
                @endphp
                <div class="rounded-xl border {{ $canPublish ? 'border-slate-200 bg-white' : 'border-amber-200 bg-amber-50' }} px-3 py-2">
                    <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700">
                        <input type="checkbox" name="program_is_active" value="1"
                            @checked($useOld ? old('program_is_active', false) : ($program->is_active ?? false))
                            @disabled(! $canPublish)
                            class="rounded border-slate-300 text-purple-600 disabled:opacity-40">
                        Publish program (make active)
                    </label>
                    @if (! $canPublish)
                        <p class="mt-1 text-[11px] font-semibold text-amber-800">
                            {{ $program === null ? 'You can publish only after saving the program and adding at least one batch.' : 'Add at least one batch to this program before publishing it.' }}
                        </p>
                    @endif
                    @error('program_is_active', 'program') <p class="mt-1 text-xs font-medium text-red-600">{{ $message }}</p> @enderror
                </div>

                <button type="submit" data-action-button class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-purple-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-purple-800">
                    <x-dashboard-icon name="save" class="h-4 w-4" />
                    {{ $program ? 'Save program' : 'Add program' }}
                </button>

                @if ($program)
                    <a href="{{ route('admin.training-programs.index') }}" class="block text-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </a>
                @else
                    <button type="button" data-program-dialog-close class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        Cancel
                    </button>
                @endif
            </form>
        </div>
    </dialog>

    <script>
        (() => {
            const dialog = document.querySelector('[data-program-dialog]');
            const form = document.querySelector('[data-program-form]');

            if (!dialog || !form) return;

            const openDialog = () => {
                if (!dialog.open) dialog.showModal();
                requestAnimationFrame(() => form.querySelector('input:not([type="hidden"])')?.focus());
            };

            const closeDialog = () => {
                if (dialog.dataset.cancelUrl) {
                    window.location.assign(dialog.dataset.cancelUrl);
                    return;
                }

                dialog.close();
            };

            document.querySelectorAll('[data-program-dialog-open]').forEach((button) => {
                button.addEventListener('click', openDialog);
            });

            dialog.querySelectorAll('[data-program-dialog-close]').forEach((button) => {
                button.addEventListener('click', closeDialog);
            });

            dialog.addEventListener('cancel', (event) => {
                if (!dialog.dataset.cancelUrl) return;
                event.preventDefault();
                closeDialog();
            });

            dialog.addEventListener('click', (event) => {
                const bounds = dialog.getBoundingClientRect();
                const outside = event.clientX < bounds.left || event.clientX > bounds.right
                    || event.clientY < bounds.top || event.clientY > bounds.bottom;

                if (outside) closeDialog();
            });

            form.addEventListener('submit', () => {
                form.querySelectorAll('[data-action-button]').forEach((button) => {
                    button.disabled = true;
                    button.classList.add('cursor-not-allowed', 'opacity-70');
                    button.textContent = '{{ $program ? 'Saving changes...' : 'Adding program...' }}';
                });
            });

            if (dialog.dataset.autoOpen === 'true') openDialog();
        })();
    </script>
@endsection
