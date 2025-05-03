<?php session_start(); 
if(isset($_SESSION['admin_number'])){ require_once("resource/includes/efm_header.php"); ?>
<div class="page-content d-flex align-items-stretch"> 
<?php require_once("resource/includes/efm_sidebar.php"); ?>
<div class="content-inner">
<!-- Page Header-->
<?php require_once("resource/includes/efm_pageheader.php"); ?>
<!-- Dashboard Counts Section-->
<section class="dashboard-counts no-padding-bottom p-4">
	<div class="p-4">
		<?php 
		//admin details update
		if(isset($_POST['company_profile'])){
			$check_websettings=$obj->select_all("frontend_settings");
			if(empty($check_websettings)){
				$add_settings=$obj->add_data("frontend_settings","site_name,site_message,support_mobile,support_email,site_slogan,site_location,fb_profile,twitter_profile,linkedin_profile,admin_id","'$company_name','$company_message','$mobile_number','$support_email','$company_title','$company_location','$facebook_profile','$twiter_profile','$linkin_profile','$admin_number'");
				if($add_settings>0){
					echo "<div class='alert alert-success'>
					  <i class='fa fa-check text-white bg-success p-1 rounded-circle'></i> Records save successful.
					</div>";
					echo "<script type='text/javascript'>   
					function Redirect(){  
						window.location='$server_url/website-settings.php'; 
					}  
					setTimeout('Redirect()',0);   
					</script>";
				}else{
					echo "<div class='alert alert-danger'>
					  <i class='fa fa-times text-white bg-danger p-1 rounded-circle'></i> Records save failed.
					</div>";
					echo "<script type='text/javascript'>   
					function Redirect(){  
						window.location='$server_url/website-settings.php'; 
					}  
					setTimeout('Redirect()', 0);   
					</script>";
				}
			}else{
	$update_settings=$obj->up_data("frontend_settings","site_name='$company_name',site_slogan='$company_title',site_message='$company_message',support_mobile='$mobile_number',support_email='$support_email',site_location='$company_location',fb_profile='$facebook_profile',twitter_profile='$twiter_profile',linkedin_profile='$linkin_profile'","admin_id='$admin_number'");
			if($update_settings>0){
					echo "<div class='alert alert-success'>
					  <i class='fa fa-check text-white bg-success p-1 rounded-circle'></i> Records update successful.
					</div>";
					echo "<script type='text/javascript'>   
					function Redirect(){  	
						window.location='$server_url/website-settings.php'; 
					}  
					setTimeout('Redirect()', 0);   
					</script>";
				}else{
					echo "<div class='alert alert-danger'>
					  <i class='fa fa-times text-white bg-danger p-1 rounded-circle'></i> Records update failed.
					</div>";
					echo "<script type='text/javascript'>   
					function Redirect(){  
						window.location='$server_url/website-settings.php'; 
					}  
					setTimeout('Redirect()', 0);   
					</script>";
				}
			}
		}
		 ?>
	<div class="card mb-4 pb-4 rounded">
		<div class="card-block mb-4 pb-4">
			<div class="row">
				<div class="col-md-6 col-sm-12">
					<form action="" class="form" method="POST">
						<label for="CompanyName"><i class="fa fa-building"></i> Company Name</label>
						<input type="text" name="company_name" class="form-control mb-4" value="<?php echo $website_name; ?>" placeholder="Enter company name" />
						<label for="Title"><i class="fa fa-header"></i> Company Title</label>
						<input type="text" name="company_title" class="form-control mb-4" value="<?php echo $website_title; ?>" placeholder="Enter company slogan"  />
						<label for="Mobile"><i class="fa fa-mobile"></i> Support Mobile</label>
						<input type="text" name="mobile_number" value="<?php echo $support_mobile; ?>" class="form-control mb-4" placeholder="Enter support mobile number" />
						<label for="Twitter"><i class="fa fa-twitter"></i> Twitter Page Link</label>
						<input type="text" class="form-control mb-4" name="twiter_profile" value="<?php echo $twitter_profile; ?>" />
						<label for="Linkedin"><i class="fa fa-linkedin-square"></i> Linkedin Profile Link</label>
						<input type="text" class="form-control mb-4" name="linkin_profile" value="<?php echo $linkedin_profile; ?>" />
				</div>
				<div class="col-md-6 col-sm-12">
						<label for="Announcement"><i class="fa fa-comment"></i> Company Message</label>
						<textarea class="form-control mb-4" name="company_message" placeholder="Enter company announcement"><?php echo $website_message; ?></textarea>
						<label for="Email"><i class="fa fa-map-marker"></i> Head Office</label>
						<input type="text" class="form-control mb-4" name="company_location" value="<?php echo $site_location; ?>" placeholder="Enter head office location"  />
						<label for="Email"><i class="fa fa-envelope"></i> Support Email</label>
						<input type="email" class="form-control mb-4" name="support_email" value="<?php echo $support_email; ?>" placeholder="Enter webmail id(support@websitename.com)"  />
						<label for="FB"><i class="fa fa-facebook-square"></i> FB Page Link</label>
						<input type="text" class="form-control mb-4" name="facebook_profile" value="<?php echo $fb_profile; ?>" />
				</div>
						<button type="submit" onclick="javascript:return confirm('Do You Want change/update These Record?')"  name="company_profile" class="col-md-6 mx-auto mt-4 mb-2 btn btn-success btn-block fa fa-bookmark"> Update Records</button>
				</form>
			</div>
		</div>
	</div>
</div>
</section>
<?php require_once("resource/includes/efm_footer.php"); }else{
echo "<script type='text/javascript'>   
function Redirect(){  
	window.location='login.php'; 
}  
setTimeout('Redirect()', 0);   
</script>";

} ?>