<?php
namespace App\Services;

use App\Repositories\AdminRepository;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Aspects\TraceContext;
use App\Aspects\TraceAspect;


use Exception;

class AdminService
{
 protected $repo;

    public function __construct(AdminRepository $repo)
    {
        $this->repo = $repo;


    }
    public function login(string $mobile, string $password)
    {
        $user = $this->repo->findByMobile($mobile);
TraceContext::setEntity('user', $user?->id);
TraceAspect::record(
    userId: $user->id,
    userRole: $user->role
);
        if (!$user || !Hash::check($password, $user->password)) {
            return ['ok' => false, 'message' => 'Invalid mobile number or password.'];
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        return [
            'ok' => true,
            'user' => $user,
            'token' => $token
        ];
    }
    public function logout1($user)
    {

          if (!$user) {
              return [
                 'status' => 401,
                 'message' => 'user not authenticated.'
            ];
        }
        $token = $user->currentAccessToken();
        if ($token) {
            $token->delete();
        }

        return [
            'status' => 200,
           'message' => 'Logged out successfully.'
        ];
    }   
        /**
     * إنشاء موظف جديد - مع صلاحيات مطلوبة
     */
    public function createEmployee(array $data, array $permissions = [])
    {
       $user = auth()->user();
       if (!$user || !$user->hasRole('admin')) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized token'], 403)
            );
        }
        // التحقق أن الصلاحيات غير فارغة
        if (empty($permissions)) {
            throw new \Exception('Permissions array cannot be empty');
        }
    // لاحظ أن كلمة المرور مؤقتة لجميع الموظفين الجدد
    $tempPassword = 'Temp1234';
    $user = $this->repo->create([
        'username' => $data['username'],
        'mobile' => $data['mobile'],
        'password' => Hash::make($tempPassword),
        'responsible_party' => $data['responsible_party'],
        'must_change_password' => true,
    ]);
    TraceContext::setEntity('user', $user?->id);
 // تعيين دور الموظف
    $user->assignRole('employee');
    // منح الصلاحيات المحددة
    $user->syncPermissions($permissions);
    // 3. تسجيل في السجل
        \Log::info('📝 Employee created by admin', [
            'admin_id' => auth()->id(),
            'employee_id' => $user->id,
            'mobile' => $user->mobile,
            'assigned_permissions' => $permissions,
            'temp_password' => $tempPassword // للتوثيق فقط
        ]);
    // 4. إرجاع البيانات (بما فيها كلمة المرور المؤقتة)
        return [
            'user' => $user,
            'temp_password' => $tempPassword
        ];
        Cache::forget('all_employees');
Cache::forget('all_permissions');
    }
    /**
     * تحديث صلاحيات الموظف
     */
    public function updateEmployeePermissions(User $employee, array $permissions): void
    {
       $user = auth()->user();
       if (!$user || !$user->hasRole('admin')) {
            throw new HttpResponseException(
                response()->json(['message' => 'Unauthorized token'], 403)
            );
        }
        // التحقق أن الصلاحيات غير فارغة
        if (empty($permissions)) {
            throw new \Exception('Permissions array cannot be empty');
        }
        // حفظ الصلاحيات القديمة للتسجيل
        $oldPermissions = $employee->getPermissionNames()->toArray();
        
        // مزامنة الصلاحيات الجديدة
        $employee->syncPermissions($permissions);
        TraceContext::setEntity('user', $user?->id);
// تسجيل التغيير في السجل
        \Log::info('🔄 Employee permissions updated', [
            'admin_id' => auth()->id(),
            'employee_id' => $employee->id,
            'old_permissions' => $employee->getPermissionNames()->toArray(),
            'new_permissions' => $permissions
        ]);
        Cache::forget("employee_permissions_{$employee->id}");
Cache::forget('all_employees');
    }


    /**
     * يعرض جميع الشكاوى (مخصص للمدير)
     */
    // public function listAllComplaints()
    // {
    //     $complaints = $this->repo->getAllComplaints();
    //     return ['ok' => true, 'data' => $complaints];
    // }
// public function listAllComplaints()
// {
//     return Cache::remember(
//         'admin_complaints',
//         30,
//         fn () => [
//             'ok' => true,
//             'data' => $this->repo->getAllComplaints()
//         ]
//     );
// }
 /**
     * تابع جديد: عرض معلومات المواطن والموظف للشكوى
     */
    // public function showComplaint($complaintId)
    // {
    //         $complaint = $this->repo->getComplaintDetails($complaintId);
      
    //         // تنسيق البيانات
    //         $formattedData = [
    //             'complaint_id' => $complaint->id,
    //             'citizen' => $complaint->citizen ? [
    //                 'id' => $complaint->citizen->id,
    //                 'username' => $complaint->citizen->username,
    //                 'mobile' => $complaint->citizen->mobile
    //             ] : null,
                
    //             'handler' => $complaint->handler ? [
    //                 'id' => $complaint->handler->id,
    //                 'username' => $complaint->handler->username,
    //                 'mobile' => $complaint->handler->mobile,
    //                 'responsible_party' => $complaint->handler->responsible_party
    //             ] : null,      
                
    //             'locked_by_user' => $complaint->lockedBy ? [
    //                 'id' => $complaint->lockedBy->id,
    //                 'username' => $complaint->lockedBy->username
    //             ] : null
    //         ];}
    // public function listAllComplaints()
    // {
    //      return Cache::remember(
    //     'admin_complaints',
    //     30,
    //     fn () => [
    //     $user = auth()->user(),
    //    if (!$user || !$user->hasRole('admin')) {
    //         throw new HttpResponseException(
    //             response()->json(['message' => 'Unauthorized token'], 403)
    //         );
    //     }
    //     $complaints = $this->repo->getAllComplaints();
    //     return ['ok' => true, 'data' => $complaints];
    //     ]
    // );
    // }
    public function listAllComplaints()
{
    $user = auth()->user();

    if (!$user || !$user->hasRole('admin')) {
        throw new HttpResponseException(
            response()->json(['message' => 'Unauthorized token'], 403)
        );
    }
TraceContext::setEntity('complaint', $user?->id);
    $complaints = Cache::remember(
        'admin_complaints',
        30, // seconds
        fn () => $this->repo->getAllComplaints()
    );

    return [
        'ok' => true,
        'data' => $complaints
    ];
}


