<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {
    DB::table('personal_access_tokens')
        ->whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
    Log::info('[TEST] Scheduler ran at: ' . now());
})->daily();
