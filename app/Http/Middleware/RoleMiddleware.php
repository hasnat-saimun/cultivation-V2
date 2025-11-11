<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\CultivationAdmin;
use Session;

class RoleMiddleware
{
    /**
     * Expected usage: ->middleware('role:teacher') or 'role:cash' or 'role:general'
     */
    public function handle(Request $request, Closure $next, string $requiredRole): Response
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

        $map = [
            'teacher' => CultivationAdmin::ROLE_TEACHER,
            'cash'    => CultivationAdmin::ROLE_CASH,
            'general' => CultivationAdmin::ROLE_GENERAL,
        ];
        $required = $map[$requiredRole] ?? null;

        if($required === null){
            abort(500,'Role middleware misconfigured');
        }

        // General admin can access everything (fall-through privilege)
        $userRole = (int)$user->userType;
        $isAllowed = false;
        if($userRole === CultivationAdmin::ROLE_GENERAL){
            $isAllowed = true; // full access
        } elseif ($userRole === $required){
            $isAllowed = true;
        } elseif ($requiredRole === 'cash' && $userRole === CultivationAdmin::ROLE_CASH){
            $isAllowed = true;
        }

        if(!$isAllowed){
            abort(403,'Unauthorized for this feature');
        }
        return $next($request);
    }
}
