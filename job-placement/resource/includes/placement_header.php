<?php require_once("placement_link.php"); 
	 if(isset($logout)){
	 	session_start();
	 	session_destroy();
		echo "<script type='text/javascript'>   
		function Redirect(){  
			window.location='login.php'; 
		}  
		setTimeout('Redirect()', 0);   
		</script>";
	 }
?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>BSIEDU | Job Placement Information</title>
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
	<script src="<?php echo $rich_url; ?>/ckeditor.js"></script>
	<script src="<?php echo $rich_url; ?>/js/sample.js"></script>
    <!-- Favicon-->
    <link rel="shortcut icon" href="<?php echo $media_url; ?>/favicon.ico">
    <!-- Tweaks for older IEs--><!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
        <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script><![endif]-->
  </head>
  
  <body>
  <div class="page">
      <!-- Main Navbar-->
      <header class="header">
        <nav class="navbar">
          <!-- Search Box-->
          <div class="search-box">
            <button class="dismiss"><i class="icon-close"></i></button>
            <form id="searchForm" action="#" role="search">
              <input type="search" placeholder="What are you looking for..." class="form-control">
            </form>
          </div>
          <div class="container-fluid">
            <div class="navbar-holder d-flex align-items-center justify-content-between">
              <!-- Navbar Header-->
              <div class="navbar-header">
                <!-- Navbar Brand --><a href="index.html" class="navbar-brand">
                  <div class="brand-text brand-big"><span><?php echo $website_name; ?></span></div>
                  <div class="brand-text brand-small"><strong>BD</strong></div></a><a id="toggle-btn" href="#" class="menu-btn active"><span></span><span></span><span></span></a>
              </div>
            </div>
          </div>
        </nav>
      </header>