<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class Citizen extends Authenticatable
{ 
 use HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'username','mobile','password','verification_code',
        'code_expires_at','is_verified','fcm_token'
    ];

    protected $hidden = ['password'];


}
