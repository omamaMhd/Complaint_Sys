<?php

// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;
// use Illuminate\Http\Exceptions\ThrottleRequestsException;
// use Symfony\Component\HttpFoundation\Response;

// class TraceRequest
// {
//     public function handle($request, Closure $next)
//     {
//         $traceId = (string) \Illuminate\Support\Str::uuid();
//         $start = microtime(true);

//         $request->attributes->set('trace_id', $traceId);

//         $response = $next($request);

//         \App\Models\System_trace::create([
//             'trace_id' => $traceId,
//             'user_id' => optional(auth()->user())->id,
//             'user_role' => 'null',
//           //  optional(auth()->user()),
//             //->roles->pluck('name')->first(),
//             'action' => 'http_request',
//             'context' => [
//                 'method' => $request->method(),
//                 'path' => $request->path(),
//                 'status_code' => $response->status(),
//             ],
//             'duration_ms' => (int)((microtime(true) - $start) * 1000),
//         ]);

//         return $response;
//     }
// }


// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;
// use Illuminate\Support\Str;
// use App\Models\System_trace;
// use Illuminate\Support\Facades\Auth;
// use App\Models\User;
// use App\Models\Citizen;
// use Carbon\Carbon;

// use Throwable;

// class TraceRequest
// {
//     public function handle(Request $request, Closure $next)
//     {
//         $traceId = (string) Str::uuid();
// $startTime = microtime(true);

// $response = $next($request);

// $duration = microtime(true) - $startTime;

// $this->storeTrace(
//     $traceId,
//     $request,
//     'success',
//     $response->getStatusCode(),
//     $duration,
//     []
// );

// return $response;


//     }

//    private function storeTrace(
//     string $traceId,
//     $request,
//     string $status,
//     int $statusCode,
//     float $duration,
//     array $context = []
// ) {
//     $user = Auth::user();
// $actor = \App\Aspects\TraceContext::getActor();
//     $userId = null;
//     $userRole = 'guest';

//     // if ($user) {
//     //     $userId = $user->id;

//     //     // مواطن
//     //     if ($user instanceof \App\Models\Citizen) {
//     //         $userRole = 'citizen';
//     //     }

//     //     // موظف أو أدمن
//     //     elseif ($user instanceof \App\Models\User) {
//     //         $userRole = $user->role; // admin | employee
//     //     }
//     // }
//     /**
//  * 1️⃣ إذا في توكن → هذا الأساس
//  */
// if (Auth::check()) {
//     $user = Auth::user();
//     $userId = $user->id;

//     if ($user instanceof \App\Models\Citizen) {
//         $userRole = 'citizen';
//     } else {
//         $userRole = $user->roles->pluck('name')->first();
//     }
// }
// /**
//  * 2️⃣ إذا ما في توكن بس في Actor مؤقت (login / register)
//  */
// elseif ($actor['user_id']) {
//     $userId = $actor['user_id'];
//     $userRole = $actor['user_role'];
// }

//     \App\Models\System_trace::create([
//         'trace_id'   => $traceId,
//         'user_id'    => $userId,
//         'user_role'  =>  $userRole,
//         'action'     =>$action = str_replace('.', '_', $request->route()?->getName()),

//         // $request->route()?->getName() ?? $request->path(),
//       'entity' => \App\Aspects\TraceContext::getEntity(),
// 'entity_id' => \App\Aspects\TraceContext::getEntityId(),
//         'status'     => $status,
//         'context'    => json_encode([
//             'method' => $request->method(),
//             'ip'     => $request->ip(),
//             'url'    => $request->fullUrl(),
//             'time'   => round($duration, 3),
//         ]),

//     ]);
// }

// }

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\Citizen;
use Throwable;

class TraceRequest
{
    public function handle(Request $request, Closure $next)
    {
        $traceId = (string) Str::uuid();
        $startTime = microtime(true);

        $status = 'success';
        $statusCode = 200;
        $exception = null;

        try {
            $response = $next($request);
            $statusCode = $response->getStatusCode();

            if ($statusCode >= 400) {
                $status = 'error';
            }

            return $response;

        } catch (Throwable $e) {
            $status = 'error';
            $statusCode = method_exists($e, 'getStatusCode')
                ? $e->getStatusCode()
                : 500;

            $exception = $e;

            throw $e;

        } finally {
            $duration = microtime(true) - $startTime;

            $this->storeTrace(
                $traceId,
                $request,
                $status,
                $statusCode,
                $duration,
                [
                    'exception' => $exception ? class_basename($exception) : null,
                    'error_message' => $exception?->getMessage(),
                ]
            );
        }
    }

    private function storeTrace(
        string $traceId,
        Request $request,
        string $status,
        int $statusCode,
        float $duration,
        array $context = []
    ) {
        $actor = \App\Aspects\TraceContext::getActor();

        $userId = null;
        $userRole = 'guest';

        /**
         * 1️⃣ إذا في توكن (الأساس)
         */
        if (Auth::check()) {
            $user = Auth::user();
            $userId = $user->id;

            if ($user instanceof Citizen) {
                $userRole = 'citizen';
            } else {
                $userRole = $user->roles->pluck('name')->first();
            }
        }
        /**
         * 2️⃣ login / register بدون توكن
         */
        elseif ($actor['user_id']) {
            $userId = $actor['user_id'];
            $userRole = $actor['user_role'];
        }

        \App\Models\System_trace::create([
            'trace_id'  => $traceId,
            'user_id'   => $userId,
            'user_role' => $userRole,
            'action'    => str_replace('.', '_', $request->route()?->getName()),
            'entity'    => \App\Aspects\TraceContext::getEntity(),
            'entity_id' => \App\Aspects\TraceContext::getEntityId(),
            'status'    => $status,
            'context'   => json_encode(array_merge([
                'method' => $request->method(),
                'ip'     => $request->ip(),
                'url'    => $request->fullUrl(),
                'status_code' => $statusCode,
                'duration' => round($duration, 3),
            ], $context)),
        ]);
    }
}
