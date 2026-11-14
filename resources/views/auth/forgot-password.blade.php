@extends('auth.password-layout', ['title' => 'Forgot password'])

@section('auth-content')
    <div class="auth-card-heading">
        <h2>Forgot password?</h2>
        <p>Enter your MCARE account email and we will send a one-time code.</p>
    </div>
    @if ($errors->any())<div class="auth-alert auth-alert-error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.email') }}" class="auth-form">
        @csrf
        <div class="auth-field-group">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required autofocus placeholder="Enter your email address" class="auth-input">
            @error('email')<p class="auth-field-error">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="auth-primary-button"><span>Send OTP</span><x-dashboard-icon name="arrow-right" aria-hidden="true" /></button>
    </form>
    <div class="auth-secondary-links auth-secondary-links-single"><a href="{{ route('login') }}">Back to sign in</a></div>
@endsection