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
                    @php
                        $homeActive = request()->routeIs('cultivationIndex');
                        $sliderActive = request()->routeIs('sliderInfo');

                        $instRoutes = ['insInfo','principalSpeech','exPrincipal','managingCommittee'];
                        $acadRoutes = ['syllabusManage','classRoutineManage','semisterPlanManage','examRoutineManage','internalResultManage'];
                        $placementRoutes = ['placementCell','needyStudentPanel'];
                        $noticeRoutes = ['newNotice','noticeList'];
                        $galleryRoutes = ['newPhoto','newVideo'];

                        $instOpen = request()->routeIs($instRoutes);
                        $acadOpen = request()->routeIs($acadRoutes);
                        $placementOpen = request()->routeIs($placementRoutes);
                        $noticeOpen = request()->routeIs($noticeRoutes);
                        $galleryOpen = request()->routeIs($galleryRoutes);
                    @endphp
                    <ul class="nav nav-sidebar-menu sidebar-toggle-view">
                        <li class="nav-item">
                            <a href="{{ route('cultivationIndex') }}" class="nav-link {{ $homeActive ? 'active' : '' }}"><i class="flaticon-dashboard"></i><span>Cultivation Panel</span></a>
                        </li> 
                        <li class="nav-item">
                            <a href="{{ route('sliderInfo') }}" class="nav-link {{ $sliderActive ? 'active' : '' }}"><i class="fa-solid fa-building-columns"></i> <span>Home Slider</span></a>
                        </li>
                        <li class="nav-item sidebar-nav-item {{ $instOpen ? 'open' : '' }}" data-group="academic-institute">
                            <a href="#" class="nav-link {{ $instOpen ? 'active' : '' }}"><i class="fa-regular fa-building-flag"></i> <span>Institute Info</span></a>
                            <ul class="nav sub-group-menu{{ $instOpen ? ' menu-open' : '' }}">
                                <li class="nav-item">
                                    <a href="{{ route('insInfo') }}" class="nav-link {{ request()->routeIs('insInfo') ? 'active' : '' }}"><i
                                            class="fas fa-angle-right"></i>About Institue</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('principalSpeech') }}" class="nav-link {{ request()->routeIs('principalSpeech') ? 'active' : '' }}"><i
                                            class="fas fa-angle-right"></i>Principal Speech</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('exPrincipal') }}" class="nav-link {{ request()->routeIs('exPrincipal') ? 'active' : '' }}"><i
                                            class="fas fa-angle-right"></i>Ex Principals</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('managingCommittee') }}" class="nav-link {{ request()->routeIs('managingCommittee') ? 'active' : '' }}"><i
                                            class="fas fa-angle-right"></i>Governing Body</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item sidebar-nav-item {{ $acadOpen ? 'open' : '' }}" data-group="academic-info">
                            <a href="#" class="nav-link {{ $acadOpen ? 'active' : '' }}"><i class="fa-regular fa-book-open"></i> <span>Academic Info</span></a>
                            <ul class="nav sub-group-menu{{ $acadOpen ? ' menu-open' : '' }}">
                                <li class="nav-item">
                                    <a href="{{ route('syllabusManage') }}" class="nav-link {{ request()->routeIs('syllabusManage') ? 'active' : '' }}"><i
                                            class="fas fa-angle-right"></i>Syllabus</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('classRoutineManage') }}" class="nav-link {{ request()->routeIs('classRoutineManage') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Class Routine</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('semisterPlanManage') }}" class="nav-link {{ request()->routeIs('semisterPlanManage') ? 'active' : '' }}"><i
                                            class="fas fa-angle-right"></i>Semister Plan</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('examRoutineManage') }}" class="nav-link {{ request()->routeIs('examRoutineManage') ? 'active' : '' }}"><i
                                            class="fas fa-angle-right"></i>Exam Routine</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('internalResultManage') }}" class="nav-link {{ request()->routeIs('internalResultManage') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Internal Results</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item sidebar-nav-item {{ $placementOpen ? 'open' : '' }}" data-group="academic-placement">
                            <a href="#" class="nav-link {{ $placementOpen ? 'active' : '' }}"><i class="fa-thin fa-database"></i>  <span>Placement Cell</span></a>
                            <ul class="nav sub-group-menu{{ $placementOpen ? ' menu-open' : '' }}">
                                <li class="nav-item">
                                    <a href="{{ route('placementCell') }}" class="nav-link {{ request()->routeIs('placementCell') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Job Placement</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('needyStudentPanel') }}" class="nav-link {{ request()->routeIs('needyStudentPanel') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Job Needy Student</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item sidebar-nav-item {{ $noticeOpen ? 'open' : '' }}" data-group="academic-notice">
                            <a href="#" class="nav-link {{ $noticeOpen ? 'active' : '' }}"><i class="fa-sharp fa-solid fa-list-check"></i>  <span>Notice</span></a>
                            <ul class="nav sub-group-menu{{ $noticeOpen ? ' menu-open' : '' }}">
                                <li class="nav-item">
                                    <a href="{{ route('newNotice') }}" class="nav-link {{ request()->routeIs('newNotice') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>New Notice</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('noticeList') }}" class="nav-link {{ request()->routeIs('noticeList') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> All Notice</a>
                                </li>
                            </ul>
                        </li>
                        <li class="nav-item sidebar-nav-item {{ $galleryOpen ? 'open' : '' }}" data-group="academic-gallery">
                            <a href="#" class="nav-link {{ $galleryOpen ? 'active' : '' }}"><i class="fa-brands fa-envira"></i>  <span>Gallery</span></a>
                            <ul class="nav sub-group-menu{{ $galleryOpen ? ' menu-open' : '' }}">
                                <li class="nav-item">
                                    <a href="{{ route('newPhoto') }}" class="nav-link {{ request()->routeIs('newPhoto') ? 'active' : '' }}"><i
                                            class="fas fa-angle-right"></i>Photos</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('newVideo') }}" class="nav-link {{ request()->routeIs('newVideo') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Videos</a>
                                </li>
                            </ul>
                        </li>
                    </ul>
                </div>
                <script>
                    $(document).ready(function() {
                        // Clear old corrupted localStorage
                        localStorage.removeItem('academicSidebarOpenGroups');
                        
                        // Academic sidebar menu toggle - Simple and clean
                        const groups = document.querySelectorAll('li.sidebar-nav-item[data-group]');
                        
                        groups.forEach((menuItem)=>{
                            const link = menuItem.querySelector(':scope > a.nav-link');
                            const submenu = menuItem.querySelector('ul.sub-group-menu');
                            
                            if(link && submenu) {
                                link.addEventListener('click', function(e){
                                    if(this.getAttribute('href')==='#'){
                                        e.preventDefault();
                                        e.stopPropagation();
                                        e.stopImmediatePropagation();
                                        
                                        const isOpen = menuItem.classList.contains('open');
                                        
                                        // Close all other menus
                                        groups.forEach(otherItem=>{
                                            if(otherItem !== menuItem && otherItem.classList.contains('open')){
                                                otherItem.classList.remove('open');
                                                const otherSub = otherItem.querySelector('ul.sub-group-menu');
                                                if(otherSub) otherSub.classList.remove('menu-open');
                                                const otherLink = otherItem.querySelector(':scope > a.nav-link');
                                                if(otherLink) otherLink.classList.remove('active');
                                            }
                                        });
                                        
                                        // Toggle current menu
                                        if(isOpen){
                                            menuItem.classList.remove('open');
                                            submenu.classList.remove('menu-open');
                                            link.classList.remove('active');
                                        } else {
                                            menuItem.classList.add('open');
                                            submenu.classList.add('menu-open');
                                            link.classList.add('active');
                                        }
                                    }
                                }, true);
                            }
                        });
                    });
                </script>
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