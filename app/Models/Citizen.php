<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Citizen extends Authenticatable
{ 
 use HasApiTokens, Notifiable, SoftDeletes;

    protected $fillable = [
        'username','mobile','password','verification_code',
        'code_expires_at','is_verified','fcm_token'
    ];
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class, 'citizen_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ComplaintAttachment::class, 'uploaded_by');
    }

    protected $hidden = ['password'];


}
