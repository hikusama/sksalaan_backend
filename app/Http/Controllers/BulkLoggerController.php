<?php

namespace App\Http\Controllers;

use App\Models\Bulk_logger;
use Illuminate\Http\Request;

class BulkLoggerController extends Controller
{
    public function bulkGet()
    {
        return Bulk_logger::all();
    }
}
