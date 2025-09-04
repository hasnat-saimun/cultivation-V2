<!DOCTYPE html>
<html lang="zxx">
    <!-- Mirrored from themeknit.com/demo/html/authfy/demo/login-07.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 03 Sep 2025 06:30:00 GMT -->
    <head>
        <!-- Basic Page Needs
  ================================================== -->
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <!-- Mobile Specific Metas
  ================================================== -->
        <meta name="viewport" content="width=device-width, initial-scale=1" />

        <!-- For Search Engine Meta Data  -->
        <meta name="description" content="" />
        <meta name="keywords" content="" />
        <meta name="author" content="yoursite.com" />

        <title>Cultivation | Login</title>

        <!-- Favicon -->
        <link rel="shortcut icon" type="image/icon" href="{{ asset('/public/loginPart/themeknit/images') }}/favicon-16x16.html" />

        <!-- Main structure css file -->
        <link rel="stylesheet" href="{{ asset('/public/loginPart/themeknit/css') }}/login7-style.css" />

        <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if IE]>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.3/html5shiv.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->
    </head>

    <body>
        <!-- Start Preloader -->
        <div id="preload-block">
            <div class="square-block"></div>
        </div>
        <!-- Preloader End -->

        <div class="container-fluid">
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
            <div class="row">
                
                <div class="authfy-container col-xs-12 col-sm-10 col-md-8 col-lg-6 col-sm-offset-1 col-md-offset-2 col-lg-offset-3">
                    <div class="col-sm-5 authfy-panel-left">
                        <div class="brand-col">
                            <div class="headline">
                                <!-- brand-logo start -->
                                <div class="brand-logo">
                                    <img src="{{ asset('/public/loginPart/themeknit/images') }}/logo1.png" width="150" alt="brand-logo" />
                                </div>
                                <!-- ./brand-logo -->
                                <p>Login using social media to get quick access</p>
                                <!-- social login buttons start -->
                                <div class="row social-buttons">
                                    <div class="col-xs-4 col-sm-4 col-md-12">
                                        <a href="#" class="btn btn-block btn-facebook"> <i class="fa fa-facebook"></i> <span class="hidden-xs hidden-sm">Signin with facebook</span> </a>
                                    </div>
                                    <div class="col-xs-4 col-sm-4 col-md-12">
                                        <a href="#" class="btn btn-block btn-twitter"> <i class="fa fa-twitter"></i> <span class="hidden-xs hidden-sm">Signin with twitter</span> </a>
                                    </div>
                                    <div class="col-xs-4 col-sm-4 col-md-12">
                                        <a href="#" class="btn btn-block btn-google"> <i class="fa fa-google-plus"></i> <span class="hidden-xs hidden-sm">Signin with google</span> </a>
                                    </div>
                                </div>
                                <!-- ./social-buttons -->
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-7 authfy-panel-right">
                        <!-- authfy-login start -->
                        <div class="authfy-login">
                            <!-- panel-login start -->
                            <div class="authfy-panel panel-login text-center active">
                                <div class="authfy-heading">
                                    <h3 class="auth-title">Login to your account</h3>
                                    <p>Don’t have an account? <a class="lnk-toggler" data-panel=".panel-signup" href="#">Sign Up Free!</a></p>
                                </div>
                                <div class="row">
                                @if($cultivation->count()>0) 
                                    <div class="col-xs-12 col-sm-12">                
                                        <form action="{{ route('cultivationLogin') }}" class="login-form" method="POST">
                                            @csrf
                                            <div class="form-group wrap-input">
                                                <input type="text" class="form-control email" name="cultivationUser" placeholder="Enter the user name" />
                                                <span class="focus-input"></span>
                                            </div>
                                            <div class="form-group wrap-input">
                                                <div class="pwdMask">
                                                    <input type="cultivationPass" class="form-control password" name="cultivationPass" placeholder="Password" />
                                                    <span class="focus-input"></span>
                                                    <span class="fa fa-eye-slash pwd-toggle"></span>
                                                </div>
                                            </div>
                                            <!-- start remember-row -->
                                            <div class="row remember-row">
                                                <div class="col-xs-6 col-sm-6">
                                                    <label class="checkbox text-left">
                                                        <input type="checkbox" value="remember-me" />
                                                        <span class="label-text">Remember me</span>
                                                    </label>
                                                </div>
                                                <div class="col-xs-6 col-sm-6">
                                                    <p class="forgotPwd">
                                                        <a class="lnk-toggler" data-panel=".panel-forgot" href="#">Forgot password?</a>
                                                    </p>
                                                </div>
                                            </div>
                                            <!-- ./remember-row -->
                                            <div class="form-group">
                                                <button class="btn btn-lg btn-primary btn-block" type="submit">Login with email</button>
                                            </div>
                                        </form>
                                    @else
                                    </div>
                                </div>
                            </div>
                            <!-- ./panel-login -->
                            <!-- panel-signup start -->
                            <div class="authfy-panel panel-signup text-center">
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12">
                                        <div class="authfy-heading">
                                            <h3 class="auth-title">Sign up for free!</h3>
                                        </div>
                                        <form action="{{ route('adminRegister') }}" class="login-form" method="POST">
                                        @csrf
                                            <div class="form-group wrap-input">
                                                <input type="text" class="form-control" name="adminName" placeholder="Admin name" />
                                                <span class="focus-input"></span>
                                            </div>
                                            <div class="form-group wrap-input">
                                                <input type="email" class="form-control" name="username" placeholder="Email address" />
                                                <span class="focus-input"></span>
                                            </div>
                                            <div class="form-group wrap-input">
                                                <input type="text" class="form-control" name="cultivationUser" placeholder="User name" />
                                                <span class="focus-input"></span>
                                            </div>
                                            <div class="form-group wrap-input">
                                                <div class="pwdMask">
                                                    <input type="password" class="form-control" name="cultivationPass" placeholder="Password" />
                                                    <span class="focus-input"></span>
                                                    <span class="fa fa-eye-slash pwd-toggle"></span>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <p class="term-policy text-muted small">I agree to the <a href="#">privacy policy</a> and <a href="#">terms of service</a>.</p>
                                            </div>
                                            <div class="form-group">
                                                <button class="btn btn-lg btn-primary btn-block" type="submit">Sign up with email</button>
                                            </div>
                                        </form>
                                    @endif
                                        <a class="lnk-toggler" data-panel=".panel-login" href="#">Already have an account?</a>
                                    </div>
                                </div>
                            </div>
                            <!-- ./panel-signup -->
                            <!-- panel-forget start -->
                            <div class="authfy-panel panel-forgot">
                                <div class="row">
                                    <div class="col-xs-12 col-sm-12">
                                        <div class="authfy-heading">
                                            <h3 class="auth-title">Recover your password</h3>
                                            <p>Fill in your e-mail address below and we will send you an email with further instructions.</p>
                                        </div>
                                        <form name="forgetForm" class="forgetForm" action="#" method="POST">
                                            <div class="form-group wrap-input">
                                                <input type="email" class="form-control" name="username" placeholder="Email address" />
                                                <span class="focus-input"></span>
                                            </div>
                                            <div class="form-group">
                                                <button class="btn btn-lg btn-primary btn-block" type="submit">Recover your password</button>
                                            </div>
                                            <div class="form-group">
                                                <a class="lnk-toggler" data-panel=".panel-login" href="#">Already have an account?</a>
                                            </div>
                                            <div class="form-group">
                                                <a class="lnk-toggler" data-panel=".panel-signup" href="#">Don’t have an account?</a>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!-- ./panel-forgot -->
                        </div>
                        <!-- ./authfy-login -->
                    </div>
                </div>
            </div>
            <!-- ./row -->
        </div>
        <!-- ./container -->

        <!-- Javascript Files -->

        <!-- initialize jQuery Library -->
        <script src="{{ asset('/public/loginPart/themeknit/js') }}/jquery-2.2.4.min.js"></script>

        <!-- for Bootstrap js -->
        <script src="{{ asset('/public/loginPart/themeknit/js') }}/bootstrap.min.js"></script>

        <!-- Custom js-->
        <script src="{{ asset('/public/loginPart/themeknit/js') }}/custom.js"></script>
    </body>

    <!-- Mirrored from themeknit.com/demo/html/authfy/demo/login-07.html by HTTrack Website Copier/3.x [XR&CO'2014], Wed, 03 Sep 2025 06:30:03 GMT -->
</html>
