<?php require_once("resource/includes/placement_link.php"); ?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>BD Survey admin register</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="all,follow">
    <!-- Bootstrap CSS-->
    <link rel="stylesheet" href="<?php echo $vendor_url; ?>/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome CSS-->
    <link rel="stylesheet" href="<?php echo $vendor_url; ?>/font-awesome/css/font-awesome.min.css">
    <!-- Fontastic Custom icon font-->
    <link rel="stylesheet" href="<?php echo $css_url; ?>/fontastic.css">
    <!-- Google fonts - Poppins -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins:300,400,700">
    <!-- theme stylesheet-->
    <link rel="stylesheet" href="<?php echo $css_url; ?>/style.default.css" id="theme-stylesheet">
    <!-- Custom stylesheet - for your changes-->
    <link rel="stylesheet" href="<?php echo $css_url; ?>/custom.css">
    <!-- Favicon-->
    <link rel="shortcut icon" href="<?php echo $media_url; ?>/favicon.ico">
    <!-- Tweaks for older IEs--><!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script><![endif]-->
  </head>
  
  <body>
    <div class="page login-page">
      <div class="container d-flex align-items-center">
        <div class="form-holder has-shadow">
          <div class="row">
            <!-- Logo & Information Panel-->
            <div class="col-lg-6">
              <div class="info d-flex align-items-center  text-center">
                <div class="content">
                  <div class="logo">
                    <h1><?php echo $website_name;?></h1>
                  </div>
                  <p><?php echo $website_title;?></p>
                </div>
              </div>
            </div>
            <!-- Form Panel    -->
            <div class="col-lg-6 bg-white">
              <div class="form d-flex align-items-center">
                <div class="content">
			<?php if(isset($panel_register)){
						$conv_pass=$obj->passCreate($loginPassword);
						$check_user_info=$obj->fixed_data("admin_panel","login_id='$loginUsername' && login_password='$conv_pass'");
						if($check_user_info==1){
							echo "<div class='alert alert-danger alert-dismissible fade show text-center' role='alert'>
							  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
							    <span aria-hidden='true'>&times;</span>
							  </button>
							  Userid or Email already exist on our records.
							</div>";
						}else{
						    $store_data=$obj->add_data("placement_admin","login_id,login_password","'$loginUsername','$conv_pass'");
						    if($store_data==1):
    							echo "<div class='alert alert-success'>
    							  <i class='fa fa-check text-white bg-success p-1 rounded-circle'></i> User created successfully....
    							</div>";
    							echo "<script type='text/javascript'>   
    								function Redirect(){  
    									window.location='$server_url/admin_register.php'; 
    								}  
    								setTimeout('Redirect()', 0);   
    								</script>";
    						else:
    							echo "<div class='alert alert-warning'>
    							  <i class='fa fa-times text-white bg-danger p-1 rounded-circle'></i> User created failed....
    							</div>";
    						endif;
						}
					} ?>
                <h2 class="mb-4">Admin Panel Register</h2>
                  <form id="login-form" method="post">
                    <div class="form-group">
                      <input id="login-username" type="text" name="loginUsername" required="" class="input-material">
                      <label for="login-username" class="label-material">User Name</label>
                    </div>
                    <div class="form-group">
                      <input id="login-password" type="password" name="loginPassword" required="" class="input-material">
                      <label for="login-password" class="label-material">Password</label>
                    </div><input type="submit" name="panel_register" value="Register" class="btn btn-primary">
                  </form>
                </div>
              </div>
            </div>
            <div class="col-12 text-center bg-white p-2">
            <p>Design by <a href="https://www.virtualitprofessional.net/" target="_blank" class="external">Virtual IT Professional</a></p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- Javascript files-->
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="<?php echo $vendor_url; ?>/popper.js/umd/popper.min.js"> </script>
    <script src="<?php echo $vendor_url; ?>/bootstrap/js/bootstrap.min.js"></script>
    <script src="<?php echo $vendor_url; ?>/jquery.cookie/jquery.cookie.js"> </script>
    <script src="<?php echo $vendor_url; ?>/chart.js/Chart.min.js"></script>
    <script src="<?php echo $vendor_url; ?>/jquery-validation/jquery.validate.min.js"></script>
    <script src="<?php echo $js_url; ?>/charts-home.js"></script>
    <!-- Main File-->
    <script src="<?php echo $js_url; ?>/front.js"></script>
  </body>
</html>