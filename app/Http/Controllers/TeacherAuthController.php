<?php

namespace App\Http\Controllers;

use App\Models\CultivationAdmin;
use App\Services\TeacherDashboardService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class TeacherAuthController extends Controller
{
    private const FAILURE_MESSAGE = 'Unable to sign in with the provided credentials.';

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
            Log::warning('Teacher login rejected.', [
                'reason' => $matches->isEmpty() ? 'unknown_identifier' : 'ambiguous_identifier',
                'identifier_hash' => hash('sha256', mb_strtolower($identifier)),
                'ip' => $request->ip(),
            ]);

            return back()->withErrors(['identifier' => self::FAILURE_MESSAGE])->onlyInput('identifier');
        }

        /** @var CultivationAdmin $teacher */
        $teacher = $matches->first();
        if (! $teacher->isTeacher() || ! Auth::guard('teacher')->getProvider()->validateCredentials($teacher, [
            'password' => $validated['password'],
        ])) {
            Log::warning('Teacher login rejected.', [
                'reason' => $teacher->isTeacher() ? 'invalid_credentials' : 'ineligible_account',
                'teacher_id' => $teacher->id,
                'ip' => $request->ip(),
            ]);

            return back()->withErrors(['identifier' => self::FAILURE_MESSAGE])->onlyInput('identifier');
        }

        Auth::guard('teacher')->login($teacher, false);
        $request->session()->regenerate();

        Log::info('Teacher login succeeded.', [
            'teacher_id' => $teacher->id,
            'ip' => $request->ip(),
        ]);

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
