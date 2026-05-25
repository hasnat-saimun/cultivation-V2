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
                        $adminId = session('cultivationAdmin');
                        $currentUser = $adminId ? \App\Models\CultivationAdmin::find($adminId) : null;

                        // Determine active routes for highlighting and keeping groups open
                        $isCultivationHome = request()->routeIs('cultivationIndex');

                        $studentFeesRoutes = [
                            'tuitionFee','tuitionFeeList','feesReport','duesDashboard','classWiseFeeSetup','tuitionFeeView','editTuitionFee','tuitionReport'
                        ];
                        $cashRoutes = [
                            'cashCalculasView','reportListView','cashDateReport'
                        ];
                        $addFeesRoutes = ['feesForm'];

                        $studentFeesOpen = request()->routeIs($studentFeesRoutes);
                        $cashOpen = request()->routeIs($cashRoutes);
                        $addFeesActive = request()->routeIs($addFeesRoutes);
                        $canUseCashCalculas = $currentUser && ($currentUser->isCash() || $currentUser->isGeneral());
                    @endphp
                    <ul class="nav nav-sidebar-menu sidebar-toggle-view">
                        <li class="nav-item">
                            <a href="{{ route('cultivationIndex') }}" class="nav-link {{ $isCultivationHome ? 'active' : '' }}"><i class="flaticon-dashboard"></i><span>Cultivation Panel</span></a>
                        </li>
                        <li class="nav-item sidebar-nav-item {{ $studentFeesOpen ? 'open' : '' }}">
                            <a href="#" class="nav-link {{ $studentFeesOpen ? 'active' : '' }}"><i class="fa-regular fa-building-flag"></i> <span>Student Fees</span></a>
                            <ul class="nav sub-group-menu{{ $studentFeesOpen ? ' menu-open' : '' }}">
                                <li class="nav-item">
                                    <a href="{{ route('tuitionFee') }}" class="nav-link {{ request()->routeIs('tuitionFee') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Fees Collection</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('tuitionFeeList') }}" class="nav-link {{ request()->routeIs('tuitionFeeList') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Single Invoice</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('feesReport') }}" class="nav-link {{ request()->routeIs('feesReport') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Genetrate Report</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('duesDashboard') }}" class="nav-link {{ request()->routeIs('duesDashboard') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Dues Dashboard</a>
                                </li>
                                @if(!$currentUser || !$currentUser->isTeacher())
                                <li class="nav-item">
                                    <a href="{{ route('classWiseFeeSetup') }}" class="nav-link {{ request()->routeIs('classWiseFeeSetup') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Classwise Fees Setup</a>
                                </li>
                                @endif
                            </ul>
                        </li>
                        @if($canUseCashCalculas)
                        <li class="nav-item sidebar-nav-item {{ $cashOpen ? 'open' : '' }}">
                            <a href="#" class="nav-link {{ $cashOpen ? 'active' : '' }}"><i class="fa-regular fa-book-open"></i> <span>Cash Calculas</span></a>
                            <ul class="nav sub-group-menu{{ $cashOpen ? ' menu-open' : '' }}">
                                <li class="nav-item">
                                    <a href="{{ route('cashCalculasView') }}" class="nav-link {{ request()->routeIs('cashCalculasView') ? 'active' : '' }}"><i  class="fas fa-angle-right"></i>Debit/Credit</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('reportListView') }}" class="nav-link {{ request()->routeIs('reportListView') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Single Voucher</a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('cashDateReport') }}" class="nav-link {{ request()->routeIs('cashDateReport') ? 'active' : '' }}"><i class="fas fa-angle-right"></i> Generate Report</a>
                                </li>
                            </ul>
                        </li>
                        @endif
                        @if(!$currentUser || !$currentUser->isTeacher())
                        <li class="nav-item ">
                            <a href="{{route('feesForm')}}" class="nav-link {{ $addFeesActive ? 'active' : '' }}"><i class="fa-thin fa-database"></i> <span>Add New Fees</span></a>
                        </li>
                        @endif
                    </ul>
                </div>
            </div>
            <!-- Sidebar Area End Here -->
            <div class="dashboard-content-one">
                <!-- Breadcubs Area Start Here -->
                <div class="breadcrumbs-area d-print-none">
                    <h3>Account Panel</h3>
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
            
            // Account sidebar menu toggle - Simple and clean
            const groups = document.querySelectorAll('li.sidebar-nav-item');
            
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