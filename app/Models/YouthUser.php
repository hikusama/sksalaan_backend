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


    // In YouthUser.php
    public function info()
    {
        return $this->hasOne(YouthInfo::class);
    }
    public function educbg()
    {
        return $this->hasOne(EducBG::class);
    }
    public function civicInvolvement()
    {
        return $this->hasOne(civicInvolvement::class);
    }
}
/*
	-youth_id
	-user_id - user
	-youthType
	-registeredAt
*/
