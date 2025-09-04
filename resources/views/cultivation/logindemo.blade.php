<!doctype html>
<html class="no-js" lang="">


<!-- Mirrored from www.radiustheme.com/demo/html/psdboss/akkhor/akkhor/login.html by HTTrack Website Copier/3.x [XR&CO'2014], Tue, 17 Oct 2023 05:49:51 GMT -->
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <title>Cultivation | Login</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Favicon -->
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('/public/back-office/img/') }}/favicon.png">
    <!-- Normalize CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/css/') }}/normalize.css">
    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/css/') }}/main.css">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/css/') }}/bootstrap.min.css">
    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/css/') }}/all.min.css">
    <!-- Flaticon CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/fonts/') }}/flaticon.css">
    <!-- Animate CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/css/') }}/animate.min.css">
    <script src="https://kit.fontawesome.com/32dcd4a478.js" crossorigin="anonymous"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="{{ asset('/public/back-office/') }}/style.css">
    <!-- Modernize js -->
    <script src="{{ asset('/public/back-office/js/') }}/modernizr-3.6.0.min.js"></script>
    <style>
        .logosize{
                width:180px;
            }
    </style>
</head>

<body>
    <!-- Preloader Start Here -->
    <div id="preloader"></div>
    <!-- Preloader End Here -->
    <!-- Login Page Start Here -->
    <div class="row login-page-wrap py-4">
        <div class="col-8 mx-auto login-page-content">
            <div class="card card-body login-box">
                <div class="item-logo">
                    <img src="{{ asset('/public/back-office/img/') }}/logo3.png" class="logosize" alt="logo">
                </div>
                <div class="row">
                    <div class="col-12">
                        @if(session()->has('success'))
                            <div class="alert alert-success w-100">
                                {{ session()->get('success') }}
                            </div>
                        @endif
                        @if(session()->has('error'))
                            <div class="alert alert-danger w-100">
                                {{ session()->get('error') }}
                            </div>
                        @endif
                    </div>
                </div>
                @if($cultivation->count()>0)                
                <form action="{{ route('cultivationLogin') }}" class="login-form" method="POST">
                    @csrf
                    <div class="form-group">
                        <label><i class="far fa-envelope"></i> Username</label>
                        <input type="text" placeholder="Enter usrename" class="form-control" name="cultivationUser">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" placeholder="Enter password" class="form-control" name="cultivationPass">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="login-btn">Login</button>
                    </div>
                </form>
                @else
                <form action="{{ route('adminRegister') }}" class="login-form" method="POST">
                    @csrf
                    <div class="row">
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label>Admin Name</label>
                                <input type="text" placeholder="Enter admin name" class="form-control" name="adminName">
                                <i class="far fa-user"></i>
                            </div>
                            <div class="form-group">
                                <label>Admin Email</label>
                                <input type="text" placeholder="Enter admin email" class="form-control" name="adminEmail">
                                <i class="far fa-envelope"></i>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="login-btn">
                                <span class="fa-solid fa-right-to-bracket"></span> Register</button>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="form-group">
                                <label>Username</label>
                                <input type="text" placeholder="Enter usrename" class="form-control" name="cultivationUser">
                                <i class="far fa-key"></i>
                            </div>
                            <div class="form-group">
                                <label>Password</label>
                                <input type="password" placeholder="Enter admin password" class="form-control" name="cultivationPass">
                                <i class="fas fa-lock"></i>
                            </div>
                        </div>
                    </div>
                </form>
                @endif
            </div>
        </div>
    </div>
    <!-- Login Page End Here -->
    <!-- jquery-->
    <script src="{{ asset('/public/back-office/js/') }}/jquery-3.3.1.min.js"></script>
    <!-- Plugins js -->
    <script src="{{ asset('/public/back-office/js/') }}/plugins.js"></script>
    <!-- Popper js -->
    <script src="{{ asset('/public/back-office/js/') }}/popper.min.js"></script>
    <!-- Bootstrap js -->
    <script src="{{ asset('/public/back-office/js/') }}/bootstrap.min.js"></script>
    <!-- Scroll Up Js -->
    <script src="{{ asset('/public/back-office/js/') }}/jquery.scrollUp.min.js"></script>
    <!-- Custom Js -->
    <script src="{{ asset('/public/back-office/js/') }}/main.js"></script>

</body>
</html>