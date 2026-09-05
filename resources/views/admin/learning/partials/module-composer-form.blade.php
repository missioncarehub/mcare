@php
    $module = $module ?? null;
    $prefix = $prefix ?? 'admin';
    $errorBag = $errorBag ?? 'default';
    $isEdit = $module instanceof \App\Models\TrainingModule;
    $currentTrainerId = (int) old('trainer_id', $isEdit ? $module->trainer_id : 0);
    $currentBatchId = (int) old('training_batch_id', $isEdit ? $module->training_batch_id : 0);
    $currentCategory = old('competency_category', $isEdit ? ($module->competency_category ?: 'custom') : 'custom');
    $currentCompletion = old('completion_mode', $isEdit ? $module->completion_mode : 'assessed');
    $isPublished = filter_var(old('is_published', $isEdit ? $module->is_published : true), FILTER_VALIDATE_BOOLEAN);
    $submoduleTitles = old(
        'submodule_titles',
        $isEdit ? ($module->submodules->pluck('title')->filter()->values()->all() ?: ['']) : ['']
    );
@endphp

<form
    method="POST"
    action="{{ $isEdit ? route('admin.learning.modules.update', $module) : route('admin.learning.modules.store') }}"
    enctype="multipart/form-data"
    class="lms-composer-form grid gap-4 md:grid-cols-2 xl:grid-cols-4"
    data-dashboard-dialog-form
    data-module-preset-form
    data-submit-label="{{ $isEdit ? 'Saving module...' : 'Adding module...' }}"
