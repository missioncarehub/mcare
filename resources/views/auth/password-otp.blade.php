@extends('auth.password-layout', ['title' => 'Enter OTP'])

@section('auth-content')
    <div class="auth-card-heading"><h2>Enter OTP</h2><p>We sent a six-digit code to {{ $email }}.</p></div>
    @if (session('password_reset_notice'))<div class="auth-alert auth-alert-notice" role="status">{{ session('password_reset_notice') }}</div>@endif
    @if ($errors->any())<div class="auth-alert auth-alert-error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.otp.verify') }}" class="auth-form">
        @csrf
        <div class="auth-field-group"><label for="otp">One-time password</label><input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" maxlength="6" required autofocus placeholder="000000" class="auth-input auth-code-input">@error('otp')<p class="auth-field-error">{{ $message }}</p>@enderror</div>
        <button type="submit" class="auth-primary-button"><span>Verify OTP</span><x-dashboard-icon name="arrow-right" aria-hidden="true" /></button>
    </form>
    <div class="auth-secondary-links auth-secondary-links-single"><a href="{{ route('password.request') }}">Request a new OTP</a></div>
@endsection