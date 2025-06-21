<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Supabase;
use App\Http\Controllers\ValidationFormController;
use App\Http\Controllers\YouthUserController;
use App\Models\YouthUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\CheckAdmin;

Route::middleware(['web', 'auth:sanctum', CheckAdmin::class])->get('/user', function (Request $request) {
    $user = $request->user()->load('admin'); 
    return [
        'user' => $user,
    ];
 });
Route::middleware(['web', 'auth:sanctum'])->get('/userAPI', function (Request $request) {
    $user = $request->user()->load('skofficials');  
    return [
        'user' => $user,
    ];
 });


Route::middleware(['web', 'auth:sanctum'])->group(function () {
    Route::post('vals1', [ValidationFormController::class, 'valStep1']);
    Route::post('vals2', [ValidationFormController::class, 'valStep2']);
    Route::post('vals3', [ValidationFormController::class, 'valStep3']);
    Route::post('vals4', [ValidationFormController::class, 'valStep4']);
    Route::post('vals3b', [ValidationFormController::class, 'valStep3b']);
    Route::post('vals4b', [ValidationFormController::class, 'valStep4b']);
});

Route::middleware(['web', 'auth:sanctum', CheckAdmin::class])->group(function () {
    Route::apiResource('/youth', YouthUserController::class);
    Route::put('/youthApprove', [YouthUserController::class, 'youthApprove']);
    Route::post('/search', [YouthUserController::class, 'searchName']);
    Route::apiResource('supah', Supabase::class);
    Route::post('/user/modify', [AuthController::class, 'modifyUser']);
    Route::delete('/deleteskaccount/{id}', [AuthController::class, 'destroy'])->middleware('auth:sanctum');;
    Route::post('/searchSkOfficial', [AuthController::class, 'searchSkOfficial']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::post('registerYouth', [YouthUserController::class, 'registerYouth']);

Route::post('/loginAdmin', [AuthController::class, 'loginAdmin']);
Route::post('/loginOfficials', [AuthController::class, 'loginOfficials']);
