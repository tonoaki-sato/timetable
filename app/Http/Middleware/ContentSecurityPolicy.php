<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    /**
     * Handle an incoming request and attach Content-Security-Policy header.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // APIやJSONレスポンスにはCSPヘッダーを付与しない
        if ($request->is('api/*') || $request->expectsJson()) {
            return $response;
        }

        $cspPolicy = implode('; ', [
            "default-src 'self'",
            "script-src 'self' https://static.line-scdn.net https://*.line-scdn.net",
            "style-src 'self' https://fonts.bunny.net",
            "font-src 'self' https://fonts.bunny.net data:",
            "img-src 'self' data: https:",
            "connect-src 'self' https://api.line.me https://*.line.me https://access.line.me",
            "frame-src 'self' https://access.line.me https://*.line.me https://line.me",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'self' https://*.line.me https://line.me",
        ]);

        $response->headers->set('Content-Security-Policy', $cspPolicy);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
