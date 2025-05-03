<?php session_start(); 
if(isset($_SESSION['admin_number'])){ require_once("resource/includes/efm_header.php"); ?>
<div class="page-content d-flex align-items-stretch"> 
<?php require_once("resource/includes/efm_sidebar.php"); ?>
<div class="content-inner">
<!-- Page Header-->
<?php require_once("resource/includes/efm_pageheader.php"); ?>
<!-- Dashboard Counts Section-->
<section class="dashboard-counts no-padding-bottom p-4">	
	<div class="bg-white p-4 rounded">	
		<h4 class="mb-4"> <i class="fa fa-product-hunt"></i> <span class="text-uppercase"><?php echo $page; ?></span> </h4>
			<a class="btn btn-default" href="create-notice.php">Create New</a> <a class="btn btn-default text-danger" href="manage-notice.php">Manage Notice</a>
			<div class="card mb-4 pb-4 pt-4">
				<div class="card-block mb-4 pb-4">
				<?php 
				//create category
				if(isset($create)){
				  $new_notice_location="$media_folder/notice";
				  $img_valid = $obj->bigImageUpload("notice_image","$new_notice_location");
					
					if($img_valid==0){
						$img_location = $obj->imageLocation("notice_image","notice");
						$check_data=$obj->fixed_data("biggopti","Notice_title='$notice_title'");
						if(empty($check_data)){
							$old_date_timestamp = strtotime($datetime);
							$date = date('jS', $old_date_timestamp); 
							$month = date('M', $old_date_timestamp); 
							$year = date('Y', $old_date_timestamp); 

							$store_data=$obj->add_data("biggopti","Notice_title,Notice_location,date,year,month,publish_date","'$notice_title','$img_location','$date','$year','$month','$datetime'");
							if($store_data){
								echo "<div class='alert alert-success'>Notice successfully created</div>";
								echo "<script type='text/javascript'>   
								function Redirect(){  
									window.location='$server_url/create-notice.php'; 
								}  
								setTimeout('Redirect()', 0);   
								</script>";
							}else{
								echo "<div class='alert alert-danger'>
								  <i class='fa fa-times text-white bg-danger p-1 rounded-circle'></i> Something went wrong</div>";
							}
						}else{
							echo "<div class='alert alert-danger'>
							  <i class='fa fa-times text-white bg-danger p-1 rounded-circle'></i> Notice already exist.
							</div>";
						}
					}else{
							echo "<div class='alert alert-danger'>
							  <i class='fa fa-times text-white bg-danger p-1 rounded-circle'></i> Check a valid type image
							</div>";
						}
				}
				
				//edit category
				if(isset($edit)){
				$select_data=$obj->fixed_data("biggopti","Sl_no='$edit'");
				if(!empty($select_data)){
					foreach($select_data as $data){
						$title=$data['Notice_title'];
						$location=$data['Notice_location'];
						$publish=$data['Publish_date'];
						$id=$data['Sl_no'];
					}
				}
			
				
				if(isset($update_picture)){
					  $new_notice_location="$media_folder/notice";
					  $img_valid = $obj->bigImageUpload("notice_image","$new_notice_location");
						
					if($img_valid==0){
							  @$del_pre_image=unlink($media_folder.'/'.$location);
							  if(empty($del_pre_image)){
								$del_pre_image=1;
							  }
							  if($del_pre_image>0){ 
						$img_location = $obj->imageLocation("notice_image","notice");
						$store_data=$obj->up_data("biggopti","Notice_location='$img_location'","Sl_no='$edit'");
							if($store_data){
								echo "<div class='alert alert-success'>Notice update successful</div>";
								echo "<script type='text/javascript'>   
									function Redirect(){  
										window.location='manage-notice.php'; 
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
					//update table
				if(isset($update)){
					$store_data=$obj->up_data("biggopti","Notice_title='$notice_title'","Sl_no='$edit'");
					if($store_data){
						echo "<div class='alert alert-success'>Notice update successfully</div>";
						echo "<script type='text/javascript'>   
							function Redirect(){  
								window.location='$server_url/create-notice.php?edit=$edit'; 
							}  
							setTimeout('Redirect()', 0);   
							</script>";
					}else{
						echo "<div class='alert alert-danger'>
							<i class='fa fa-times text-white bg-danger p-1 rounded-circle'></i> Something went wrong</div>";
					}
				}
			 ?>
			<form action="" class="form col-md-4 mx-auto mt-4" method="POST" enctype="multipart/form-data">
                <img src="<?php echo $media_url.'/'.$location; ?>" alt="" class="img-fluid" />
				<div class="form-group">
					<label for="Headline">Notice Picture</label>
                    <input type="file" name="notice_image" required class="form-control" />
				</div>
				<div class="form-group">
					<button type="submit" onclick="javascript:return confirm('Do You Want change/update These Record?')"  name="update_picture" class="btn btn-primary">Save</button>
					<button type="reset" class="btn btn-danger">Reset</button>
				</div>
			</form>

			<form action="" class="form col-md-10 mx-auto mt-4" method="POST" enctype="multipart/form-data">
				<div class="form-group">
					<label for="Headline">Notice Title</label>
					<input type="text" name="notice_title" class="text-muted form-control mb-4" value="<?php echo $title; ?>" />
				</div>
				<div class="form-group">
					<button type="submit" onclick="javascript:return confirm('Do You Want change/update These Record?')"  name="update" class="btn btn-primary">Save</button>
					<button type="reset" class="btn btn-danger">Reset</button>
				</div>
			</form>
			<?php }else{ ?>
			<p class="text-danger">All the field like (*) must be required</p>
			<form action="" class="form col-md-10 mx-auto mt-4" method="POST" enctype="multipart/form-data">
				<div class="form-group">
					<label for="Headline">Notice Title</label>
					<input type="text" name="notice_title" class="text-muted form-control mb-4" placeholder="Enter notice title" />
				</div>
				<div class="form-group">
					<label for="Headline">Notice Date</label>
					<input type="date" name="datetime" class="text-muted form-control mb-4" placeholder="Enter notice date" />
				</div>
				<div class="form-group">
					<label for="Headline">Notice Picture</label>
					<input type="file" name="notice_image" required class="form-control" />
				</div>
				<div class="form-group">
					<button type="submit" onclick="javascript:return confirm('Do You Want change/update These Record?')"  name="create" class="btn btn-primary">Save</button>
					<button type="reset" class="btn btn-danger">Reset</button>
				</div>
			</form>
			<?php } ?>
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