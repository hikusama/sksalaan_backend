<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComposedAnnouncement extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'webStatus',
        'smsStatus',
        'when',
        'where',
        'what',
        'cycle',
        'addresses',
        'description',
    ];
}


/*
$table->string('status')->default('pending');
$table->dateTime('when');
$table->string('where');
$table->string('addresses');
$table->string('cycle');
$table->string('what');
$table->string('description');
*/