>
    @csrf
    @if($isEdit)
        @method('PATCH')
        <input type="hidden" name="_editing_module_id" value="{{ $module->id }}">
    @endif

    <div class="md:col-span-2 xl:col-span-4">
        <label class="mb-2 block text-xs font-bold uppercase text-purple-800">Caregiving NC II Course Module Preset</label>
        <x-module-preset-select
            :id="$prefix.'-module-preset-select'"
            :units="$trainerCatalogUnits"
            data-role="module-preset"
            class="form-field border-purple-300 focus:border-purple-600"
        />
        <small class="mt-1 block text-xs text-slate-500">This list is the same database catalog trainers use. Choosing a preset fills module code, title, hours, and every official learning outcome.</small>
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Trainer</label>
        <select name="trainer_id" class="form-field" required>
            <option value="">Select trainer</option>
            @foreach($trainers as $trainer)
                <option value="{{ $trainer->id }}" @selected($currentTrainerId === $trainer->id)>{{ $trainer->name }} · {{ $trainer->email }}</option>
            @endforeach
        </select>
        @error('trainer_id', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Batch Assignment</label>
        <select name="training_batch_id" class="form-field" required>
            <option value="">Select batch</option>
            @foreach($batches as $batch)
                <option value="{{ $batch->id }}" @selected($currentBatchId === $batch->id)>{{ $batch->name }} {{ $batch->year }}</option>
            @endforeach
        </select>
        @error('training_batch_id', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Module Code</label>
        <input id="{{ $prefix }}-module-code" data-role="module-code" name="module_code" value="{{ old('module_code', $isEdit ? $module->module_code : '') }}" class="form-field font-mono font-bold" placeholder="e.g. HCS323301">
        @error('module_code', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Competency Category</label>
        <select id="{{ $prefix }}-module-category" data-role="module-category" name="competency_category" class="form-field">
            <option value="custom" @selected($currentCategory === 'custom')>Institutional / Custom</option>
            <option value="core" @selected($currentCategory === 'core')>Core Competency</option>
            <option value="common" @selected($currentCategory === 'common')>Common Competency</option>
            <option value="basic" @selected($currentCategory === 'basic')>Basic Competency</option>
        </select>
        @error('competency_category', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2">
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Module Requirement</label>
        <select name="completion_mode" class="form-field" required>
            <option value="assessed" @selected($currentCompletion === 'assessed')>Assessed module — classwork and trainer evaluation required</option>
            <option value="material_only" @selected($currentCompletion === 'material_only')>Learning material only — no Mark as Done</option>
        </select>
        <p class="mt-1 text-xs text-slate-500">Material-only modules remain readable but do not create a competency completion requirement.</p>
        @error('completion_mode', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Sub-topic / Learning Outcome</label>
        <input id="{{ $prefix }}-module-topic" data-role="module-topic" name="topic" list="{{ $prefix }}-subtopics-list" value="{{ old('topic', $isEdit ? $module->topic : '') }}" class="form-field" placeholder="e.g. Comfort infants and toddlers">
        <datalist id="{{ $prefix }}-subtopics-list" data-role="module-subtopics"></datalist>
        @error('topic', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Nominal Hours</label>
        <input id="{{ $prefix }}-module-hours" data-role="module-hours" name="estimated_hours" type="number" min="1" max="500" value="{{ old('estimated_hours', $isEdit ? $module->estimated_hours : '') }}" class="form-field" placeholder="e.g. 40">
        @error('estimated_hours', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2 xl:col-span-4" data-submodule-builder>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Submodules / Competency Outcomes</label>
        <p class="mb-2 text-xs text-slate-500">Choosing a course preset loads every official outcome for that unit, the same way the trainer classwork composer does. For a custom assessed module, enter each outcome to evaluate separately.</p>
        <div class="space-y-2" data-role="module-submodule-list">
            @foreach($submoduleTitles as $submoduleTitle)
                <input name="submodule_titles[]" value="{{ $submoduleTitle }}" maxlength="255" class="form-field" placeholder="Submodule or competency outcome">
            @endforeach
        </div>
        <button type="button" class="secondary-action mt-2 text-xs" data-role="module-add-submodule">Add custom submodule</button>
        @error('submodule_titles', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
        @error('submodule_titles.*', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2 xl:col-span-4">
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Module Title</label>
        <input id="{{ $prefix }}-module-title" data-role="module-title" name="title" value="{{ old('title', $isEdit ? $module->title : '') }}" class="form-field" required placeholder="e.g. Provide Care and Support to Infants and Toddlers">
        @error('title', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2 xl:col-span-3">
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Instructions & Description</label>
        <textarea name="description" rows="3" class="form-field" required placeholder="Overview of the core module, competencies, and learning instructions.">{{ old('description', $isEdit ? $module->description : '') }}</textarea>
        @error('description', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">{{ \App\Support\TrainingModuleFiles::humanLabel() }}</label>
        <input name="module_file" type="file" accept="{{ \App\Support\TrainingModuleFiles::acceptAttribute() }}" class="form-field" @required(! $isEdit)>
        @if($isEdit && filled($module->original_file_name))
            <p class="mt-1 text-xs text-slate-500">Current file: {{ $module->original_file_name }}. Leave empty to keep it.</p>
        @endif
        @error('module_file', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="md:col-span-2 xl:col-span-4">
        <label class="mb-2 block text-xs font-bold uppercase text-slate-500">Supplementary Handouts (Optional)</label>
        <input name="supplementary_files[]" type="file" multiple accept="{{ \App\Support\TrainingModuleFiles::acceptAttribute() }}" class="form-field">
        <p class="mt-1 text-xs text-slate-500">
            @if($isEdit && count($module->supplementaryList()) > 0)
                {{ count($module->supplementaryList()) }} supplementary file(s) already attached.
            @endif
            Up to {{ \App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_FILES }} files, {{ number_format(\App\Support\TrainingModuleFiles::MAX_SUPPLEMENTARY_UPLOAD_KB / 1024) }} MB each.
        </p>
        @error('supplementary_files', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
        @error('supplementary_files.*', $errorBag)<p class="mt-2 text-xs font-bold text-red-600">{{ $message }}</p>@enderror
    </div>

    <label class="flex items-center gap-3 text-sm font-semibold text-slate-700">
        <input type="hidden" name="is_published" value="0">
        <input name="is_published" type="checkbox" value="1" @checked($isPublished)> Publish as the current active batch module
    </label>

    <div class="flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 md:col-span-2 md:flex-row md:justify-end xl:col-span-3">
        <button type="button" data-dashboard-dialog-close class="secondary-action">Cancel</button>
        <button type="submit" class="primary-action" data-action-button>{{ $isEdit ? 'Save changes' : 'Add module' }}</button>
    </div>
</form>
