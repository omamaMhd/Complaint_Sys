<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->renderable(function (ThrottleRequestsException $e, $request) {
            // إذا كان الطلب من API (JSON)
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Too many login attempts. Please try again later.',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? 60, // الوقت بالثواني
                    'status' => 429
                ], 429);
            }
        });
    }
}
/*

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Throwable;

class Handler extends ExceptionHandler
{

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }


    public function render($request, Throwable $e)
    {
        // التعامل مع Throttle Exception وإرجاع JSON نظيف
        if ($e instanceof ThrottleRequestsException) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;

            return response()->json([
                'ok' => false,
                'message' => 'Too many attempts. Please try again later.',
                'retry_after_seconds' => (int) $retryAfter,
                'retry_after_minutes' => ceil($retryAfter / 60),
            ], 429);
        }

        return parent::render($request, $e);
    }
}*/