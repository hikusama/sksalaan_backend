<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreyouthInfoRequest;
use App\Http\Requests\UpdateyouthInfoRequest;
use App\Models\RegistrationCycle;
use App\Models\YouthInfo;
use App\Models\YouthUser;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;

class YouthInfoController extends Controller
{
    /**
     * Display a listing of the resource.   
     */
    public function getInfoData()
    {
        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }

        $yt = YouthUser::select(DB::raw("LOWER(youthType) as name"), DB::raw('COUNT(*) as value'))
            ->whereRaw("LOWER(youthType) IN ('isy', 'osy')")
            ->where('registration_cycle_id', $cycleID)
            ->whereNotNull('user_id')
            ->groupBy('name')
            ->get();

        $sex = YouthInfo::select(DB::raw("LOWER(sex) as name"), DB::raw('COUNT(*) as value'))
            ->whereRaw("LOWER(sex) IN ('male', 'female')")
            ->whereHas('yUser', function ($q) use ($cycleID) {
                $q->where('registration_cycle_id', $cycleID)->whereNotNull('user_id');
            })
            ->groupBy('name')
            ->get();

        $gender = YouthInfo::select(DB::raw("COALESCE(NULLIF(LOWER(gender), ''), 'not-specified') as name"), DB::raw('COUNT(*) as value'))
            ->where(function ($q) {
                $q->whereRaw("LOWER(gender) IN ('non-binary', 'transgender', 'agender', 'bigender', 'others')")
                    ->orWhereNull('gender')
                    ->orWhere('gender', '');
            })
            ->whereHas('yUser', function ($q) use ($cycleID) {
                $q->where('registration_cycle_id', $cycleID)->whereNotNull('user_id');
            })
            ->groupBy('name')
            ->get();

        $ages = YouthInfo::select('age', DB::raw('COUNT(*) as count'))
            ->whereIn('age', range(15, 30))
            ->whereHas('yUser', function ($q) use ($cycleID) {
                $q->where('registration_cycle_id', $cycleID)->whereNotNull('user_id');
            })
            ->groupBy('age')
            ->orderBy('age')
            ->get();

        $civilStats = YouthInfo::select(DB::raw("LOWER(civilStatus) as name"), DB::raw('COUNT(*) as value'))
            ->whereRaw("LOWER(civilStatus) IN ('single', 'married', 'divorce', 'outside-marriage')")
            ->whereHas('yUser', function ($q) use ($cycleID) {
                $q->where('registration_cycle_id', $cycleID)->whereNotNull('user_id');
            })
            ->groupBy('name')
            ->orderBy('name')
            ->get();

        $religions = YouthInfo::select(DB::raw("LOWER(religion) as name"), DB::raw('COUNT(*) as value'))
            ->whereRaw("LOWER(religion) IN ('islam', 'christianity', 'judaism', 'buddhism', 'hinduism', 'atheism', 'others')")
            ->whereHas('yUser', function ($q) use ($cycleID) {
                $q->where('registration_cycle_id', $cycleID)->whereNotNull('user_id');
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
