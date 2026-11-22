@extends('admin.layouts.app')
@section('title', 'Upload document')
@push('scripts')
    @vite(['resources/js/document-studio/upload.js'])
@endpush
@section('content')
<div class="max-w-5xl mx-auto space-y-4">
    <h1 class="text-xl font-semibold">Upload document</h1>
    <p>Convert a PDF into editable text and a reusable design. Artwork stays as a locked background. Review spacing and personal information before reuse.</p>
    @if($errors->any())<div role="alert" class="border border-red-500 rounded-lg p-3">{{ $errors->first() }}</div>@endif
    <form id="pdf-conversion-form" data-cancel-url="{{ route('admin.document-studio.cancel-conversion') }}" method="POST" action="{{ route('admin.document-studio.convert-pdf') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <input type="hidden" name="operation" value="{{ (string) \Illuminate\Support\Str::uuid() }}" />
        <div class="grid sm:grid-cols-2 gap-4">
            <label>Template name<input name="name" value="{{ old('name') }}" maxlength="150" required class="block w-full rounded-lg min-h-[44px]" /></label>
            <label>Document kind<select name="document_kind" class="block w-full rounded-lg min-h-[44px]">
                @foreach(['general' => 'General', 'soa' => 'Statement of Account', 'cotc' => 'COTC', 'tor' => 'TOR'] as $value => $label)<option value="{{ $value }}" @selected(old('document_kind') === $value)>{{ $label }}</option>@endforeach
            </select></label>
            <label>Program<select name="training_program_id" class="block w-full rounded-lg min-h-[44px]"><option value="">All programs</option>@foreach($programs as $program)<option value="{{ $program->id }}">{{ $program->name }}</option>@endforeach</select></label>
            <label>PDF (up to 15 MB, 20 pages)<input id="conversion-file" name="file" type="file" accept=".pdf,application/pdf" required class="block w-full min-h-[44px]" /></label>
        </div>
        <p id="conversion-status" role="status" aria-live="polite">Select a PDF to inspect its pages.</p>
        <div id="conversion-review" hidden class="space-y-3">
            <div class="flex flex-wrap gap-2 items-center">
                <button type="button" id="conversion-previous" class="border rounded-lg min-h-[44px] px-3">Previous</button>
                <span id="conversion-page"></span>
                <button type="button" id="conversion-next" class="border rounded-lg min-h-[44px] px-3">Next</button>
            </div>
            <p>For a scanned page, drag over each value to replace, or enter a region below. The selected pixels are removed from the new background. No automatic OCR is enabled.</p>
            <div style="max-height:65vh;overflow:auto;border:1px solid #94a3b8"><canvas id="conversion-canvas" style="max-width:100%;display:block;touch-action:none" aria-label="PDF page for selecting replacement regions"></canvas></div>
            <fieldset class="border rounded-lg p-3 space-y-3">
                <legend>Manual replacement region</legend>
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach(['x' => 'Left (mm)', 'y' => 'Top (mm)', 'width' => 'Width (mm)', 'height' => 'Height (mm)'] as $field => $label)
                        <label>{{ $label }}<input id="region-{{ $field }}" type="number" min="0" max="1000" step="0.1" value="{{ in_array($field, ['width', 'height']) ? 20 : 0 }}" class="block w-full min-h-[44px] rounded-lg" /></label>
                    @endforeach
                </div>
                <label class="block">Replacement text<input id="region-content" maxlength="2000" class="block w-full min-h-[44px] rounded-lg" placeholder="Type text or choose an MCARE field" /></label>
                <label class="block">MCARE field<select id="region-binding" class="block w-full min-h-[44px] rounded-lg"><option value="">Choose field</option>@foreach($placeholders as $key => $definition)<option value="{{ $key }}">{{ $definition['label'] ?? $key }}</option>@endforeach</select></label>
                <label class="flex items-center gap-3">Background colour<input type="color" id="region-color" value="#ffffff" style="width:48px;height:44px" /></label>
                <button type="button" id="region-add" class="border rounded-lg min-h-[44px] px-3">Add region</button>
                <ul id="region-list" class="space-y-2"></ul>
            </fieldset>
        </div>
        <input type="hidden" name="regions" id="conversion-regions" value="[]" />
        <div class="flex flex-wrap gap-3">
            <button id="conversion-submit" type="submit" disabled class="min-h-[44px] px-4 rounded-lg bg-indigo-600 text-white disabled:opacity-50">Convert to editable draft</button>
            <button id="conversion-cancel" type="button" hidden class="min-h-[44px] px-3 border rounded-lg">Cancel conversion</button>
            <a href="{{ route('admin.document-studio.create') }}" class="inline-flex items-center min-h-[44px] px-3">Create blank / DOCX / image design</a>
            <a href="{{ route('admin.document-studio.create', ['background' => 1]) }}" class="inline-flex items-center min-h-[44px] px-3">Use as background</a>
            <a href="{{ route('admin.document-studio.index') }}" class="inline-flex items-center min-h-[44px] px-3">Cancel</a>
        </div>
    </form>
</div>
@endsection
