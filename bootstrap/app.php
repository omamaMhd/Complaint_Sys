<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use Spatie\Permission\Middlewares\RoleMiddleware;
use Spatie\Permission\Middlewares\PermissionMiddleware;
use Spatie\Permission\Middlewares\RoleOrPermissionMiddleware;

use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        $middleware->alias([
            'ensure.role' => \App\Http\Middleware\EnsureUserRole::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'request.timing' => \App\Http\Middleware\RequestTiming::class,
            'throttle.json' => \App\Http\Middleware\HandleThrottleJsonResponse::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\RequestTiming::class,
        ]);
        
        $middleware->priority([
            \Illuminate\Foundation\Http\Middleware\HandlePrecognitiveRequests::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            \App\Http\Middleware\HandleThrottleJsonResponse::class, // أضف هذا
            \Illuminate\Routing\Middleware\ThrottleRequests::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);

    })
    ->withExceptions(function (Exceptions $exceptions) {
        
        // 1. التعامل مع ThrottleRequestsException (429 - Too Many Requests)
        $exceptions->render(function (ThrottleRequestsException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
                $seconds = is_numeric($retryAfter) ? $retryAfter : 60;
                
                return response()->json([
                    'success' => false,
                    'message' => 'Too many attempts. Please try again in ' . $seconds . ' seconds.',
                    'retry_after' => $seconds,
                    'timestamp' => now()->toISOString()
                ], 429, $e->getHeaders());
            }
        });

        // 2. التعامل مع AuthenticationException (401 - Unauthorized)
        $exceptions->render(function (AuthenticationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please login.',
                    'timestamp' => now()->toISOString()
                ], 401);
            }
        });

        // 3. التعامل مع ValidationException (422 - Validation Error)
        $exceptions->render(function (ValidationException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors(),
                    'timestamp' => now()->toISOString()
                ], 422);
            }
        });

        // 4. التعامل مع NotFoundHttpException (404 - Not Found)
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found',
                    'path' => $request->path(),
                    'timestamp' => now()->toISOString()
                ], 404);
            }
        });

        // 5. التعامل مع MethodNotAllowedHttpException (405 - Method Not Allowed)
        $exceptions->render(function (MethodNotAllowedHttpException $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Method not allowed for this route',
                    'allowed_methods' => $e->getHeaders()['Allow'] ?? [],
                    'timestamp' => now()->toISOString()
                ], 405);
            }
        });

        // 6. التعامل مع أي Exception عام (500 - Server Error)
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*')) {
                $response = [
                    'success' => false,
                    'message' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred',
                    'timestamp' => now()->toISOString()
                ];

                // في بيئة التطوير، أضف تفاصيل أكثر
                if (config('app.debug')) {
                    $response['debug'] = [
                        'exception' => get_class($e),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTrace()
                    ];
                }

                $statusCode = method_exists($e, 'getStatusCode') 
                    ? $e->getStatusCode() 
                    : 500;

                return response()->json($response, $statusCode);
            }
        });

        // تسجيل الاستثناءات (للأرشفة)
        $exceptions->reportable(function (Throwable $e) {
            // يمكنك إضافة تسجيل مخصص هنا
            \Log::error('Exception occurred: ' . $e->getMessage(), [
                'exception' => $e,
                'url' => request()->fullUrl(),
                'ip' => request()->ip(),
                'user_id' => auth()->id() ?? 'guest'
            ]);
        });

    })->create();