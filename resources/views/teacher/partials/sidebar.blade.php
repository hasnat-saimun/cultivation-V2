<aside class="tp-sidebar" id="teacher-sidebar" aria-label="Teacher portal navigation">
    <div class="tp-brand">
        <img src="{{ asset('public/assets/images/logo.png') }}" alt="">
        <div><strong>Teacher Portal</strong><span>{{ $instituteName }}</span></div>
    </div>
    <nav class="tp-nav">
        <x-teacher.nav-item label="Dashboard" icon="⌂" :href="route('teacher.dashboard')" :active="request()->routeIs('teacher.dashboard')" />
        <x-teacher.nav-item label="Attendance" icon="✓" :href="route('teacher.attendance.index')" :active="request()->routeIs('teacher.attendance.*')" />
        <x-teacher.nav-item label="Results" icon="▤" :href="route('teacher.results.index')" :active="request()->routeIs('teacher.results.*')" />
        <x-teacher.nav-item label="My Classes" icon="◇" :href="route('teacher.classes.index')" :active="request()->routeIs('teacher.classes.*')" />
        <x-teacher.nav-item label="My Students" icon="♙" :href="route('teacher.students.index')" :active="request()->routeIs('teacher.students.*')" />
        <x-teacher.nav-item label="Routine" icon="▷" />
        <x-teacher.nav-item label="Profile" icon="○" :href="route('teacher.profile.show')" :active="request()->routeIs('teacher.profile.*') || request()->routeIs('teacher.password.*')" />
    </nav>
</aside>
