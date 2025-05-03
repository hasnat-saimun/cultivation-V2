<?php session_start(); 
if(isset($_SESSION['admin_number'])){ require_once("resource/includes/efm_header.php"); ?>
<div class="page-content d-flex align-items-stretch"> 
<?php require_once("resource/includes/efm_sidebar.php"); ?>
<div class="content-inner">
<!-- Page Header-->
<?php require_once("resource/includes/efm_pageheader.php"); ?>
<!-- Dashboard Counts Section-->
<section class="dashboard-counts no-padding-bottom p-4">	
	<div class="bg-white p-4 rounded mb-4">	
		<h4 class="mb-4"> <i class="fa fa-product-hunt"></i> <span class="text-uppercase"><?php echo $page; ?></span> </h4>
			<?php 
			//admin details update
			if(isset($profile_details)){
				$update_profile=$obj->up_data("admin_account","account_name='$user_name',access_id='$access_id',mobile='$mobile',email='$email'","admin_id='$admin_number'");
				if($update_profile>0){
						echo "<div class='alert alert-success'>
						  <i class='fa fa-check text-white bg-success p-1 rounded-circle'></i> Profile update successful.
						</div>";
						echo "<script type='text/javascript'>   
						function Redirect(){  
							window.location='admin-profile.php'; 
						}  
						setTimeout('Redirect()', 0);   
						</script>";
					}else{
						echo "<div class='alert alert-danger'>
						  <i class='fa fa-times text-white bg-danger p-1 rounded-circle'></i> Profile update failed.
						</div>";
						echo "<script type='text/javascript'>   
						function Redirect(){  
							window.location='admin-profile.php'; 
						}  
						setTimeout('Redirect()', 0);   
						</script>";
					}
				}
		//admin picture update
	if(isset($pic_up)){
		  $new_product_location="$media_folder/admin";
		  $img_valid = $obj->bigImageUpload("pro_pic","$new_product_location");
			
		if($img_valid==0){
				  @$del_pre_image=unlink($media_folder.'/'.$admin_picture);
				  if(empty($del_pre_image)){
					$del_pre_image=1;
				  }
				  if($del_pre_image>0){ 
			$img_location = $obj->imageLocation("pro_pic","admin");
			$store_data=$obj->up_data("admin_account","pro_pic='$img_location'","admin_id='$admin_number'");
				if($store_data){
					echo "<div class='alert alert-success'>Profile update successful</div>";
					echo "<script type='text/javascript'>   
						function Redirect(){  
							window.location='admin-profile.php'; 
						}  
						setTimeout('Redirect()', 0);   
						</script>";
				}else{
				echo "<div class='alert alert-danger'>
				  <i class='fa fa-times text-white bg-danger p-1 rounded-circle'></i> Something wrong!
				</div>";
				}
			}
		}else{
			echo "<div class='alert alert-danger'>
			  <i class='fa fa-times text-white bg-danger p-1 rounded-circle'></i> Check a valid type image
			</div>";
		}
	}
				
				//admin password update
				if(isset($profile_password)){
				if($new_password!=$confirm_password){
					echo "<div class='alert alert-danger'>
					  <i class='fa fa-times text-white bg-danger p-1 rounded-circle'></i> Password don't match. Please use same password in both field.
					</div>";
				}else{
					$conv_pass=$obj->passCreate("$new_password");
					$update_password=$obj->up_data("admin_account","password='$conv_pass'","admin_id='$admin_number'");
					if($update_password>0){
							echo "<div class='alert alert-success'>
							  <i class='fa fa-check text-white bg-success p-1 rounded-circle'></i> Password update successful.
							</div>";
							echo "<script type='text/javascript'>   
							function Redirect(){  
								window.location='admin-profile.php'; 
							}  
							setTimeout('Redirect()', 0);   
							</script>";
						}else{
							echo "<div class='alert alert-danger'>
							  <i class='fa fa-times text-white bg-danger p-1 rounded-circle'></i> Password update failed.
							</div>";
						}
					}
				}
			 ?>
		<div class="card mb-4 pb-4">
			<div class="card-block mb-4 pb-4">
				<div class="row">
					<div class="col-md-6 col-sm-12">
						<form action="" class="form" method="POST">
							<label for="UserName"><i class="fa fa-user"></i> Name</label>
							<input type="text" name="user_name" class="form-control mb-4" value="<?php echo $admin_name; ?>" placeholder="Enter your name" />
							<label for="Mobile"><i class="fa fa-mobile"></i> Mobile</label>
							<input type="text" class="form-control mb-4" name="mobile" value="<?php echo $admin_mobile; ?>" placeholder="Enter mobile" />
							<label for="Email"><i class="fa fa-envelope"></i> Email</label>
							<input type="email" name="email" class="form-control mb-4" value="<?php echo $admin_email; ?>" placeholder="Enter Email"  />
							<label for="LoginID"><i class="fa fa-lock"></i> Login ID</label>
							<input type="text" name="access_id" class="form-control mb-4" value="<?php echo $admin_id; ?>" />
							<button type="submit" name="profile_details" class="mt-4 mb-2 btn btn-success btn-block fa fa-bookmark"> Update Record</button>
						</form>
					</div>
					<div class="col-md-6 col-sm-12">
						<form action="" class="form mb-4" method="POST">
							<label for="Password"><i class="fa fa-key"></i> New Password</label>
							<input type="password" name="new_password" class="form-control" placeholder="Enter password" />
							<label for="Password"><i class="fa fa-key"></i> Confirm Password</label>
							<input type="password" name="confirm_password" class="form-control" placeholder="Confirm your password" />
							<button type="submit" onclick="javascript:return confirm('Do You Want change/update These Record?')"  name="profile_password" class="mt-4 mb-2 btn btn-success btn-block fa fa-bookmark"> Update Password</button>
						</form>
						<form action="" class="form mt-4" method="POST" enctype="multipart/form-data">
						<div class="col-md-4 mx-auto">
							<img class="img-fluid" src="<?php echo $media_url."/".$admin_picture; ?>" alt="<?php echo $admin_name; ?>" />
							<input type="file" name="pro_pic" class="form-control mt-4" />
						</div>
							<button type="submit" onclick="javascript:return confirm('Do You Want change/update These Record?')"  name="pic_up" class="mt-4 mb-2 btn btn-primary btn-block fa fa-bookmark"> Update Picture</button>
						</form>
					</div>
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