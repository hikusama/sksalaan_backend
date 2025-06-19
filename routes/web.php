<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Supabase;
use App\Http\Controllers\ValidationFormController;
use App\Http\Controllers\YouthUserController;
use App\Models\YouthUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->get('/user', function (Request $request) {
    return $request->user();
});

