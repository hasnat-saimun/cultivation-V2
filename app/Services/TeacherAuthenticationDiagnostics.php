<?php

namespace App\Services;

use Illuminate\Http\Request;

final class TeacherAuthenticationDiagnostics
{
    public function context(Request $request, array $context = []): array
    {
        $sessionId = $request->hasSession() ? (string) $request->session()->getId() : '';
        $cookieName = (string) config('session.cookie');

        return array_merge([
            'route' => (string) ($request->route()?->getName() ?? 'teacher.login'),
            'guard' => 'teacher',
            'host' => $request->getHost(),
            'scheme' => $request->getScheme(),
            'session_id_hash' => $sessionId === '' ? null : hash('sha256', $sessionId),
            'session_cookie_present' => $cookieName !== '' && $request->cookies->has($cookieName),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ], $context);
    }
}
