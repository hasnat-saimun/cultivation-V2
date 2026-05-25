@php
    $items = [
        ['label' => 'Dashboard', 'route' => 'adminModernDashboard'],
        ['label' => 'Users', 'route' => 'adminModernUsersIndex'],
        ['label' => 'Attendance', 'route' => 'attendanceIndex'],
        ['label' => 'Student List', 'route' => 'studentList'],
        ['label' => 'Teacher List', 'route' => 'teacherList'],
        ['label' => 'Staff List', 'route' => 'staffList'],
        ['label' => 'Configuration', 'route' => 'serverConfig'],
    ];
@endphp

<aside class="am-sidebar" id="adminModernSidebar" aria-label="Primary">
    <div class="am-brand">
        <span class="am-brand-mark">C2</span>
        <div>
            <div class="am-brand-title">Cultivation V2</div>
            <div class="am-brand-sub">Admin Modern</div>
        </div>
    </div>

    <nav class="am-nav">
        @foreach($items as $item)
            @php
                $isActive = request()->routeIs($item['route']);
                $href = route($item['route']);
            @endphp
            <a href="{{ $href }}" class="am-nav-link {{ $isActive ? 'is-active' : '' }}">
                <span>{{ $item['label'] }}</span>
            </a>
        @endforeach
    </nav>

    <div class="am-sidebar-footer">
        <a href="{{ route('cultivationIndex') }}" class="am-link-muted">Old Dashboard</a>
    </div>
</aside>
