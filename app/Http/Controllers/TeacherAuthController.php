<?php

namespace App\Http\Controllers;

use App\Models\CultivationAdmin;
use App\Services\TeacherAuthenticationDiagnostics;
use App\Services\TeacherDashboardService;
use App\Services\TeacherIdentifierNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class TeacherAuthController extends Controller
{
    private const FAILURE_MESSAGE = 'Unable to sign in with the provided credentials.';
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 60;

    public function __construct(
        private TeacherAuthenticationDiagnostics $diagnostics,
        private TeacherIdentifierNormalizer $identifiers,
    ) {}

    public function create(TeacherDashboardService $dashboard): View
    {
        return view('teacher.auth.login', [
            'instituteName' => $dashboard->instituteName(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $normalizedIdentifier = $this->identifiers->normalize((string) $request->input('identifier', ''));
        $validator = Validator::make([
            'identifier' => $normalizedIdentifier,
            'password' => $request->input('password'),
        ], [
            'identifier' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            Log::warning('Teacher login validation failed.', $this->diagnostics->context($request, [
                'validation_passed' => false,
                'normalized_identifier' => $normalizedIdentifier,
                'identifier_character_length' => mb_strlen($normalizedIdentifier),
                'identifier_byte_length' => strlen($normalizedIdentifier),
                'authentication_result' => 'validation_failed',
                'redirect_result' => 'teacher.login',
            ]));

            return back()->withErrors($validator)->withInput($request->except('password'));
        }

        $validated = $validator->validated();
        $identifier = $validated['identifier'];
        $identifierHash = hash('sha256', mb_strtolower($identifier));
        $throttleKey = 'teacher-login:'.$identifierHash.'|'.$request->ip();
        $trace = [
            'validation_passed' => true,
            'normalized_identifier' => $identifier,
            'identifier_character_length' => mb_strlen($identifier),
            'identifier_byte_length' => strlen($identifier),
            'identifier_hash' => $identifierHash,
        ];

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            Log::warning('Teacher login rate limited.', $this->diagnostics->context($request, $trace + [
                'authentication_result' => 'rate_limited',
                'redirect_result' => 'teacher.login',
                'retry_after_seconds' => RateLimiter::availableIn($throttleKey),
            ]));

            return back()
                ->withErrors(['identifier' => 'Too many sign-in attempts. Please try again shortly.'])
                ->onlyInput('identifier')
                ->setStatusCode(429);
        }

        [$matches, $matchedField] = $this->findTeachers($identifier);

        if ($matches->count() !== 1) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            Log::warning('Teacher login rejected.', $this->diagnostics->context($request, $trace + [
                'reason' => $matches->isEmpty() ? 'unknown_identifier' : 'ambiguous_identifier',
                'teacher_record_found' => ! $matches->isEmpty(),
                'matched_field' => $matchedField,
                'teacher_eligibility_passed' => null,
                'password_check_passed' => null,
                'guard_login_passed' => false,
                'authentication_result' => 'rejected',
                'redirect_result' => 'teacher.login',
            ]));

            return back()->withErrors(['identifier' => self::FAILURE_MESSAGE])->onlyInput('identifier');
        }

        /** @var CultivationAdmin $teacher */
        $teacher = $matches->first();
        $eligible = $teacher->isTeacher();
        $passwordPassed = $eligible && Hash::check($validated['password'], $teacher->getAuthPassword());

        if (! $eligible || ! $passwordPassed) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);
            Log::warning('Teacher login rejected.', $this->diagnostics->context($request, $trace + [
                'reason' => $eligible ? 'invalid_credentials' : 'ineligible_account',
                'teacher_id' => $teacher->id,
                'teacher_record_found' => true,
                'matched_field' => $matchedField,
                'teacher_eligibility_passed' => $eligible,
                'password_check_passed' => $passwordPassed,
                'guard_login_passed' => false,
                'authentication_result' => 'rejected',
                'redirect_result' => 'teacher.login',
            ]));

            return back()->withErrors(['identifier' => self::FAILURE_MESSAGE])->onlyInput('identifier');
        }

        Auth::guard('teacher')->login($teacher, false);
        $request->session()->regenerate();
        $guardLoginPassed = Auth::guard('teacher')->check()
            && (string) Auth::guard('teacher')->id() === (string) $teacher->getAuthIdentifier();
        RateLimiter::clear($throttleKey);

        Log::info('Teacher login succeeded.', $this->diagnostics->context($request, $trace + [
            'teacher_id' => $teacher->id,
            'teacher_record_found' => true,
            'matched_field' => $matchedField,
            'teacher_eligibility_passed' => true,
            'password_check_passed' => true,
            'guard_login_passed' => $guardLoginPassed,
            'authentication_result' => 'succeeded',
            'redirect_result' => 'teacher.dashboard',
        ]));

        return redirect()->route('teacher.dashboard');
    }

    private function findTeachers(string $identifier): array
    {
        if ($this->identifiers->isEmail($identifier)) {
            return [
                CultivationAdmin::query()
                    ->whereRaw('LOWER(adminMail) = ?', [mb_strtolower($identifier)])
                    ->limit(2)
                    ->get(),
                'email',
            ];
        }

        if (($mobile = $this->identifiers->canonicalMobile($identifier)) !== null) {
            return [
                CultivationAdmin::query()
                    ->whereIn('adminMobile', $this->identifiers->mobileLookupValues($mobile))
                    ->limit(2)
                    ->get(),
                'mobile',
            ];
        }

        return [
            CultivationAdmin::query()
                ->whereRaw('LOWER(adminUser) = ?', [mb_strtolower($identifier)])
                ->limit(2)
                ->get(),
            'teacherId',
        ];
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
