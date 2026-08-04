<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class BulkStudentUpdateDiagnostics
{
    public function handle(Request $request, Closure $next): Response
    {
        $incomingId = (string) $request->header('X-Request-ID', '');
        $requestId = preg_match('/^[A-Za-z0-9._-]{1,100}$/', $incomingId)
            ? $incomingId
            : (string) Str::uuid();
        $requestData = $request->request->all();
        $rawStudents = $requestData['students'] ?? null;
        $context = [
            'request_id' => $requestId,
            'route' => $request->route()?->getName(),
            'url' => $request->fullUrl(),
            'host' => $request->getHost(),
            'http_method' => $request->method(),
            'content_type' => $request->header('Content-Type'),
            'content_length_header' => $request->header('Content-Length'),
            'content_length_server' => $request->server('CONTENT_LENGTH'),
            'request_keys' => array_keys($requestData),
            'students_present' => $request->request->has('students'),
            'students_type' => get_debug_type($rawStudents),
            'students_bytes' => is_string($rawStudents) ? strlen($rawStudents) : null,
            'post_max_size' => ini_get('post_max_size'),
            'max_input_vars' => ini_get('max_input_vars'),
            'max_input_time' => ini_get('max_input_time'),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'php_ini_loaded_file' => php_ini_loaded_file() ?: null,
            'modsecurity_unique_id' => $request->server('UNIQUE_ID'),
            'user_agent' => Str::limit((string) $request->userAgent(), 300, ''),
        ];

        if ($context['students_present']) {
            Log::info('student_bulk_update_request_diagnostic', $context);
        } else {
            Log::warning('student_bulk_update_request_diagnostic', $context);
        }
        $request->attributes->set('bulk_update_request_id', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }
}