// public function showComplaint(int $complaintId)
// {
//     $complaint = $this->repo->getComplaintDetails($complaintId);

//     $complaint->histories = $complaint->histories->map(function($h) {
//         $history = [
//             'id' => $h->id,
//             'complaint_id' => $h->complaint_id,
//             'action' => $h->action,
//             'data' => $h->data,
//             'created_at' => $h->created_at,
//             'updated_at' => $h->updated_at,
//         ];
//         // ⭐⭐ التعديل هنا: فقط لـ status_changed و note_added ⭐⭐
//         if (in_array($h->action, ['status_changed', 'note_added']) && $h->performedBy) {
//             $history['performed_by'] = [
//                 'id' => $h->performedBy->id,
//                 'username' => $h->performedBy->username
//             ];
//         }
//         // ⭐ لا نضيف performed_by للحالات الأخرى ⭐
//         return $history;
//     });
//     // تنظيف المرفقات
//     $complaint->attachments = $complaint->attachments->map(function($att) {
//         return [
//             'id' => $att->id,
//             'complaint_id' => $att->complaint_id,
//             'path' => $att->path,
//             'original_name' => $att->original_name,
//             'created_at' => $att->created_at,
//             'updated_at' => $att->updated_at,
//         ];
//     });

//     return $complaint;
// }

 public function showComplaint(int $complaintId)
{
    // جلب تفاصيل الشكوى مع الهيستوري والمرفقات
    $complaint = $this->repo->getComplaintDetails($complaintId);

    // تحويل الهيستوري
    $complaint->histories = $complaint->histories->map(function($h) {
        $history = [
            'id' => $h->id,
            'complaint_id' => $h->complaint_id,
            'action' => $h->action,
            'data' => $h->data,
            'performed_by_id' => $h->performed_by ?? null,
            'performed_by_type' => $h->performed_by_type ?? null,
            'performed_by_name' => $h->performed_by_name ?? null,
            'created_at' => $h->created_at,
            'updated_at' => $h->updated_at,
        ];

        // // إذا موجود علاقة المستخدم اللي عمل الإجراء (performedBy) نضيفه
        // if ($h->performedBy) {
        //     $history['performed_by_details'] = [
        //         'id' => $h->performedBy->id,
        //         'username' => $h->performedBy->username,
        //         'email' => $h->performedBy->email ?? null,
        //         'role' => $h->performedBy->roles->pluck('name')->toArray() ?? []
        //     ];
        // }

        return $history;
    });

    // // تحويل المرفقات
    // $complaint->attachments = $complaint->attachments->map(function($att) {
    //     return [
    //         'id' => $att->id,
    //         'complaint_id' => $att->complaint_id,
    //         'path' => $att->path,
    //         'original_name' => $att->original_name,
    //         'created_at' => $att->created_at,
    //         'updated_at' => $att->updated_at,
    //     ];
    // });

    return $complaint;
}

// public function showComplaint(int $complaintId)
// {
//     $complaint = $this->repo->getComplaintDetails($complaintId);

//     $complaint->histories = $complaint->histories
//         ->map(function ($h) use ($complaint) {
//             $history = [
//                 // 'id' => $h->id,
//                 // 'complaint_id' => $h->complaint_id,
//                 // 'performed_by' => $h->performed_by, // سيتم تعبئتها لاحقًا
//                 // 'performed_by_type' => $h->performed_by_type,
//                 // 'performed_by_name' => $h->performed_by_name,
//                 // 'action' => $h->action,
//                 // 'data' => $h->data,
//                 // 'created_at' => $h->created_at,
//                 // 'updated_at' => $h->updated_at,
//             ];

//             // ⭐ إذا أول حدث "created" من user (المواطن) → استخدم اسمه الحقيقي
//             // if ($h->action === 'created' && $h->performed_by_type === 'user') {
//             //     $history['performed_by'] = [
//             //         'id' => $complaint->citizen->id,
//             //         'username' => $complaint->citizen->username,
//             //     ];
//             // }

//             // ⭐ باقي الأحداث لموظفين
//             if ($h->performedBy && $h->performed_by_type === 'employee') {
//                 $history['performed_by'] = [
//                     'id' => $h->performedBy->id,
//                     'username' => $h->performedBy->username
//                 ];
//             }

//             return $history;
//         });

//     // تنظيف المرفقات
//     $complaint->attachments = $complaint->attachments->map(function ($att) {
//         return [
//             'id' => $att->id,
//             'complaint_id' => $att->complaint_id,
//             'path' => $att->path,
//             'original_name' => $att->original_name,
//             'created_at' => $att->created_at,
//             'updated_at' => $att->updated_at,
//         ];
//     });

//     return $complaint;
// }

}
