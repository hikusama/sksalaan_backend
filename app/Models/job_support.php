<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Job_support extends Model
{
    //
    protected $fillable = [
        'youth_user_id',
        'task',
        'paid_at',
        'location',
        'start',
        'end',
    ];

    public function yUser()
    {
        return $this->belongsTo(YouthUser::class);
    }
}
