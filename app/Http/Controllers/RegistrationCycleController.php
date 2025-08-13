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

 
    public function show(Request $request)
    {

        $results = RegistrationCycle::withCount('validatedYouths')->orderBy('created_at', 'DESC')->get();

        return response()->json([
            'data' => $results,

        ]);
    }
}
