<?php


use App\Http\Controllers\ValidationFormController;
use App\Http\Controllers\YouthUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('youth',YouthUserController::class);

Route::post('vals1', [ValidationFormController::class,'valStep1']);
Route::post('vals2', [ValidationFormController::class,'valStep2']);
Route::post('vals3', [ValidationFormController::class,'valStep3']);
Route::post('vals4', [ValidationFormController::class,'valStep4']);
Route::post('vals3b', [ValidationFormController::class,'valStep3b']);
Route::post('vals4b', [ValidationFormController::class,'valStep4b']);

