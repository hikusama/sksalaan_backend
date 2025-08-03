<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValidateYouth extends Model
{
    use HasFactory;
    protected $fillable = [
        'youth_user_id',
        'registration_cycle_id',
    ];
}
