<?php $__env->startSection('content'); ?>
    <tr>
        <td style="padding:4px 0 16px;">
            <h1 style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:normal;line-height:1.4;color:#0f172a;">Verify your email address</h1>
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
            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;">MCARE needs you to verify this email address before you can sign in. Use the same Gmail account you used for enrollment, then open the verification link below.</p>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
        </td>
    </tr>
    <tr>
        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;">
            <a href="<?php echo e($verificationUrl); ?>" style="color:#5b21b6;font-family:Arial,Helvetica,sans-serif;font-weight:normal;text-decoration:underline;">Verify your email address</a>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 0;">
            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
        </td>
    </tr>
    <tr>
        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;color:#64748b;">
            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;">If you did not create a MCARE account, you can ignore this message.</p>
        </td>
    </tr>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('mail.official-layout', ['title' => 'Verify your email address'], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\mail\verify-email.blade.php ENDPATH**/ ?>