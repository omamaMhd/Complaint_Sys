<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\AdminService;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    protected $service;

    public function __construct(AdminService $service)
    {
        $this->service = $service;
         // تحميل الـ middleware على كل التوابع إلا login1
        $this->middleware(function ($request, $next) {
            $user = Auth::user();
        
            if (!$user || !$user->hasRole('admin')) {
                return response()->json(['message' => 'Unauthorized. Admin access only.'], 403);
            }
            
            return $next($request);
        })->except(['login1']);
           
    }

    public function login1(Request $request)
    {
        $request->validate([
            'mobile' => 'required',
            'password' => 'required|min:8',
        ]);
        $res = $this->service->login($request->mobile, $request->password);

        if (!$res['ok']) {
            return response()->json(['message' => $res['message']], 401);
        }

        // تحقق أن المستخدم admin
        if (!$res['user']->hasRole('admin')) {

            return response()->json(['message' => 'Access denied. Admin only.'], 403);
        }
        return response()->json([
            'message' => 'Login successfully.',
            'user' => [
                'id' => $res['user']->id,
                'username' => $res['user']->username,
                'mobile' => $res['user']->mobile,
            ],
            'access_token' => $res['token'],
            'token_type' => 'Bearer',
        ], 200);
    }

    public function logout1(Request $request)
    {
        $user = $request->user(); 
        $res = $this->service->logout1($user); 

        return response()->json([
            'message' => $res['message']
        ], $res['status']);
    }

    public function createEmployee(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string',
            'mobile' => 'required|unique:users,mobile',
            'responsible_party' => 'required|in:وزارة الداخلية,وزارة الصحة,وزارة التربية والتعليم,وزارة النقل,وزارة المالية,البلدية,هيئة المياه,هيئة الكهرباء,شرطة المرور|string',
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name', // ✅ جدول permissions الصحيح
        ]);
        $result = $this->service->createEmployee(
            $validated,
            $request->permissions // مصفوفة الصلاحيات (مطلوبة)
        );

        return response()->json([
            'message' => '✅ Employee created successfully',
            'employee' => [
                'id' => $result['user']->id,
                'username' => $result['user']->username,
                'mobile' => $result['user']->mobile,
                'responsible_party' => $result['user']->responsible_party,
            ],
            'assigned_permissions' => $result['user']->getPermissionNames()
        ], 201);
    }
    /**
     * عرض جميع الصلاحيات المتاحة
     */

    public function listPermissions()
    {
        return response()->json(Permission::all(['id', 'name']));
    }

    /**
     * عرض موظف مع صلاحياته
     */
        public function getEmployeeWithPermissions($employeeId)
    {
        $employee = User::findOrFail($employeeId);

        // التحقق أن المستخدم موظف
        if (!$employee->hasRole('employee')) {
            return response()->json(['message' => 'User is not an employee'], 400);
        }
        
        return response()->json([
            'employee' => [
                'id' => $employee->id,
                'username' => $employee->username,
                'mobile' => $employee->mobile,
                'responsible_party' => $employee->responsible_party,
            ],
            'current_permissions' => $employee->getPermissionNames(),
            'all_permissions' => Permission::all(['id', 'name'])->pluck('name')
        ]);
    }
        /**
     * تحديث صلاحيات موظف
     */
        public function updateEmployeePermissions(Request $request,  $employeeId)
    {
        $request->validate([
            'permissions' => 'required|array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $employee = User::findOrFail($employeeId);

        // التحقق أن المستخدم موظف
        if (!$employee->hasRole('employee')) {
            return response()->json(['message' => 'User is not an employee'], 400);
        }

        $this->service->updateEmployeePermissions($employee, $request->permissions);
        
        return response()->json([
            'message' => 'Permissions updated successfully',
            'employee_id' => $employee->id,
            'assigned_permissions' => $employee->fresh()->getPermissionNames()
        ]);
    }

    /**
     * عرض جميع الموظفين مع صلاحياتهم
     */
    public function listAllEmployees()
    {
        $employees = User::role('employee')
            ->with('permissions')
            ->select('id', 'username', 'mobile', 'responsible_party')
            ->get()
            ->map(function ($employee) {
                return [
                    'id' => $employee->id,
                    'username' => $employee->username,
                    'mobile' => $employee->mobile,
                    'responsible_party' => $employee->responsible_party,
                    'permissions' => $employee->getPermissionNames(),
                ];
            });
        
        return response()->json([
            'employees' => $employees,
            'total' => $employees->count()
        ]);
    }


    // عرض كل الشكاوي
    public function index()
{
    $res = $this->service->listAllComplaints();

    if (!$res['ok']) {
        return response()->json(['message' => $res['message']], 403);
    }

    return response()->json($res['data'], 200);
}



 // تفاصيل شكوى للادمن
 public function show($id)
    {
// التحقق من وجود الشكوى
        try {
            $result = $this->service->showComplaint($id);
            return response()->json($result);
            
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(
                ['message' => 'Complaint not found'], 
                404
            );
        }
    }
}
