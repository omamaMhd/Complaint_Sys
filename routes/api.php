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

RateLimiter::for('citizen-login', function ($request) {
    return Limit::perMinute(5)->by($request->ip());
});

// Citizen
Route::get('/complaints/track/{reference}', [ComplaintController::class, 'track']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify', [AuthController::class, 'verifyCode']);
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:citizen-login');
Route::post('/resend', [AuthController::class, 'resendVerificationCode']);

// Admin
Route::post('/login1', [AdminController::class, 'login1']);//هي للادمن 

Route::post('/employee/login', [EmployeeController::class, 'login']);


Route::middleware('auth:sanctum')->group(function () {
Route::post('/change-password', [EmployeeController::class, 'changePassword']);

    Route::post('/logout', [AuthController::class, 'logout']);// للمواطن
    Route::post('/employee/logout', [EmployeeController::class, 'logout']);
    Route::post('/logout1', [AdminController::class, 'logout1']);

    Route::post('/complaints', [ComplaintController::class, 'store']);
    Route::get('/complaints/my', [ComplaintController::class, 'myComplaints']);
    Route::post('/complaints/{id}/attachments', [ComplaintController::class, 'addAttachment']);
  //  Route::get('/complaints/my', [ComplaintController::class, 'myComplaints']);
    // Route::patch('/complaints/{id}/status', [ComplaintController::class, 'changeStatus'])
    //     ->middleware('auth:admin');

    Route::get('/complaints/{id}/history', [ComplaintController::class, 'history']);

    // employee 
    Route::get('/departmentComplaints', [ComplaintController::class, 'Department_Complaints']);
     // ->middleware( 'permission:Department_Complaints'); 
    Route::post('/complaints/{id}/status', [ComplaintController::class, 'Change_Status']);
    Route::post('/addnote/{id}', [ComplaintController::class, 'Add_Note']);

    // إدارة الموظفين والصلاحيات
    Route::post('/CreateEmploye', [AdminController::class, 'createEmployee']); 
    Route::get('/Allemployees', [AdminController::class, 'listAllEmployees']);
    Route::get('/employe/{id}/permissions', [AdminController::class, 'getEmployeeWithPermissions']);
    Route::post('/employe/{id}/UpdatePer', [AdminController::class, 'updateEmployeePermissions']);
    Route::get('/Allpermissions', [AdminController::class, 'listPermissions']);

    // شكاوى الإدارة

    Route::get('/Admin/complaints', [AdminController::class, 'index']);
    Route::get('/Admin/complaints/{id}', [AdminController::class, 'show']);

    // اشعارات النظام للمواطنين
    // عرض الاشعارات
    Route::get('/notifications', [CitizenNotificationController::class, 'index']);
    Route::post('/notifications/{id}/read', [CitizenNotificationController::class, 'markAsRead']);

     Route::post('/fcm-token', [CitizenNotificationController::class, 'updateFcmToken']);

     //admin
    Route::get('/statistics', [ComplaintController::class, 'statistics']);
    Route::get('/reports/complaints/export/csv', [ComplaintController::class, 'exportCsv']);
    Route::get('/reports/complaints/export/pdf', [ComplaintController::class, 'exportStatisticsPdf']);
  

});
