
<ul class="nav nav-sidebar-menu sidebar-toggle-view">
    <li class="nav-item">
        <a href="{{ route('cultivationIndex') }}" class="nav-link"><i class="flaticon-dashboard"></i><span>Cultivation Panel</span></a>
    </li>
    <li class="nav-item">
        <a href="{{route('academicPart') }}" class="nav-link"><i class="fa-solid fa-building-columns"></i><span>Academic Panel</span></a>
    </li>
    <li class="nav-item">
        <a href="{{ route('resultPart') }}" class="nav-link"><i class="fa-sharp fa-thin fa-square-poll-horizontal"></i><span>Results Management</span></a>
    </li>
    <li class="nav-item">
        <a href="{{ route('accountPart') }}" class="nav-link"><i class="fa-solid fa-receipt"></i><span>Accounts Management</span></a>
    </li>
    {{-- ...rest of the full menu... --}}
    <li class="nav-item sidebar-nav-item">
        <a href="#" class="nav-link"><i class="flaticon-classmates"></i><span>Admission</span></a>
        <ul class="nav sub-group-menu">
            <li class="nav-item">
                <a href="{{ route('admitStudent') }}" class="nav-link"><i class="fas fa-angle-right"></i>New Admission</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('studentList') }}" class="nav-link"><i class="fas fa-angle-right"></i> Student List</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('studentPromotion') }}" class="nav-link"><i class="fas fa-angle-right"></i>Manage Promotion</a>
            </li>
        </ul>
    </li>
    <li class="nav-item sidebar-nav-item">
        <a href="#" class="nav-link"><i class="flaticon-multiple-users-silhouette"></i><span>Teachers Panel</span></a>
        <ul class="nav sub-group-menu">
            <li class="nav-item">
                <a href="{{ route('teacherList') }}" class="nav-link"><i class="fas fa-angle-right"></i> Teacher List</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('addTeacher') }}" class="nav-link"><i class="fas fa-angle-right"></i> New Profile</a>
            </li>
        </ul>
    </li>
    <li class="nav-item sidebar-nav-item">
        <a href="#" class="nav-link"><i class="flaticon-couple"></i><span>Staffs</span></a>
        <ul class="nav sub-group-menu">
            <li class="nav-item">
                <a href="{{ route('staffList') }}" class="nav-link"><i class="fas fa-angle-right"></i>All Staffs</a>
            </li>
            <li class="nav-item">
                <a href="{{ route('addStaff') }}" class="nav-link"><i class="fas fa-angle-right"></i> New Profile</a>
            </li>
        </ul>
    </li>
    <li class="nav-item sidebar-nav-item">
        <a href="#" class="nav-link"><i class="flaticon-couple"></i><span>User Panel</span></a>
        <ul class="nav sub-group-menu">
            <li class="nav-item">
                <a href="{{route('userType')}}" class="nav-link"><i class="fas fa-angle-right"></i>User Register</a>
            </li>
            <li class="nav-item">
                <a href="{{route('userRegList')}}" class="nav-link"><i class="fas fa-angle-right"></i>User List</a>
            </li>
        </ul>
    </li>
    <li class="nav-item">
        <a href="{{ route('serverConfig') }}" class="nav-link"><i class="fa-solid fa-screwdriver-wrench"></i><span>Configuration</span></a>
    </li>
</ul>