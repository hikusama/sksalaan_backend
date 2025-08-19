<?php

namespace App\Http\Controllers;

use App\Models\SyncHub;
use App\Models\YouthInfo;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ], 422);
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
        $openhub = SyncHub::where('status', 'opened')->first();
        if ($openhub && now()->greaterThan($openhub->expires_at)) {
            $openhub->status = 'closed';
            $openhub->expires_at = null;
            $openhub->save();
        }
        $res = SyncHub::all();

        return response()->json([
            'data' => $res,
            'expires_at' => $openhub ? $openhub->expires_at : ''
        ]);
    }

    public function openHub(Request $req)
    {
        $res = SyncHub::findOrFail($req->input('id'));

        SyncHub::where('status', 'opened')->update(['status' => 'closed']);
        $xp = now()->addMinutes(2);
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

        SyncHub::where('status', 'opened')->update(['status' => 'closed', 'expires_at' => null]);

        return response()->json([
            'msg' => 'Success...',
            'action' => 'close'
        ]);
    }

    public function getDataFromHub(Request $req)
    {
        $hub = SyncHub::findOrFail($req->input('id'));

        if ($hub->status === 'closed') {
            return response()->json([
                'msg' => 'Hub is closed.',
            ], 422);
        }
        if (now()->greaterThan($hub->expires_at)) {
            return response()->json([
                'msg' => 'Hub is closed.',
            ], 422);
        }
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $ageExpression = 'CAST((strftime("%Y", "now") - strftime("%Y", dateOfBirth)) AS INTEGER)';
        } else {
            $ageExpression = 'TIMESTAMPDIFF(YEAR, dateOfBirth, CURDATE())';
        }

        $addresses = array_map('trim', explode(",", $hub->addresses));

        $query = YouthInfo::whereHas('yUser.validated', function ($qq) {
            $qq->whereNotNull('youth_user_id');
        })
            ->whereBetween(DB::raw($ageExpression), [15, 30])
            ->whereIn('address', $addresses)
            ->with([
                'yUser',
                'yUser.educbg',
                'yUser.civicInvolvement'
            ])->get();


        return response()->json([
            'hub' => $hub,
            'addr' => $addresses,
            'query' => $query,
            'msg' => 'success',
        ]);
    }

    public function pickHub()
    {
        $hubs = SyncHub::where('status', 'opened')->get();

        return response()->json([
            'hubs' => $hubs,
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
