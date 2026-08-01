<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PublicAssetResponseService
{
    private const MIME_TYPES = [
        'css' => 'text/css; charset=UTF-8',
        'js' => 'application/javascript; charset=UTF-8',
        'json' => 'application/json; charset=UTF-8',
        'map' => 'application/json; charset=UTF-8',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'avif' => 'image/avif',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'otf' => 'font/otf',
        'eot' => 'application/vnd.ms-fontobject',
    ];

    public function serve(string $root, string $path): BinaryFileResponse
    {
        $path = $this->validatedPath($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if (! array_key_exists($extension, self::MIME_TYPES)) {
            abort(404);
        }

        $rootPath = realpath($root);
        $filePath = $rootPath === false ? false : realpath($rootPath.DIRECTORY_SEPARATOR.$path);

        if ($rootPath === false || $filePath === false || ! is_file($filePath)) {
            abort(404);
        }

        $rootPrefix = rtrim($rootPath, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        if (! str_starts_with(strtolower($filePath), strtolower($rootPrefix))) {
            abort(404);
        }

        return response()->file($filePath, [
            'Content-Type' => self::MIME_TYPES[$extension],
            'Cache-Control' => 'public, max-age=3600',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function validatedPath(string $path): string
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            $decoded = rawurldecode($path);
            if ($decoded === $path) {
                break;
            }
            $path = $decoded;
        }

        if ($path === ''
            || str_contains($path, "\0")
            || str_contains($path, '\\')
            || str_starts_with($path, '/')
            || preg_match('/^[A-Za-z]:/', $path) === 1
            || preg_match('~^[a-z][a-z0-9+.-]*://~i', $path) === 1) {
            abort(404);
        }

        $segments = explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..' || str_starts_with($segment, '.')) {
                abort(404);
            }
        }

        return implode(DIRECTORY_SEPARATOR, $segments);
    }
}
