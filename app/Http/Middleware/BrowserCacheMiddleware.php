<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BrowserCacheMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $type = 'short'): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Don't cache authenticated/POST/PUT/DELETE requests
        if ($request->isMethod('POST') || $request->isMethod('PUT') || $request->isMethod('DELETE')) {
            return $response;
        }

        // Don't cache if user is logged in (except specific routes)
        if (auth()->check() && !$this->isPublicRoute($request)) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            return $response;
        }

        return match ($type) {
            // Static assets: 1 year
            'assets' => $this->setCache($response, 'public, max-age=31536000, immutable'),

            // Images: 1 month
            'images' => $this->setCache($response, 'public, max-age=2592000'),

            // Public pages (landing, guide): 1 hour, revalidate
            'public' => $this->setCache($response, 'public, max-age=3600, must-revalidate'),

            // Guide pages: 30 minutes
            'guide' => $this->setCache($response, 'public, max-age=1800, must-revalidate'),

            // API responses: no cache
            'api' => $this->setCache($response, 'no-store'),

            // Default: short cache
            default => $this->setCache($response, 'private, max-age=300, must-revalidate'),
        };
    }

    private function setCache(Response $response, string $directive): Response
    {
        $response->headers->set('Cache-Control', $directive);
        $response->headers->set('Vary', 'Accept-Encoding');
        return $response;
    }

    private function isPublicRoute(Request $request): bool
    {
        return $request->routeIs('landing')
            || $request->routeIs('guide.*')
            || $request->is('favicon.ico')
            || $request->is('robots.txt');
    }
}
