@extends('admin.layouts.app')
@section('title', 'New document')
@push('scripts')
    @vite(['resources/js/document-studio/new-document.js'])
@endpush
@section('content')
@php
    $selectedSource = old('source', request('source', request()->boolean('background') ? 'upload' : 'current'));
    if (!in_array($selectedSource, ['current', 'upload', 'blank'], true)) $selectedSource = 'current';
@endphp
<div class="max-w-5xl mx-auto space-y-5">
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.document-studio.index') }}" aria-label="Back to Document Studio" class="min-h-[44px] min-w-[44px] inline-flex items-center justify-center rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800">
            <x-dashboard-icon name="arrow-left" class="w-5 h-5" />
        </a>
        <h1 class="text-xl font-semibold text-gray-900 dark:text-white">New document</h1>
    </div>
    @if($errors->any())
        <div role="alert" class="rounded-lg border border-red-500 p-3 text-sm">{{ $errors->first() }}</div>
    @endif
    <form id="pdf-conversion-form" data-cancel-url="{{ route('admin.document-studio.cancel-conversion') }}" method="POST" action="{{ route('admin.document-studio.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-850 p-4 sm:p-6">
        @csrf
        <input type="hidden" name="operation" value="{{ (string) \Illuminate\Support\Str::uuid() }}" />
        <div class="grid sm:grid-cols-2 gap-4">
            <label class="text-sm font-medium">Name
                <input name="name" value="{{ old('name') }}" maxlength="150" required class="form-field mt-1 block min-h-[44px] dark:bg-gray-900 dark:border-gray-700" />
            </label>
            <label class="text-sm font-medium">Document
                <select name="document_kind" class="form-field mt-1 block min-h-[44px] dark:bg-gray-900 dark:border-gray-700">
                    @foreach(['cotc' => 'COTC', 'tor' => 'TOR', 'soa' => 'Statement of Account', 'general' => 'General'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('document_kind', 'cotc') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-medium">Program
                <select name="training_program_id" class="form-field mt-1 block min-h-[44px] dark:bg-gray-900 dark:border-gray-700">
                    <option value="">All programs</option>
                    @foreach($programs as $program)
                        <option value="{{ $program->id }}" @selected((string) old('training_program_id') === (string) $program->id)>{{ $program->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <fieldset>
            <legend class="mb-2 text-sm font-medium">Start from</legend>
            <div class="grid sm:grid-cols-3 gap-3">
                @foreach(['current' => 'Current design', 'upload' => 'Upload file', 'blank' => 'Blank page'] as $value => $label)
                    <label class="flex items-center gap-3 min-h-[44px] rounded-lg border border-gray-300 dark:border-gray-700 p-3 cursor-pointer">
                        <input type="radio" name="source" value="{{ $value }}" @checked($selectedSource === $value) />
                        <span class="text-sm">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </fieldset>
        <p data-source-panel="current" @if($selectedSource !== 'current') hidden @endif class="text-sm text-gray-500 dark:text-gray-400">Copy the published design into an editable draft. Changes take effect after publishing.</p>
        <div data-source-panel="upload" @if($selectedSource !== 'upload') hidden @endif class="space-y-4">
            <label class="block text-sm font-medium">PDF, DOCX or image (up to 15 MB)
                <input id="conversion-file" name="source_file" type="file" accept=".pdf,.docx,.png,.jpg,.jpeg" @disabled($selectedSource !== 'upload') @required($selectedSource === 'upload') class="mt-1 block w-full min-h-[44px] text-sm" />
            </label>
            <fieldset id="pdf-options" hidden class="space-y-2">
                <legend class="text-sm font-medium">PDF import</legend>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center gap-2 min-h-[44px]"><input type="radio" name="pdf_mode" value="editable" @checked(old('pdf_mode', request()->boolean('background') ? 'background' : 'editable') === 'editable') /> Editable text</label>
                    <label class="flex items-center gap-2 min-h-[44px]"><input type="radio" name="pdf_mode" value="background" @checked(old('pdf_mode', request()->boolean('background') ? 'background' : 'editable') === 'background') /> Fixed PDF background</label>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400">PDF artwork and flattened images keep their background. Add editable fields over them; review converted text before publishing.</p>
            </fieldset>
        </div>
        <p id="conversion-status" role="status" aria-live="polite" class="text-sm text-gray-600 dark:text-gray-400"></p>
        @include('admin.document-studio.partials.pdf-import-review')
        <input type="hidden" name="regions" id="conversion-regions" value="[]" />
        <div class="flex flex-wrap gap-3">
            <button id="conversion-submit" type="submit" class="min-h-[44px] px-5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white font-semibold disabled:opacity-50">Open editor</button>
            <button id="conversion-cancel" type="button" hidden class="min-h-[44px] px-3 border rounded-lg">Cancel conversion</button>
            <a href="{{ route('admin.document-studio.index') }}" class="inline-flex items-center min-h-[44px] px-3">Cancel</a>
        </div>
    </form>
</div>
@endsection
