@extends('auth.password-layout', ['title' => 'Change password'])

@section('auth-content')
    <div class="auth-card-heading"><h2>Change password</h2><p>Create a new password for your MCARE account.</p></div>
    @if ($errors->any())<div class="auth-alert auth-alert-error" role="alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.update') }}" class="auth-form">
        @csrf
        <div class="auth-field-group">
            <label for="password">New password</label>
            <div class="auth-password-field">
                <input id="password" name="password" type="password" autocomplete="new-password" required autofocus aria-describedby="password-requirements" placeholder="Enter a strong new password" class="auth-input">
                <button type="button" class="auth-password-toggle" data-password-toggle="password" aria-label="Show new password" title="Show new password">
                    <x-dashboard-icon name="eye" aria-hidden="true" />
                </button>
            </div>
            <p id="password-requirements" class="mt-2 text-xs leading-5 text-slate-500">At least 10 characters, including an uppercase letter (A-Z), a lowercase letter (a-z), and a number (0-9).</p>
            @error('password')<p class="auth-field-error">{{ $message }}</p>@enderror
        </div>
        <div class="auth-field-group">
            <label for="password_confirmation">Confirm new password</label>
            <div class="auth-password-field">
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required placeholder="Repeat your new password" class="auth-input">
                <button type="button" class="auth-password-toggle" data-password-toggle="password_confirmation" aria-label="Show password confirmation" title="Show password confirmation">
                    <x-dashboard-icon name="eye" aria-hidden="true" />
                </button>
            </div>
        </div>
        <button type="submit" class="auth-primary-button"><span>Change password</span><x-dashboard-icon name="arrow-right" aria-hidden="true" /></button>
    </form>
@endsection