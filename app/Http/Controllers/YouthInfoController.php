<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreyouthInfoRequest;
use App\Http\Requests\UpdateyouthInfoRequest;
use App\Models\YouthInfo;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;

class YouthInfoController extends Controller
{
    /**
     * Display a listing of the resource.   
     */
    public function getInfoData()
    {

        $sex = YouthInfo::select('sex', DB::raw('COUNT(*) as count'))
            ->whereIn('sex', ['Male', 'Female'])
            ->groupBy('sex')
            ->get();


        $gender = YouthInfo::select('gender', DB::raw('COUNT(*) as count'))
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
            ->get();

        $ages =  YouthInfo::select('age', DB::raw('COUNT(*) as count'))
            ->whereIn('age', [range(15, 30)])
            ->groupBy('age')
            ->orderBy('age')
            ->get();

        $civilstats =  YouthInfo::select('civilcivilStatus', DB::raw('COUNT(*) as count'))
            ->whereIn('civilcivilStatus', [
                'Single',
                'Married',
                'Divorce',
                'Outside-marriage',
            ])
            ->groupBy('civilStatus')
            ->orderBy('civilStatus')
            ->get();

        $religions =  YouthInfo::select('religion', DB::raw('COUNT(*) as count'))
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

            'ages' => $ages
        ]);
    }


    public function destroy(YouthInfo $youthInfo)
    {
        //
    }
}
