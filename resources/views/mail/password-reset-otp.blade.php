@extends('mail.official-layout', ['title' => 'MCARE password reset'])

@section('content')
    <tr>
        <td style="padding:4px 0 16px;">
            <h1 style="margin:0;font-family:Arial,Helvetica,sans-serif;font-size:20px;font-weight:normal;line-height:1.4;color:#0f172a;">Reset your MCARE password</h1>
        </td>
    </tr>
    <tr>
        <td style="padding:4px 0 16px;font-family:Arial,Helvetica,sans-serif;font-size:15px;color:#334155;">
            <p style="margin:0 0 12px;">Dear {{ $recipientName }},</p>
            <p style="margin:0;">Use this one-time code to continue changing your MCARE password:</p>
        </td>
    </tr>
    <tr>
        <td style="padding:20px;text-align:center;background:#f5f3ff;border:1px dashed #7c3aed;font-family:monospace;font-size:30px;font-weight:bold;letter-spacing:8px;color:#581c87;">{{ $code }}</td>
    </tr>
    <tr>
        <td style="padding:16px 0;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#64748b;">
            This code expires in <strong>{{ $expiresInMinutes }} minutes</strong>. If you did not request a password reset, ignore this email.
        </td>
    </tr>
@endsection