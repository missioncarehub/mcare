<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetOtpMail;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Throwable;

class ForgotPasswordController extends Controller
{
    private const SESSION_KEY = 'password_reset.challenge';

    public function create(Request $request): View
    {
        return view('auth.forgot-password', [
            'email' => $request->session()->get('password_reset.email', old('email')),
        ]);
    }

    public function sendOtp(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $email = Str::lower(trim($validated['email']));
        $user = User::query()->where('email', $email)->first();

        if (! $user) {
            return back()
                ->withErrors(['email' => 'No MCARE account was found with that email address.'])
                ->withInput();
        }

        $ttlMinutes = max(1, (int) config('services.password_reset.otp_ttl', 10));
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        try {
            Mail::to($user->email)->send(new PasswordResetOtpMail(
                recipientName: $user->name ?: 'MCARE account holder',
                code: $code,
                expiresInMinutes: $ttlMinutes,
            ));
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['email' => 'We could not send the OTP. Check the SMTP configuration or try again later.'])
                ->withInput();
        }

        $request->session()->put('password_reset.email', $email);
        $request->session()->put(self::SESSION_KEY, [
            'user_id' => $user->id,
            'hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($ttlMinutes)->timestamp,
            'attempts' => 0,
        ]);

        return redirect()
            ->route('password.otp')
            ->with('password_reset_notice', 'A six-digit OTP was sent to your email address.');
    }

    public function otpForm(Request $request): View|RedirectResponse
    {
        if (! is_array($request->session()->get(self::SESSION_KEY))) {
            return redirect()->route('password.request');
        }

        return view('auth.password-otp', [
            'email' => $request->session()->get('password_reset.email'),
        ]);
    }

    public function verifyOtp(Request $request): RedirectResponse
    {
        $challenge = $request->session()->get(self::SESSION_KEY);
        if (! is_array($challenge) || ! isset($challenge['user_id'])) {
            return redirect()->route('password.request')->withErrors(['email' => 'Your OTP session has expired. Request a new code.']);
        }

        $validated = $request->validate(['otp' => ['required', 'digits:6']]);
        $attempts = (int) ($challenge['attempts'] ?? 0);

        if (now()->timestamp >= (int) ($challenge['expires_at'] ?? 0) || $attempts >= 5) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('password.request')->withErrors(['email' => 'That OTP has expired. Request a new code.']);
        }

        $challenge['attempts'] = $attempts + 1;
        $request->session()->put(self::SESSION_KEY, $challenge);

        if (! Hash::check($validated['otp'], (string) ($challenge['hash'] ?? ''))) {
            return back()->withErrors(['otp' => $challenge['attempts'] >= 5
                ? 'Too many incorrect OTP attempts. Request a new code.'
                : 'The OTP is incorrect.']);
        }

        $request->session()->put('password_reset.verified', true);

        return redirect()->route('password.reset');
    }

    public function resetForm(Request $request): View|RedirectResponse
    {
        if (! $request->session()->get('password_reset.verified')) {
            return redirect()->route('password.request');
        }

        return view('auth.reset-password');
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        if (! $request->session()->get('password_reset.verified')) {
            return redirect()->route('password.request');
        }

        $validated = $request->validate([
            'password' => ['required', 'confirmed', 'max:255', Password::min(10)->mixedCase()->letters()->numbers()],
        ]);
        $challenge = $request->session()->get(self::SESSION_KEY);
        $user = is_array($challenge) ? User::query()->find($challenge['user_id'] ?? 0) : null;

        if (! $user) {
            $request->session()->forget(['password_reset.email', self::SESSION_KEY, 'password_reset.verified']);

            return redirect()->route('password.request')->withErrors(['email' => 'That account is no longer available.']);
        }

        $user->forceFill(['password' => $validated['password']])->save();
        $request->session()->forget(['password_reset.email', self::SESSION_KEY, 'password_reset.verified']);

        try {
            $user->notify(new PasswordChangedNotification(
                changedAt: now()->format('F j, Y \a\t g:i A T'),
                ipAddress: $request->ip(),
                source: 'Password Reset',
            ));
        } catch (Throwable $exception) {
            report($exception);
        }

        return redirect()->route('login')->with('password_reset', 'Your password was changed successfully. You can now sign in.');
    }
}