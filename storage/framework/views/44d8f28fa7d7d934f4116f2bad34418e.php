<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your MCARE application is approved</title>
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
                            <h1 style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:normal;line-height:1.4;color:#0f172a;">Your application is approved</h1>
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
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;">MCARE approved your <?php echo e($program); ?> application. You may now open the enrollment form, enter this application number, and complete your TESDA applicant profile. Use the same Gmail address you submitted on the application.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;color:#334155;">
                            <p style="margin:0 0 4px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-weight:normal;color:#64748b;">Application number</p>
                            <p style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:normal;letter-spacing:0.04em;color:#0f172a;"><?php echo e($applicationNumber); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:16px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;font-weight:normal;">
                            <a href="<?php echo e($enrollmentUrl); ?>" style="color:#5b21b6;font-family:Arial,Helvetica,sans-serif;font-weight:normal;text-decoration:underline;">Open enrollment form</a>
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
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\mail\admission-application-approved.blade.php ENDPATH**/ ?>