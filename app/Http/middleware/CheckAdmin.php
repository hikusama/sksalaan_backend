<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || $user->role !== 'Admin') {
            return response()->json(['error' => 'role unauthorized'], 403);
        }

        return $next($request);
    }
}
