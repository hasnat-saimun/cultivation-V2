@extends('frontend.include')
@section('fronttitle')
Enter to learn & Leave to serve
@endsection
@section('sliderninfo')
    @include('frontend.sliderinfo')
@endsection
@section('sideinfo')
    @include('frontend.sideInfo')
@endsection

@section('frontcontent')

<div class="col-3 mx-auto">
                    @yield('sideinfo')
</div>
<div class="col-8 mx-auto">
    <div class="rowalign-items-center">
        <div class="col-12 mx-auto row">
            <div class="col-1 bg-success np-1 ml-2">Notice</div>
            <div class="col-11 latest-news np-1">
                <marquee>
                    <ul>
                        <li>
                            <a href="#"><i class="fa-thin fa-hand-point-right"></i> Lorem ipsom dollar site is a common text in web design and development industry.</a>
                        </li>
                        <li>
                            <a href="#"><i class="fa-thin fa-hand-point-right"></i> Lorem ipsom dollar site is a common text in web design and development industry.</a>
                        </li>
                    </ul>
                </marquee>
            </div>
        </div>

        <div class="col-12 mx-auto my-2">
            <h2>Welcome to SBC Cumilla</h2>
            <p class="text-justify">On the first day of July 2000 the Sonar Bangla College opened its doors to students with Sir P.J. Hartog as the first Vice-Chancellor of the University. The University was set up in a picturesque part of the city known as Ramna on 600 acres of land.The University started its activities with 3 Faculties,12 Departments, 60 teachers, 877 students and 3 dormitories (Halls of Residence) for the students. At present the University consists of 13 Faculties, 83 Departments, 12 Institutes, 20 residential halls, 3 hostels and more than 56 Research Centres. The number of students and teachers has risen to about 37018 and 1992 respectively.The main purpose of the University was to create new areas of knowledge and disseminate this knowledge to the society through its students. Since its inception the University has a distinct character of having distinguished scholars as faculties who have enriched the global pool of knowledge by making notable contributions in the fields of teaching and <a href="#">Readmore</a></p>
        </div>
        <!-- mission & vission -->
        <div class="col-12 mx-auto my-4">
            <div class="card rounded-0">
                <div class="card-header rounded-0 bg-success text-white h5">
                    Mission & Vission
                </div>
                <div class="card-body">
                    <figure class="text-center">
                        <blockquote class="blockquote">
                            <p class="h4">The number of students and teachers has risen to about 37018 and 1992 respectively.The main purpose of the University was to create new areas of knowledge and disseminate this knowledge to the society through its students.</p>
                        </blockquote>
                        <figcaption class="blockquote-footer">
                            Abu Saleque Md. Selim Reza Shourav <cite title="Source Title">, Principal</cite>
                        </figcaption>
                    </figure>
                    <div class="alert alert-success">Since its inception the University has a distinct character of having distinguished scholars as faculties</div>
                </div>
            </div>
        </div>

        <!-- Notice board start here -->
        <div class="col-12 mx-auto mb-4">
            <h2>Leatest Notice</h2>
            <div class="bg-success p-2 notice-box my-2">
                <div class="row align-items-center">
                    <div class="col-2 mx-auto date-box"><span><i class="fa-thin fa-calendar"></i></span> 20th September</div>
                    <div class="col-8 mx-auto">বঙ্গবন্ধু শেখ মুজিব স্কলার” নির্বাচন ও অ্যাওয়ার্ড প্রদান-২০২৩ এর জন্য আবেদন করার বিজ্ঞপ্তি</div>
                    <div class="col-1 mx-auto download"><i class="fa-light fa-down-to-bracket"></i></div>
                </div>
            </div>
            <div class="bg-success p-2 notice-box my-2">
                <div class="row align-items-center">
                    <div class="col-2 mx-auto date-box"><span><i class="fa-thin fa-calendar"></i></span> 20th September</div>
                    <div class="col-8 mx-auto">বঙ্গবন্ধু শেখ মুজিব স্কলার” নির্বাচন ও অ্যাওয়ার্ড প্রদান-২০২৩ এর জন্য আবেদন করার বিজ্ঞপ্তি</div>
                    <div class="col-1 mx-auto download"><i class="fa-light fa-down-to-bracket"></i></div>
                </div>
            </div>
            <div class="bg-success p-2 notice-box my-2">
                <div class="row align-items-center">
                    <div class="col-2 mx-auto date-box"><span><i class="fa-thin fa-calendar"></i></span> 20th September</div>
                    <div class="col-8 mx-auto">বঙ্গবন্ধু শেখ মুজিব স্কলার” নির্বাচন ও অ্যাওয়ার্ড প্রদান-২০২৩ এর জন্য আবেদন করার বিজ্ঞপ্তি</div>
                    <div class="col-1 mx-auto download"><i class="fa-light fa-down-to-bracket"></i></div>
                </div>
            </div>
            <div class="bg-success p-2 notice-box my-2">
                <div class="row align-items-center">
                    <div class="col-2 mx-auto date-box"><span><i class="fa-thin fa-calendar"></i></span> 20th September</div>
                    <div class="col-8 mx-auto">বঙ্গবন্ধু শেখ মুজিব স্কলার” নির্বাচন ও অ্যাওয়ার্ড প্রদান-২০২৩ এর জন্য আবেদন করার বিজ্ঞপ্তি</div>
                    <div class="col-1 mx-auto download"><i class="fa-light fa-down-to-bracket"></i></div>
                </div>
            </div>
            <div class="bg-success p-2 notice-box my-2">
                <div class="row align-items-center">
                    <div class="col-2 mx-auto date-box"><span><i class="fa-thin fa-calendar"></i></span> 20th September</div>
                    <div class="col-8 mx-auto">বঙ্গবন্ধু শেখ মুজিব স্কলার” নির্বাচন ও অ্যাওয়ার্ড প্রদান-২০২৩ এর জন্য আবেদন করার বিজ্ঞপ্তি</div>
                    <div class="col-1 mx-auto download"><i class="fa-light fa-down-to-bracket"></i></div>
                </div>
            </div>
            <a href="#" class="btn btn-primary rounded-0">All Notice</a>
        </div>
        <div class="row g-0">
            <div class="col-12 mx-auto my-4 row">
                <!-- info box start here -->
                <div class="col-12 col-md-6 mx-auto my-4 infobox">
                    <div class="card rounded-0">
                        <div class="card-header rounded-0 bg-success text-white h5">
                            Admission Info
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-3">
                                    <img src="{{ asset('/public/') }}/img/forms.jpg" class="w-100" alt="Institute">
                                </div>
                                <div class="col-12 col-md-9">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> Honors Admission</li>
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> XI Class Admission</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 mx-auto my-4 infobox">
                    <div class="card rounded-0">
                        <div class="card-header rounded-0 bg-success text-white h5">
                            Institute Info
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-3">
                                    <img src="{{ asset('/public/') }}/img/institute.jpg" class="w-100" alt="Institute">
                                </div>
                                <div class="col-12 col-md-9">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> About Us</li>
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> Teacher Database</li>
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> Staff Database</li>
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> Principal Speech</li>
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> Managing Comittee</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 mx-auto my-4 infobox">
                    <div class="card rounded-0">
                        <div class="card-header rounded-0 bg-success text-white h5">
                            Academic
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-3">
                                    <img src="{{ asset('/public/') }}/img/academic.png" class="w-100" alt="Institute">
                                </div>
                                <div class="col-12 col-md-9">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> Semister Plan</li>
                                        <li><a class="list-group-item" href="{{route('newSyllabus')}}">Syllabus</a>
                                        </li>
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> Class Routine</li>
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> Exam Routine</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 mx-auto my-4 infobox">
                    <div class="card rounded-0">
                        <div class="card-header rounded-0 bg-success text-white h5">
                            Student Corner
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-12 col-md-3">
                                    <img src="{{ asset('/public/') }}/img/studentCorner.png" class="w-100" alt="Institute">
                                </div>
                                <div class="col-12 col-md-9">
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> Student Database</li>
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> X-Student Archive</li>
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> Placement Cell</li>
                                        <li class="list-group-item"><i class="fa-regular fa-arrow-turn-down-right"></i> Job Seekers</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- carusel slider start -->
        <div id="demo" class="col-12 mx-auto mt-4">
            <h2>Photo Gallery</h2>
            <div id="owl-demo" class="owl-carousel">
                <div class="item"><img src="{{ asset('/public/') }}/img/campus.jpeg" alt="campus"></div>
                <div class="item"><img src="{{ asset('/public/') }}/img/mainbuilding.jpg" alt="main building"></div>
                <div class="item"><img src="{{ asset('/public/') }}/img/office.jpg" alt="office room"></div>
                <div class="item"><img src="{{ asset('/public/') }}/img/principalroom.jpg" alt="principal room"></div>
                <div class="item"><img src="{{ asset('/public/') }}/img/hostel.jpg" alt="hostel"></div>
                <div class="item"><img src="{{ asset('/public/') }}/img/auditoriam.jpg" alt="auditoriam"></div>
            </div>
        </div>
    </div>
</div>
@endsection