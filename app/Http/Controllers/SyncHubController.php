<?php

namespace App\Http\Controllers;

use App\Models\RegistrationCycle;
use App\Models\SyncHub;
use App\Models\YouthInfo;
use Carbon\Carbon;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncHubController extends Controller
{
    //
    public function createHub(Request $request)
    {

        $fields = $request->validate([
            'addresses' => [
                'required',
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

    public function getAddresses()
    {
        $cycleID = $this->getCycle();

        $res = YouthInfo::whereDoesntHave('yUser.validated', function ($qq) use ($cycleID) {
            $qq->where('registration_cycle_id', $cycleID);
        })->distinct()->pluck('address');
        $addresses = $res->mapWithKeys(function ($addr) {
            return [$addr => false];
        })->toArray();

        return response()->json([
            'addr' => $addresses,
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

        SyncHub::where('status', 'opened')->update(['status' => 'closed', 'expires_at' => null]);
        $xp = now()->addMinutes(15);
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
        // Log::info($req->all());

        $hub = SyncHub::where('status', 'opened')->first();
        if ($hub) {
            if (Carbon::now()->greaterThan($hub->expires_at)) {
                return response()->json([
                    'msg' => 'Hub expired.',
                ], 422);
            }
        } else {
            return response()->json([
                'msg' => 'Hub is closed.',
            ], 422);
        }

        $fieldAddrs = $req->validate([
            'addresses' => [
                'required',
                function ($attr, $val, $fail) {
                    if (!collect($val)->pluck('status')->contains(true)) {
                        $fail($attr . ' must be chosen at least one.');
                    }
                }
            ]
        ]);


        $addressesReq = collect($fieldAddrs['addresses'])
            ->filter(fn($a) => $a['status'] === true)
            ->keys()
            ->map(fn($key) => strtolower(trim($key)))
            ->toArray();

        $addresses = collect(explode(',', $hub->addresses))
            ->map(fn($a) => strtolower(trim($a)))
            ->toArray();

        foreach ($addressesReq as $val) {
            if (!in_array($val, $addresses)) {
                return response()->json([
                    'val' => $addressesReq,
                    'msg' => 'Address not present in opened hub.',
                ], 422);
            }
        }

        $cycleID = $this->getCycle();


        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $ageExpression = 'CAST((strftime("%Y", "now") - strftime("%Y", dateOfBirth)) AS INTEGER)';
        } else {
            $ageExpression = 'TIMESTAMPDIFF(YEAR, dateOfBirth, CURDATE())';
        }


        $query = YouthInfo::whereDoesntHave('yUser.validated', function ($qq) use ($cycleID) {
            $qq->where('registration_cycle_id', $cycleID);
        })->whereHas('yUser', function ($q) {
            $q->whereNotNull('user_id');
        })
            ->whereBetween(DB::raw($ageExpression), [15, 30])
            ->whereIn(DB::raw('LOWER(address)'), collect($addressesReq)->map(fn($a) => strtolower($a)))
            ->with([
                'yUser',
                'yUser.educbg',
                'yUser.civicInvolvement'
            ])->get();
        $profiles = $query->map(function ($youth) {
            return [
                'youthUser' => [
                    'orgId' => $youth->yUser->id,
                    'youthType' => $youth->yUser->youthType,
                    'skills' => $youth->yUser->skills,
                    'status' => 'Unvalidated',
                    'registerAt' => $youth->yUser->created_at,
                ],
                'youthInfo' => [
                    'fname' => $youth->fname,
                    'mname' => $youth->mname,
                    'lname' => $youth->lname,
                    'age' => Carbon::parse($youth->dateOfBirth)->age,
                    'sex' => $youth->sex,
                    'gender' => $youth->gender,
                    'address' => $youth->address,
                    'dateOfBirth' => $youth->dateOfBirth,
                    'placeOfBirth' => $youth->placeOfBirth,
                    'contactNo' => $youth->contactNo,
                    'religion' => $youth->religion,
                    'occupation' => $youth->occupation ?? '',
                    'civilStatus' => $youth->civilStatus,
                    'noOfChildren' => $youth->noOfChildren ?? 0,
                    'height' => $youth->height ?? 0,
                    'weight' => $youth->weight ?? 0,
                ],
                'educBgs' => $youth->yUser->educbg->map(fn($e) => [
                    'orgId' => $e->id,
                    'level' => $e->level,
                    'nameOfSchool' => $e->nameOfSchool,
                    'periodOfAttendance' => $e->periodOfAttendance,
                    'yearGraduate' => $e->yearGraduate,
                ]),
                'civicInvolvements' => $youth->yUser->civicInvolvement->map(fn($c) => [
                    'orgId' => $c->id,
                    'nameOfOrganization' => $c->nameOfOrganization,
                    'addressOfOrganization' => $c->addressOfOrganization,
                    'start' => $c->start,
                    'end' => $c->end,
                    'yearGraduated' => $c->yearGraduated,
                ]),
            ];
        });



        return response()->json([
            'hub' => $hub,
            'profiles' => $profiles,
            'msg' => 'success',
        ]);
    }

    public function getOpenHub()
    {
        $cycleID = $this->getCycle();

        $hub = SyncHub::where('status', 'opened')->first();
        if ($hub) {
            if (now()->greaterThan($hub->expires_at)) {
                return response()->json([
                    'msg' => 'Hub expired.',
                ], 422);
            }
        } else {
            return response()->json([
                'msg' => 'Hub is closed.',
            ], 422);
        }
        $addresses = array_map('trim', explode(",", $hub->addresses));

        $counts = YouthInfo::select('address', DB::raw('COUNT(*) as value'))
            ->whereIn('address', $addresses)
            ->whereDoesntHave('yUser.validated', function ($qq) use ($cycleID) {
                $qq->where('registration_cycle_id', $cycleID);
            })->groupBy('address')
            ->pluck('value', 'address')
            ->toArray();

        $q = [];
        foreach ($addresses as $addr) {
            $q[$addr] = [
                'value' => $counts[$addr] ?? 0,
                'status' => false,
            ];
        }

        return response()->json([
            'hub' => $hub,
            'q' => $q,
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

    public function getCycle()
    {
        $res = RegistrationCycle::where('cycleStatus', 'active')->first();
        return $res->id ?? 0;
    }
}
