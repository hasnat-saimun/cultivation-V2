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
                        $isHome = request()->routeIs('resultPart');
                        $marksRoutes = ['addMarks'];
                        $resultRoutes = ['addMarks','createMarksheet','allMarksheet'];
                        $classRoutes = ['allClasses','createClass'];
                        $deptRoutes = ['allDepartment','createDepartment'];
                        $sectionRoutes = ['allSection','createSection'];
                        $sessionRoutes = ['allSession','createSession'];
                        $subjectRoutes = ['allSubject','createSubject'];
                        $examRoutes = ['allExam','createExam','admitCard','attendSheet'];

                        $resultOpen = request()->routeIs($resultRoutes);
                        $classOpen = request()->routeIs($classRoutes);
                        $deptOpen = request()->routeIs($deptRoutes);
                        $sectionOpen = request()->routeIs($sectionRoutes);
                        $sessionOpen = request()->routeIs($sessionRoutes);
                        $subjectOpen = request()->routeIs($subjectRoutes);
                        $examOpen = request()->routeIs($examRoutes);

                        $loginUser = \App\Models\CultivationAdmin::find(session('cultivationAdmin'));
                        $userType = $loginUser['userType'] ?? null;
                    @endphp
                    <ul class="nav nav-sidebar-menu sidebar-toggle-view">
                        @if($userType == 1)
                            <li class="nav-item sidebar-nav-item open" data-group="result-marks">
                                <a href="#" class="nav-link active"><i class="flaticon-books"></i><span>Marks & Attendance</span></a>
                                <ul class="nav sub-group-menu" style="display:block;">
                                    <li class="nav-item">
                                        <a href="{{ route('addMarks') }}" class="nav-link {{ request()->routeIs('addMarks') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Add Marks</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('attendanceIndex') }}" class="nav-link {{ request()->routeIs('attendanceIndex') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Attendance</a>
                                    </li>
                                </ul>
                            </li>
                        @else
                            <li class="nav-item">
                                <a href="{{ route('cultivationIndex') }}" class="nav-link {{ request()->routeIs('cultivationIndex') ? 'active' : '' }}"><i class="flaticon-dashboard"></i><span>Cultivation Panel</span></a>
                            </li>
                            <li class="nav-item sidebar-nav-item {{ $resultOpen ? 'open' : '' }}" data-group="result-core">
                                <a href="#" class="nav-link {{ $resultOpen ? 'active' : '' }}"><i class="flaticon-books"></i><span>Result</span></a>
                                <ul class="nav sub-group-menu" style="{{ $resultOpen ? 'display:block;' : '' }}">
                                    <li class="nav-item">
                                        <a href="{{ route('addMarks') }}" class="nav-link {{ request()->routeIs('addMarks') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Add Marks</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('createMarksheet') }}" class="nav-link {{ request()->routeIs('createMarksheet') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Marksheet Generate</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('allMarksheet') }}" class="nav-link {{ request()->routeIs('allMarksheet') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Marksheet</a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item sidebar-nav-item {{ $classOpen ? 'open' : '' }}" data-group="result-class">
                                <a href="#" class="nav-link {{ $classOpen ? 'active' : '' }}"><i class="flaticon-maths-class-materials-cross-of-a-pencil-and-a-ruler"></i><span>Class</span></a>
                                <ul class="nav sub-group-menu" style="{{ $classOpen ? 'display:block;' : '' }}">
                                    <li class="nav-item">
                                        <a href="{{ route('allClasses') }}" class="nav-link {{ request()->routeIs('allClasses') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>All Classes</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('createClass') }}" class="nav-link {{ request()->routeIs('createClass') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Add New Class</a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item sidebar-nav-item {{ $deptOpen ? 'open' : '' }}" data-group="result-dept">
                                <a href="#" class="nav-link {{ $deptOpen ? 'active' : '' }}"><i class="flaticon-maths-class-materials-cross-of-a-pencil-and-a-ruler"></i><span>Department</span></a>
                                <ul class="nav sub-group-menu" style="{{ $deptOpen ? 'display:block;' : '' }}">
                                    <li class="nav-item">
                                        <a href="{{ route('allDepartment') }}" class="nav-link {{ request()->routeIs('allDepartment') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>All Department</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('createDepartment') }}" class="nav-link {{ request()->routeIs('createDepartment') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Add New Department</a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item sidebar-nav-item {{ $sectionOpen ? 'open' : '' }}" data-group="result-section">
                                <a href="#" class="nav-link {{ $sectionOpen ? 'active' : '' }}"><i class="flaticon-maths-class-materials-cross-of-a-pencil-and-a-ruler"></i><span>Section</span></a>
                                <ul class="nav sub-group-menu" style="{{ $sectionOpen ? 'display:block;' : '' }}">
                                    <li class="nav-item">
                                        <a href="{{ route('allSection') }}" class="nav-link {{ request()->routeIs('allSection') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>All Section</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('createSection') }}" class="nav-link {{ request()->routeIs('createSection') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Add New Section</a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item sidebar-nav-item {{ $sessionOpen ? 'open' : '' }}" data-group="result-session">
                                <a href="#" class="nav-link {{ $sessionOpen ? 'active' : '' }}"><i class="flaticon-maths-class-materials-cross-of-a-pencil-and-a-ruler"></i><span>Session</span></a>
                                <ul class="nav sub-group-menu" style="{{ $sessionOpen ? 'display:block;' : '' }}">
                                    <li class="nav-item">
                                        <a href="{{ route('allSession') }}" class="nav-link {{ request()->routeIs('allSession') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>All Session</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('createSession') }}" class="nav-link {{ request()->routeIs('createSession') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Add New</a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item sidebar-nav-item {{ $subjectOpen ? 'open' : '' }}" data-group="result-subject">
                                <a href="#" class="nav-link {{ $subjectOpen ? 'active' : '' }}"><i class="flaticon-open-book"></i><span>Subject</span></a>
                                <ul class="nav sub-group-menu" style="{{ $subjectOpen ? 'display:block;' : '' }}">
                                    <li class="nav-item">
                                        <a href="{{ route('allSubject') }}" class="nav-link {{ request()->routeIs('allSubject') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>All Subject</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('createSubject') }}" class="nav-link {{ request()->routeIs('createSubject') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Add New Subject</a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item sidebar-nav-item {{ $examOpen ? 'open' : '' }}" data-group="result-exam">
                                <a href="#" class="nav-link {{ $examOpen ? 'active' : '' }}"><i class="flaticon-shopping-list"></i><span>Exam</span></a>
                                <ul class="nav sub-group-menu" style="{{ $examOpen ? 'display:block;' : '' }}">
                                    <li class="nav-item">
                                        <a href="{{ route('allExam') }}" class="nav-link {{ request()->routeIs('allExam') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Exam Schedule</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('createExam') }}" class="nav-link {{ request()->routeIs('createExam') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Create Exam</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('admitCard') }}" class="nav-link {{ request()->routeIs('admitCard') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Admit Card</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('attendSheet') }}" class="nav-link {{ request()->routeIs('attendSheet') ? 'active' : '' }}"><i class="fas fa-angle-right"></i>Attended Sheet</a>
                                    </li>
                                </ul>
                            </li>
                            <li class="nav-item sidebar-nav-item">
                                <a href="#" class="nav-link"><i class="flaticon-checklist"></i><span>Grade Point</span></a>
                                <ul class="nav sub-group-menu">
                                    <li class="nav-item">
                                        <a href="{{ route('allGrade') }}" class="nav-link"><i class="fas fa-angle-right"></i>G.P Manage</a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="{{ route('createGrade') }}" class="nav-link"><i class="fas fa-angle-right"></i>New G.P</a>
                                    </li>
                                </ul>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
            <!-- Sidebar Area End Here -->
            <div class="dashboard-content-one">
                <!-- Breadcubs Area Start Here -->
                <div class="breadcrumbs-area d-print-none">
                    <h3>Result Management Panel</h3>
                    <ul>
                        <li>
                            <a href="{{ route('resultPart') }}">Home</a>
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
    <script>
        $(document).ready(function() {
            $(".alert").fadeTo(2000, 500).slideUp(500, function() {
                $(".alert").slideUp(500);
            });
            // Persist open groups for Result sidebar
            const storageKey = 'resultSidebarOpenGroups';
            let openGroups = JSON.parse(localStorage.getItem(storageKey) || '[]');
            function save(){ localStorage.setItem(storageKey, JSON.stringify(openGroups)); }
            function apply(){
                openGroups.forEach(id=>{
                    const li = document.querySelector('li.sidebar-nav-item[data-group="'+id+'"]');
                    if(li){
                        li.classList.add('open');
                        const sub = li.querySelector('ul.sub-group-menu');
                        if(sub) sub.style.display = 'block';
                        const a = li.querySelector('> a.nav-link');
                        if(a) a.classList.add('active');
                    }
                });
            }
            const groups = document.querySelectorAll('li.sidebar-nav-item');
            groups.forEach((li, idx)=>{
                if(!li.dataset.group) li.dataset.group = 'rg'+idx;
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
            // record already-open groups
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

</body>
</html>