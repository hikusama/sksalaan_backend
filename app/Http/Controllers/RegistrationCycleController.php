<?php

namespace App\Http\Controllers;

use App\Models\RegistrationCycle;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class RegistrationCycleController extends Controller
{

    public function store(Request $request)
    {
        $field = $request->validate([
            'cycleName' => 'required|max:20|min:4',
        ]);

        RegistrationCycle::create($field);

        return response()->json([
            'message' => 'Created Successfully...'
        ]);
    }

    public function deleteCycle($id)
    {
        $cycleID = $id;

        try {
            $cycle = RegistrationCycle::findOrFail($cycleID);
            RegistrationCycle::destroy($cycle->id);
            return response()->json([
                'message' => 'Cycle deleted Successfully...'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong!' . $cycleID,
                'er' => $th->getMessage(),

            ], 400);
        }
    }

    public function stopCycle(Request $request)
    {
        $cycleID = $request->input('cycleID');

        try {
            $cycle = RegistrationCycle::findOrFail($cycleID);
            RegistrationCycle::where('cycleStatus', 'active')->update(['cycleStatus' => 'inactive']);
            $cycle->end = now();
            $cycle->save();
            return response()->json([
                'message' => 'Cycle Stopped Successfully...'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong!'
            ], 400);
        }
    }
    public function runCycle(Request $request)
    {
        $cycleID = $request->input('cycleID');
        $toend = $request->input('toend');
        try {
            $tablesCount = RegistrationCycle::take(2)->get(['id']);
            if (count($tablesCount) > 1 && $toend) {
                $cycleToEnd = RegistrationCycle::findOrFail($toend);
                $cycleToEnd->end = now();
                $cycleToEnd->save();
            } 
            $cycle = RegistrationCycle::findOrFail($cycleID);

            RegistrationCycle::where('cycleStatus', 'active')
                ->where('id', '!=', $cycleID)
                ->update([
                    'cycleStatus' => 'inactive',
                ]);

            $cycle->cycleStatus = 'active';
            $cycle->end = null;
            if (!$cycle->start) {
                $cycle->start = now();
            }

            $cycle->save();

            return response()->json([
                'message' => 'Cycle started successfully.',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Something went wrong! ID: ' . $cycleID,
                'error' => $th->getMessage(),
            ], 400);
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
