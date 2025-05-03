	<!-- Side Navbar -->
        <nav class="side-navbar">
          <!-- Sidebar Header-->
          <div class="sidebar-header d-flex align-items-center">
            <div class="avatar"><img src="avatar.png" alt="<?php echo $admin_name; ?>" class="img-fluid rounded-circle"></div>
            <div class="title">
              <h1 class="h4"><?php echo $admin_name; ?></h1>
            </div>
          </div>
          <!-- Sidebar Navidation Menus-->
          <span class="heading">Admin Options</span>
          <ul class="list-unstyled">
                    <li class="<?php if($page_name=='index.php'){echo "active";}?>"><a href="index.php"> <i class="fa fa-dashboard"></i>Dashboard</a></li>
                    <li><a href="<?php echo $website_url; ?>" target="_blank"> <i class="icon-home"></i>Website Homepage</a></li>
                    <li class="<?php if($page_name=='admin-profile.php'){echo "active";}?>"><a href="admin-profile.php"> <i class="fa fa-user-secret"></i>Admin Profile </a></li>
          </ul>
          <span class="heading">Server Options</span>
          <ul class="list-unstyled">
                    <li class="<?php if($page_name=='website-settings.php'){echo "active";}?>"><a href="website-settings.php"> <i class="fa fa-info-circle"></i>Website Settings</a></li>
                    <li class="<?php if($page_name=='create-notice.php' || $page_name=='manage-notice.php'){echo "active";}?>"><a href="#package" aria-expanded="false" data-toggle="collapse"> <i class="fa fa-product-hunt"></i>Server Notice</a>
                      <ul id="package" class="collapse list-unstyled ">
                        <li class="<?php if($page_name=='create-notice.php'){echo "active";}?>"><a href="create-notice.php" class="fa fa-circle-o"> New Notice</a></li>
                        <li class="<?php if($page_name=='manage-notice.php'){echo "active";}?>"><a href="manage-notice.php" class="fa fa-circle-o"> Notice Management</a></li>
                      </ul>
                    </li>
                    <li class="<?php if($page_name=='create-slider.php' || $page_name=='manage-slider.php'){echo "active";}?>"><a href="#slider" aria-expanded="false" data-toggle="collapse"> <i class="fa fa-camera"></i>Slider</a>
                      <ul id="slider" class="collapse list-unstyled ">
                        <li class="<?php if($page_name=='create-slider.php'){echo "active";}?>"><a href="create-slider.php" class="fa fa-circle-o"> New Slider</a></li>
                        <li class="<?php if($page_name=='manage-slider.php'){echo "active";}?>"><a href="manage-slider.php" class="fa fa-circle-o"> Slider Management</a></li>
                      </ul>
                    </li>
          </ul>
        </nav>