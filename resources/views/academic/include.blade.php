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
                        <li class="nav-item sidebar-nav-item">
                            <a href="#" class="nav-link"><i class="fa-regular fa-building-flag"></i><span>Institute Info</span></a>
                            <ul class="nav sub-group-menu">
                                <li class="nav-item">
                                    <a href="{{ route('insInfo') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>About Institue</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('principalSpeech') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>Principal Speech</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('exPrincipal') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>Ex Principals</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('managingCommittee') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>Managing Comittee</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item sidebar-nav-item">
                            <a href="#" class="nav-link"><i class="fa-regular fa-book-open"></i><span>Academic Info</span></a>
                            <ul class="nav sub-group-menu">
                                <li class="nav-item">
                                    <a href="{{ route('syllabusManage') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>Syllabus</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('classRoutineManage') }}" class="nav-link"><i class="fas fa-angle-right"></i> Class Routine</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('semisterPlanManage') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>Semister Plan</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('examRoutineManage') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>Exam Routine</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item sidebar-nav-item">
                            <a href="#" class="nav-link"><i class="fa-thin fa-database"></i> <span>Placement Cell</span></a>
                            <ul class="nav sub-group-menu">
                                <li class="nav-item">
                                    <a href="{{ route('placementCell') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>Job Placement</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('needyStudentPanel') }}" class="nav-link"><i class="fas fa-angle-right"></i> Job Needy Student</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item sidebar-nav-item">
                            <a href="#" class="nav-link"><i class="fa-sharp fa-solid fa-list-check"></i> <span>Notice</span></a>
                            <ul class="nav sub-group-menu">
                                <li class="nav-item">
                                    <a href="{{ route('newNotice') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>New Notice</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('noticeList') }}" class="nav-link"><i class="fas fa-angle-right"></i> All Notice</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item sidebar-nav-item">
                            <a href="#" class="nav-link"><i class="fa-brands fa-envira"></i> <span>Gallery</span></a>
                            <ul class="nav sub-group-menu">
                                <li class="nav-item">
                                    <a href="{{ route('newPhoto') }}" class="nav-link"><i
                                            class="fas fa-angle-right"></i>Photos</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('newVideo') }}" class="nav-link"><i class="fas fa-angle-right"></i> Videos</a>
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
                    <h3>Academic Panel</h3>
                    <ul>
                        <li>
                            <a href="{{ route('cultivationIndex') }}">Home</a>
                        </li>
                        <li>@yield('backTitle')</li>
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