<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'month',
    'sessions' => null,
    'monthRoute' => null,
    'selectedDate' => null,
    'eyebrow' => 'Schedule',
    'heading' => 'Training calendar',
    'description' => 'Select a date to review every scheduled session.',
    'emptyMessage' => 'No sessions are scheduled for this date.',
    'showBatch' => true,
    'editable' => false,
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
    'month',
    'sessions' => null,
    'monthRoute' => null,
    'selectedDate' => null,
    'eyebrow' => 'Schedule',
    'heading' => 'Training calendar',
    'description' => 'Select a date to review every scheduled session.',
    'emptyMessage' => 'No sessions are scheduled for this date.',
    'showBatch' => true,
    'editable' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $sessions = collect($sessions ?? [])->values();
    $eventsByDate = $sessions->groupBy('date_key');
    $monthRoute = $monthRoute ?: url()->current();
    $gridStart = $month->copy()->startOfMonth()->startOfWeek(\Illuminate\Support\Carbon::SUNDAY);
    $calendarDays = collect(range(0, 41))->map(fn ($offset) => $gridStart->copy()->addDays($offset));
    $dayKeys = $calendarDays->map->toDateString();
    $todayKey = now()->toDateString();
    $initialDate = $selectedDate && $dayKeys->contains($selectedDate)
        ? $selectedDate
        : ($month->isSameMonth(now())
            ? $todayKey
            : ($sessions->first()['date_key'] ?? $month->toDateString()));
    $calendarId = 'training-calendar-'.substr(md5($heading.$monthRoute.$month->format('Y-m')), 0, 10);
    $baseQuery = collect(request()->query())->except(['page', 'date'])->all();
    $calendarUrl = function ($targetMonth, ?string $date = null) use ($monthRoute, $baseQuery) {
        $query = array_merge($baseQuery, ['month' => $targetMonth->format('Y-m')]);
        if ($date) $query['date'] = $date;

        return $monthRoute.'?'.http_build_query($query);
    };
?>

