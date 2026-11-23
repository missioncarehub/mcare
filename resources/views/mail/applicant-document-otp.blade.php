@extends('mail.official-layout', ['title' => 'MCARE Document Verification Code'])

@section('content')
    <tr>
        <td style="padding:4px 0 16px;">
            <h1 style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:bold;line-height:1.4;color:#0f172a;">Document Verification Code</h1>
        </td>
    </tr>
    <tr>
        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#334155;line-height:1.6;">
            <p style="margin:0 0 12px;">Dear {{ $recipientName }},</p>
            <p style="margin:0 0 12px;">You requested to upload or replace a document requirement for your application (<strong>{{ $applicationNumber }}</strong>).</p>
            <p style="margin:0;">Please use the one-time verification code below to authorize document changes on your status page:</p>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 0;text-align:center;">
            <div style="display:inline-block;padding:16px 32px;background:#f5f3ff;border:2px dashed #7c3aed;border-radius:12px;text-align:center;">
                <span style="font-family:'Courier New',Courier,monospace;font-size:32px;font-weight:bold;letter-spacing:10px;color:#5b21b6;margin-left:10px;">{{ $otpCode }}</span>
            </div>
            <p style="margin:12px 0 0;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#64748b;">This code is valid for <strong>10 minutes</strong>.</p>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 20px;background:#f8fafc;border-left:4px solid #7c3aed;border-radius:4px;font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#475569;line-height:1.5;">
            <p style="margin:0 0 4px;"><strong style="color:#1e293b;">Security Reminder:</strong></p>
            <p style="margin:0;">Never share this verification code with anyone. MCARE staff will never ask you for this code. If you did not request this update, you can safely ignore this email.</p>
        </td>
    </tr>
    <tr>
        <td style="padding:20px 0 8px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#64748b;">
            <p style="margin:0;">Thank you,<br><strong style="color:#334155;">MCARE Admissions &amp; Registrar</strong></p>
        </td>
    </tr>
@endsection
