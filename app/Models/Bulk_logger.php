<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bulk_logger extends Model
{

    use HasFactory;

    protected $fillable = [
        'user_id',
        'batchNo',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
