<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SyncHub extends Model
{
    //
    use HasFactory;
 
    protected $fillable = [
        'status',
        'addresses',
    ];
}
