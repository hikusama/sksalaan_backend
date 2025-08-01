<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BulkLoggerController;
use App\Http\Controllers\ComposedAnnouncementController;
use App\Http\Controllers\JobPlacementController;
use App\Http\Controllers\RegistrationCycleController;
use App\Http\Controllers\Supabase;
use App\Http\Controllers\XportExcel;
use App\Http\Controllers\YouthDataExportController;
use App\Http\Controllers\YouthInfoController;
use App\Http\Controllers\YouthUserController;
use App\Http\Middleware\CheckAdmin;
use App\Http\Middleware\CheckCycleOpen;
use App\Models\RegistrationCycle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::middleware(['auth', CheckAdmin::class])->get('/web/user', function (Request $request) {
    $user = $request->user()->load('admin');
    return [
        'user' => $user,
    ];
});

Route::prefix('web')->middleware(['auth', CheckAdmin::class])->group(function () {
    Route::post('/valStep1Post', [ComposedAnnouncementController::class, 'valStep1Post']);
    Route::post('/valStep2Post', [ComposedAnnouncementController::class, 'valStep2Post']);
    Route::post('/compose', [ComposedAnnouncementController::class, 'compose']);
    Route::get('/dropDownCycle', [ComposedAnnouncementController::class, 'getAllCycle']);
    Route::get('/getSMSPending', [ComposedAnnouncementController::class, 'getSMSPending']);
    Route::get('/getSMSDelivered', [ComposedAnnouncementController::class, 'getSMSDelivered']);
    Route::get('/getWebPending', [ComposedAnnouncementController::class, 'getWebPending']);
    Route::get('/getWebDelivered', [ComposedAnnouncementController::class, 'getWebDelivered']);

    Route::middleware(CheckCycleOpen::class)->group(function () {
        Route::apiResource('/youth', YouthUserController::class);
        Route::put('/youthApprove', [YouthUserController::class, 'youthApprove']);
        Route::post('/search', [YouthUserController::class, 'searchName']);
        Route::apiResource('/supah', Supabase::class);
        Route::post('/youthLight', [JobPlacementController::class, 'youthLightData']);
        Route::post('/youthOnJobRecord', [JobPlacementController::class, 'searchJobPlacedYouth']);
        Route::post('/recruitYouth', [JobPlacementController::class, 'recruitYouth']);
        Route::post('/getInfoData', [YouthInfoController::class, 'getInfoData']);
        Route::put('/payYouth', [JobPlacementController::class, 'payYouth']);
        Route::delete('/deleteJobRecord/{jobPlacement}', [JobPlacementController::class, 'deleteJobRecord']);
        Route::post('/bulkGetUser', [BulkLoggerController::class, 'bulkGetUser']);
        Route::post('/bulkGetBatchContent', [BulkLoggerController::class, 'bulkGetBatchContent']);
        Route::post('/bulkGet', [BulkLoggerController::class, 'bulkGet']);
        Route::delete('/bulkDelete/{batchNo}', [BulkLoggerController::class, 'bulkDelete']);
        Route::post('/export-excel', [XportExcel::class, 'export']);
    });
    Route::post('/user/modify', [AuthController::class, 'modifyUser']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/searchSkOfficial', [AuthController::class, 'searchSkOfficial']);
    Route::delete('/deleteskaccount/{id}', [AuthController::class, 'destroy']);

    Route::post('/createCycle', [RegistrationCycleController::class, 'store']);
    Route::get('/getAllCycle', [RegistrationCycleController::class, 'show']);
    Route::put('/runCycle', [RegistrationCycleController::class, 'runCycle']);
    Route::delete('/deleteCycle/{id}', [RegistrationCycleController::class, 'deleteCycle']);
    Route::put('/stopCycle', [RegistrationCycleController::class, 'stopCycle']);

    Route::post('/logout-web', function (Request $request) {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json([
            'message' => 'Logged out'
        ]);
    });
});


Route::post('/web/registerYouth', [YouthUserController::class, 'registerYouth']);

Route::post('/web/loginAdmin', [AuthController::class, 'loginAdmin']);
