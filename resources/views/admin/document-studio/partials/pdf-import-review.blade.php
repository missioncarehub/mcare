<div id="conversion-review" hidden class="space-y-3">
    <div class="flex flex-wrap gap-2 items-center">
        <button type="button" id="conversion-previous" class="border rounded-lg min-h-[44px] px-3">Previous</button>
        <span id="conversion-page" class="text-sm"></span>
        <button type="button" id="conversion-next" class="border rounded-lg min-h-[44px] px-3">Next</button>
    </div>
    <p class="text-sm text-gray-500 dark:text-gray-400">For scans, select each value to replace and add text or an MCARE field. Automatic OCR is unavailable.</p>
    <div style="max-height:65vh;overflow:auto;border:1px solid #94a3b8"><canvas id="conversion-canvas" style="max-width:100%;display:block;touch-action:none" aria-label="PDF page for selecting replacement regions"></canvas></div>
    <fieldset class="border rounded-lg p-3 space-y-3">
        <legend class="text-sm font-medium">Manual replacement region</legend>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
            @foreach(['x' => 'Left (mm)', 'y' => 'Top (mm)', 'width' => 'Width (mm)', 'height' => 'Height (mm)'] as $field => $label)
                <label class="text-sm">{{ $label }}<input id="region-{{ $field }}" type="number" min="0" max="1000" step="0.1" value="{{ in_array($field, ['width', 'height']) ? 20 : 0 }}" class="block w-full min-h-[44px] rounded-lg bg-white dark:bg-gray-900" /></label>
            @endforeach
        </div>
        <label class="block text-sm">Replacement text<input id="region-content" maxlength="2000" class="block w-full min-h-[44px] rounded-lg bg-white dark:bg-gray-900" placeholder="Type text or choose an MCARE field" /></label>
        <label class="block text-sm">MCARE field<select id="region-binding" class="block w-full min-h-[44px] rounded-lg bg-white dark:bg-gray-900"><option value="">Choose field</option>@foreach($placeholders as $key => $definition)<option value="{{ $key }}">{{ $definition['label'] ?? $key }}</option>@endforeach</select></label>
        <label class="flex items-center gap-3 text-sm">Background colour<input type="color" id="region-color" value="#ffffff" style="width:48px;height:44px" /></label>
        <button type="button" id="region-add" class="border rounded-lg min-h-[44px] px-3">Add region</button>
        <ul id="region-list" class="space-y-2 text-sm"></ul>
    </fieldset>
</div>
