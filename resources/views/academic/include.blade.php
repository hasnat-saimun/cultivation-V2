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
                        $acadRoutes = ['syllabusManage','classRoutineManage','semisterPlanManage','examRoutineManage'];
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
                            <ul class="nav sub-group-menu" style="{{ $instOpen ? 'display:block;' : '' }}">
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
                            <ul class="nav sub-group-menu" style="{{ $acadOpen ? 'display:block;' : '' }}">
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
                            </ul>
                        </li>
                        <li class="nav-item sidebar-nav-item {{ $placementOpen ? 'open' : '' }}" data-group="academic-placement">
                            <a href="#" class="nav-link {{ $placementOpen ? 'active' : '' }}"><i class="fa-thin fa-database"></i>  <span>Placement Cell</span></a>
                            <ul class="nav sub-group-menu" style="{{ $placementOpen ? 'display:block;' : '' }}">
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
                            <ul class="nav sub-group-menu" style="{{ $noticeOpen ? 'display:block;' : '' }}">
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
                            <ul class="nav sub-group-menu" style="{{ $galleryOpen ? 'display:block;' : '' }}">
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
                    // Robust sidebar group toggle: immediate first-click response, prevents text selection flicker, keyboard accessible.
                    document.addEventListener('DOMContentLoaded', function () {
                        const storageKey = 'academicSidebarOpenGroups';
                        let saved; try { saved = JSON.parse(localStorage.getItem(storageKey) || '[]'); } catch(_) { saved = []; }
                        const groupItems = Array.from(document.querySelectorAll('.sidebar-menu-content .sidebar-nav-item[data-group]'));

                        function persist(groups){
                            try { localStorage.setItem(storageKey, JSON.stringify(groups)); } catch(_) { /* ignore */ }
                        }

                        groupItems.forEach(li => {
                            const group = li.getAttribute('data-group');
                            const link = li.querySelector(':scope > a.nav-link');
                            const submenu = li.querySelector(':scope > ul.sub-group-menu');
                            if(!link || !submenu) return; // skip non-group

                            // Ensure link is focusable for accessibility
                            link.setAttribute('role','button');
                            link.setAttribute('tabindex','0');

                            const serverOpen = li.classList.contains('open') || (submenu && submenu.style.display === 'block');
                            const shouldOpen = serverOpen || saved.includes(group);
                            if (shouldOpen) {
                                li.classList.add('open');
                                submenu.style.display = 'block';
                                link.classList.add('active');
                            } else {
                                submenu.style.display = 'none';
                            }

                            function toggle(e){
                                if(e){ e.preventDefault(); e.stopPropagation(); if(e.stopImmediatePropagation) e.stopImmediatePropagation(); }
                                const nowOpen = !li.classList.contains('open');

                                // If opening this group, collapse all others (accordion behavior)
                                if(nowOpen){
                                    groupItems.forEach(other => {
                                        if(other === li) return;
                                        other.classList.remove('open','active');
                                        const otherLink = other.querySelector(':scope > a.nav-link');
                                        const otherSub = other.querySelector(':scope > ul.sub-group-menu');
                                        if(otherLink) otherLink.classList.remove('active');
                                        if(otherSub){
                                            otherSub.style.display = 'none';
                                            otherSub.classList.remove('menu-open');
                                        }
                                    });
                                }

                                li.classList.toggle('open', nowOpen);
                                submenu.style.display = nowOpen ? 'block' : 'none';
                                link.classList.toggle('active', nowOpen);
                                li.classList.toggle('active', nowOpen);
                                submenu.classList.toggle('menu-open', nowOpen);

                                // Persistence: only keep this group if open (single open policy)
                                saved = nowOpen ? [group] : [];
                                persist(saved);
                            }

                            // Capture phase to preempt theme's jQuery delegated handler
                            link.addEventListener('click', function(e){ toggle(e); }, true);
                            // Support Enter/Space key
                            link.addEventListener('keydown', function(ev){
                                if(ev.key === 'Enter' || ev.key === ' '){
                                    ev.preventDefault();
                                    // synthesize a click-like toggle with propagation stop
                                    toggle(ev);
                                }
                            });
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