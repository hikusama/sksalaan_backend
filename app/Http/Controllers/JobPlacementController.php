<?php

namespace App\Http\Controllers;

use App\Models\YouthInfo;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class JobPlacementController extends Controller
{
    //

    public function searchJobPlacedYouth(Request $request)
    {
        $search = $request->input('q');
        $perPage = $request->input('perPage', 15);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sortBy', 'fname');
        $typeId = $request->input('typeId');

        $allowedFilters = ['fname', 'lname', 'age', 'created_at'];
        if (!in_array($sortBy, $allowedFilters)) {
            return response()->json(['error' => 'Invalid filter field'], 400);
        }

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $results = YouthInfo::where(function ($query) use ($search) {
            $query->where('fname', 'LIKE', '%' . $search . '%')
                ->orWhere('mname', 'LIKE', '%' . $search . '%')
                ->orWhere('lname', 'LIKE', '%' . $search . '%');
        })
            ->whereIn('youth_user_id', function ($query) {
                $query->select('youth_user_id')->from('job_supports');
            })
            ->orderBy($sortBy, 'ASC')
            ->with([
                'yUser',
                'yUser.job_supp',
            ])
            ->paginate($perPage)
            ->appends([
                'q' => $search,
                'typeId' => $typeId,
                'sortBy' => $sortBy,
                'perPage' => $perPage,
                'page' => $page
            ]);

        $pass = $results->map(function ($info) {
            return [
                'youthUser' => [
                    $info
                ]
            ];
        });

        return response()->json([
            'data' => $pass,
            'pagination' => [
                'current_page' => $results->currentPage(),
                'total_pages' => $results->lastPage(),
                'total_items' => $results->total(),
            ]
        ]);
    }



    public function youthLightData(Request $request)
    {
        $search = $request->input('q');
        $perPage = $request->input('perPage', 15);
        $page = $request->input('page', 1);
        $sortBy = $request->input('sortBy', 'fname');

        $allowedFilters = ['fname', 'lname', 'age', 'created_at'];
        if (!in_array($sortBy, $allowedFilters)) {
            return response()->json(['error' => 'Invalid filter field'], 400);
        }

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $results = YouthInfo::where(function ($query) use ($search) {
            $query->where('fname', 'LIKE', '%' . $search . '%')
                ->orWhere('mname', 'LIKE', '%' . $search . '%')
                ->orWhere('lname', 'LIKE', '%' . $search . '%');
        })
            ->orderBy($sortBy, 'ASC')
            ->with('yUser')
            ->addSelect([
                'job_supports_count' => DB::table('job_supports')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('job_supports.youth_user_id', 'youth_infos.youth_user_id')
            ])
            ->paginate($perPage)
            ->appends([
                'q' => $search,
                'sortBy' => $sortBy,
                'perPage' => $perPage,
                'page' => $page
            ]);

        $pass = $results->map(function ($info) {
            return [
                'youthUser' => [
                    $info
                ]
            ];
        });

        return response()->json([
            'data' => $pass,
            'pagination' => [
                'current_page' => $results->currentPage(),
                'total_pages' => $results->lastPage(),
                'total_items' => $results->total(),
            ]
        ]);
    }
}
