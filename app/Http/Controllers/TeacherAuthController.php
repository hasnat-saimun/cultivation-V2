<?php

namespace App\Http\Controllers;

use App\Models\CultivationAdmin;
use App\Services\TeacherAuthenticationDiagnostics;
use App\Services\TeacherDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class TeacherAuthController extends Controller
{
    private const FAILURE_MESSAGE = 'Unable to sign in with the provided credentials.';
    private const MAX_ATTEMPTS = 25;
    private const DECAY_SECONDS = 60;

    public function __construct(private TeacherAuthenticationDiagnostics $diagnostics)
    {
    }

    public function create(): View
    {
        return view('teacher.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $identifier = trim($validated['identifier']);
        $identifierHash = hash('sha256', mb_strtolower($identifier));
        $throttleKey = 'teacher-login:'.$identifierHash.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            Log::warning('Teacher login rate limited.', $this->diagnostics->context($request, [
                'identifier_hash' => $identifierHash,
                'authentication_result' => 'rate_limited',
                'redirect_result' => 'teacher.login',
                'retry_after_seconds' => RateLimiter::availableIn($throttleKey),
            ]));

            return back()
                ->withErrors(['identifier' => 'Too many sign-in attempts. Please try again shortly.'])
                ->onlyInput('identifier')
                ->setStatusCode(429);
        }

        $matches = CultivationAdmin::query()
            ->where('userType', CultivationAdmin::ROLE_TEACHER)
            ->where(function ($query) use ($identifier) {
                $query->where('adminUser', $identifier)
                    ->orWhere('adminMail', $identifier)
                    ->orWhere('adminMobile', $identifier);
            })
            ->limit(2)
            ->get();

        if ($matches->count() !== 1) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            Log::warning('Teacher login rejected.', $this->diagnostics->context($request, [
                'reason' => $matches->isEmpty() ? 'unknown_identifier' : 'ambiguous_identifier',
                'identifier_hash' => $identifierHash,
                'authentication_result' => 'rejected',
                'redirect_result' => 'teacher.login',
            ]));

            return back()->withErrors(['identifier' => self::FAILURE_MESSAGE])->onlyInput('identifier');
        }

        /** @var CultivationAdmin $teacher */
        $teacher = $matches->first();
        if (! $teacher->isTeacher() || ! Auth::guard('teacher')->getProvider()->validateCredentials($teacher, [
            'password' => $validated['password'],
        ])) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            Log::warning('Teacher login rejected.', $this->diagnostics->context($request, [
                'reason' => $teacher->isTeacher() ? 'invalid_credentials' : 'ineligible_account',
                'teacher_id' => $teacher->id,
                'identifier_hash' => $identifierHash,
                'authentication_result' => 'rejected',
                'redirect_result' => 'teacher.login',
            ]));

            return back()->withErrors(['identifier' => self::FAILURE_MESSAGE])->onlyInput('identifier');
        }

        Auth::guard('teacher')->login($teacher, false);
        $request->session()->regenerate();
        RateLimiter::clear($throttleKey);

        Log::info('Teacher login succeeded.', $this->diagnostics->context($request, [
            'teacher_id' => $teacher->id,
            'identifier_hash' => $identifierHash,
            'authentication_result' => 'succeeded',
            'redirect_result' => 'teacher.dashboard',
        ]));

        return redirect()->route('teacher.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $teacherId = Auth::guard('teacher')->id();
        $adminId = $request->session()->get('cultivationAdmin');

        Auth::guard('teacher')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($adminId !== null && $adminId !== '') {
            $request->session()->put('cultivationAdmin', $adminId);
        }

        Log::info('Teacher logout completed.', [
            'teacher_id' => $teacherId,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('teacher.login');
    }

    public function dashboard(TeacherDashboardService $dashboard): View
    {
        /** @var CultivationAdmin $teacher */
        $teacher = Auth::guard('teacher')->user();

        return view('teacher.dashboard', ['teacher' => $teacher] + $dashboard->build($teacher));
    }
}
