<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CashAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // userType 3 = General Admin, userType 4+ = Super/Other Admin
        if (Auth::check() && (Auth::user()->userType == 3 || Auth::user()->userType > 3)) {
            return $next($request);
        }
        abort(403, 'Unauthorized');
    }
}
