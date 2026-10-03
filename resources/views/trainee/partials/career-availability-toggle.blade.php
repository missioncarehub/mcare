@php
    $isAvailable = $alumniProfile->is_available_for_duty;
@endphp

<form
    method="POST"
    action="{{ $action }}"
    @if ($confirm ?? true) data-confirm="Update your caregiver availability?" @endif
    class="flex shrink-0 items-center"
>
    @csrf
    @method('PATCH')
    <label class="dashboard-availability-toggle">
        <input type="hidden" name="is_available_for_duty" value="0">
        <input
            type="checkbox"
            name="is_available_for_duty"
            value="1"
            class="dashboard-availability-toggle__input"
            role="switch"
            aria-checked="{{ $isAvailable ? 'true' : 'false' }}"
            aria-labelledby="availability-title"
            @checked($isAvailable)
            onchange="this.form.requestSubmit()"
        >
        <span class="dashboard-availability-toggle__switch" aria-hidden="true">
            <span class="dashboard-availability-toggle__knob"></span>
        </span>
        <span class="dashboard-availability-toggle__status {{ $isAvailable ? 'is-on' : 'is-off' }}">
            {{ $isAvailable ? 'Available' : 'Unavailable' }}
        </span>
    </label>
</form>
