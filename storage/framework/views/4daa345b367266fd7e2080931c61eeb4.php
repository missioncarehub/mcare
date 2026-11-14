<?php $__env->startSection('content'); ?>
    <tr>
        <td style="padding:4px 0 16px;">
            <h1 style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:normal;line-height:1.4;color:#0f172a;"><?php echo e($heading); ?></h1>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
        </td>
    </tr>
    <tr>
        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;color:#334155;">
            <p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;">Dear <?php echo e($recipientName); ?>,</p>
            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;"><?php echo e($intro); ?></p>
        </td>
    </tr>
    <?php if(filled($enrollmentNumber)): ?>
        <tr>
            <td style="padding:16px 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
            </td>
        </tr>
        <tr>
            <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;color:#334155;">
                <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:normal;color:#64748b;">Enrollment number</p>
                <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:normal;letter-spacing:0.04em;color:#0f172a;"><?php echo e($enrollmentNumber); ?></p>
            </td>
        </tr>
    <?php endif; ?>
    <?php if(filled($adminNotes)): ?>
        <tr>
            <td style="padding:16px 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
            </td>
        </tr>
        <tr>
            <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;color:#334155;">
                <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:normal;color:#64748b;">Administrator note</p>
                <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;color:#0f172a;"><?php echo e($adminNotes); ?></p>
            </td>
        </tr>
    <?php endif; ?>
    <tr>
        <td style="padding:16px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
        </td>
    </tr>
    <tr>
        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;">
            <a href="<?php echo e($actionUrl); ?>" style="color:#5b21b6;font-family:Arial,Helvetica,sans-serif;font-weight:normal;text-decoration:underline;"><?php echo e($actionLabel); ?></a>
        </td>
    </tr>
    <?php if(filled($secondaryActionUrl) && filled($secondaryActionLabel)): ?>
        <tr>
            <td style="padding:16px 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
            </td>
        </tr>
        <tr>
            <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;">
                <a href="<?php echo e($secondaryActionUrl); ?>" style="color:#5b21b6;font-family:Arial,Helvetica,sans-serif;font-weight:normal;text-decoration:underline;"><?php echo e($secondaryActionLabel); ?></a>
            </td>
        </tr>
    <?php endif; ?>
    <?php if(filled($closing)): ?>
        <tr>
            <td style="padding:16px 0;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
            </td>
        </tr>
        <tr>
            <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;color:#64748b;">
                <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;"><?php echo e($closing); ?></p>
            </td>
        </tr>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('mail.official-layout', ['title' => $title], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\mail\enrollment-status.blade.php ENDPATH**/ ?>