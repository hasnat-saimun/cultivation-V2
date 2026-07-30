<?php

namespace App\Services;

use App\Models\ServerConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherLoginBrandingService
{
    /** @return array{instituteName:string,instituteLogoUrl:string,academicSession:?string} */
    public function resolve(): array
    {
        $config = Schema::hasTable('server_configs')
            ? ServerConfig::query()->latest('id')->first()
            : null;

        return [
            'instituteName' => filled($config?->instituteName)
                ? (string) $config->instituteName
                : 'Cultivation',
            'instituteLogoUrl' => $this->logoUrl($config?->logo),
            'academicSession' => $this->academicSession(),
        ];
    }

    private function logoUrl(?string $logo): string
    {
        if (filled($logo)) {
            if (preg_match('~^https?://~i', $logo) === 1) {
                return $logo;
            }

            return asset(
                trim((string) config('branding.institute_logo_directory'), '/')
                .'/'.ltrim($logo, '/')
            );
        }

        return asset((string) config('branding.cultivation_logo'));
    }

    private function academicSession(): ?string
    {
        if (Schema::hasTable('academic_sessions')) {
            $current = DB::table('academic_sessions')
                ->where('is_current', true)
                ->where('status', 'active')
                ->latest('id')
                ->value('name');

            if (filled($current)) {
                return (string) $current;
            }
        }

        if (Schema::hasTable('sessions_years')) {
            $active = DB::table('sessions_years')
                ->where('is_active', true)
                ->latest('id')
                ->value('name');

            if (filled($active)) {
                return (string) $active;
            }
        }

        return null;
    }
}
