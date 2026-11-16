@php
    $toastItems = [];

    $pushToast = function (string $type, string|array $message, int $duration, ?string $icon = null) use (&$toastItems): void {
        $messages = is_array($message)
            ? $message
            : [trim($message)];
        $messages = array_values(array_unique(array_filter(
            array_map(fn ($item) => trim((string) $item), $messages),
            fn ($item) => $item !== '',
        )));

        if ($messages === []) {
            return;
        }

        $toastItems[] = [
            'type' => $type,
            'messages' => $messages,
            'duration' => $duration,
            'icon' => $icon,
        ];
    };

    if (session('saved')) {
        $pushToast('success', (string) session('saved'), 5000, (string) session('saved_icon', 'circle-check'));
    }

    if (session('status')) {
        $pushToast('success', (string) session('status'), 5000);
    }

    if (session('verification_notice')) {
        $pushToast('info', (string) session('verification_notice'), 7000);
    }

    $errorMessages = collect($errors->getBags())
        ->flatMap(fn ($bag) => $bag->all())
        ->map(fn ($message) => trim((string) $message))
        ->filter()
        ->unique()
        ->values()
        ->all();

    $sessionError = trim((string) (session('error') ?? session('alert') ?? ''));

    if ($sessionError !== '' && ! in_array($sessionError, $errorMessages, true)) {
        $errorMessages[] = $sessionError;
    }

    if ($errorMessages !== []) {
        $pushToast('error', $errorMessages, 8000);
    }

    $toastIcons = [
        'success' => 'circle-check',
        'error' => 'circle-minus',
        'info' => 'bell',
    ];
@endphp

@if ($toastItems !== [])
    <div
        class="dashboard-toast-region"
        data-dashboard-toast-region
        aria-live="polite"
        aria-relevant="additions"
    >
        @foreach ($toastItems as $toast)
            <div
                class="dashboard-toast dashboard-toast-{{ $toast['type'] }}"
                data-dashboard-toast
                data-auto-dismiss="{{ $toast['duration'] }}"
                data-flash-icon="{{ $toast['icon'] ?? $toastIcons[$toast['type']] ?? 'bell' }}"
                role="{{ $toast['type'] === 'error' ? 'alert' : 'status' }}"
            >
                <span class="dashboard-toast-icon" aria-hidden="true">
                    <x-dashboard-icon :name="$toast['icon'] ?? $toastIcons[$toast['type']] ?? 'bell'" class="h-4 w-4" />
                </span>
                <div class="dashboard-toast-body">
                    @if (count($toast['messages']) === 1)
                        <p>{{ $toast['messages'][0] }}</p>
                    @else
                        <ul>
                            @foreach ($toast['messages'] as $message)
                                <li>{{ $message }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <button type="button" class="dashboard-toast-dismiss" data-dashboard-toast-dismiss aria-label="Dismiss notification">
                    <x-dashboard-icon name="xmark" class="h-3.5 w-3.5" />
                </button>
            </div>
        @endforeach
    </div>
@endif
