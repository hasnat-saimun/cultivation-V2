<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DocsController extends Controller
{
    public function userGuide()
    {
        $path = base_path('docs/user-guide.md');
        $exists = File::exists($path);
        $markdown = $exists ? File::get($path) : '# User Guide\nFile not found.';

        // Determine last updated timestamp & version
        $updatedAt = $exists ? Carbon::createFromTimestamp(File::lastModified($path)) : Carbon::now();
        $version = $updatedAt->format('Y.m.d');

        // Server-side markdown conversion with graceful fallback
        $html = $this->convertMarkdown($markdown);

        return view('docs.user-guide', [
            'html' => $html,
            'raw' => $markdown,
            'title' => 'User Guide',
            'version' => $version,
            'updatedAt' => $updatedAt->format('Y-m-d H:i')
        ]);
    }

    protected function convertMarkdown(string $markdown): string
    {
        try {
            if (class_exists(\League\CommonMark\GithubFlavoredMarkdownConverter::class)) {
                $converter = new \League\CommonMark\GithubFlavoredMarkdownConverter();
                return $converter->convert($markdown)->getContent();
            }
            if (class_exists(\League\CommonMark\CommonMarkConverter::class)) {
                $converter = new \League\CommonMark\CommonMarkConverter();
                return $converter->convertToHtml($markdown);
            }
        } catch (\Throwable $e) {
            // Fallback below
        }
        // Basic fallback: escape then minimal formatting for headings/inline code
        $escaped = e($markdown);
        $escaped = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $escaped);
        $escaped = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $escaped);
        $escaped = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $escaped);
        $escaped = preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped);
        $paragraphs = collect(preg_split('/\n\n+/', $escaped))->map(fn($p) => Str::startsWith($p, '<h') ? $p : '<p>'.$p.'</p>')->implode("\n");
        return $paragraphs;
    }
}
