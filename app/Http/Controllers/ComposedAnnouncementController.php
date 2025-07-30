<?php

namespace App\Http\Controllers;

use App\Models\ComposedAnnouncement;
use App\Models\RegistrationCycle;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

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
    public function getWebPending(Request $request)
    {
        $page = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $results = ComposedAnnouncement::where('webStatus', 'pending')->paginate(10);
        return response()->json([
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'total_pages' => $results->lastPage(),
                'total_items' => $results->total(),
            ]
        ]);
    }
    public function getWebDelivered(Request $request)
    {
        $page = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $results = ComposedAnnouncement::where('webStatus', 'delivered')->paginate(10);
        return response()->json([
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'total_pages' => $results->lastPage(),
                'total_items' => $results->total(),
            ]
        ]);
    }


    public function getSMSPending(Request $request)
    {
        $page = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $results = ComposedAnnouncement::where('smsStatus', 'pending')->paginate(10);
        return response()->json([
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'total_pages' => $results->lastPage(),
                'total_items' => $results->total(),
            ]
        ]);
    }
    public function getSMSDelivered(Request $request)
    {
        $page = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $results = ComposedAnnouncement::where('smsStatus', 'delivered')->paginate(10);
        return response()->json([
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'total_pages' => $results->lastPage(),
                'total_items' => $results->total(),
            ]
        ]);
    }
}
