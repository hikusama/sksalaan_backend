<?php

namespace App\Http\Controllers;

use App\Models\SyncHub;
use Illuminate\Http\Request;

class SyncHubController extends Controller
{
    //
    public function createHub(Request $request)
    {
        $fields = $request->validate([
            'addresses' => [
                function ($attr, $val, $fail) {
                    if (!collect($val)->flatten()->contains(true)) {
                        $fail($attr . ' must be chosen at least one.');
                    }
                }
            ]
        ]);

        $res = SyncHub::all();
        if (count($res) >= 10) {
            return response()->json([
                'errors' => [
                    'size' => 'Max hubs reached please delete hubs to enable create'
                ]
            ],422);
        }


        $selectedString = collect($fields['addresses'])
            ->filter(fn($v) => $v === true)
            ->keys()
            ->map(fn($key) => str_replace('_', ' ', $key))
            ->implode(', ');
        $fields['addresses'] = $selectedString;
        try {
            $res = SyncHub::create($fields);
        } catch (\Throwable $th) {
            return $th->getMessage();
        }

        return response()->json([
            $res
        ]);
    }

    public function getHubs()
    {
        $res = SyncHub::all();
        return response()->json([
            'data' => $res
        ]);
    }

    public function openHub(Request $req)
    {
        $res = SyncHub::findOrFail($req->input('id'));

        SyncHub::where('status', 'opened')->update(['status' => 'closed']);
        $xp = now()->addMinute();
        $res->update([
            'status' => 'opened',
            'expires_at' => $xp,
        ]);
        $res->save();

        return response()->json([
            'msg' => 'Success...',
            'action' => 'open',
            'time' => $xp
        ]);
    }

    public function closeHub(Request $req)
    {
        SyncHub::findOrFail($req->input('id'));

        SyncHub::where('status', 'opened')->update(['status' => 'closed']);

        return response()->json([
            'msg' => 'Success...',
            'action' => 'close'
        ]);
    }

    public function deleteHub($id)
    {
        SyncHub::findOrFail($id);

        SyncHub::destroy($id);

        return response()->json([
            'msg' => 'Success...',
            'action' => 'destroy'
        ]);
    }
}
