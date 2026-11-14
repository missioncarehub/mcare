<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'enabled' => false,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'enabled' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php if($enabled): ?>
    <div class="landing-chat" data-landing-chat data-chat-url="<?php echo e(route('landing.chat')); ?>">
        <button
            type="button"
            class="landing-chat-toggle"
            data-landing-chat-toggle
            aria-expanded="false"
            aria-controls="landing-chat-panel"
            title="Open MCARE assistant"
        >
            <span class="sr-only">Open MCARE assistant</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="landing-chat-toggle-icon" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 6.5A3.5 3.5 0 0 1 8.5 3H16a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3h-4.2L8 19.5V16H8.5A3.5 3.5 0 0 1 5 12.5v-6Z"/>
            </svg>
        </button>

        <section
            id="landing-chat-panel"
            class="landing-chat-panel"
            data-landing-chat-panel
            hidden
            aria-labelledby="landing-chat-title"
        >
            <header class="landing-chat-head">
                <div class="landing-chat-head-copy">
                    <p class="landing-chat-kicker">MCARE Hub</p>
                    <h2 id="landing-chat-title" class="landing-chat-title">MCARE assistant</h2>
                    <p class="landing-chat-note">Public training questions only. Do not send passwords or IDs.</p>
                </div>
                <button type="button" class="landing-chat-close" data-landing-chat-close>
                    Close
                </button>
            </header>

            <div class="landing-chat-thread" data-landing-chat-thread role="log" aria-live="polite"></div>

            <form class="landing-chat-form" data-landing-chat-form>
                <label class="sr-only" for="landing-chat-input">Ask the MCARE assistant</label>
                <textarea
                    id="landing-chat-input"
                    name="message"
                    rows="1"
                    maxlength="500"
                    required
                    placeholder="Ask about this website in any language"
                    data-landing-chat-input
                ></textarea>
                <button type="submit" class="landing-chat-send" data-landing-chat-send>
                    <span class="sr-only">Send message</span>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </button>
            </form>
        </section>
    </div>
<?php endif; ?>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\components\landing-chat.blade.php ENDPATH**/ ?>