<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CultivationAdmin;
use Session;

class Roles
{
    /**
     * Usage: ->middleware('roles:1,3') where numbers are userType values, or use constants if desired.
     */
    public function handle(Request $request, Closure $next, ...$allowed): Response
    {
        $adminId = Session::get('cultivationAdmin');
        if(!$adminId){
            return redirect()->route('adminLogin')->with('error','Please login to continue');
        }
        $user = CultivationAdmin::find($adminId);
        if(!$user){
            Session::forget('cultivationAdmin');
            return redirect()->route('adminLogin')->with('error','Invalid session. Please login again');
        }

        $userType = (int)($user->userType ?? 0);
        if ($userType >= CultivationAdmin::ROLE_GENERAL) {
            // General or higher admin has full access
            return $next($request);
        }

        // Normalize allowed list to ints
        $allowedTypes = array_map(function($v){ return (int)trim((string)$v); }, $allowed);

        if (in_array($userType, $allowedTypes, true)) {
            return $next($request);
        }

        abort(403, 'Unauthorized for this feature');
    }
}
