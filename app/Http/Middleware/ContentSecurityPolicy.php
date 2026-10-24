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
            "script-src 'self' https://static.line-scdn.net https://*.line-scdn.net 'sha256-tlsXfg8h5KkfyREFcD0sSChT6TIEk9yUVIIOsB6wuDE='",
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

        return $response;
    }
}
