<?php

namespace App\Http\Controllers;

use App\Models\RegistrationCycle;
use Illuminate\Http\Request;

class ComposedAnnouncementController extends Controller
{
    //

    public function valStep1Post(Request $request)
    {
        $request->validate([
            'cycle' => 'required|exists:registration_cycles,cycleName',
            'addresses' => [
                function ($attr, $val,$fail){
                    if (!collect($val)->flatten()->contains(true)) {
                        $fail($attr . ' must be chosen at least one.');
                    }
                }
            ]
        ]);
 
        return response()->json([
            true
        ]);
  
    }
    public function valStep2Post(Request $request)
    {
        $request->validate([
            'when' => 'required|date_format:Y-m-d',
            'where' => 'required|max:60',
            'what' => 'required|max:60',
            'description' => 'required|max:120',
        ]);
 
        return response()->json([
            true
        ]);
  
    }

    public function compose(Request $request)
    {
        $request->validate([
            'organization' => 'required|string|max:255',
            'orgaddress' => 'required|string|max:255',
            'start' => 'required|date_format:Y-m-d',
            'end' => 'required|string|max:255',
            'yearGraduated' => 'required|integer|between:1995,2100',
        ]);
        return true;
    }
    public function getAllCycle(Request $request)
    {
        $cycle = RegistrationCycle::all();
        return response()->json([
            'cycle' => $cycle
        ]);
    }
}
