<?php

namespace App\Http\Controllers;

use App\Models\ComposedAnnouncement;
use App\Models\RegistrationCycle;
use Illuminate\Http\Request;

class ComposedAnnouncementController extends Controller
{
    //

    public function valStep1Post(Request $request)
    {
        $fields = $request->validate([
            'cycle' => 'required|exists:registration_cycles,cycleName',
            'addresses' => [
                function ($attr, $val, $fail) {
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
            'when' => 'required|date_format:Y-m-d\TH:i|after_or_equal:' . now()->format('Y-m-d\TH:i'),
            'where' => 'required|max:60',
            'what' => 'required|max:60',
            'description' => 'required|max:120',
        ]);

        return response()->json([
            $request->all()
        ]);
    }

    public function compose(Request $request)
    {
        $fields = $request->validate([
            'when' => 'required|date_format:Y-m-d\TH:i|after_or_equal:' . now()->format('Y-m-d\TH:i'),
            'where' => 'required|max:60',
            'what' => 'required|max:60',
            'description' => 'required|max:120',
            'cycle' => 'required|exists:registration_cycles,cycleName',
            'addresses' => [
                function ($attr, $val, $fail) {
                    if (!collect($val)->flatten()->contains(true)) {
                        $fail($attr . ' must be chosen at least one.');
                    }
                }
            ]
        ]);

        $selectedString = collect($fields['addresses'])
            ->filter(fn($v) => $v === true)
            ->keys()
            ->map(fn($key) => str_replace('_', ' ', $key))
            ->implode(', ');
        $fields['addresses'] = $selectedString;
        try {
            //code...
            $res = ComposedAnnouncement::create($fields);
        } catch (\Throwable $th) {
        return $th->getMessage();

            //throw $th;
        }

        return response()->json([
            $res
        ]);
    }
    public function getAllCycle(Request $request)
    {
        $cycle = RegistrationCycle::all();
        return response()->json([
            'cycle' => $cycle
        ]);
    }
}
