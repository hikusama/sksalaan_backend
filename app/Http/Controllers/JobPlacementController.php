<?php

namespace App\Http\Controllers;

use App\Models\Job_support;
use App\Models\RegistrationCycle;
use App\Models\YouthInfo;
use App\Models\YouthUser;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class JobPlacementController extends Controller
{
    //

    public function searchJobPlacedYouth(Request $request)
    {
        $cycleID = $request->input('cID');

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }

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

        $results = DB::table('youth_users')
            ->rightJoin('job_supports', 'youth_users.id', '=', 'job_supports.youth_user_id')
            ->join('youth_infos', 'youth_infos.youth_user_id', '=', 'youth_users.id')
            ->where(function ($query) use ($search) {
                $query->where('youth_infos.fname', 'LIKE', '%' . $search . '%')
                    ->orWhere('youth_infos.mname', 'LIKE', '%' . $search . '%')
                    ->orWhere('youth_infos.lname', 'LIKE', '%' . $search . '%');
            })->when($cycleID !== 'all', function ($qq) use ($cycleID) {
                $qq->join('validated_youths', 'validated_youths.youth_user_id', '=', 'youth_users.id')
                    ->where('validated_youths.registration_cycle_id', $cycleID);
            })
            ->groupBy('job_supports.id')
            ->orderBy("youth_infos.$sortBy", 'DESC')
            ->select(
                'youth_infos.youth_user_id',
                'youth_infos.fname',
                'youth_infos.mname',
                'youth_infos.dateOfBirth',
                'youth_infos.lname',
                'youth_infos.address',
                'job_supports.created_at',

                'youth_users.skills',
                'job_supports.id',
                'youth_users.youthType',
                'job_supports.task',
                'job_supports.amountToPay',
                'job_supports.paid_at',
                'job_supports.location',
                'job_supports.start',
                'job_supports.end',
            )
            ->paginate($perPage)
            ->appends([
                'q' => $search,
                'sortBy' => $sortBy,
                'perPage' => $perPage,
                'page' => $page
            ]);

        return response()->json([
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'total_pages' => $results->lastPage(),
                'total_items' => $results->total(),
            ]
        ]);
    }



    public function recruitYouth(Request $request)
    {

        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }
        $recruited = $request->validate([
            'youth_user_id' => 'required',
            'task' => 'required|max:100',
            'amountToPay' => 'required|integer|between:100,10000',
            'location' => 'required|max:100',
            'start' => 'required|date_format:Y-m-d|before_or_equal:end',
            'end' => 'required|date_format:Y-m-d|after_or_equal:start',
        ]);

        if ($request->input('paid_at') == 'yes') {
            $recruited['paid_at'] =  now();
        } else {
            $recruited['paid_at'] =  NULL;
        }

        Job_support::create($recruited);

        return response()->json([
            'data' => $recruited,
        ]);
    }
    public function youthLightData(Request $request)
    {
        $cycleID = $request->input('cID');

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }
        $search = $request->input('q');
        $perPage = $request->input('perPage', 15);
        $page = $request->input('page', 1);
        $typeId = $request->input('typeId');


        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        $results = YouthInfo::whereHas('yUser', function ($q) use ($cycleID, $search) {
            $q->when($search, function ($q) use ($search) {
                $q->where('skills', 'LIKE', '%' . $search . '%');
            });
        })->when($cycleID !== 'all', function ($qq) use ($cycleID) {
            $qq->whereHas('yUser.validated', function ($q) use ($cycleID) {
                $q->where('registration_cycle_id', $cycleID);
            });
        })
            ->with('yUser')
            ->addSelect([
                'job_supports_count' => DB::table('job_supports')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('job_supports.youth_user_id', 'youth_infos.youth_user_id')
            ])->when(!is_null($typeId), function ($query) use ($typeId) {
                $linked = filter_var($typeId, FILTER_VALIDATE_BOOLEAN);
                return $linked
                    ? $query->whereHas('yUser', function ($q) {
                        $q->whereNotNull('user_id');
                    })
                    : $query->whereHas('yUser', function ($q) {
                        $q->whereNull('user_id');
                    });
            })
            ->paginate($perPage)
            ->appends([
                'q' => $search,
                'perPage' => $perPage,
                'page' => $page
            ]);

        $pass = $results->map(function ($info) {
            return [
                'id' => $info->id,
                'youth_user_id' => $info->youth_user_id,
                'fname' => $info->fname,
                'mname' => $info->mname,
                'lname' => $info->lname,
                'jobCount' => $info->job_supports_count,
                'created_at' => $info->created_at,
                'user' => $info->yUser,
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

    public function deleteJobRecord($jobPlacement)
    {
        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }
        $jobPlacement = Job_support::findOrFail($jobPlacement);

        Job_support::destroy($jobPlacement->id);
        return response()->json([
            'message' => "Deleted successfully..."
        ]);
    }

    public function payYouth(Request $request)
    {
        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }
        $job = Job_support::findOrFail($request->input('id'));
        $request->validate([
            'date' => 'required:date_format:Y-m-d|before_or_equal:today',
        ]);
        $job->paid_at =  $request->input('date');
        $job->save();
        return '';
    }

    public function getCycle()
    {
        $res = RegistrationCycle::where('cycleStatus', 'active')->first();
        return $res->id ?? 0;
    }
}
