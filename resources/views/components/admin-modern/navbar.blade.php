@php
    $adminId = session('cultivationAdmin');
    $user = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;
    $name = $user->adminName ?? 'Admin';
    $role = $user->adminType ?? 'User';
@endphp

<header class="am-topbar">
    <button type="button" class="am-icon-btn" data-am-sidebar-toggle aria-label="Toggle navigation">
        <span></span>
        <span></span>
        <span></span>
    </button>

    <div class="am-topbar-title-wrap">
        <p class="am-topbar-title">Education ERP</p>
        <p class="am-topbar-sub">Modern admin shell (Phase 1)</p>
    </div>

    <div class="am-topbar-actions">
        <button type="button" class="am-btn am-btn-ghost" data-am-theme-toggle>Theme</button>
        <a href="{{ route('adminProfile') }}" class="am-user-pill" title="Profile">
            <span class="am-user-name">{{ $name }}</span>
            <span class="am-user-role">{{ $role }}</span>
        </a>
    </div>
</header>
