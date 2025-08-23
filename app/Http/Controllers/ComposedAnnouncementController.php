<?php

namespace App\Http\Controllers;

use App\Models\ComposedAnnouncement;
use App\Models\RegistrationCycle;
use App\Models\YouthInfo;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Validator;

class ComposedAnnouncementController extends Controller
{
    //

    public function valStep1Post(Request $request)
    {
        $fields = $request->validate([
            'registration_cycle_id' => 'required|exists:registration_cycles,id',
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
            'registration_cycle_id' => 'required|exists:registration_cycles,id',
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
                'last_pages' => $results->lastPage(),
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
                'last_pages' => $results->lastPage(),
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
                'last_pages' => $results->lastPage(),
            ]
        ]);
    }
    public function getComposedAnnouncement(Request $request)
    {
        $page = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $results = ComposedAnnouncement::paginate(10);
        return response()->json([
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_pages' => $results->lastPage(),
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
                'last_pages' => $results->lastPage(),
            ]
        ]);
    }


    public function getYouthContacts(Request $request)
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
                'last_pages' => $results->lastPage(),
            ]
        ]);
    }

    public function delSms($id)
    {
        $validator = Validator::make(
            ['id' => $id],
            ['id' => 'required|exists:composed_announcements,id'],
        );
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        ComposedAnnouncement::destroy($validator->valid());

        return response()->json([
            'msg' => 'Success..',
        ]);
    }


    public function sendSMS($id)
    {

        $validator = Validator::make(
            ['id' => $id],
            ['id' => 'required|exists:composed_announcements,id'],
        );
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        $composedAn = ComposedAnnouncement::where('id', $id)->first();

        $addresses = array_map('trim', explode(',', $composedAn->addresses));

        $numbers = YouthInfo::whereIn('address', $addresses)
            ->whereHas('yUser.validated', function ($q) use ($id) {
                $q->where('registration_cycle_id', $id);
            })
            ->pluck('contactNo')->toArray();

        $date = new DateTime($composedAn->when);
        $date = $date->format("M, d Y g:ia");
        $body = "ANNOUNCEMENT!! \nWHAT: $composedAn->what \nWHEN: $date\nWHERE: $composedAn->where \n\nNOTE: $composedAn->description \n\n\nThankyou\nBy: SK Chairman of salaan";
        $composedAn->update([
            'smsStatus' => 'delivered'
        ]);
        return response()->json([
            'msg' => 'Success..',
            'numbers' => $numbers,
            'body' => $body,
        ]);
    }
}
