<!doctype html>
<html class="no-js" lang="">
<head>
    @include('cultivation.includeSection')
    {{-- Page-level stacked styles --}}
    @stack('styles')
</head>
<body>
    <!-- Preloader Start Here -->
    <div id="preloader"></div>
    <!-- Preloader End Here -->
    <div id="wrapper" class="wrapper bg-ash min-vh-100">
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
                        // Get logged-in user info from session
                        $loginUser = \App\Models\CultivationAdmin::find(session('cultivationAdmin'));
                        $userType = $loginUser['userType'] ?? null;
                    @endphp

                    @if($userType == 3) {{-- General Admin: Full Menu --}}
                        @include('cultivation.fullMenu')
                    @elseif($userType == 2) {{-- Cash Admin: Only Accounts Management --}}
                        @include('cultivation.cashMenu')
                    @elseif($userType == 1) {{-- Teacher Admin: Only Result Management --}}
                        @include('cultivation.teacherMenu')
                    @endif
                </div>
            </div>
            <!-- Sidebar Area End Here -->
            <div class="dashboard-content-one">
                <!-- Breadcubs Area Start Here -->
                <div class="breadcrumbs-area d-print-none">
                    <h3>Admin Dashboard</h3>
                    <ul>
                        <li>
                            <a href="{{ route('cultivationIndex') }}">Home</a>
                        </li>
                        <li>Admin</li>
                    </ul>
                </div>
                <!-- Breadcubs Area End Here -->
                @yield('backIndex')
                @include('partials.flash-modal')
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
            $(".alert").fadeTo(20000, 5000).slideUp(5000, function() {
                $(".alert").slideUp(5000);
            });
            
            // Simple sidebar menu toggle: one open at a time
            const groups = document.querySelectorAll('.sidebar-menu-content li.sidebar-nav-item');
            
            groups.forEach((li)=>{
                const link = li.querySelector(':scope > a.nav-link');
                const sub = li.querySelector('ul.sub-group-menu');
                
                // Only attach click if there's a submenu
                if(link && sub){
                    link.addEventListener('click', function(e){
                        if(this.getAttribute('href')==='#'){
                            e.preventDefault();
                            e.stopPropagation();
                            e.stopImmediatePropagation();
                            
                            const isOpen = li.classList.contains('open');
                            
                            // Close all others
                            groups.forEach(other=>{
                                other.classList.remove('open');
                                const otherSub = other.querySelector('ul.sub-group-menu');
                                if(otherSub) otherSub.classList.remove('menu-open');
                                const otherLink = other.querySelector(':scope > a.nav-link');
                                if(otherLink) otherLink.classList.remove('active');
                            });
                            
                            // Toggle current
                            if(!isOpen){
                                li.classList.add('open');
                                sub.classList.add('menu-open');
                                link.classList.add('active');
                            } else {
                                li.classList.remove('open');
                                sub.classList.remove('menu-open');
                                link.classList.remove('active');
                            }
                        }
                    }, true);
                }
            });
        });
    </script>
    <!-- Compatibility shim: map Bootstrap 5 data attributes to Bootstrap 4 equivalents -->
    <script>
        (function(){
            document.addEventListener('DOMContentLoaded', function(){
                try{
                    document.querySelectorAll('[data-bs-dismiss]').forEach(function(el){
                        if(!el.hasAttribute('data-dismiss')) el.setAttribute('data-dismiss', el.getAttribute('data-bs-dismiss'));
                    });
                    document.querySelectorAll('[data-bs-toggle]').forEach(function(el){
                        if(!el.hasAttribute('data-toggle')) el.setAttribute('data-toggle', el.getAttribute('data-bs-toggle'));
                    });
                }catch(e){ console.warn('BS attr shim failed', e); }
            });
        })();
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

    {{-- Stacked page scripts --}}
    @stack('scripts')

</body>
</html>