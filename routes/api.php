<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulkLoggerController;
use App\Http\Controllers\ComposedAnnouncementController;
use App\Http\Controllers\ValidationFormController;
use App\Http\Controllers\XportExcel;
use App\Http\Controllers\YouthUserController;
use App\Http\Middleware\CheckCycleOpen;
use App\Models\RegistrationCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth:sanctum'])->get('/userAPI', function (Request $request) {
    $token = $request->user()->currentAccessToken();
    $res = RegistrationCycle::where('cycleStatus', 'active')->first();
    $cycleID = $res->id ?? null;
    try {
        //code...

        if (!$cycleID) {
            $request->user()->tokens()->delete();
            return response()->json(['auth' => 'No active cycle.'], 400);
        }
        if ($token && $token->expires_at && now()->greaterThan($token->expires_at)) {
            $request->user()->tokens()->delete();
            return response()->json(['message' => 'Token expired'], 401);
        }
        $user = $request->user()->load('skofficials');
    } catch (\Throwable $th) {
        Log::info("4545:" . $th->getMessage());
    }
    return [
        'user' => $user,
    ];
});

Route::get('/getMessagePending', [ComposedAnnouncementController::class, 'getMessagePending']);
Route::get('/getMessageDelivered', [ComposedAnnouncementController::class, 'getMessageDelivered']);
Route::middleware(CheckCycleOpen::class)->group(function () {

    Route::post('/vals1', [ValidationFormController::class, 'valStep1']);
    Route::post('/vals2', [ValidationFormController::class, 'valStep2']);
    Route::post('/vals3', [ValidationFormController::class, 'valStep3']);
    Route::post('/vals4', [ValidationFormController::class, 'valStep4']);
    Route::post('/vals3b', [ValidationFormController::class, 'valStep3b']);
    Route::post('/vals4b', [ValidationFormController::class, 'valStep4b']);
    Route::middleware(['auth:sanctum'])->post('/migrate', [YouthUserController::class, 'migrateFromMobile']);
});



Route::post('/loginOfficials', [AuthController::class, 'loginOfficials']);
