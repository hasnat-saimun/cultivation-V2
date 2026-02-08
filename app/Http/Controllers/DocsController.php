<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\ServerConfig;

class DocsController extends Controller
{
    public function userGuide()
    {
        return $this->renderGuide(base_path('docs/user-guide.md'), 'User Guide');
    }

    public function userGuideGeneralAdmin()
    {
        return $this->renderGuide(base_path('docs/user-guide-general-admin.md'), 'User Guide - General Admin');
    }

    public function userGuideTeacherAdmin()
    {
        return $this->renderGuide(base_path('docs/user-guide-teacher-admin.md'), 'User Guide - Teacher Admin');
    }

    public function userGuideCashAdmin()
    {
        return $this->renderGuide(base_path('docs/user-guide-cash-admin.md'), 'User Guide - Cash Admin');
    }

    public function brochure()
    {
        // Serve a minimal brochure view without app sidebar/layout
        $version = config('app.version') ?: trim(@file_get_contents(base_path('VERSION')));
        $config = ServerConfig::query()->latest('id')->first();
        $brandColor = config('app.brand_primary') ?: '#0f62fe';
        return view('docs.brochure', [
            'title' => 'Client Offer',
            'version' => $version,
            'config' => $config,
            'brandColor' => $brandColor,
        ]);
    }

    public function brochurePrint()
    {
        $version = config('app.version') ?: trim(@file_get_contents(base_path('VERSION')));
        $config = ServerConfig::query()->latest('id')->first();
        $brandColor = config('app.brand_primary') ?: '#0f62fe';
        return view('docs.brochure-print', [
            'title' => 'Client Offer (Print)',
            'version' => $version,
            'config' => $config,
            'brandColor' => $brandColor,
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

    protected function renderGuide(string $path, string $title)
    {
        $exists = File::exists($path);
        $markdown = $exists ? File::get($path) : "# {$title}\nFile not found.";

        // Determine last updated timestamp & version
        $updatedAt = $exists ? Carbon::createFromTimestamp(File::lastModified($path)) : Carbon::now();
        $version = config('app.version') ?: $updatedAt->format('Y.m.d');

        // Server-side markdown conversion with graceful fallback
        $html = $this->convertMarkdown($markdown);

        return view('docs.user-guide', [
            'html' => $html,
            'raw' => $markdown,
            'title' => $title,
            'version' => $version,
            'updatedAt' => $updatedAt->format('Y-m-d H:i')
        ]);
    }
}
