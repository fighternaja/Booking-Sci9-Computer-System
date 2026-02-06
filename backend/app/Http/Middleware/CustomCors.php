<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomCors
{
    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigin = $this->resolveAllowedOrigin($origin);

        if ($request->getMethod() === 'OPTIONS') {
            $response = response('', 204);
        } else {
            $response = $next($request);
        }

        if ($allowedOrigin) {
            $response->headers->set('Access-Control-Allow-Origin', $allowedOrigin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', $request->header('Access-Control-Request-Headers', '*'));
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Vary', 'Origin');
        }

        return $response;
    }

    private function resolveAllowedOrigin(?string $origin): ?string
    {
        if (!$origin) {
            return null;
        }

        $origin = rtrim($origin, '/');

        $envOrigins = array_filter(array_map(function ($value) {
            $value = trim($value);
            return $value === '' ? '' : rtrim($value, '/');
        }, explode(',', env('CORS_ALLOWED_ORIGINS', ''))));

        $defaultAllowed = [
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'http://localhost:3001',
            'http://127.0.0.1:3001',
        ];

        $allowedOrigins = array_merge($defaultAllowed, $envOrigins);

        if (in_array($origin, $allowedOrigins, true)) {
            return $origin;
        }

        if (preg_match('/^https:\/\/.*\.vercel\.app$/', $origin)) {
            return $origin;
        }

        if (preg_match('/^http:\/\/localhost:\d+$/', $origin)) {
            return $origin;
        }

        if (preg_match('/^http:\/\/127\.0\.0\.1:\d+$/', $origin)) {
            return $origin;
        }

        return null;
    }
}

