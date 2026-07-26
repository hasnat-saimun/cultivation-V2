<?php

namespace App\Http\Middleware;

use App\Models\CultivationAdmin;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherAuthenticated
{
    public function handle(Request $request, Closure $next): Response
    {
        $teacher = Auth::guard('teacher')->user();

        if (! $teacher instanceof CultivationAdmin || ! $teacher->isTeacher()) {
            if ($teacher !== null) {
                Log::warning('Teacher portal access rejected for ineligible account.', [
                    'teacher_id' => $teacher->getAuthIdentifier(),
                    'ip' => $request->ip(),
                ]);
                Auth::guard('teacher')->logout();
                $request->session()->regenerate();
            }

            return redirect()->route('teacher.login');
        }

        return $next($request);
    }
}
