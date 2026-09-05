<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Avatar and document-file GETs run through the web session. Laravel would
 * otherwise treat them as the previous page, so a later redirect()->back()
 * can open a raw photo instead of the form the admin was using.
 */
class DoNotStoreAsPreviousUrl
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethod('GET') && $this->isEmbeddedFileRequest($request)) {
            $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        }

        return $next($request);
    }

    private function isEmbeddedFileRequest(Request $request): bool
    {
        return $request->is([
            'admin/enrollments/*/photo',
            'admin/accounts/*/photo',
            'admin/enrollments/*/documents/*/content',
            'admin/payment-scheduling/transactions/*/proof',
            'admin/historical-alumni/*/evidence',
            'admin/accounts/historical-alumni/*/evidence',
            'admin/learning/modules/*/content',
            'admin/learning/modules/*/download',
            'account/registrar-signature',
            'trainer/modules/*/content',
            'trainer/modules/*/download',
            'trainer/modules/*/supplementary/*',
            'trainee/modules/*/content',
            'trainee/modules/*/download',
            'trainee/modules/*/supplementary/*',
        ]);
    }
}
