<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistrationCycle extends Model
{

    use HasFactory;
    protected $fillable = [
        'cycleName',
        'cycleStatus',
        'start',
        'end',
    ];

    public function validatedYouths()
    {
        return $this->hasMany(ValidateYouth::class);
    }
}
