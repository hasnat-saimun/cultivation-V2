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
        <meta name="author" content="cultivationapp.com" />

        <title>Cultivation | Login</title>

        <!-- Favicon -->
        <link rel="shortcut icon" type="image/icon" href="{{ asset('/public/loginPart/themeknit/images') }}/favicon-16x16.html" />

        <!-- Main structure css file -->
        <link rel="stylesheet" href="{{ asset('/public/loginPart/themeknit/css') }}/login7-style.css" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  

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
            <div class="row d-flex align-items-center vh-100">
                <div class="authfy-container col-xs-12 col-sm-10 col-md-8 col-lg-6 col-sm-offset-1 col-md-offset-2 col-lg-offset-3">
                    <div class="col-sm-5 authfy-panel-left">
                        <div class="brand-col">
                            <div class="headline">
                                <!-- brand-logo start -->
                                <div class="brand-logo">
                                    <img src="{{ asset('/public/loginPart/themeknit/images') }}/logo1.png" width="150" alt="brand-logo" />
                                </div>
                                <!-- ./brand-logo -->
                                <h4>Let's get you logged in with cultivation and check your progress</h4>
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
                                </div>
                                <div class="row">
                                    @if($cultivation->count()>0) 
                                        <div class="col-xs-12 col-sm-12">                
                                            <form action="{{ route('cultivationLogin') }}" class="login-form" method="POST">
                                                @csrf
                                                <div class="form-group wrap-input">
                                                    <input type="text" class="form-control " name="cultivationUser" placeholder="Enter the user name" />
                                                    <span class="focus-input"></span>
                                                </div>
                                                <div class="form-group wrap-input">
                                                    <div class="pwdMask">
                                                        <input type="password" class="form-control password" name="cultivationPass" placeholder="Password" />
                                                        <span class="focus-input"></span>
                                                        <span class="fa fa-eye-slash pwd-toggle"></span>
                                                    </div>
                                                </div>
                                                <div class="form-group mt-5">
                                                    <button class="btn btn-lg btn-primary btn-block" type="submit">Login</button>
                                                </div>
                                            </form>
                                        </div>
                                        
                                </div>
                            </div>
                            <!-- ./panel-login -->
                            <!-- panel-signup start -->
                            <div class="authfy-panel panel-signup text-center">
                                @else
                                <form action="{{ route('adminRegister') }}" class="row" method="POST">
                                        <div class="authfy-heading">
                                            <h3 class="auth-title">Get Started with cultivation</h3>
                                        </div>
                                        @csrf
                                        <div class="col-6">
                                            <div class="form-group wrap-input">
                                                <input type="text" class="form-control" name="adminName" placeholder="Admin name" />
                                                <span class="focus-input"></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group wrap-input">
                                                <input type="email" class="form-control" name="username" placeholder="Email address" />
                                                <span class="focus-input"></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group wrap-input">
                                                <input type="text" class="form-control" name="cultivationUser" placeholder="User name" />
                                                <span class="focus-input"></span>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="form-group wrap-input">
                                                <div class="pwdMask">
                                                    <input type="password" class="form-control" name="cultivationPass" placeholder="Password" />
                                                    <span class="focus-input"></span>
                                                    <span class="fa fa-eye-slash pwd-toggle"></span>
                                                </div>
                                            </div>
                                        </div>
                                                <div class="form-group">
                                                    <button class="btn btn-lg btn-primary btn-block" type="submit">Get Register</button>
                                                </div>
                                    </form>
                                </div>
                                @endif  
                            </div>
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
