<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreyouthInfoRequest;
use App\Http\Requests\UpdateyouthInfoRequest;
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

        $yt = YouthUser::select('youthType as name', DB::raw('COUNT(*) as value'))
            ->whereIn('youthType', ['ISY', 'OSY'])
            ->groupBy('youthType')
            ->get();

        $sex = YouthInfo::select('sex as name', DB::raw('COUNT(*) as value'))
            ->whereIn('sex', ['Male', 'Female'])
            ->groupBy('sex')
            ->get();



        $gender = YouthInfo::select('gender', DB::raw('COUNT(*) as value'))
            ->where(function ($query) {
                $query->whereIn('gender', [
                    'Non-binary',
                    'Transgender',
                    'Agender',
                    'Bigender',
                    'Others'
                ])->orWhereNull('gender');
            })
            ->groupBy('gender')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->gender ?? 'Not-specified',
                    'value' => $item->value,
                ];
            });

        $ages =  YouthInfo::select('age', DB::raw('COUNT(*) as count'))
            ->whereIn('age', range(15, 30))
            ->groupBy('age')
            ->orderBy('age')
            ->get();

        $civilStats =  YouthInfo::select('civilStatus as name', DB::raw('COUNT(*) as value'))
            ->whereIn('civilStatus', [
                'Single',
                'Married',
                'Divorce',
                'Outside-marriage',
            ])
            ->groupBy('civilStatus')
            ->orderBy('civilStatus')
            ->get();

        $religions =  YouthInfo::select('religion as name', DB::raw('COUNT(*) as value'))
            ->whereIn('religion', [
                'Islam',
                'Christianity',
                'Judaism',
                'Buddhism',
                'Hinduism',
                'Atheism',
                'Others',
            ])
            ->groupBy('religion')
            ->orderBy('religion')
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


    public function destroy(YouthInfo $youthInfo)
    {
        //
    }
}
