@php
    // Derive the active-group prefix from a route name.
    // Strips the trailing 'Index' suffix so that related create/edit routes
    // (which share the same root name) also highlight the parent nav item.
    // e.g. 'adminModernUsersIndex' -> 'adminModernUsers'
    //       str_starts_with('adminModernUsersCreate', 'adminModernUsers') = true
    $amNavPrefix = fn(string $r): string =>
        str_ends_with($r, 'Index') ? substr($r, 0, -5) : $r;

    $currentRouteName = request()->route()?->getName() ?? '';

    $navIcons = [
    'dashboard' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 13h6V4H4z"></path><path d="M14 20h6v-7h-6z"></path><path d="M14 4h6v4h-6z"></path><path d="M4 20h6v-4H4z"></path></svg>
SVG,
    'users' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-1.5a4.5 4.5 0 0 0-9 0V21"></path><path d="M12 11.5a4 4 0 1 0 0-8 4 4 0 0 0 0 8z"></path></svg>
SVG,
    'attendance' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 2v4"></path><path d="M16 2v4"></path><path d="M3 9h18"></path><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="m8 14 2 2 4-5"></path></svg>
SVG,
    'studentList' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16"></path><path d="M4 12h10"></path><path d="M4 18h16"></path><circle cx="17" cy="12" r="2"></circle></svg>
SVG,
    'teacherList' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 7h14"></path><path d="M5 12h9"></path><path d="M5 17h14"></path><circle cx="17.5" cy="12" r="1.75"></circle></svg>
SVG,
    'staffList' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="4" width="14" height="16" rx="2"></rect><path d="M9 4v16"></path><path d="M11.5 10h4"></path><path d="M11.5 14h4"></path></svg>
SVG,
    'serverConfig' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5z"></path><path d="m19.4 15-.1.2 1.1 1.9-1.8 3.1-2.2-.3-.1.1a8.8 8.8 0 0 1-1.3.8l-.8 2h-3.6l-.8-2a8.8 8.8 0 0 1-1.3-.8l-.1-.1-2.2.3-1.8-3.1 1.1-1.9-.1-.2a8.8 8.8 0 0 1 0-1.6l.1-.2-1.1-1.9 1.8-3.1 2.2.3.1-.1a8.8 8.8 0 0 1 1.3-.8l.8-2h3.6l.8 2c.46.2.9.46 1.3.8l.1.1 2.2-.3 1.8 3.1-1.1 1.9.1.2a8.8 8.8 0 0 1 0 1.6z"></path></svg>
SVG,
    'classList' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h14v16H5z"></path><path d="M8 8h8"></path><path d="M8 12h8"></path><path d="M8 16h5"></path></svg>
SVG,
    'departmentList' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20h16"></path><path d="M6 20V8h12v12"></path><path d="M9 8V4h6v4"></path><path d="M10 12h4"></path></svg>
SVG,
    'sectionList' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16"></path><path d="M4 12h16"></path><path d="M4 18h16"></path><path d="M7 6v12"></path><path d="M12 12v6"></path></svg>
SVG,
    'sessionList' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"></circle><path d="M12 8v4l3 2"></path></svg>
SVG,
    'subjectList' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h10a4 4 0 0 1 4 4v12H9a4 4 0 0 0-4 4V4z"></path><path d="M9 8h7"></path><path d="M9 12h7"></path></svg>
SVG,
    'gradeList' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3 14.8 8.6 21 9.5l-4.5 4.4 1.1 6.1L12 17.9 6.4 20l1.1-6.1L3 9.5l6.2-.9z"></path></svg>
SVG,
    'examList' => <<<'SVG'
<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" style="width:1rem;height:1rem;flex-shrink:0;" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h10l3 3v15H4V3z"></path><path d="M8 12h8"></path><path d="M8 16h5"></path></svg>
SVG,
    ];

    $groups = [
        [
            'label' => 'Main',
            'items' => [
        ['label' => 'Dashboard', 'route' => 'adminModernDashboard', 'icon' => 'dashboard'],
        ['label' => 'Users', 'route' => 'adminModernUsersIndex', 'icon' => 'users'],
        ['label' => 'Attendance', 'route' => 'attendanceIndex', 'icon' => 'attendance'],
        ['label' => 'Student List', 'route' => 'adminModernStudentsIndex', 'icon' => 'studentList'],
        ['label' => 'Teacher List', 'route' => 'adminModernTeachersIndex', 'icon' => 'teacherList'],
        ['label' => 'Staff List', 'route' => 'adminModernStaffIndex', 'icon' => 'staffList'],
        ['label' => 'Configuration', 'route' => 'serverConfig', 'icon' => 'serverConfig'],
            ],
        ],
        [
            'label' => 'Academic',
            'items' => [
                ['label' => 'Class List', 'route' => 'adminModernAcademicClassesIndex', 'icon' => 'classList'],
                ['label' => 'Department List', 'route' => 'adminModernAcademicDepartmentsIndex', 'icon' => 'departmentList'],
                ['label' => 'Section List', 'route' => 'adminModernAcademicSectionsIndex', 'icon' => 'sectionList'],
                ['label' => 'Session List', 'route' => 'adminModernAcademicSessionsIndex', 'icon' => 'sessionList'],
                ['label' => 'Subject List', 'route' => 'adminModernAcademicSubjectsIndex', 'icon' => 'subjectList'],
                ['label' => 'Grade List', 'route' => 'adminModernAcademicGradesIndex', 'icon' => 'gradeList'],
                ['label' => 'Exam List', 'route' => 'adminModernAcademicExamsIndex', 'icon' => 'examList'],
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
                    $prefix   = $amNavPrefix($item['route']);
                    $isActive = str_starts_with($currentRouteName, $prefix);
                    $href     = route($item['route']);
                    $icon     = $navIcons[$item['icon'] ?? ''] ?? '';
                @endphp
                <a href="{{ $href }}" class="am-nav-link {{ $isActive ? 'is-active' : '' }}" style="display:flex; align-items:center; gap:0.65rem;">
                    {!! $icon !!}
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="am-sidebar-footer">
        <a href="{{ route('cultivationIndex') }}" class="am-link-muted">Old Dashboard</a>
    </div>
</aside>
