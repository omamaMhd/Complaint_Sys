<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Symfony\Component\HttpFoundation\Response;

class HandleThrottleJsonResponse
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (ThrottleRequestsException $e) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
                
                return response()->json([
                    'message' => 'Too many login attempts. Please try again later.',
                    'retry_after' => $retryAfter,
                ], 429, $e->getHeaders());
            }
            
            throw $e;
        }
    }
}