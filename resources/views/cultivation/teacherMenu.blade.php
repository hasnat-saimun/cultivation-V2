
@php
    $attendanceRoutes = ['attendanceIndex','attendanceReport','attendanceMonthly'];
    $attendanceOpen = request()->routeIs($attendanceRoutes);
    $marksRoutes = ['addMarks'];
    $marksOpen = request()->routeIs($marksRoutes);
    $archiveRoutes = ['resultArchive'];
    $guideRoutes = ['userGuide.teacherAdmin'];
    $guideOpen = request()->routeIs($guideRoutes);
@endphp
<ul class="nav nav-sidebar-menu sidebar-toggle-view">
    <li class="nav-item">
        <a href="{{ route('cultivationIndex') }}" class="nav-link {{ request()->routeIs('cultivationIndex') ? 'active' : '' }}"><i class="flaticon-dashboard"></i><span>Cultivation Admin</span></a>
    </li>
    <li class="nav-item">
        @php
            $adminId = session('cultivationAdmin');
            $currentUser = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;
        @endphp
        @if($currentUser && $currentUser->isGeneral())
        <a href="{{ route('resultArchive') }}" class="nav-link {{ request()->routeIs('resultArchive') ? 'active' : '' }}">
            <i class="fa fa-archive"></i><span>Result Archive</span>
        </a>
        @endif
    </li>
    <li class="nav-item sidebar-nav-item {{ $marksOpen ? 'open' : '' }}" data-group="teacher-marks">
        <a href="#" class="nav-link {{ $marksOpen ? 'active' : '' }}"><i class="flaticon-books"></i><span>Marks Entry</span></a>
        <ul class="nav sub-group-menu{{ $marksOpen ? ' menu-open' : '' }}">
            <li class="nav-item">
                <a href="{{ route('addMarks') }}" class="nav-link {{ request()->routeIs('addMarks') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Marks Entry</a>
            </li>
        </ul>
    </li>
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
    <li class="nav-item sidebar-nav-item {{ $guideOpen ? 'open' : '' }}" data-group="teacher-guides">
        <a href="#" class="nav-link {{ $guideOpen ? 'active' : '' }}"><i class="fa-regular fa-book"></i><span>User Guides</span></a>
        <ul class="nav sub-group-menu{{ $guideOpen ? ' menu-open' : '' }}">
            <li class="nav-item">
                <a href="{{ route('userGuide.teacherAdmin') }}" class="nav-link {{ request()->routeIs('userGuide.teacherAdmin') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Teacher Admin</a>
            </li>
        </ul>
    </li>
</ul>