<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Complaint extends Model
{
     protected $fillable = [
        'citizen_id','user_id','type','location','description',
        'status','reference_number','locked_by','locked_until', 'responsible_party'
    ];

    public function citizen(): BelongsTo
    {
        return $this->belongsTo(Citizen::class, 'citizen_id');
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ComplaintHistory::class, 'complaint_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ComplaintAttachment::class, 'complaint_id');
    }
}
