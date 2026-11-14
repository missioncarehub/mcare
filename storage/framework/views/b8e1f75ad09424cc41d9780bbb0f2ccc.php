<?php
    $isAvailable = $alumniProfile->is_available_for_duty;
?>

<form
    method="POST"
    action="<?php echo e($action); ?>"
    <?php if($confirm ?? true): ?> data-confirm="Update your caregiver availability?" <?php endif; ?>
    class="flex shrink-0 items-center"
>
    <?php echo csrf_field(); ?>
    <?php echo method_field('PATCH'); ?>
    <label class="dashboard-availability-toggle">
        <input type="hidden" name="is_available_for_duty" value="0">
        <input
            type="checkbox"
            name="is_available_for_duty"
            value="1"
            class="dashboard-availability-toggle__input"
            role="switch"
            aria-checked="<?php echo e($isAvailable ? 'true' : 'false'); ?>"
            aria-labelledby="availability-title"
            <?php if($isAvailable): echo 'checked'; endif; ?>
            onchange="this.form.requestSubmit()"
        >
        <span class="dashboard-availability-toggle__switch" aria-hidden="true">
            <span class="dashboard-availability-toggle__knob"></span>
        </span>
        <span class="dashboard-availability-toggle__status <?php echo e($isAvailable ? 'is-on' : 'is-off'); ?>">
            <?php echo e($isAvailable ? 'Available' : 'Unavailable'); ?>

        </span>
    </label>
</form>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\trainee\partials\career-availability-toggle.blade.php ENDPATH**/ ?>