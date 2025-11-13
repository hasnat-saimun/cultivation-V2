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
                        @if($userType == 3)
                            @include('cultivation.fullMenu')
                        @elseif($userType == 2)
                            @include('cultivation.cashMenu')
                        @elseif($userType == 1)
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
            // Persistent open groups for cultivation full menu (only if fullMenu included)
            const storageKey = 'cultivationSidebarOpenGroups';
            let openGroups = JSON.parse(localStorage.getItem(storageKey) || '[]');
            function save(){ localStorage.setItem(storageKey, JSON.stringify(openGroups)); }
            function apply(){
                openGroups.forEach(id=>{
                    const li = document.querySelector('li.sidebar-nav-item[data-group="'+id+'"]');
                    if(li){
                        li.classList.add('open');
                        const sub = li.querySelector('ul.sub-group-menu');
                        if(sub) sub.style.display='block';
                        const a = li.querySelector('> a.nav-link');
                        if(a) a.classList.add('active');
                    }
                });
            }
            const groups = document.querySelectorAll('.sidebar-menu-content li.sidebar-nav-item');
            groups.forEach((li, idx)=>{
                if(!li.dataset.group) li.dataset.group = 'cg'+idx;
                const a = li.querySelector('> a.nav-link');
                if(a){
                    a.addEventListener('click', function(e){
                        if(this.getAttribute('href')==='#'){
                            e.preventDefault();
                            const id = li.dataset.group;
                            const sub = li.querySelector('ul.sub-group-menu');
                            const open = li.classList.contains('open');
                            if(open){
                                li.classList.remove('open'); if(sub) sub.style.display='none';
                                openGroups = openGroups.filter(g=>g!==id);
                                this.classList.remove('active');
                            }else{
                                li.classList.add('open'); if(sub) sub.style.display='block';
                                if(!openGroups.includes(id)) openGroups.push(id);
                                this.classList.add('active');
                            }
                            save();
                        }
                    });
                }
            });
            // record server-open groups
            groups.forEach(li=>{
                if(li.classList.contains('open')){
                    const id = li.dataset.group; if(id && !openGroups.includes(id)) openGroups.push(id);
                }
            });
            apply(); save();
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

    {{-- Stacked page scripts --}}
    @stack('scripts')

</body>
</html>