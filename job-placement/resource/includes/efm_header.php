<?php require_once("efm_link.php"); 
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
    <title><?php echo $website_name." | ".$website_title; ?></title>
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
                  <div class="brand-text brand-big"><span><?php echo $website_name; ?></span><strong> Admin Panel</strong></div>
                  <div class="brand-text brand-small"><strong>BD</strong></div></a>
                <!-- Toggle Button--><a id="toggle-btn" href="#" class="menu-btn active"><span></span><span></span><span></span></a>
              </div>
              <!-- Navbar Menu -->
              <ul class="nav-menu list-unstyled d-flex flex-md-row align-items-md-center">
                <!-- Search-->
                <li class="nav-item d-flex align-items-center"><a id="search" href="#"><i class="icon-search"></i></a></li>
                <!-- Notifications-->
                <li class="nav-item dropdown"> <a id="notifications" rel="nofollow" data-target="#" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link"><i class="fa fa-product-hunt"></i><span class="badge bg-red"><?php if(empty($total_pending_order)){echo 0;}else{ echo $total_pending_order; } ?></span></a>
                  <ul aria-labelledby="notifications" class="dropdown-menu">
                  <?php 
                  	$pending_order_notification=$obj->select_fixed_limit_ordby("active_order","status='Pending'","serial","5","DESC");
                  	if($pending_order_notification){
                  		foreach($pending_order_notification as $p_o_n){
                  			$user_id=$p_o_n['order_user'];
                  			$package=$p_o_n['product_name'];
                  		$user_list=$obj->fixed_data("register_admin","serial='$user_id'");
                  			if($user_list){
                  				foreach($user_list as $list_user){
                  					$user_name=$list_user['user_name'];
                  				}
                  			}
                  ?>                  		
                    <li><a rel="nofollow" href="#" class="dropdown-item"> 
                        <div class="notification">
                          <div class="notification-content"><?php echo $user_name; ?> order product called</div>
                          <div class="notification-time"><small><?php echo $package; ?></small></div>
                        </div></a></li>
                   <?php } }else{ ?>
                    <li><a rel="nofollow" href="#" class="dropdown-item d-flex"> 
                        <div class="msg-body text-center">
                          <p class="text-center">No new order</p>
                        </div></a>
                    </li>
                   <?php } ?>
                    <li><a rel="nofollow" href="customer-order.php" class="dropdown-item all-notifications text-center"> <strong>View All Order</strong></a></li>
                  </ul>
                </li>
                <!-- Messages                        -->
                <li class="nav-item dropdown"> <a id="messages" rel="nofollow" data-target="#" href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="nav-link"><i class="fa fa-envelope-o"></i><span class="badge bg-orange"><?php if(empty($total_support_message)){echo 0;}else{ echo $total_support_message; } ?></span></a>
                  <ul aria-labelledby="notifications" class="dropdown-menu">
                  <?php 
                  	$unread_support_message=$obj->select_limit("support_data","id","5","DESC");
                  	if($unread_support_message){
                  		foreach($unread_support_message as $unread_message){
                  			$visitor=$unread_message['visitor_name'];
                  			$subject=$unread_message['visitor_subject'];
                  ?>                  		
                    <li><a rel="nofollow" href="#" class="dropdown-item d-flex"> 
                        <div class="msg-body">
                          <h3 class="h5"><?php echo $visitor; ?></h3><span><?php echo $subject; ?></span>
                        </div></a>
                    </li>
                   <?php } }else{ ?>
                    <li><a rel="nofollow" href="#" class="dropdown-item d-flex"> 
                        <div class="msg-body text-center">
                          <p class="text-center">No new request</p>
                        </div></a>
                    </li>
                   <?php } ?>
                    <li><a rel="nofollow" href="support-data.php" class="dropdown-item all-notifications text-center"> <strong>View all messages    </strong></a></li>
                  </ul>
                </li>
                <!-- Logout    -->
                <li class="nav-item"><a href="?logout" class="nav-link logout">Logout<i class="fa fa-sign-out"></i></a></li>
              </ul>
            </div>
          </div>
        </nav>
      </header>