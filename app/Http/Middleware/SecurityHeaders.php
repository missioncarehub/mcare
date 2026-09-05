<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Add a security baseline to every HTTP response.
 *
 * Important: headers are defense-in-depth. They do not replace validation,
 * authorization, CSRF protection, or safe database queries.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $isEmbeddableContent = $request->routeIs(
            'trainer.modules.content',
            'trainee.modules.content',
            'admin.enrollments.documents.content'
        );

        // Prevent browsers from guessing a different MIME type than the server sent.
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // MCARE pages are not designed to be embedded in iframes. This reduces
        // clickjacking risk, where a malicious site overlays our buttons invisibly.
        $response->headers->set(
            'X-Frame-Options',
            $isEmbeddableContent ? 'SAMEORIGIN' : 'DENY'
        );

        // Modern browsers use CSP, while this explicit value disables unsafe
        // legacy XSS filters that could create their own injection behavior.
        $response->headers->set('X-XSS-Protection', '0');

        // Isolate MCARE from cross-origin windows and resource embedding while
        // retaining compatibility with OAuth redirects opened by the browser.
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin-allow-popups');
        $response->headers->set('Cross-Origin-Resource-Policy', 'same-origin');

        /*
         * Use a safe public default, but do not overwrite a stricter policy set
         * by PrivateResponseHeaders (for example, `no-referrer` on PII pages).
         */
        if (! $response->headers->has('Referrer-Policy')) {
            $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        // Disable browser capabilities that the current application does not need.
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // Legacy Adobe cross-domain policy files should not grant access to this app.
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        if (app()->isProduction()) {
            /*
             * Current Blade pages contain inline JavaScript (for example, the
             * signature pad), so 'unsafe-inline' is temporarily required here.
             * A future hardening step should move inline scripts to Vite bundles
             * and replace this with nonces or hashes.
             */
            $frameAncestors = $isEmbeddableContent ? "'self'" : "'none'";

            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; base-uri 'self'; form-action 'self'; object-src 'none'; frame-ancestors {$frameAncestors}; "
                ."frame-src 'self' blob: https://www.facebook.com https://facebook.com; img-src 'self' data: blob: https:; "
                ."font-src 'self' data: https://fonts.gstatic.com; "
                ."style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; "
                ."script-src 'self' 'unsafe-inline' blob:; worker-src 'self' blob:; "
                ."connect-src 'self' https:; upgrade-insecure-requests"
            );

            // Only advertise HSTS when the current production request is really HTTPS.
            // Enabling HSTS on plain HTTP would lock browsers into a broken setup.
            if ($request->isSecure()) {
                $response->headers->set(
                    'Strict-Transport-Security',
                    'max-age=31536000; includeSubDomains'
                );
            }
        }

        return $response;
    }
}
