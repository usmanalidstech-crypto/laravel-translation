<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CdnHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Add CDN-friendly headers
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        
        // CDN caching headers
        if ($request->is('api/export*')) {
            $response->headers->set('Cache-Control', 'public, max-age=300, s-maxage=600');
            $response->headers->set('CDN-Cache-Control', 'public, max-age=600');
            $response->headers->set('Vary', 'Accept-Encoding, Authorization');
        } else {
            $response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        // CORS headers for CDN compatibility
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
        $response->headers->set('Access-Control-Max-Age', '86400');

        // Compression headers
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }
}
