<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your MCARE <?php echo e($roleLabel); ?> account</title>
</head>
<body style="margin:0;padding:0;background:#ffffff;color:#1e293b;font-family:Arial,Helvetica,sans-serif;font-weight:normal;line-height:1.6;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff;font-family:Arial,Helvetica,sans-serif;">
        <tr>
            <td align="center" style="padding:36px 24px 44px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:560px;margin:0 auto;font-family:Arial,Helvetica,sans-serif;">
                    <tr>
                        <td style="padding:0 0 16px;">
                            <img src="https://iili.io/nHTStnf.md.png" alt="MCARE" width="72" style="width:72px;max-width:72px;height:auto;display:block;border:0;" />
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;color:#475569;">
                            <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;color:#334155;">Mission Care Training and Assessment Center</p>
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;color:#64748b;">Caregiving NC II</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0 16px;">
                            <h1 style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:normal;line-height:1.4;color:#0f172a;">Your <?php echo e($roleLabel); ?> account is ready</h1>
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
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;">An administrator has created your MCARE Hub <?php echo e($roleLabel); ?> account. Please verify your email address, then sign in with the credentials below. Change the temporary password after your first sign-in, and do not share it with anyone.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;color:#334155;">
                            <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:normal;color:#64748b;">Email</p>
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;color:#0f172a;"><?php echo e($accountEmail); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;color:#334155;">
                            <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:normal;color:#64748b;">Temporary password</p>
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;color:#0f172a;"><?php echo e($plainPassword); ?></p>
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
                        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;">
                            <a href="<?php echo e($loginUrl); ?>" style="color:#5b21b6;font-family:Arial,Helvetica,sans-serif;font-weight:normal;text-decoration:underline;">Sign in to MCARE Hub</a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;color:#64748b;">
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;">If you did not expect this account, please contact MCARE administration before signing in.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;color:#64748b;">
                            <p style="margin:0 0 2px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;color:#334155;">Mission Care Training Center (MCTC)</p>
                            <p style="margin:0 0 2px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;">San Isidro Poblacion, Pili, Camarines Sur</p>
                            <p style="margin:0 0 12px;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:normal;">09298202898</p>
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:12px;font-weight:normal;color:#94a3b8;">&copy; <?php echo e(date('Y')); ?> <?php echo e(config('app.name', 'MCARE')); ?></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\mail\staff-account-credentials.blade.php ENDPATH**/ ?>