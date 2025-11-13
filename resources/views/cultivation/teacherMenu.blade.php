
@php
    $attendanceRoutes = ['attendanceIndex','attendanceReport','attendanceMonthly'];
    $attendanceOpen = request()->routeIs($attendanceRoutes);
    $marksRoutes = ['addMarks'];
    $marksOpen = request()->routeIs($marksRoutes);
@endphp
<ul class="nav nav-sidebar-menu sidebar-toggle-view">
    <li class="nav-item sidebar-nav-item open" data-group="teacher-marks-attendance">
        <a href="#" class="nav-link active">
            <i class="fa-solid fa-building-columns"></i>
            <span>Marks & Attendance</span>
            <span class="arrow"></span>
        </a>
        <ul class="nav sub-group-menu" style="display:block;">
            <li class="nav-item">
                <a href="{{ route('addMarks') }}" class="nav-link {{ request()->routeIs('addMarks') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Add Marks</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('attendanceIndex') }}" class="nav-link {{ request()->routeIs('attendanceIndex') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Attendance</a>
            </li>
        </ul>
    </li>
</ul>