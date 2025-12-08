<?php

namespace App\Http\Controllers;

use App\Models\Bulk_logger;
use App\Models\RegistrationCycle;
use App\Models\SkOfficial;
use App\Models\User;
use App\Models\YouthUser;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class BulkLoggerController extends Controller
{
    public function bulkGetBatchContent(Request $request)
    {
        $cycleID = $request->input('cID', 'all');

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }

        $page = $request->input('page', 1);
        $user_id = $request->input('user_id');
        User::findOrFail($user_id);

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        // Select batchNo and first id per batch to satisfy MySQL
        $query = Bulk_logger::select('batchNo', DB::raw('MIN(id) as id'))
            ->where('user_id', $user_id)
            ->when($cycleID != 'all', function ($q) use ($cycleID) {
                $q->whereHas('user.youthUser.validated', function ($qq) use ($cycleID) {
                    $qq->where('registration_cycle_id', $cycleID);
                });
            })
            ->groupBy('batchNo');

        $results = $query->paginate(10);

        // Fetch the full records for these IDs
        $fullRecords = Bulk_logger::whereIn(
            'id',
            $results->pluck('id')->toArray()
        )->get();

        return response()->json([
            'data' => $fullRecords, // full records like select *
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_pages' => $results->lastPage(),
            ]
        ]);
    }


    public function bulkGetUser(Request $request)
    {
        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }
        $page = $request->input('page', 1);
        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $results = SkOfficial::paginate(10);
        return response()->json([
            'data' => $results->items(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_pages' => $results->lastPage(),
            ]
        ]);
    }


    public function bulkDelete(Request $request, $batchNo)
    {

        $bulkData = Bulk_logger::where('batchNo', $batchNo)->firstOrFail();
        Bulk_logger::destroy($bulkData->id);
        return response()->json([
            'message' => 'success'
        ], 200);
    }
    public function bulkGet(Request $request)
    {
        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }

        $page = $request->input('page', 1);
        $batchNo = $request->input('batchNo');

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });

        // Filter YouthUser through the validated relationship
        $results = YouthUser::whereHas('validated', function ($q) use ($cycleID) {
            $q->where('registration_cycle_id', $cycleID);
        })
            ->where('batchNo', $batchNo)
            ->with('info')
            ->paginate(10);

        $res = $results->items();
        $full = [];

        foreach ($res as $d) {
            $data = [
                'fname' => $d->info->fname,
                'mname' => $d->info->mname,
                'lname' => $d->info->lname,
                'batchNo' => $d->batchNo,
            ];
            $full[] = $data;
        }

        return response()->json([
            'data' => $full,
            'pagination' => [
                'current_page' => $results->currentPage(),
                'last_pages' => $results->lastPage(),
            ]
        ]);
    }

    public function getCycle()
    {
        $res = RegistrationCycle::where('cycleStatus', 'active')->first();
        return $res->id ?? 0;
    }
}
