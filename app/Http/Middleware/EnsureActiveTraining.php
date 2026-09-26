<?php

namespace App\Http\Middleware;

use App\Models\EnrollmentApplication;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveTraining
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user?->hasRole('trainee') && ! $user->isGraduate(), 403);

        // Path: app/Http/Middleware/EnsureActiveTraining.php | Label: Require approved enrollment before LMS access
        // A user with the "trainee" role may exist as soon as they submit the enrollment form,
        // but must not reach LMS modules, stream, quizzes, or classwork downloads until the
        // administrator explicitly approves their enrollment application.
        $hasApprovedEnrollment = EnrollmentApplication::query()
            ->where('user_id', $user->id)
            ->where('status', EnrollmentApplication::STATUS_APPROVED)
            ->whereNull('archived_at')
            ->exists();

        if (! $hasApprovedEnrollment) {
            return redirect()
                ->route('payments.show')
                ->with(
                    'payment_notice',
                    'Your enrollment is still being reviewed. Classwork, modules, and other learning tools open only after MCARE approves your enrollment.',
                );
        }

        return $next($request);
    }
}
