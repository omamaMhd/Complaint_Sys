<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\CitizenNotificationController;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

// RateLimiter::for('citizen-login', function ($request) {
//     return Limit::perMinute(5)->by($request->ip());
// });

// // Citizen
// Route::get('/complaints/track/{reference}', [ComplaintController::class, 'track']);

// Route::post('/register', [AuthController::class, 'register'])->name();
// Route::post('/verify', [AuthController::class, 'verifyCode']);
// Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:citizen-login');
// Route::post('/resend', [AuthController::class, 'resendVerificationCode']);

// // Admin
// Route::post('/login1', [AdminController::class, 'login1']);//هي للادمن 

// Route::post('/employee/login', [EmployeeController::class, 'login']);
// ///////////////////////////////////////////
// Route::get('/whoami', function () {
//     return response()->json([
//         'server_id' => config('app.server_id'),
//         'time' => now(),
//     ]);
// });
// //////////////////////////////////////////////////

// Route::middleware('auth:sanctum')->group(function () {
// Route::post('/change-password', [EmployeeController::class, 'changePassword']);

//     Route::post('/logout', [AuthController::class, 'logout']);// للمواطن
//     Route::post('/employee/logout', [EmployeeController::class, 'logout']);
//     Route::post('/logout1', [AdminController::class, 'logout1']);

//     Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaint.create');
//     Route::get('/complaints/my', [ComplaintController::class, 'myComplaints']);
//     Route::post('/complaints/{id}/attachments', [ComplaintController::class, 'addAttachment']);
//   //  Route::get('/complaints/my', [ComplaintController::class, 'myComplaints']);
//     // Route::patch('/complaints/{id}/status', [ComplaintController::class, 'changeStatus'])
//     //     ->middleware('auth:admin');

//     Route::get('/complaints/{id}/history', [ComplaintController::class, 'history']);
// Route::get('/complaints/{id}/notes', [ComplaintController::class, 'myComplaintNotes']);
//     // employee 
//     Route::get('/departmentComplaints', [ComplaintController::class, 'Department_Complaints']);
//      // ->middleware( 'permission:Department_Complaints'); 
//     Route::post('/complaints/{id}/status', [ComplaintController::class, 'Change_Status']);
//     Route::post('/addnote/{id}', [ComplaintController::class, 'Add_Note']);

//     // إدارة الموظفين والصلاحيات
//     Route::post('/CreateEmploye', [AdminController::class, 'createEmployee']); 
//     Route::get('/Allemployees', [AdminController::class, 'listAllEmployees']);
//     Route::get('/employe/{id}/permissions', [AdminController::class, 'getEmployeeWithPermissions']);
//     Route::post('/employe/{id}/UpdatePer', [AdminController::class, 'updateEmployeePermissions']);
//     Route::get('/Allpermissions', [AdminController::class, 'listPermissions']);

//     // شكاوى الإدارة

//     Route::get('/Admin/complaints', [AdminController::class, 'index']);
//     Route::get('/Admin/complaints/{id}', [AdminController::class, 'show']);

//     // اشعارات النظام للمواطنين
//     // عرض الاشعارات
//     Route::get('/notifications', [CitizenNotificationController::class, 'index']);
//     Route::post('/notifications/{id}/read', [CitizenNotificationController::class, 'markAsRead']);

//      Route::post('/fcm-token', [CitizenNotificationController::class, 'updateFcmToken']);



// });
// Rate limiter
RateLimiter::for('citizen-login', function ($request) {
    return Limit::perMinute(5)->by($request->ip());
});

// Citizen
Route::get('/complaints/track/{reference}', [ComplaintController::class, 'track'])->name('complaint.track');

Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/verify', [AuthController::class, 'verifyCode'])->name('auth.verify');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:citizen-login')->name('auth.login');
Route::post('/resend', [AuthController::class, 'resendVerificationCode'])->name('auth.resend');

// Admin
Route::post('/login1', [AdminController::class, 'login1'])->name('admin.login');

// Employee
Route::post('/employee/login', [EmployeeController::class, 'login'])->name('employee.login');

Route::get('/whoami', function () {
    return response()->json([
        'server_id' => config('app.server_id'),
        'time' => now(),
    ]);
})->name('system.whoami');

//////////////////////////////////////////////////

Route::middleware('auth:sanctum')->group(function () {

    Route::post('/change-password', [EmployeeController::class, 'changePassword'])->name('employee.changePassword');

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout'); // Citizen
    Route::post('/employee/logout', [EmployeeController::class, 'logout'])->name('employee.logout');
    Route::post('/logout1', [AdminController::class, 'logout1'])->name('admin.logout');

    // Complaints
    Route::post('/complaints', [ComplaintController::class, 'store'])->name('complaint.create');
    Route::get('/complaints/my', [ComplaintController::class, 'myComplaints'])->name('complaint.my');
    Route::post('/complaints/{id}/attachments', [ComplaintController::class, 'addAttachment'])->name('complaint.addAttachment');
    Route::get('/complaints/{id}/history', [ComplaintController::class, 'history'])->name('complaint.history');
    Route::get('/complaints/{id}/notes', [ComplaintController::class, 'myComplaintNotes'])->name('complaint.notes');

<<<<<<< HEAD
    // Department / Employee
    Route::get('/departmentComplaints', [ComplaintController::class, 'Department_Complaints'])->name('complaint.department');
    Route::post('/complaints/{id}/status', [ComplaintController::class, 'Change_Status'])->name('complaint.changeStatus');
    Route::post('/addnote/{id}', [ComplaintController::class, 'Add_Note'])->name('complaint.addNote');

    // Employee & Permissions
    Route::post('/CreateEmploye', [AdminController::class, 'createEmployee'])->name('admin.createEmployee');
    Route::get('/Allemployees', [AdminController::class, 'listAllEmployees'])->name('admin.listEmployees');
    Route::get('/employe/{id}/permissions', [AdminController::class, 'getEmployeeWithPermissions'])->name('admin.getEmployeePermissions');
    Route::post('/employe/{id}/UpdatePer', [AdminController::class, 'updateEmployeePermissions'])->name('admin.updateEmployeePermissions');
    Route::get('/Allpermissions', [AdminController::class, 'listPermissions'])->name('admin.listPermissions');
=======
     //admin
    Route::get('/statistics', [ComplaintController::class, 'statistics']);
    Route::get('/reports/complaints/export/csv', [ComplaintController::class, 'exportCsv']);
    Route::get('/reports/complaints/export/pdf', [ComplaintController::class, 'exportStatisticsPdf']);
  
>>>>>>> 7a800d545bae5e07b6d64490fd460ac5aabb015c

    // Admin Complaints
    Route::get('/Admin/complaints', [AdminController::class, 'index'])->name('admin.complaints.index');
    Route::get('/Admin/complaints/{id}', [AdminController::class, 'show'])->name('admin.complaints.show');
     Route::get('/traces', [AdminController::class, 'indexs']);
    // Notifications
    Route::get('/notifications', [CitizenNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [CitizenNotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/fcm-token', [CitizenNotificationController::class, 'updateFcmToken'])->name('notifications.updateFcm');
});
