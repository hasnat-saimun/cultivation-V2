<!doctype html>
<html class="no-js" lang="">
<head>
    @include('cultivation.includeSection')
</head>
<body>
    <!-- Preloader Start Here -->
    <div id="preloader"></div>
    <!-- Preloader End Here -->
    <div id="wrapper" class="wrapper bg-ash">
       <!-- Header Menu Area Start Here -->
        <div class="navbar navbar-expand-md header-menu-one bg-light">
            @include('cultivation.topBar')
        </div>
        <!-- Header Menu Area End Here -->
        <!-- Page Area Start Here -->
        <div class="dashboard-page-one">
            <!-- Sidebar Area Start Here -->
            <div class="sidebar-main sidebar-menu-one sidebar-expand-md sidebar-color d-print-none">
               <div class="mobile-sidebar-header d-md-none">
                    @include('cultivation.logoSection')
               </div>
                <div class="sidebar-menu-content">
                    <ul class="nav nav-sidebar-menu sidebar-toggle-view">
                        <li class="nav-item">
                            <a href="{{ route('cultivationIndex') }}" class="nav-link"><i class="flaticon-dashboard"></i><span>Cultivation Panel</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{route('academicPart') }}" class="nav-link"><i class="fa-solid fa-building-columns"></i><span>Academic</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('resultPart') }}" class="nav-link"><i class="fa-sharp fa-thin fa-square-poll-horizontal"></i><span>Results</span></a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('accountPart') }}" class="nav-link"><i class="fa-solid fa-receipt"></i><span>Accounts</span></a>
                        </li>
                        <li class="nav-item sidebar-nav-item">
                            <a href="#" class="nav-link"><i class="flaticon-classmates"></i><span>Admission</span></a>
                            <ul class="nav sub-group-menu">
                                <li class="nav-item">
                                    <a href="{{ route('admitStudent') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>New Admission</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('studentList') }}" class="nav-link"><i class="fas fa-angle-right"></i> Student List</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('studentPromotion') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>Manage Promotion</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item sidebar-nav-item">
                            <a href="#" class="nav-link"><i
                                    class="flaticon-multiple-users-silhouette"></i><span>Teachers Panel</span></a>
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
                                    <a href="{{ route('staffList') }}" class="nav-link"><i class="fas fa-angle-right"></i>All
                                        Staffs</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('addStaff') }}" class="nav-link"><i class="fas fa-angle-right"></i> New
                                        Profile</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('serverConfig') }}" class="nav-link"><i class="fa-solid fa-screwdriver-wrench"></i><span>Configuration</span></a>
                        </li>
                        <li class="nav-item sidebar-nav-item">
                            <a href="#" class="nav-link"><i class="flaticon-couple"></i><span>Admin</span></a>
                            <ul class="nav sub-group-menu">
                                <li class="nav-item">
                                    <a href="{{route('registerForm')}}" class="nav-link"><i class="fas fa-angle-right"></i>Register Form</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{route('registerList')}}" class="nav-link"><i class="fas fa-angle-right"></i> Register List</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>
            <!-- Sidebar Area End Here -->
            <div class="dashboard-content-one">
                <!-- Breadcubs Area Start Here -->
                <div class="breadcrumbs-area d-print-none">
                    <h3>Admin Dashboard</h3>
                    <ul>
                        <li>
                            <a href="index-2.html">Home</a>
                        </li>
                        <li>Admin</li>
                    </ul>
                </div>
                <!-- Breadcubs Area End Here -->
                @yield('backIndex')
                <!-- Footer Area Start Here -->
                <footer class="footer-wrap-layout1 d-print-none">
                    @include('cultivation.footer')
                </footer>
                <!-- Footer Area End Here -->
            </div>
        </div>
        <!-- Page Area End Here -->
    </div>
    
    <!-- jquery-->

    <script>
        $(document).ready(function() {
            $(".alert").fadeTo(2000, 500).slideUp(500, function() {
                $(".alert").slideUp(500);
            });
        });
    </script>
    <!-- Plugins js -->
    <script src="{{ asset('/public/back-office/') }}/js/plugins.js"></script>
    <!-- Popper js -->
    <script src="{{ asset('/public/back-office/') }}/js/popper.min.js"></script>
    <!-- Bootstrap js -->
    <script src="{{ asset('/public/back-office/') }}/js/bootstrap.min.js"></script>
    <!-- Counterup Js -->
    <script src="{{ asset('/public/back-office/') }}/js/jquery.counterup.min.js"></script>
    <!-- Moment Js -->
    <script src="{{ asset('/public/back-office/') }}/js/moment.min.js"></script>
    <!-- Waypoints Js -->
    <script src="{{ asset('/public/back-office/') }}/js/jquery.waypoints.min.js"></script>
    <!-- Scroll Up Js -->
    <script src="{{ asset('/public/back-office/') }}/js/jquery.scrollUp.min.js"></script>
    <!-- Full Calender Js -->
    <script src="{{ asset('/public/back-office/') }}/js/fullcalendar.min.js"></script>
    <!-- Select 2 Js -->
    <script src="{{ asset('/public/back-office/') }}/js/select2.min.js"></script>
    <!-- Chart Js -->
    <script src="{{ asset('/public/back-office/') }}/js/Chart.min.js"></script>
    <!-- Custom Js -->
    <script src="{{ asset('/public/back-office/') }}/js/main.js"></script>

</body>
</html>