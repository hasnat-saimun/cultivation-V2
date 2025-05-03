	<!-- Side Navbar -->
        <nav class="side-navbar">
          <!-- Sidebar Navidation Menus-->
          <ul class="list-unstyled">
                <li><a href="<?php echo $website_url; ?>" target="_blank"> <i class="icon-home"></i>Homepage</a></li>
                <li class="<?php if($page_name=='index.php'){echo "active";}?>"><a href="/job-placement"> <i class="fa fa-dashboard"></i>Job Placement</a></li>
            <?php if(isset($_SESSION['admin_number'])): ?>
                <li><a href="?adminlogout"> <i class="fa fa-sign-out"></i>Logout</a></li>
            <?php endif; ?>
          </ul>
        </nav>