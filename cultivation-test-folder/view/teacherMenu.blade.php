
@php
    $adminId = session('cultivationAdmin');
    $currentUser = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;
    $assignedClassIds = [];
    $hasMarksAssignment = true;
    if($currentUser && $currentUser->isTeacher()){
        $assignedClassIds = app(\App\Services\MarksEntryAuthorizationService::class)->authorizedClassIds($currentUser);
        $hasMarksAssignment = app(\App\Services\MarksEntryAuthorizationService::class)->hasAnyMarksAssignment($currentUser);
    }
    $hasClassTeacherAssignment = !$currentUser || !$currentUser->isTeacher() ? true : app(\App\Services\TeacherAuthorizationService::class)->isAssignedClassTeacher($currentUser);
    if(!$currentUser || !$currentUser->isTeacher()){
        $hasMarksAssignment = true;
    }

    $attendanceRoutes = ['attendanceIndex','attendanceReport','attendanceMonthly'];
    $attendanceOpen = request()->routeIs($attendanceRoutes);
    $marksRoutes = ['addMarks'];
    $marksOpen = request()->routeIs($marksRoutes);
    $studentFeesRoutes = ['tuitionFee','duesDashboard','feesReport','collectDueForm','tuitionFeeList','tuitionFeeView'];
    $studentFeesOpen = request()->routeIs($studentFeesRoutes);
    $archiveRoutes = ['resultArchive'];
    $guideRoutes = ['userGuide.teacherAdmin'];
    $guideOpen = request()->routeIs($guideRoutes);
@endphp
<ul class="nav nav-sidebar-menu sidebar-toggle-view">
    <li class="nav-item">
        <a href="{{ route('cultivationIndex') }}" class="nav-link {{ request()->routeIs('cultivationIndex') ? 'active' : '' }}"><i class="flaticon-dashboard"></i><span>Cultivation Admin</span></a>
    </li>
    <li class="nav-item">
        @if($currentUser && $currentUser->isGeneral())
        <a href="{{ route('resultArchive') }}" class="nav-link {{ request()->routeIs('resultArchive') ? 'active' : '' }}">
            <i class="fa fa-archive"></i><span>Result Archive</span>
        </a>
        @endif
    </li>
    @if($hasMarksAssignment)
    <li class="nav-item sidebar-nav-item {{ $marksOpen ? 'open' : '' }}" data-group="teacher-marks">
        <a href="#" class="nav-link {{ $marksOpen ? 'active' : '' }}"><i class="flaticon-books"></i><span>Marks Entry</span></a>
        <ul class="nav sub-group-menu{{ $marksOpen ? ' menu-open' : '' }}">
            <li class="nav-item">
                <a href="{{ route('addMarks') }}" class="nav-link {{ request()->routeIs('addMarks') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Marks Entry</a>
            </li>
        </ul>
    </li>
    @endif
    @if($hasClassTeacherAssignment)
    <li class="nav-item sidebar-nav-item {{ $studentFeesOpen ? 'open' : '' }}" data-group="teacher-student-fees">
        <a href="#" class="nav-link {{ $studentFeesOpen ? 'active' : '' }}"><i class="fa-solid fa-receipt"></i><span>Student Fees</span></a>
        <ul class="nav sub-group-menu{{ $studentFeesOpen ? ' menu-open' : '' }}">
            <li class="nav-item">
                <a href="{{ route('tuitionFee') }}" class="nav-link {{ request()->routeIs('tuitionFee') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Fees Collection</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('tuitionFeeList') }}" class="nav-link {{ request()->routeIs('tuitionFeeList') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Single Invoice</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('duesDashboard') }}" class="nav-link {{ request()->routeIs('duesDashboard') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Dues Dashboard</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('feesReport') }}" class="nav-link {{ request()->routeIs('feesReport') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Generate Report</a>
            </li>
        </ul>
    </li>
    @endif
    @if($hasClassTeacherAssignment)
    <li class="nav-item sidebar-nav-item {{ $attendanceOpen ? 'open' : '' }}" data-group="teacher-attendance">
        <a href="#" class="nav-link {{ $attendanceOpen ? 'active' : '' }}"><i class="fa-regular fa-calendar-check"></i><span>Attendance Management</span></a>
        <ul class="nav sub-group-menu{{ $attendanceOpen ? ' menu-open' : '' }}">
            <li class="nav-item">
                <a href="{{ route('attendanceIndex') }}" class="nav-link {{ request()->routeIs('attendanceIndex') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Mark Attendance</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('attendanceReport') }}" class="nav-link {{ request()->routeIs('attendanceReport') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Daily Report</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('attendanceMonthly') }}" class="nav-link {{ request()->routeIs('attendanceMonthly') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Monthly Sheet</a>
            </li>
        </ul>
    </li>
    @endif
    <li class="nav-item sidebar-nav-item {{ $guideOpen ? 'open' : '' }}" data-group="teacher-guides">
        <a href="#" class="nav-link {{ $guideOpen ? 'active' : '' }}"><i class="fa-regular fa-book"></i><span>User Guides</span></a>
        <ul class="nav sub-group-menu{{ $guideOpen ? ' menu-open' : '' }}">
            <li class="nav-item">
                <a href="{{ route('userGuide.teacherAdmin') }}" class="nav-link {{ request()->routeIs('userGuide.teacherAdmin') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Teacher Admin</a>
            </li>
        </ul>
    </li>
</ul>