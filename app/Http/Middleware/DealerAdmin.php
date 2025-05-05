<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DealerAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Session::has('superAdmin') || Session::has('moderator') || Session::has('dealerAdmin') || Session::has('adminGuard')):
            $x ="";
        else:
            return redirect(route('adminLogin'))->with('error','Please login to continue');
        endif;
        return $next($request);
    }
}
