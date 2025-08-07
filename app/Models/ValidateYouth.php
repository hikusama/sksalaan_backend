<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValidateYouth extends Model
{
    use HasFactory;
    protected $table = 'validated_youths';

    protected $fillable = [
        'youth_user_id',
        'registration_cycle_id',
    ];
    public function regCycle()
    {
        return $this->belongsTo(RegistrationCycle::class, 'registration_cycle_id');
    }
    public function yUser() {
        return $this->belongsTo(YouthUser::class);
    }
}
