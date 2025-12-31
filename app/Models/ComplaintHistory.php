<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ComplaintHistory extends Model
{
      protected $fillable = [
        'complaint_id', 'performed_by', 'performed_by_type', 'action', 'data'
    ];

    protected $casts = [
        'data' => 'array'
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class, 'complaint_id');
    }
    public function performedBy()
{
    return $this->belongsTo(\App\Models\User::class, 'performed_by');
}

}
