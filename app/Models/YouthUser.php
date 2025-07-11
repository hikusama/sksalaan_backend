<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YouthUser extends Model
{
    /** @use HasFactory<\Database\Factories\YouthUserFactory> */
    use HasFactory;
    protected $fillable = [
        'user_id',
        'batchNo',
        'youthType',
        'skills',
    ];


    // In YouthUser.php
    public function info()
    {
        return $this->hasOne(YouthInfo::class);
    }
    public function educbg()
    {
        return $this->hasMany(EducBG::class);
    }
    public function civicInvolvement()
    {
        return $this->hasMany(civicInvolvement::class);
    }
    public function job_supp()
    {
        return $this->hasMany(Job_support::class);
    }


    public function user()  {
        return $this->belongsTo(User::class, 'user_id');
    }


}
/*
	-youth_id
	-user_id - user
	-youthType
	-registeredAt
*/
