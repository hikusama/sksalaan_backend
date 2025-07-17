<?php

namespace App\Http\Controllers;

use App\Models\Bulk_logger;
use App\Models\SkOfficial;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class BulkLoggerController extends Controller
{
    public function bulkGetBatchContent(Request $request)
    {
        //
        $page = $request->input('page', 1);
        $user_id = $request->input('user_id');
        User::findOrFail($user_id);

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $results = Bulk_logger::where('user_id', $user_id)->paginate(10);
        return json_encode([
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_pages' => $results->lastPage(),
            ]
        ]);
    }
    public function bulkGetUser(Request $request)
    {
        $page = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $results = SkOfficial::paginate(10);
        return json_encode([
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_pages' => $results->lastPage(),
            ]
        ]);
    }
    public function bulkGet()
    {
        return Bulk_logger::all();
    }
}
