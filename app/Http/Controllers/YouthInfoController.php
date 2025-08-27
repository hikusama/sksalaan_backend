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
        $cid = $this->getCycle();
        $cycleID = $request->input('cID', $cid);

        $validity = strtolower($request->input('validity', 'validated'));
        $youthType = strtolower($request->input('youthType'));
        $sex = strtolower($request->input('sex'));
        $gender = strtolower($request->input('gender'));
        $civilStatus = strtolower($request->input('civilStatus'));
        $ageType = $request->input('ageType');
        $ageValue = $request->input('ageValue', []);
        $qualification = strtolower($request->input('qualification', 'qualified'));
        $driver = DB::getDriverName();
        if ($driver === 'sqlite') {
            $ageExpression = 'CAST((strftime("%Y", "now") - strftime("%Y", dateOfBirth)) AS INTEGER)';
        } else {
            $ageExpression = 'TIMESTAMPDIFF(YEAR, dateOfBirth, CURDATE())';
        }


        $points = [
            'sittio carreon' => [
                122.09316481424457,
                7.06129199083955
            ],
            'sittio baluno' => [
                122.10558661098503,
                7.051041979286168
            ],
            'sittio hapa' => [
                122.10726145998348,
                7.0359435762926665
            ],
            'sittio lugakit' => [
                122.1053074694857,
                7.015165154822228
            ],
            'sittio san antonio' => [
                122.10893630898192,
                6.992584885594354
            ],
            'zone 1' => [
                122.12079982272235,
                6.9766533638894686
            ],
            'zone 2' => [
                122.11870626147487,
                6.985796738358118
            ],
            'zone 3' => [
                122.11200686547954,
                6.980670929222086
            ],
            'zone 4' => [
                122.11800840772531,
                6.992861950733442
            ],
        ];

        $query = YouthInfo::select(DB::raw("LOWER(address) as name"), DB::raw('COUNT(*) as value'))
            ->whereRaw("LOWER(address) IN (
                'sittio carreon', 
                'sittio baluno', 
                'sittio lugakit', 
                'sittio hapa', 
                'sittio san antonio',
                'zone 1',
                'zone 2',
                'zone 3',
                'zone 4'
            )")
            ->whereHas('yUser.validated')
            ->when(!empty($youthType), fn($q) => $q->where('youthType', $youthType))
            ->when(!empty($sex), fn($q) => $q->where('sex', $sex))
            ->when(!empty($gender), fn($q) => $q->where('gender', $gender))
            ->when(!empty($civilStatus), fn($q) => $q->where('civilStatus', $civilStatus))
            ->groupBy(DB::raw('LOWER(address)'));
        if ($validity === 'all') {

            $query->when($cycleID !== 'all', function ($qq) use ($cycleID) {
                $qq->whereHas('yUser.validated', function ($q) use ($cycleID) {
                    $q->where('registration_cycle_id', $cycleID);
                });
            });
        } else {
            if ($validity === 'validated') {
                $query->where(function ($sub) use ($cid) {
                    $sub->whereHas('yUser.validated', function ($qq) use ($cid) {
                        $qq->where('registration_cycle_id', $cid);
                    });
                });
            } elseif ($validity === 'unvalidated') {
                $query->whereDoesntHave('yUser.validated', function ($qq) use ($cid) {
                    $qq->where('registration_cycle_id', $cid);
                });
            }
        }

        $query->when(true, function ($q) use ($qualification, $ageType, $ageValue, $ageExpression) {
            if ($qualification === 'unqualified') {
                $q->where(function ($sub) use ($ageExpression) {
                    $sub->where(DB::raw($ageExpression), '<', 15)
                        ->orWhere(DB::raw($ageExpression), '>', 30);
                });
            } elseif ($qualification === 'qualified') {
                if ($ageType === 'single' && !empty($ageValue['min'])) {
                    $q->whereRaw("$ageExpression = ?", [intval($ageValue['min'])]);
                } elseif ($ageType === 'range' && !empty($ageValue['min']) && !empty($ageValue['max'])) {
                    $q->whereBetween(DB::raw($ageExpression), [
                        intval($ageValue['min']),
                        intval($ageValue['max'])
                    ]);
                } else {
                    $q->whereBetween(DB::raw($ageExpression), [15, 30]);
                }
            }
        });

        $result = $query->get()->keyBy('name');

        $funnelChart = [];
        $mapChart    = [];

        foreach ($points as $addr => $coords) {
            $value = $result->has($addr) ? $result[$addr]->value : 0;

            $funnelChart[] = [
                "name"  => $this->ft($addr),
                "value" => $value,
            ];

            $mapChart[] = [...$coords, $value];
        }

        usort($funnelChart, fn($a, $b) => $b['value'] <=> $a['value']);

        return response()->json([
            'funnelChart' => $funnelChart,
            'mapChart' => $mapChart,
        ]);
    }


    public function ft($text)
    {
        return ucfirst(strtolower($text));
    }
    public function getInfoData(Request $request)
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $ageExpression = 'CAST((strftime("%Y", "now") - strftime("%Y", dateOfBirth)) AS INTEGER)';
        } else {
            $ageExpression = 'TIMESTAMPDIFF(YEAR, dateOfBirth, CURDATE())';
        }
        $orgID = $this->getCycle();

        $cycleID = $request->input('cID');
        $qualification = strtolower($request->input('qualification', 'qualified'));

        if ($cycleID !== 'all') {
            RegistrationCycle::findOrFail($cycleID);
        }
        $vlRegs = YouthUser::whereHas('validated', function ($vq) use ($orgID) {
            $vq->where('registration_cycle_id', $orgID);
        })->count();
        $vlUnRegs = YouthUser::whereDoesntHave('validated', function ($vq) use ($orgID) {
            $vq->where('registration_cycle_id', $orgID);
        })->count();

        $vl = [
            [
                "name" => "validated",
                "value" => $vlRegs,
            ],
            [
                "name" => "unvalidated",
                "value" => $vlUnRegs,
            ]
        ];

        // Reusable qualification filter
        $applyQualification = function ($query) use ($qualification, $ageExpression) {
            if ($qualification === 'qualified') {
                $query->whereBetween(DB::raw($ageExpression), [15, 30]);
            } elseif ($qualification === 'unqualified') {
                $query->where(function ($sub) use ($ageExpression) {
                    $sub->where(DB::raw($ageExpression), '<', 15)
                        ->orWhere(DB::raw($ageExpression), '>', 30);
                });
            }
            // "all" → do nothing
        };

        $yt = YouthUser::select(DB::raw("LOWER(youthType) as name"), DB::raw('COUNT(*) as value'))
            ->whereRaw("LOWER(youthType) IN ('isy', 'osy')")
            ->when($cycleID !== 'all', function ($qq) use ($cycleID) {
                $qq->whereHas('validated', function ($q) use ($cycleID) {
                    $q->where('registration_cycle_id', $cycleID);
                });
            })
            ->whereNotNull('user_id')
            ->when($qualification !== 'all', function ($qq) use ($applyQualification) {
                $qq->whereHas('info', $applyQualification);
            })
            ->groupBy('name')
            ->get();

        $sex = YouthInfo::select(DB::raw("LOWER(sex) as name"), DB::raw('COUNT(*) as value'))
            ->whereRaw("LOWER(sex) IN ('male', 'female')")
            ->when($cycleID !== 'all', function ($qq) use ($cycleID) {
                $qq->whereHas('yUser.validated', function ($q) use ($cycleID) {
                    $q->where('registration_cycle_id', $cycleID);
                });
            })
            ->when($qualification !== 'all', $applyQualification)
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
            ->when($qualification !== 'all', $applyQualification)
            ->groupBy('name')
            ->get();

        $ages = YouthInfo::selectRaw("$ageExpression as age, COUNT(*) as count")
            ->when($cycleID !== 'all', function ($qq) use ($cycleID) {
                $qq->whereHas('yUser.validated', function ($q) use ($cycleID) {
                    $q->where('registration_cycle_id', $cycleID);
                });
            })
            ->when($qualification !== 'all', $applyQualification)
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
            ->when($qualification !== 'all', $applyQualification)
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
            ->when($qualification !== 'all', $applyQualification)
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
            'validity' => $vl,
        ]);
    }




    public function getCycle()
    {
        $res = RegistrationCycle::where('cycleStatus', 'active')->first();
        return $res->id ?? 0;
    }
}
