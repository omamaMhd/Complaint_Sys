<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ComplaintController;

Route::get('/complaints/track/{reference}', [ComplaintController::class, 'track']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify', [AuthController::class, 'verifyCode']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login1', [AdminController::class, 'login1']);//هي للادمن والموظفين 
Route::post('/resend', [AuthController::class, 'resendVerificationCode']);


Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
     Route::post('/logout1', [AdminController::class, 'logout1']);
     Route::post('/complaints', [ComplaintController::class, 'store']);
    Route::get('/complaints/my', [ComplaintController::class, 'myComplaints']);

    Route::post('/complaints/{id}/attachments', [ComplaintController::class, 'addAttachment']);

    Route::get('/complaints/my', [ComplaintController::class, 'myComplaints']);
    // Route::patch('/complaints/{id}/status', [ComplaintController::class, 'changeStatus'])
    //     ->middleware('auth:admin');

    Route::get('/complaints/{id}/history', [ComplaintController::class, 'history']);


    Route::get('/departmentComplaints', [ComplaintController::class, 'departmentComplaints']);
    //  ->middleware( 'permission:view_all_complaints'); 
    Route::post('/complaints/{id}/status', [ComplaintController::class, 'changeStatus']);
    Route::post('/addnote/{id}', [ComplaintController::class, 'addNote']);
});
