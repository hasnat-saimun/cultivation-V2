
@php
    $attendanceRoutes = ['attendanceIndex','attendanceReport','attendanceMonthly'];
    $attendanceOpen = request()->routeIs($attendanceRoutes);
    $marksRoutes = ['addMarks'];
    $marksOpen = request()->routeIs($marksRoutes);
@endphp
<ul class="nav nav-sidebar-menu sidebar-toggle-view">
    <li class="nav-item">
        <a href="{{ route('cultivationIndex') }}" class="nav-link {{ request()->routeIs('cultivationIndex') ? 'active' : '' }}"><i class="flaticon-dashboard"></i><span>Dashboard</span></a>
    </li>
    <li class="nav-item sidebar-nav-item {{ $marksOpen ? 'open' : '' }}" data-group="teacher-marks">
        <a href="#" class="nav-link {{ $marksOpen ? 'active' : '' }}"><i class="flaticon-books"></i><span>Marks Entry</span></a>
        <ul class="nav sub-group-menu" style="{{ $marksOpen ? 'display:block;' : '' }}">
            <li class="nav-item">
                <a href="{{ route('addMarks') }}" class="nav-link {{ request()->routeIs('addMarks') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Add Marks</a>
            </li>
        </ul>
    </li>
    <li class="nav-item sidebar-nav-item {{ $attendanceOpen ? 'open' : '' }}" data-group="teacher-attendance">
        <a href="#" class="nav-link {{ $attendanceOpen ? 'active' : '' }}"><i class="fa-regular fa-calendar-check"></i><span>Attendance</span></a>
        <ul class="nav sub-group-menu" style="{{ $attendanceOpen ? 'display:block;' : '' }}">
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
</ul>