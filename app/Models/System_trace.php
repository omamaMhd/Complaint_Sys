<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class System_trace extends Model
{
      protected $fillable = [
        'trace_id',
        'user_id',
        'user_role',
        'action',
        'entity',
        'entity_id',
        'context',
        'duration_ms',
        'status'
    ];
    protected $casts = [
    'context' => 'array',
];

}
