<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MCARE payment receipt</title>
</head>
<body style="margin:0;padding:0;background:#ffffff;color:#111111;font-family:Arial,Helvetica,sans-serif;font-weight:normal;line-height:1.5;">
    <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff;font-family:Arial,Helvetica,sans-serif;">
        <tr>
            <td align="center" style="padding:32px 20px 40px;">
                <table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="max-width:420px;margin:0 auto;font-family:Arial,Helvetica,sans-serif;">
                    <tr>
                        <td style="padding:0 0 16px;text-align:center;">
                            <img src="https://iili.io/nHTStnf.md.png" alt="MCARE" width="64" style="width:64px;max-width:64px;height:auto;display:block;margin:0 auto;border:0;" />
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;text-align:center;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#444444;">
                            Mission Care Training and Assessment Center<br>
                            Caregiving NC II
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#111111;height:2px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0 12px;text-align:center;font-family:Arial,Helvetica,sans-serif;font-size:16px;letter-spacing:0.12em;text-transform:uppercase;color:#111111;">
                            Official payment receipt
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#111111;">
                            <?php if(filled($officialReceiptNumber)): ?>
                                <p style="margin:0 0 4px;font-size:12px;color:#666666;">Official Receipt (OR) #</p>
                                <p style="margin:0 0 16px;font-size:16px;letter-spacing:0.04em;"><?php echo e($officialReceiptNumber); ?></p>
                            <?php endif; ?>
                            <?php if(filled($referenceNumber)): ?>
                                <p style="margin:0 0 4px;font-size:12px;color:#666666;">Reference number</p>
                                <p style="margin:0<?php echo e(filled($paymongoPaymentNumber ?? null) ? ' 0 16px' : ''); ?>;font-size:16px;letter-spacing:0.04em;"><?php echo e($referenceNumber); ?></p>
                            <?php endif; ?>
                            <?php if(filled($paymongoPaymentNumber ?? null)): ?>
                                <p style="margin:0 0 4px;font-size:12px;color:#666666;">PayMongo payment number</p>
                                <p style="margin:0;font-size:16px;letter-spacing:0.04em;"><?php echo e($paymongoPaymentNumber); ?></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0 8px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#111111;">
                            <p style="margin:0 0 10px;"><span style="color:#666666;">Paid by</span><br><?php echo e($recipientName); ?></p>
                            <p style="margin:0 0 10px;"><span style="color:#666666;">Email</span><br><?php echo e($applicationEmail); ?></p>
                            <p style="margin:0 0 10px;"><span style="color:#666666;">Program</span><br><?php echo e($program); ?></p>
                            <?php if(filled($enrollmentNumber)): ?>
                                <p style="margin:0 0 10px;"><span style="color:#666666;">Enrollment number</span><br><?php echo e($enrollmentNumber); ?></p>
                            <?php endif; ?>
                            <p style="margin:0 0 10px;"><span style="color:#666666;">Payment method</span><br><?php echo e($paymentChannel); ?></p>
                            <p style="margin:0 0 10px;"><span style="color:#666666;">Payment type</span><br><?php echo e($paymentType); ?></p>
                            <p style="margin:0;"><span style="color:#666666;">Date paid</span><br><?php echo e($paidAt ?: 'Recorded by MCARE'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#111111;height:2px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;font-family:Arial,Helvetica,sans-serif;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td style="font-size:13px;color:#666666;text-transform:uppercase;letter-spacing:0.08em;"><?php echo e($amountLabel ?? 'Amount paid'); ?></td>
                                    <td align="right" style="font-size:20px;color:#111111;">PHP <?php echo e($amountPaid); ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0 8px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#111111;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation">
                                <tr>
                                    <td style="padding:0 0 8px;color:#666666;">Total paid</td>
                                    <td align="right" style="padding:0 0 8px;">PHP <?php echo e($totalPaid); ?></td>
                                </tr>
                                <tr>
                                    <td style="color:#666666;">Remaining balance</td>
                                    <td align="right">PHP <?php echo e($remainingBalance); ?></td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#666666;">
                            <?php if(! empty($isVerified)): ?>
                                Keep this email for your records. The official BIR receipt, if issued separately by the cashier, remains with MCARE finance.
                            <?php else: ?>
                                This is your MCARE pay-on-site receipt. Bring the official receipt number and reference number to the cashier for verification.
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:12px 0;">
                            <table width="100%" cellpadding="0" cellspacing="0" role="presentation"><tr><td style="background-color:#d4d4d8;height:1px;font-size:1px;line-height:1px;">&nbsp;</td></tr></table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:8px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#666666;text-align:center;">
                            This is your MCARE payment receipt. Keep a copy for your records. The official BIR receipt, if issued, remains with the MCARE cashier.
                            <br><br>
                            Mission Care Training Center (MCTC)<br>
                            San Isidro Poblacion, Pili, Camarines Sur<br>
                            09298202898
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
<?php /**PATH C:\Users\John Lloyd\Desktop\mcare_hub\resources\views\mail\payment-receipt.blade.php ENDPATH**/ ?>