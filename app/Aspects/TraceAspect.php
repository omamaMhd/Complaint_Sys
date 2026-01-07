<?php
// namespace App\Aspects;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\Request;

// use App\Models\System_trace;

// class TraceAspect
// {
//     public static function record(
//         string $action,
//         string $entity = null,
//         int $entityId = null,
//         array $context = [],
//         string $status = 'success'
//     ) {
//         System_trace::create([
//             'trace_id' => request()->attributes->get('trace_id'),
//             'user_id' => optional(auth()->user())->id,
//             'user_role' => optional(auth()->user())->roles,
//             //->pluck('name')->first(),
//             'action' => $action,
//             'entity' => $entity,
//             'entity_id' => $entityId,
//             'context' => $context,
//             'status' => $status
//         ]);
//     }
// }



// namespace App\Aspects;

// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Str;
// use Carbon\Carbon;

// class TraceAspect
// {
//     /**
//      * سجّل حدث تتبع (Tracing) لكل العمليات
//      *
//      * @param string $action   اسم العملية (create_complaint, login, ...)
//      * @param string|null $entity نوع الكيان (complaint, user, ...)
//      * @param int|null $entityId رقم الكيان إذا موجود
//      * @param array $context بيانات إضافية
//      * @param string $status الحالة (success, error)
//      * @param int|null $userId
//      * @param string|null $userRole
//      */
//     public static function record(
//         string $action,
//         ?string $entity = null,
//         ?int $entityId = null,
//         array $context = [],
//         string $status = 'success',
//         ?int $userId = null,
//         ?string $userRole = null
//     ) {
//         try {
//             // ✅ توليد UUID تلقائي لكل trace
//             $traceId = Str::uuid()->toString();

//             // ✅ تحويل الـ context لأي شيء إلى JSON
//             $contextJson = json_encode($context, JSON_UNESCAPED_UNICODE);

//             DB::table('system_traces')->insert([
//                 'trace_id'   => $traceId,
//                 'user_id'    => $userId,
//                 'user_role'  => $userRole,
//                 'action'     => $action,
//                 'entity'     => $entity,
//                 'entity_id'  => $entityId,
//                 'context'    => $contextJson,
//                 'status'     => $status,
//                 'created_at' => Carbon::now(),
//                 'updated_at' => Carbon::now(),
//             ]);

//         } catch (\Throwable $e) {
//             // إذا فشل تسجيل التتبع، نسجل خطأ في اللوج بدون كسر العملية
//             \Log::error('TraceAspect failed: '.$e->getMessage(), [
//                 'action' => $action,
//                 'entity' => $entity,
//                 'entity_id' => $entityId,
//                 'status' => $status,
//                 'original_context' => $context
//             ]);
//         }
//     }
// }
 namespace App\Aspects;

// use Illuminate\Support\Facades\DB;
// use Illuminate\Support\Str;
// use Carbon\Carbon;

// class TraceAspect
// {
//     public static function record(
//         string $action=null,
//         array $context = [],
//         string $status = 'success',
//         ?int $userId = null,
//         ?string $userRole = null
//     ) {
//         try {
//             $traceId = TraceContext::getTraceId() ?? Str::uuid()->toString();
//             TraceContext::setTraceId($traceId);

//             $entity = TraceContext::getEntity();
//             $entityId = TraceContext::getEntityId();

//             DB::table('system_traces')->insert([
//                 'trace_id'   => $traceId,
//                 'user_id'    => $userId,
//                 'user_role'  => $userRole,
//                 'action'     => $action,
//                 'entity'     => $entity,
//                 'entity_id'  => $entityId,
//                 'context'    => json_encode($context, JSON_UNESCAPED_UNICODE),
//                 'status'     => $status,
//                 'created_at' => Carbon::now(),
//                 'updated_at' => Carbon::now(),
//             ]);
//         } catch (\Throwable $e) {
//             \Log::error('TraceAspect failed: '.$e->getMessage(), [
//                 'action' => $action,
//                 'entity' => $entity ?? null,
//                 'entity_id' => $entityId ?? null,
//                 'status' => $status,
//                 'context' => $context
//             ]);
//         }
//     }
// }

use App\Aspects\TraceContext;

class TraceAspect
{
    public static function record(
        ?int $userId = null,
        ?string $userRole = null,
        ?string $action = null,
        ?string $entity = null,
        ?int $entityId = null
    ) {
        if ($userId && $userRole) {
            TraceContext::setUser($userId, $userRole);
        }

        if ($action) {
            TraceContext::setAction($action);
        }

        if ($entity && $entityId) {
            TraceContext::setEntity($entity, $entityId);
        }
    }
}



