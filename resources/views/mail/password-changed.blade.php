@extends('mail.official-layout', ['title' => 'Your MCARE password was changed'])

@section('content')
    <tr>
        <td style="padding:4px 0 16px;">
            <h1 style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:bold;line-height:1.4;color:#0f172a;">Your password was changed</h1>
        </td>
    </tr>
    <tr>
        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#334155;line-height:1.6;">
            <p style="margin:0 0 12px;">Dear {{ $recipientName }},</p>
            <p style="margin:0;">This is a security confirmation that the password for your MCARE account was successfully updated on <strong>{{ $changedAt }}</strong>.</p>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 20px;background:#f8fafc;border-left:4px solid #7c3aed;border-radius:4px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#475569;">
            <p style="margin:0 0 8px;"><strong style="color:#1e293b;">Security Details:</strong></p>
            <p style="margin:0 0 4px;">• <strong>Method:</strong> {{ $source }}</p>
            <p style="margin:0 0 4px;">• <strong>Date &amp; Time:</strong> {{ $changedAt }}</p>
            @if(filled($ipAddress))
                <p style="margin:0;">• <strong>IP Address:</strong> {{ $ipAddress }}</p>
            @endif
        </td>
    </tr>
    <tr>
        <td style="padding:20px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#334155;line-height:1.6;">
            <p style="margin:0 0 10px;"><strong style="color:#0f172a;">Did you make this change?</strong></p>
            <p style="margin:0 0 12px;">If you initiated this change, no further action is required and you may safely keep this confirmation for your records.</p>
            <p style="margin:0;color:#b91c1c;"><strong>If you did NOT make this change:</strong> Your account security may be compromised. Please reset your password immediately using the button below or contact MCARE administration at <strong>09298202898</strong>.</p>
        </td>
    </tr>
    <tr>
        <td style="padding:16px 0 8px;text-align:center;">
            <a href="{{ route('password.request') }}" style="display:inline-block;padding:12px 28px;background-color:#7c3aed;color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:14px;font-weight:bold;text-decoration:none;border-radius:8px;">Reset your password</a>
        </td>
    </tr>
@endsection
