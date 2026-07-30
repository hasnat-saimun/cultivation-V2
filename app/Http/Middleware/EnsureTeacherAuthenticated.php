<?php

namespace App\Http\Middleware;

use App\Models\CultivationAdmin;
use App\Services\TeacherAuthenticationDiagnostics;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureTeacherAuthenticated
{
    public function __construct(private TeacherAuthenticationDiagnostics $diagnostics)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $teacher = Auth::guard('teacher')->user();

        if (! $teacher instanceof CultivationAdmin || ! $teacher->isTeacher()) {
            if ($teacher !== null) {
                Log::warning('Teacher portal access rejected.', $this->diagnostics->context($request, [
                    'reason' => 'ineligible_account',
                    'teacher_id' => $teacher->getAuthIdentifier(),
                    'authentication_result' => 'middleware_rejected',
                    'redirect_result' => 'teacher.login',
                ]));
                Auth::guard('teacher')->logout();
                $request->session()->regenerate();
            } else {
                Log::notice('Teacher portal access rejected.', $this->diagnostics->context($request, [
                    'reason' => 'missing_teacher_session',
                    'authentication_result' => 'middleware_rejected',
                    'redirect_result' => 'teacher.login',
                ]));
            }

            return redirect()->route('teacher.login');
        }

        return $next($request);
    }
}
