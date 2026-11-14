<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCARE Verification Code</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;color:#334155;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;line-height:1.6">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#f8fafc;padding:32px 16px;">
        <tr>
            <td align="center">
                <!-- Header -->
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:560px;margin-bottom:20px;text-align:center;">
                    <tr>
                        <td align="center">
                            <img src="<?php echo e(rtrim(config('app.url'), '/') . '/assets/official-logo.png'); ?>" alt="MCARE Logo" width="56" height="56" style="width:56px;height:56px;max-width:56px;border-radius:14px;display:block;margin:0 auto 10px;border:0;box-shadow:0 4px 12px rgba(124,58,237,0.15);" />
                            <span style="display:block;font-size:18px;font-weight:800;color:#581c87;letter-spacing:-0.02em;line-height:1.2;">MISSION CARE</span>
                            <span style="display:block;font-size:11px;font-weight:700;color:#9333ea;letter-spacing:0.14em;text-transform:uppercase;margin-top:2px;">Training Center · Caregiving NC II</span>
                        </td>
                    </tr>
                </table>

                <!-- Main Card -->
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:560px;background:#ffffff;border:1px solid #e9d5ff;border-top:4px solid #7c3aed;border-radius:16px;box-shadow:0 10px 25px -5px rgba(124,58,237,0.08);">
                    <tr>
                        <td style="padding:36px 32px 32px;">
                            <p style="margin:0;color:#7c3aed;font-size:12px;font-weight:700;letter-spacing:.12em;text-transform:uppercase;">Admin Security Verification</p>
                            <h1 style="margin:10px 0 12px;font-size:22px;font-weight:800;color:#4c1d95;letter-spacing:-0.02em;">Your 2FA Login Code</h1>
                            <p style="margin:0 0 24px;font-size:15px;color:#334155;line-height:1.6;">Hello <strong><?php echo e($recipientName); ?></strong>, use the one-time verification code below to complete your administrative login to MCARE Hub.</p>
                            
                            <div style="padding:20px;text-align:center;background:#faf5ff;border:2px dashed #c084fc;border-radius:12px;margin:24px 0;">
                                <span style="font-size:36px;font-weight:800;letter-spacing:.32em;color:#581c87;font-family:monospace;"><?php echo e($code); ?></span>
                            </div>

                            <p style="margin:20px 0 0;font-size:13px;color:#64748b;line-height:1.5;">⏳ This code expires in <strong><?php echo e($expiresInMinutes); ?> minutes</strong>. If you did not request this login, please notify system administration immediately.</p>
                            <div style="margin-top:24px;padding-top:20px;border-top:1px solid #f1f5f9;">
                                <p style="margin:0;font-size:12px;color:#94a3b8;">🔒 MCARE will never ask you to share your verification code or credentials with anyone.</p>
                            </div>
                        </td>
                    </tr>
                </table>

                <!-- Footer -->
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:560px;margin-top:24px;text-align:center;">
                    <tr>
                        <td style="padding:0 20px;">
                            <p style="margin:0 0 4px;font-size:12px;font-weight:700;color:#581c87;">Mission Care Training Center (MCTC)</p>
                            <p style="margin:0 0 6px;font-size:11px;color:#64748b;">San Isidro Poblacion, Pili, Camarines Sur · 📞 09298202898</p>
                            <p style="margin:0;font-size:11px;color:#cbd5e1;">© <?php echo e(date('Y')); ?> <?php echo e(config('app.name', 'MCARE')); ?>. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\mail\two-factor-code.blade.php ENDPATH**/ ?>