<?php

namespace App\Services;

use App\Models\CultivationAdmin;
use Illuminate\Support\Facades\Auth;

class CultivationAdminResolver
{
    private ?CultivationAdmin $cachedAdmin = null;
    private bool $resolved = false;

    public function currentSessionAdminId(): ?int
    {
        $raw = session('cultivationAdmin');
        if (($raw === null || $raw === '') && Auth::guard('teacher')->check()) {
            $teacher = Auth::guard('teacher')->user();

            return $teacher instanceof CultivationAdmin && $teacher->isTeacher()
                ? (int) $teacher->getAuthIdentifier()
                : null;
        }

        if ($raw === null || $raw === '') {
            return null;
        }

        $id = (int) $raw;
        return $id > 0 ? $id : null;
    }

    public function current(): ?CultivationAdmin
    {
        if ($this->resolved) {
            return $this->cachedAdmin;
        }

        $this->resolved = true;
        $adminId = $this->currentSessionAdminId();
        if (!$adminId) {
            $this->cachedAdmin = null;
            return null;
        }

        $this->cachedAdmin = CultivationAdmin::find($adminId);
        return $this->cachedAdmin;
    }
}
