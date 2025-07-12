<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EducBG extends Model
{
    /** @use HasFactory<\Database\Factories\EducBGFactory> */
    use HasFactory;

    protected $fillable = [
        'youth_user_id',
        'level',
        'nameOfSchool',
        'periodOfAttendance',
        'created_at',
        'yearGraduate',
    ];


    public function yUser()
    {
        return $this->belongsTo(YouthUser::class, 'youth_user_id');
    }
}
/*	
	-level
	-nameOfSchool
	-periodOfAttendance
	-yearGraduate


            "nameOfOrganization": "",
        "addressOfOrganization": "",
        "start": "",
        "end": "",
        "yearGraduated": "",
     */