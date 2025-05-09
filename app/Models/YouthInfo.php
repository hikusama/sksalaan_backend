<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class YouthInfo extends Model
{
    /** @use HasFactory<\Database\Factories\YouthInfoFactory> */
    use HasFactory;
    protected $fillable = [
        'youth_user_id',
        'fname',
        'mname',
        'lname',
        'address',
        'dateOfBirth',
        'placeOfBirth',
        'height',
        'weight',
        'religion',
        'occupation',
        'sex',
        'age',
        'civilStatus',
        'gender',
        'noOfChildren',
    ];

    /*
        "fname" : "hiku",
        "mname" : "nakamoto",
        "lname" : "sama",
        "address" : "chenzen",
        "dateOfBirth" : "2004-09-04",
        "placeOfBirth" : "gggasf",
        "height" : 12,
        "weight" : 15,
        "religion" : "asff",
        "occupation" : "hs",
        "sex" : "m",
        "civilStatus" : "S",
        "gender" : "",
        "noOfChildren" : "",



                "level" : "",
        "nameOfSchool" : "",
        "periodOfAttendance" : "",
        "yearGraduate" : "",
    */



    public function yUser()
    {
        return $this->belongsTo(YouthUser::class);
    }
}


/*
	-youth_id - youthuser
	-fname
	-mname
	-lname
	-address
	-dateOfBirth
	-placeOfBirth
	-height
	-weight
	-religion
	-occupation
	-sex
	-civilStatus
	-gender
	-noOfChildren
	

*/
