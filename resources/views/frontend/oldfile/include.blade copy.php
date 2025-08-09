<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sonar Bangla College | @yield('fronttitle')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href="{{ asset('/public/') }}/assets/css/custom.css" rel="stylesheet">
    <link href="{{ asset('/public/') }}/assets/css/style.css" rel="stylesheet">

    <!-- Owl Carousel Assets -->
    <link href="{{ asset('/public/') }}/owl-carousel/owl.carousel.css" rel="stylesheet">
    <link href="{{ asset('/public/') }}/owl-carousel/owl.theme.css" rel="stylesheet">

    <!-- Prettify -->
    <link href="{{ asset('/public/') }}/assets/js/google-code-prettify/prettify.css" rel="stylesheet">
    <!-- font awesome kit setup -->
    <script src="https://kit.fontawesome.com/32dcd4a478.js" crossorigin="anonymous"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Roboto&family=Skranji&display=swap" rel="stylesheet">
  </head>
  <body>
    <div class="top-bar">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-12 col-md-2 text-end">
            <p class="mb-0"><i class="fa-solid fa-phone"></i> 01234567890</p>
          </div>
          <div class="col-12 col-md-3">
            <p class="mb-0"><i class="fa-solid fa-envelope"></i> info@sbccumilla.edu.bd</p>
          </div>
          <div class="col-12 col-md-7 text-end">
            <a href="#" class="mb-0 btn btn-success mt-2 mb-2"><i class="fa-solid fa-user"></i> Login/Register</a>
          </div>
        </div>
      </div>
    </div>
    <div class="menubar">
        <div class="container">
            <div class="row align-items-center p-2">
                <div class="col-12 col-md-2 text-center">
                    <img class="w-100" src="{{ asset('/public/') }}/img/sbcWhiteLogo.png" alt="SBC">
                </div>
                <div class="col-12 col-md-8">
                    <nav class="navbar bg-success navbar-expand-lg navbar-light" data-bs-theme="dark">
                        <div class="container-fluid">
                            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                            </button>
                            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                                <ul class="navbar-nav text-white sbc">
                                    <li class="nav-item">
                                        <a class="nav-link active" aria-current="page" href="#">Home</a>
                                    </li>
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Institute
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">About Us</a></li>
                                            <li><a class="dropdown-item" href="#">Principal Speech</a></li>
                                            <li><a class="dropdown-item" href="#">EX-Principals</a></li>
                                            <li><a class="dropdown-item" href="#">Our Teacher</a></li>
                                            <li><a class="dropdown-item" href="#">Staff Corner</a></li>
                                            <li><a class="dropdown-item" href="#">Managing Comittee</a></li>
                                        </ul>
                                    </li>
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Academic
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="{{route('newSyllabus')}}">Syllabus</a></li>
                                            <li><a class="dropdown-item" href="#">Class Routine</a></li>
                                            <li><a class="dropdown-item" href="#">Semister Plans</a></li>
                                            <li><a class="dropdown-item" href="#">Exam Routine</a></li>
                                        </ul>
                                    </li>
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Result Archive
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">Internal Result</a></li>
                                            <li><a class="dropdown-item" href="#">Individual Result</a></li>
                                        </ul>
                                    </li>
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Job Placement
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">Placement Cell</a></li>
                                            <li><a class="dropdown-item" href="#">Job Needy Student</a></li>
                                            <li><a class="dropdown-item" href="#">Job Circular</a></li>
                                        </ul>
                                    </li>
                                    <li class="nav-item dropdown">
                                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                        Gallery
                                        </a>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="#">Photo Gallery</a></li>
                                            <li><a class="dropdown-item" href="#">Video Gallery</a></li>
                                        </ul>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" href="#">Support</a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="container bg-full pt-2 bg-white shadow mt-4 mb-4">
        @yield('sliderninfo')
        <div class="row align-items-center">
            <div class="col-12 mx-auto row">
                <div class="col-1 bg-success np-1 ml-2">Notice</div>
                <div class="col-11 latest-news np-1">
                    <marquee>
                        <ul>
                            <li><a href="#"><i class="fa-thin fa-hand-point-right"></i> Lorem ipsom dollar site is a common text in web design and development industry.</a></li>
                            <li><a href="#"><i class="fa-thin fa-hand-point-right"></i> Lorem ipsom dollar site is a common text in web design and development industry.</a></li>
                        </ul>
                    </marquee>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12 col-md-9 mb-4">
                @yield('frontcontent')
            </div>
        </div>
        <footer class="mt-4">
            <div class="row">
                <div class="col-12 col-md-5 mx-auto">
                    <h3>Contact Details</h3>
                    <p><i class="fa-solid fa-link"></i> www.sbccumilla.edu.bd</p>
                    <p><i class="fa-solid fa-phone-office"></i> 0123 4567 890</p>
                    <p><i class="fa-solid fa-envelopes"></i> sbccumilla@gmail.com</p>
                    <p><i class="fa-brands fa-square-whatsapp"></i> 0123 4567 890</p>
                    <p><i class="fa-brands fa-square-facebook"></i> Sonar Bangla College</p>
                    <p><i class="fa-solid fa-buildings"></i> Poyat, Gobindapur, Vorasar, Burichong, Cumilla</p>
                </div>
                <div class="col-12 col-md-5 mx-auto">
                    <h3>Google Map</h3>
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3658.720943010397!2d91.14681007428437!3d23.50655879809593!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3754796e7c90d6e3%3A0x210c98d19ee0bc9c!2z4Ka44KeH4Ka-4Kao4Ka-4KawIOCmrOCmvuCmguCmsuCmviDgppXgprLgp4fgppw!5e0!3m2!1sen!2suk!4v1695524774546!5m2!1sen!2suk" width="100%" height="300" class="rounded" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <div class="col-12 mt-2">
                    <img class="w-100" src="{{ asset('/public/') }}/img/footer_top_bg.png" alt="">
                </div>
            </div>
            <div class="p-2">
                <div class="row">
                    <div class="col-md-6 col-12">
                        <p><span class="fw-bold">Planning and Implementation:</span> Principal(SBC)</p>
                    </div>
                    <div class="col-md-6 col-12">
                        <p><span class="fw-bold">Powered By:</span> VITP IMS(Version 2.0.1) by Virtual IT Professional</p>
                    </div>
                    <div class="col-12">
                        <p class="fw-bold">Copyright &copy; 2000-2023 | All Rights Reserved SBC Cumilla</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>

<script src="{{ asset('/public/') }}/assets/js/jquery-1.9.1.min.js"></script> 
<script src="{{ asset('/public/') }}/owl-carousel/owl.carousel.js"></script>


<!-- Demo -->

<style>
#owl-demo .item{
    margin: 3px;
}
#owl-demo .item img{
    display: block;
    width: 100%;
    height: auto;
}
</style>


<script>
$(document).ready(function() {
  $("#owl-demo").owlCarousel({
    autoPlay: 3000,
    items : 4,
    itemsDesktop : [1199,3],
    itemsDesktopSmall : [979,3]
  });

});
</script>

    <script src="{{ asset('/public/') }}/assets/js/bootstrap-collapse.js"></script>
    <script src="{{ asset('/public/') }}/assets/js/bootstrap-transition.js"></script>
    <script src="{{ asset('/public/') }}/assets/js/bootstrap-tab.js"></script>

    <script src="{{ asset('/public/') }}/assets/js/google-code-prettify/prettify.js"></script>
    <script src="{{ asset('/public/') }}/assets/js/application.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
  </body>
</html>