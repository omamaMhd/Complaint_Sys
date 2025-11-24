<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComplaintController;

Route::get('/complaints/track/{reference}', [ComplaintController::class, 'track']);

Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify/{citizen}', [AuthController::class, 'verifyCode']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/resend/{citizen}', [AuthController::class, 'resendVerificationCode']);


//Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
     Route::post('/complaints', [ComplaintController::class, 'store']);
    Route::get('/complaints/my', [ComplaintController::class, 'myComplaints']);

    Route::post('/complaints/{id}/attachments', [ComplaintController::class, 'addAttachment']);

    Route::patch('/complaints/{id}/status', [ComplaintController::class, 'changeStatus'])
        ->middleware('auth:admin');

    Route::get('/complaints/{id}/history', [ComplaintController::class, 'history']);

//});
