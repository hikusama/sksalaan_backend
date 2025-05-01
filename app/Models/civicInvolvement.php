<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class civicInvolvement extends Model
{
    /** @use HasFactory<\Database\Factories\CivicInvolvementFactory> */
    use HasFactory;

    protected $fillable = [
        'youth_user_id',
        'nameOfOrganization',
        'addressOfOrganization',
        'start',
        'end',
        'yearGraduated',
    ];

    public function yuser()  {
        return $this->belongsTo(YouthUser::class);
    }
}

/*
	-nameOfOrganization
	-addressOfOrganization
	-start
	-end
	-yearGraduated
*/
