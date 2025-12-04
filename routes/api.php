<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulkLoggerController;
use App\Http\Controllers\ComposedAnnouncementController;
use App\Http\Controllers\SyncHubController;
use App\Http\Controllers\ValidationFormController;
use App\Http\Controllers\XportExcel;
use App\Http\Controllers\YouthInfoController;
use App\Http\Controllers\YouthUserController;
use App\Http\Middleware\CheckCycleOpen;
use App\Models\RegistrationCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;



Route::middleware(['auth:sanctum'])->get('/userAPI', function (Request $request) {
    $token = $request->user()->currentAccessToken();

    $curMonth = date('n');
    $curYear  = date('Y');
    $activeCycle = RegistrationCycle::where('cycleStatus', 'active')->first();

    if (!$activeCycle) {
        RegistrationCycle::where('cycleStatus', 'active')->update(['cycleStatus' => 'inactive']);

        $cycleName = ($curMonth <= 6)
            ? "cycle_1_{$curYear}"
            : "cycle_2_{$curYear}";

        $activeCycle = RegistrationCycle::firstOrCreate(
            ['cycleName' => $cycleName],
            ['cycleStatus' => 'active']
        );

        if ($activeCycle->cycleStatus !== 'active') {
            $activeCycle->update(['cycleStatus' => 'active']);
        }
    }

    try {
        if (!$activeCycle) {
            $request->user()->tokens()->delete();
            return response()->json(['auth' => 'No active cycle.'], 400);
        }

        if ($token && $token->expires_at && now()->greaterThan($token->expires_at)) {
            $request->user()->tokens()->delete();
            return response()->json(['message' => 'Token expired'], 401);
        }

        $user = $request->user()->load('skofficials');
        $expires_at = $token->expires_at;
    } catch (\Throwable $th) {
        return response()->json(['error' => 'Server error'], 500);
    }

    return [
        'expires_at'  => $expires_at,
        'user'  => $user,
        'cycle' => $activeCycle,
    ];
});


Route::middleware(CheckCycleOpen::class)->group(function () {
    Route::post('/getMapData', [YouthInfoController::class, 'getMapData']);
    Route::get('/getOpenHub', [SyncHubController::class, 'getOpenHub']);
    Route::post('/getDataFromHub', [SyncHubController::class, 'getDataFromHub']);
    Route::post('/vals1', [ValidationFormController::class, 'valStep1']);
    Route::post('/vals2', [ValidationFormController::class, 'valStep2']);
    Route::post('/vals3', [ValidationFormController::class, 'valStep3']);
    Route::post('/vals4', [ValidationFormController::class, 'valStep4']);
    Route::post('/vals3b', [ValidationFormController::class, 'valStep3b']);
    Route::post('/vals4b', [ValidationFormController::class, 'valStep4b']);
    Route::middleware(['auth:sanctum'])->post('/migrate', [YouthUserController::class, 'migrateFromMobile']);
    Route::middleware(['auth:sanctum'])->post('/validate', [YouthUserController::class, 'validateFromMobile']);
});

Route::middleware(['auth:sanctum'])->get('/logoutOfficials', function (Request $request) {
    $request->user()->tokens()->delete();
    return response()->json([
        'message' => 'Logged out'
    ]);
});

Route::post('/loginOfficials', [AuthController::class, 'loginOfficials']);