<section id="<?php echo e($calendarId); ?>" class="training-calendar" data-training-calendar data-initial-date="<?php echo e($initialDate); ?>">
    <header class="training-calendar-toolbar">
        <div class="min-w-0">
            <p class="training-calendar-eyebrow"><?php echo e($eyebrow); ?></p>
            <h2 class="training-calendar-heading"><?php echo e($heading); ?></h2>
            <p class="training-calendar-description"><?php echo e($description); ?></p>
        </div>

        <div class="training-calendar-controls" aria-label="Calendar navigation">
            <a href="<?php echo e($calendarUrl(now()->startOfMonth(), $todayKey)); ?>" class="training-calendar-today">Today</a>
            <a href="<?php echo e($calendarUrl($month->copy()->subMonthNoOverflow())); ?>" class="training-calendar-arrow" aria-label="Previous month" title="Previous month">
                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'chevron-left']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-left']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
            </a>
            <a href="<?php echo e($calendarUrl($month->copy()->addMonthNoOverflow())); ?>" class="training-calendar-arrow" aria-label="Next month" title="Next month">
                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'chevron-right']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chevron-right']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
            </a>
            <form method="GET" action="<?php echo e($monthRoute); ?>" class="training-calendar-month-form">
                <?php $__currentLoopData = $baseQuery; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($key !== 'month' && is_scalar($value)): ?>
                        <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                <label for="<?php echo e($calendarId); ?>-month" class="sr-only">Choose month</label>
                <input id="<?php echo e($calendarId); ?>-month" type="month" name="month" value="<?php echo e($month->format('Y-m')); ?>">
                <button type="submit">Go</button>
            </form>
        </div>
    </header>

    <div class="training-calendar-layout">
        <div class="training-calendar-board">
            <div class="training-calendar-weekdays" aria-hidden="true">
                <?php $__currentLoopData = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $weekday): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <span><?php echo e($weekday); ?></span>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>

            <div class="training-calendar-grid" role="grid" aria-label="<?php echo e($month->format('F Y')); ?> training schedule">
                <?php $__currentLoopData = $calendarDays->chunk(7); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $week): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="training-calendar-week" role="row">
                <?php $__currentLoopData = $week; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php
                        $dateKey = $day->toDateString();
                        $dayEvents = $eventsByDate->get($dateKey, collect());
                        $isCurrentMonth = $day->month === $month->month;
                        $isToday = $dateKey === $todayKey;
                        $isSelected = $dateKey === $initialDate;
                    ?>
                    <button
                        type="button"
                        class="training-calendar-day <?php echo e($isCurrentMonth ? '' : 'is-outside-month'); ?> <?php echo e($isToday ? 'is-today' : ''); ?> <?php echo e($isSelected ? 'is-selected' : ''); ?>"
                        data-calendar-day
                        data-calendar-date="<?php echo e($dateKey); ?>"
                        <?php if(! $isCurrentMonth): ?> data-calendar-month-url="<?php echo e($calendarUrl($day->copy()->startOfMonth(), $dateKey)); ?>" <?php endif; ?>
                        aria-label="<?php echo e($day->format('l, F j, Y')); ?>; <?php echo e($dayEvents->count()); ?> <?php echo e(\Illuminate\Support\Str::plural('session', $dayEvents->count())); ?>"
                        aria-pressed="<?php echo e($isSelected ? 'true' : 'false'); ?>"
                        <?php if($isToday): ?> aria-current="date" <?php endif; ?>
                        role="gridcell"
                    >
                        <span class="training-calendar-date-number"><?php echo e($day->day); ?></span>
                        <span class="training-calendar-event-stack">
                            <?php $__currentLoopData = $dayEvents->take(3); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $session): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <span class="training-calendar-event is-<?php echo e(strtolower($session['period'])); ?>" title="<?php echo e($session['calendar_title'] ?? $session['title']); ?> — <?php echo e($session['time_range']); ?>">
                                    <span class="training-calendar-event-dot" aria-hidden="true"></span>
                                    <span class="training-calendar-event-label"><?php echo e($session['time']); ?> <?php echo e($showBatch ? $session['batch'] : $session['period']); ?></span>
                                </span>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php if($dayEvents->count() > 3): ?>
                                <span class="training-calendar-more">+<?php echo e($dayEvents->count() - 3); ?> more</span>
                            <?php endif; ?>
                        </span>
                    </button>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>

        <aside id="<?php echo e($calendarId); ?>-agenda" class="training-calendar-agenda" aria-live="polite">
            <?php $__currentLoopData = $calendarDays; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $day): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php
                    $dateKey = $day->toDateString();
                    $dayEvents = $eventsByDate->get($dateKey, collect());
                    $isSelected = $dateKey === $initialDate;
                ?>
                <section
                    class="training-calendar-agenda-panel <?php echo e($isSelected ? 'is-active' : ''); ?>"
                    data-calendar-agenda="<?php echo e($dateKey); ?>"
                    <?php if(! $isSelected): ?> hidden <?php endif; ?>
                >
                    <div class="training-calendar-agenda-heading">
                        <div>
                            <p><?php echo e($day->format('l')); ?></p>
                            <h3><?php echo e($day->format('F j, Y')); ?></h3>
                        </div>
                        <span><?php echo e($dayEvents->count()); ?> <?php echo e(\Illuminate\Support\Str::plural('session', $dayEvents->count())); ?></span>
                    </div>

                    <div class="training-calendar-agenda-list">
                        <?php $__empty_1 = true; $__currentLoopData = $dayEvents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $session): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <article class="training-calendar-agenda-session is-<?php echo e(strtolower($session['period'])); ?>">
                                <div class="training-calendar-session-time">
                                    <strong><?php echo e($session['period']); ?></strong>
                                    <time datetime="<?php echo e($session['starts_at']->toIso8601String()); ?>"><?php echo e($session['time_range']); ?></time>
                                </div>
                                <h4><?php echo e($session['title']); ?></h4>
                                <?php if($showBatch): ?>
                                    <p class="training-calendar-session-batch"><?php echo e($session['batch']); ?></p>
                                <?php endif; ?>
                                <p class="training-calendar-session-room">
                                    <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'location-dot']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'location-dot']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                                    <span><?php echo e($session['room']); ?></span>
                                </p>
                                <?php if($editable): ?>
                                    <a href="<?php echo e(route('admin.batches.edit', $session['batch_id'])); ?>" class="training-calendar-edit-link">Edit recurring schedule</a>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <div class="training-calendar-empty">
                                <?php if (isset($component)) { $__componentOriginal9107e671931ebb736f490bfa53db05d4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal9107e671931ebb736f490bfa53db05d4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.dashboard-icon','data' => ['name' => 'calendar-days']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('dashboard-icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'calendar-days']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $attributes = $__attributesOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__attributesOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal9107e671931ebb736f490bfa53db05d4)): ?>
<?php $component = $__componentOriginal9107e671931ebb736f490bfa53db05d4; ?>
<?php unset($__componentOriginal9107e671931ebb736f490bfa53db05d4); ?>
<?php endif; ?>
                                <p><?php echo e($emptyMessage); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </aside>
    </div>
</section>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\components\training-calendar.blade.php ENDPATH**/ ?>