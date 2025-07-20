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
            'cycleName' => 'required|max:20|min:4',
        ]);
        $field['start'] = now();

        // RegistrationCycle::create($field);

        return response()->json([
            'message' => 'Created Successfully...'
        ]);
    }

    public function stopRunning(Request $request)
    {
        try {
            RegistrationCycle::where('cycleStatus', 'active')->update(['cycleStatus' => 'inActive']);
            return response()->json([ 
                'message' => 'Cycle Stopped Successfully...'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong!'
            ],400);
        }
    }

    public function startRunning(Request $request)
    {
        try {
            RegistrationCycle::where('cycleStatus', 'inActive')->update(['cycleStatus' => 'active']);
            return response()->json([ 
                'message' => 'Cycle Started running...'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong!'
            ],400);
        }
    }
    public function show(Request $request)
    {
        $page = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $results = RegistrationCycle::withCount('yUser')->paginate(10);

        return response()->json([
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_pages' => $results->lastPage(),
            ]
        ]);
    }
}
