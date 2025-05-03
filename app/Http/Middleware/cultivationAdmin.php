<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class cultivationAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if(session()->has('cultivationAdmin')):
            return $next($request);
        else:
            return redirect(route('adminLogin'))->with('error','Please login first to continue');
        endif;
    }
}
