<?php

namespace App\Http\Controllers;

use App\Models\Bulk_logger;
use App\Models\RegistrationCycle;
use App\Models\SkOfficial;
use App\Models\User;
use App\Models\YouthUser;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;

class BulkLoggerController extends Controller
{
    public function bulkGetBatchContent(Request $request)
    {
        //
        $cycleID = $this->getCycle();

        if (!$cycleID) {
            return response()->json(['error' => 'No active cycle.'], 400);
        }
        $page = $request->input('page', 1);
        $user_id = $request->input('user_id');
        User::findOrFail($user_id);

        Paginator::currentPageResolver(function () use ($page) {
            return $page;
        });
        $results = Bulk_logger::whereHas('yUser', function ($q) use ($cycleID) {
            $q->where('registration_cycle_id', $cycleID);
        })
            ->where('user_id', $user_id)->groupBy('batchNo')->paginate(10);
        return response()->json([
            'data' => $results->items(),
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
        $results = YouthUser::where('registration_cycle_id', $cycleID)
            ->where('batchNo', $batchNo)->with([
                'info',
            ])->paginate(10);
        $res = $results->items();
        $data = [];
        $full = [];
        foreach ($res as $d) {
            $data['fname'] = $d['info']['fname'];
            $data['mname'] = $d['info']['mname'];
            $data['lname'] = $d['info']['lname'];
            $data['batchNo'] = $d['batchNo'];
            $full[] = $data;
            $data = [];
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
