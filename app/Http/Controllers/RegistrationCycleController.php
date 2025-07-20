<?php

namespace App\Http\Controllers;

use App\Models\RegistrationCycle;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class RegistrationCycleController extends Controller
{
    public function store(Request $request)
    {
        $field = $request->validate([
            'cycleName' => 'required|max:20',
        ]);
        $field['started'] = now();

        RegistrationCycle::create($field);

        return response()->json([
            'message' => 'Success...'
        ]);
    }
    public function show(Request $request)
    {
        $page = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $results = RegistrationCycle::paginate(10);

        return response()->json([
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_pages' => $results->lastPage(),
            ]
        ]);
    }
}
