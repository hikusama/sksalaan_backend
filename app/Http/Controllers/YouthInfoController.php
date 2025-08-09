<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreyouthInfoRequest;
use App\Http\Requests\UpdateyouthInfoRequest;
use App\Models\RegistrationCycle;
use App\Models\YouthInfo;
use App\Models\YouthUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class YouthInfoController extends Controller
{
    /**
     * Display a listing of the resource.   
     */


    public function getMapData(Request $request)
    {
        // 
        $cycleID = $request->input('cID');
    }
    public function getInfoData(Request $request)
    {
        $cycleID = $request->input('cID');

        if ($cycleID !== 'all') {
            RegistrationCycle::findOrFail($cycleID);
        }



        $yt = YouthUser::select(DB::raw("LOWER(youthType) as name"), DB::raw('COUNT(*) as value'))
            ->whereRaw("LOWER(youthType) IN ('isy', 'osy')")
            ->when($cycleID !== 'all', function ($qq) use ($cycleID) {
                $qq->whereHas('validated', function ($q) use ($cycleID) {
                    $q->where('registration_cycle_id', $cycleID);
                });
            })
            ->whereNotNull('user_id')
            ->groupBy('name')
            ->get();

        $sex = YouthInfo::select(DB::raw("LOWER(sex) as name"), DB::raw('COUNT(*) as value'))
            ->whereRaw("LOWER(sex) IN ('male', 'female')")
            ->when($cycleID !== 'all', function ($qq) use ($cycleID) {
                $qq->whereHas('yUser.validated', function ($q) use ($cycleID) {
                    $q->where('registration_cycle_id', $cycleID);
                });
            })
            ->groupBy('name')
            ->get();

        $gender = YouthInfo::select(DB::raw("COALESCE(NULLIF(LOWER(gender), ''), 'not-specified') as name"), DB::raw('COUNT(*) as value'))
            ->where(function ($q) {
                $q->whereRaw("LOWER(gender) IN ('non-binary', 'binary')")
                    ->orWhereNull('gender')
                    ->orWhere('gender', '');
            })
            ->when($cycleID !== 'all', function ($qq) use ($cycleID) {
                $qq->whereHas('yUser.validated', function ($q) use ($cycleID) {
                    $q->where('registration_cycle_id', $cycleID);
                });
            })
            ->groupBy('name')
            ->get();
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $ageExpression = 'CAST((strftime("%Y", "now") - strftime("%Y", dateOfBirth)) AS INTEGER)';
        } else {
            $ageExpression = 'TIMESTAMPDIFF(YEAR, dateOfBirth, CURDATE())';
        }

        $ages = YouthInfo::selectRaw("$ageExpression as age, COUNT(*) as count")
            ->whereBetween(DB::raw($ageExpression), [15, 30])
            ->when($cycleID !== 'all', function ($qq) use ($cycleID) {
                $qq->whereHas('yUser.validated', function ($q) use ($cycleID) {
                    $q->where('registration_cycle_id', $cycleID);
                });
            })
            ->groupBy('age')
            ->orderBy('age')
            ->get();


        $civilStats = YouthInfo::select(DB::raw("LOWER(civilStatus) as name"), DB::raw('COUNT(*) as value'))
            ->whereRaw("LOWER(civilStatus) IN ('single', 'married', 'divorce', 'outside-marriage')")
            ->when($cycleID !== 'all', function ($qq) use ($cycleID) {
                $qq->whereHas('yUser.validated', function ($q) use ($cycleID) {
                    $q->where('registration_cycle_id', $cycleID);
                });
            })
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        $religions = YouthInfo::select(DB::raw("LOWER(religion) as name"), DB::raw('COUNT(*) as value'))
            ->whereRaw("LOWER(religion) IN ('islam', 'christianity', 'judaism', 'buddhism', 'hinduism', 'atheism', 'others')")
            ->when($cycleID !== 'all', function ($qq) use ($cycleID) {
                $qq->whereHas('yUser.validated', function ($q) use ($cycleID) {
                    $q->where('registration_cycle_id', $cycleID);
                });
            })
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        return response()->json([
            'sexes' => $sex,
            'genders' => $gender,
            'civilStats' => $civilStats,
            'religions' => $religions,
            'ages' => $ages,
            'youthType' => $yt,
        ]);
    }



    public function getCycle()
    {
        $res = RegistrationCycle::where('cycleStatus', 'active')->first();
        return $res->id ?? 0;
    }
}
