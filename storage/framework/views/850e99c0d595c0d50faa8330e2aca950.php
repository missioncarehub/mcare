<?php
    $fullName = preg_replace('/\s+/', ' ', trim($application->first_name.' '.$application->middle_name.' '.$application->last_name.' '.$application->extension_name)) ?: 'Applicant';
    $batch = $application->batch;
    $programName = $application->program ?: $batch?->program?->name ?: 'Training program';
    $amount = 'PHP '.number_format((float) ($application->downpayment_amount ?: $application->payment_amount ?: 0), 2);
    $scheduleLabel = $batch?->scheduleLabelFor($application->schedule_preference) ?? 'Schedule to be confirmed';
    $roomLabel = $batch?->roomFor($application->schedule_preference) ?: 'Room to be announced';
    $address = collect([
        $application->street,
        $application->barangay,
        $application->city,
        $application->province,
        $application->region,
        $application->zip_code,
    ])->filter()->implode(', ');
?>

<div class="enrollment-lookup-summary">
<dl class="enrollment-status-row">
    <div>
        <dt>Applicant</dt>
        <dd><?php echo e($fullName); ?><span><?php echo e($application->email); ?></span></dd>
    </div>
    <div>
        <dt>Program</dt>
        <dd><?php echo e($programName); ?><span><?php echo e($batch ? $batch->name.' '.$batch->year : 'Batch to be assigned'); ?></span></dd>
    </div>
    <div>
        <dt>Downpayment</dt>
        <dd><?php echo e($amount); ?><span><?php echo e($application->paymentStatusLabel()); ?></span></dd>
    </div>
</dl>

<dl class="enrollment-payment-facts enrollment-lookup-facts">
    <div>
        <dt>Contact</dt>
        <dd><?php echo e($application->contact_number ?: 'Not on file'); ?></dd>
    </div>
    <div>
        <dt>Class</dt>
        <dd><?php echo e($application->schedule_preference); ?> · <?php echo e($scheduleLabel); ?> · <?php echo e($roomLabel); ?></dd>
    </div>
    <div>
        <dt>Enrollment status</dt>
        <dd><?php echo e($application->statusLabel()); ?></dd>
    </div>
    <?php if(filled($address)): ?>
        <div>
            <dt>Address</dt>
            <dd><?php echo e($address); ?></dd>
        </div>
    <?php endif; ?>
</dl>
</div>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\enrollment\partials\enrollment-summary.blade.php ENDPATH**/ ?>