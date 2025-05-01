<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YouthUser extends Model
{
    /** @use HasFactory<\Database\Factories\YouthUserFactory> */
    use HasFactory;
    protected $fillable = [
        'youthType',
    ];
}
/*
	-youth_id
	-user_id - user
	-youthType
	-registeredAt
*/ 