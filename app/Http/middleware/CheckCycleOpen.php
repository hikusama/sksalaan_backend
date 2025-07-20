<?php

namespace App\Http\Middleware;

use App\Models\RegistrationCycle;
use Closure;
use Illuminate\Http\Request;

class CheckCycleOpen
{
    public function handle(Request $request, Closure $next)
    {
        
        if ((RegistrationCycle::where('cycleStatus','active')->exists())) {
            return response()->json(['error' => 'No active cycle do create one!.'], 401);
        }

        return $next($request);
    }
}
