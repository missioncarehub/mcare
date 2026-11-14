@extends('auth.password-layout', ['title' => 'Change password'])

@section('auth-content')
    <div class="auth-card-heading"><h2>Change password</h2><p>Create a new password for your MCARE account.</p></div>
    @if ($errors->any())<div class="auth-alert auth-alert-error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.update') }}" class="auth-form">
        @csrf
        <div class="auth-field-group"><label for="password">New password</label><input id="password" name="password" type="password" autocomplete="new-password" required autofocus placeholder="Enter a strong new password" class="auth-input">@error('password')<p class="auth-field-error">{{ $message }}</p>@enderror</div>
        <div class="auth-field-group"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required placeholder="Repeat your new password" class="auth-input"></div>
        <button type="submit" class="auth-primary-button"><span>Change password</span><x-dashboard-icon name="arrow-right" aria-hidden="true" /></button>
    </form>
@endsection