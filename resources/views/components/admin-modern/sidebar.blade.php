@php
    $groups = [
        [
            'label' => 'Main',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'adminModernDashboard'],
                ['label' => 'Users', 'route' => 'adminModernUsersIndex'],
                ['label' => 'Attendance', 'route' => 'attendanceIndex'],
                ['label' => 'Student List', 'route' => 'studentList'],
                ['label' => 'Teacher List', 'route' => 'teacherList'],
                ['label' => 'Staff List', 'route' => 'staffList'],
                ['label' => 'Configuration', 'route' => 'serverConfig'],
            ],
        ],
        [
            'label' => 'Academic',
            'items' => [
                ['label' => 'Class List', 'route' => 'adminModernAcademicClassesIndex'],
                ['label' => 'Department List', 'route' => 'adminModernAcademicDepartmentsIndex'],
                ['label' => 'Section List', 'route' => 'adminModernAcademicSectionsIndex'],
                ['label' => 'Session List', 'route' => 'adminModernAcademicSessionsIndex'],
                ['label' => 'Subject List', 'route' => 'adminModernAcademicSubjectsIndex'],
                ['label' => 'Grade List', 'route' => 'adminModernAcademicGradesIndex'],
            ],
        ],
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
        @foreach($groups as $group)
            <div class="am-nav-group-title">{{ $group['label'] }}</div>
            @foreach($group['items'] as $item)
                @php
                    $isActive = request()->routeIs($item['route']);
                    $href = route($item['route']);
                @endphp
                <a href="{{ $href }}" class="am-nav-link {{ $isActive ? 'is-active' : '' }}">
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="am-sidebar-footer">
        <a href="{{ route('cultivationIndex') }}" class="am-link-muted">Old Dashboard</a>
    </div>
</aside>
