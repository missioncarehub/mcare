<?php

namespace App\Http\Middleware;

use App\Services\AnnouncementDeliveryService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class EnsureTrainee
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isCurrentTrainee = $user?->hasRole('trainee');
        $isLegacyGraduate = $user?->hasRole('alumni') && $user->isGraduate();

        // Legacy alumni are accepted only when a real graduated enrollment
        // exists; both account types then use the same trainee portal.
        if (! $isCurrentTrainee && ! $isLegacyGraduate) {
            abort(403);
        }

        if ($isCurrentTrainee && $user) {
            $cacheKey = 'announcement-catchup:v2:'.$user->id;
            if (! Cache::has($cacheKey)) {
                try {
                    app(AnnouncementDeliveryService::class)->catchUpFor($user);
                } catch (Throwable $exception) {
                    report($exception);
                }
                Cache::put($cacheKey, true, now()->addMinute());
            }
        }

        return $next($request);
    }
}
