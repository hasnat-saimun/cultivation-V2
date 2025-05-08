<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Session;

class BasicAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(Session::has('superAdmin') || Session::has('moderator') || Session::has('adminGuard') || Session::has('dealerAdmin') || Session::has('basicAdmin')):
            $x ="";
        else:
            return redirect(route('adminLogin'))->with('error','Please login to continue');
        endif;
        return $next($request);
    }
}
