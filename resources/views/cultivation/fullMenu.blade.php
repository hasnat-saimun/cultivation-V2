
@php
    $topRoutes = [
        'cultivationIndex'=>'cultivationIndex',
        'academicPart'=>'academicPart',
        'resultPart'=>'resultPart',
        'accountPart'=>'accountPart'
    ];
@endphp
<ul class="nav nav-sidebar-menu sidebar-toggle-view">
    <li class="nav-item">
        <a href="{{ route('cultivationIndex') }}" class="nav-link {{ request()->routeIs('cultivationIndex') ? 'active' : '' }}"><i class="flaticon-dashboard"></i> <span>Cultivation Panel</span></a>
    </li>
    <li class="nav-item">
        <a href="{{route('academicPart') }}" class="nav-link {{ request()->routeIs('academicPart') ? 'active' : '' }}"><i class="fa-solid fa-building-columns"></i> <span>Academic Panel</span></a>
    </li>
    <li class="nav-item">
        <a href="{{ route('resultPart') }}" class="nav-link {{ request()->routeIs('resultPart') ? 'active' : '' }}">
            <i class="fa-sharp fa-thin fa-square-poll-horizontal"></i> <span>Results Management</span>
        </a>
    </li>
    @php
        $adminId = session('cultivationAdmin');
        $currentUser = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;
    @endphp
    @if($currentUser && $currentUser->isGeneral())
    <li class="nav-item">
        <a href="{{ route('resultArchive') }}" class="nav-link {{ request()->routeIs('resultArchive') ? 'active' : '' }}">
            <i class="fa fa-archive"></i> <span>Result Archive</span>
        </a>
    </li>
    @endif
    <li class="nav-item">
        <a href="{{ route('accountPart') }}" class="nav-link {{ request()->routeIs('accountPart') ? 'active' : '' }}"><i class="fa-solid fa-receipt"></i> <span>Accounts Management</span></a>
    </li>
    @php
        $certRoutes = ['studentList','testimonials.*','tc.*'];
        $certOpen = request()->routeIs($certRoutes);
    @endphp
    @php
        $attendanceRoutes = ['attendanceIndex','attendanceReport','attendanceMonthly'];
        $attendanceOpen = request()->routeIs($attendanceRoutes);
    @endphp
    <li class="nav-item sidebar-nav-item {{ $attendanceOpen ? 'open' : '' }}" data-group="admin-attendance">
        <a href="#" class="nav-link {{ $attendanceOpen ? 'active' : '' }}"><i class="fa-regular fa-calendar-check"></i> <span>Attendance Management</span></a>
        <ul class="nav sub-group-menu{{ $attendanceOpen ? ' menu-open' : '' }}">
            <li class="nav-item">
                <a href="{{ route('attendanceIndex') }}" class="nav-link {{ request()->routeIs('attendanceIndex') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Mark Attendance</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('attendanceReport') }}" class="nav-link {{ request()->routeIs('attendanceReport') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Daily Report</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('attendanceMonthly') }}" class="nav-link {{ request()->routeIs('attendanceMonthly') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Monthly Sheet</a>
            </li>
        </ul>
    </li>
    {{-- ...rest of the full menu... --}}
    @php
        $admissionRoutes = ['admitStudent','studentList','studentPromotion','studentPhotoBulk'];
        $admissionOpen = request()->routeIs($admissionRoutes);
    @endphp
    <li class="nav-item sidebar-nav-item {{ $admissionOpen ? 'open' : '' }}" data-group="admin-admission">
        <a href="#" class="nav-link {{ $admissionOpen ? 'active' : '' }}"><i class="flaticon-classmates"></i> <span>Admission</span></a>
        <ul class="nav sub-group-menu{{ $admissionOpen ? ' menu-open' : '' }}">
            <li class="nav-item">
                <a href="{{ route('admitStudent') }}" class="nav-link {{ request()->routeIs('admitStudent') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>New Admission</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('studentList') }}" class="nav-link {{ request()->routeIs('studentList') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Student List</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('studentPromotion') }}" class="nav-link {{ request()->routeIs('studentPromotion') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Manage Promotion</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('studentPhotoBulk') }}" class="nav-link {{ request()->routeIs('studentPhotoBulk') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Bulk Photo Upload</a>
            </li>
        </ul>
    </li>
    @php
        $teacherRoutes = ['teacherList','addTeacher','teacherBulkPhotoUpload','teacherBulkUpdate'];
        $teacherOpen = request()->routeIs($teacherRoutes);
    @endphp
    <li class="nav-item sidebar-nav-item {{ $teacherOpen ? 'open' : '' }}" data-group="admin-teachers">
        <a href="#" class="nav-link {{ $teacherOpen ? 'active' : '' }}"><i class="flaticon-multiple-users-silhouette"></i> <span>Teachers Panel</span></a>
        <ul class="nav sub-group-menu{{ $teacherOpen ? ' menu-open' : '' }}">
            <li class="nav-item">
                <a href="{{ route('teacherList') }}" class="nav-link {{ request()->routeIs('teacherList') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Teacher List</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('addTeacher') }}" class="nav-link {{ request()->routeIs('addTeacher') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> New Profile</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('teacherBulkPhotoUpload') }}" class="nav-link {{ request()->routeIs('teacherBulkPhotoUpload') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Bulk Photo Upload</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('teacherBulkUpdate') }}" class="nav-link {{ request()->routeIs('teacherBulkUpdate') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Bulk Details Update</a>
            </li>
        </ul>
    </li>
    @php
        $staffRoutes = ['staffList','addStaff','staffBulkPhotoUpload','staffBulkUpdate'];
        $staffOpen = request()->routeIs($staffRoutes);
    @endphp
    <li class="nav-item sidebar-nav-item {{ $staffOpen ? 'open' : '' }}" data-group="admin-staffs">
        <a href="#" class="nav-link {{ $staffOpen ? 'active' : '' }}"><i class="flaticon-couple"></i> <span>Staffs</span></a>
        <ul class="nav sub-group-menu{{ $staffOpen ? ' menu-open' : '' }}">
            <li class="nav-item">
                <a href="{{ route('staffList') }}" class="nav-link {{ request()->routeIs('staffList') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>All Staffs</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('addStaff') }}" class="nav-link {{ request()->routeIs('addStaff') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> New Profile</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('staffBulkPhotoUpload') }}" class="nav-link {{ request()->routeIs('staffBulkPhotoUpload') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Bulk Photo Upload</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('staffBulkUpdate') }}" class="nav-link {{ request()->routeIs('staffBulkUpdate') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Bulk Details Update</a>
            </li>
        </ul>
    </li>
    @php
        $governingBodyRoutes = ['managingCommittee','governingBodyBulkPhotoUpload'];
        $governingBodyOpen = request()->routeIs($governingBodyRoutes);
    @endphp
    <li class="nav-item sidebar-nav-item {{ $governingBodyOpen ? 'open' : '' }}" data-group="admin-governing-body">
        <a href="#" class="nav-link {{ $governingBodyOpen ? 'active' : '' }}"><i class="fa-solid fa-users"></i> <span>Governing Body</span></a>
        <ul class="nav sub-group-menu{{ $governingBodyOpen ? ' menu-open' : '' }}">
            <li class="nav-item">
                <a href="{{ route('managingCommittee') }}" class="nav-link {{ request()->routeIs('managingCommittee') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Members List</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('governingBodyBulkPhotoUpload') }}" class="nav-link {{ request()->routeIs('governingBodyBulkPhotoUpload') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Bulk Photo Upload</a>
            </li>
        </ul>
    </li>
    @php
        $userRoutes = ['userType','userRegList'];
        $userOpen = request()->routeIs($userRoutes);
    @endphp
    <li class="nav-item sidebar-nav-item {{ $userOpen ? 'open' : '' }}" data-group="admin-users">
        <a href="#" class="nav-link {{ $userOpen ? 'active' : '' }}"><i class="flaticon-couple"></i> <span>User Panel</span></a>
        <ul class="nav sub-group-menu{{ $userOpen ? ' menu-open' : '' }}">
            <li class="nav-item">
                <a href="{{route('userType')}}" class="nav-link {{ request()->routeIs('userType') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>User Register</a>
            </li>
            <li class="nav-item">
                <a href="{{route('userRegList')}}" class="nav-link {{ request()->routeIs('userRegList') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>User List</a>
            </li>
        </ul>
    </li>
    <li class="nav-item">
        <a href="{{ route('serverConfig') }}" class="nav-link {{ request()->routeIs('serverConfig') ? 'active' : '' }}"><i class="fa-solid fa-screwdriver-wrench"></i> <span>Configuration</span></a>
    </li>
    <li class="nav-item">
        <a href="{{ route('designationsIndex') }}" class="nav-link {{ request()->routeIs('designationsIndex','designationsCreate','designationsEdit') ? 'active' : '' }}"><i class="fa-solid fa-list"></i> <span>Designations</span></a>
    </li>
    <li class="nav-item">
        <a href="{{ route('userGuide') }}" class="nav-link {{ request()->routeIs('userGuide') ? 'active' : '' }}"><i class="fa-regular fa-book"></i> <span>User Guide</span></a>
    </li>
</ul>