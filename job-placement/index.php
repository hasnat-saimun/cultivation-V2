<?php session_start();
    require_once("resource/includes/placement_header.php");
    if(isset($adminlogout)): 
        session_destroy(); 
    	echo "<script type='text/javascript'>   
    		function Redirect(){  
    			window.location='$server_url'; 
    		}  
    		setTimeout('Redirect()', 0);   
    		</script>";
    endif;
?>
    <div class="page-content d-flex align-items-stretch">
        <?php require_once("resource/includes/placement_sidebar.php"); ?>
            <div class="content-inner">
                <!-- Placement Form Header -->
                <?php
                    if(isset($del)):
                        $del_student = $obj->del_data("job_placement","id='$del'");
                        if($del_student==1):
                            echo "<div class='alert alert-success'>Student profile deleted successfully</div>"; 
                        	echo "<script type='text/javascript'>   
                        		function Redirect(){  
                        			window.location='$server_url'; 
                        		}  
                        		setTimeout('Redirect()', 0);   
                        		</script>";
                        else:
                            echo "<div class='alert alert-warning'>Student data not found</div>";
                        endif;
                    endif;
                    if(isset($placement_submit)):
                        $chk_mobile = $obj->fixed_data("job_placement","mobile='$student_mobile'");
                        $job_details = $obj->SQLInjection($job_details);
                        if(!empty($chk_mobile)):
                            echo "<div class='alert alert-warning'>Mobile number $student_mobile already exist</div>";
                        else:
            				  $new_slider_location="$media_folder/placement";
            				  $img_valid = $obj->bigImageUpload("student_pic","$new_slider_location");
                    			
                        		if($img_valid==1):
                        			$img_location = $obj->imageLocation("student_pic","placement");
                        			$store_data=$obj->add_data("job_placement","name,email,mobile,position,roll_number,company,session,details,photo","'$student_name','$student_email','$student_mobile','$student_position','$student_roll','$company_name','$student_session','$job_details','$img_location'");
                        				if($store_data):
                        					echo "<div class='alert alert-success'>Profile created successfully</div>";
                        					echo "<script type='text/javascript'>   
                        						function Redirect(){  
                        							window.location=''; 
                        						}  
                        						setTimeout('Redirect()', 0);   
                        						</script>";
                        				else:
                            				echo "<div class='alert alert-danger'>
                            				  <i class='fa fa-times text-white bg-danger p-1 rounded-circle'></i> Profile created failed
                            				</div>";
                        				endif;
                        		endif;
                        endif;
                    endif;
                ?>
                <section class="dashboard-header">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12 bg-white p-4 rounded">
                                <h4 class="mb-4"> <i class="icon-bill"></i> <span class="text-uppercase">Job Placement Form</span> </h4>
                                <form action="" method="post" class="form" enctype="multipart/form-data">
                                  <div class="row form-group">
                                    <div class="col">
                                      <input type="text" name="student_name" class="form-control" placeholder="Enter Your Name(*)" required>
                                    </div>
                                    <div class="col">
                                      <input type="text" name="student_position" class="form-control" placeholder="Job Title/Position(*)" required>
                                    </div>
                                  </div>
                                  <div class="row form-group">
                                    <div class="col">
                                      <input type="text" name="student_roll" class="form-control" placeholder="Roll Number(*)" required>
                                    </div>
                                    <div class="col">
                                      <input type="text" name="student_session" class="form-control" placeholder="Enter your session(*)" required>
                                    </div>
                                  </div>
                                  <div class="row form-group">
                                    <div class="col">
                                      <input type="text" name="student_mobile" class="form-control" placeholder="Mobile Number(*)" required>
                                    </div>
                                    <div class="col">
                                      <input type="email" name="student_email" class="form-control" placeholder="Enter your email(optional)">
                                    </div>
                                  </div>
                                  <div class="row form-group">
                                    <div class="col">
                                      <input type="text" name="company_name" class="form-control" placeholder="Company(*)" required>
                                    </div>
                                  </div>
                                  <div class="row form-group">
                                    <div class="col">
                                      <textarea class="form-control" name="job_details" placeholder="Job Details(*) not more then 255 charectar" required></textarea>
                                    </div>
                                  </div>
                                  <div class="row form-group">
                                    <div class="col">
                                      <input type="file" name="student_pic" class="form-control" required>
                                    </div>
                                    <div class="col">
                                    </div>
                                  </div>
                                  <div class="row form-group">
                                    <div class="col">
                                      <button type="submit" name="placement_submit" class="btn btn-success">Save</button>
                                    </div>
                                    <div class="col">
                                    </div>
                                  </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>
                <!-- Placement List Header-->
                <section class="dashboard-header">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12 bg-white p-4 rounded">
                                <h4 class="mb-4"> <i class="icon-bill"></i> <span class="text-uppercase">Job Placement List</span> </h4>
                                <?php if(isset($_SESSION['admin_number'])): ?>
                                    <form action="export.php" method="post" class="form" enctype="multipart/form-data">
                                        <div class="form-group"><input type="submit" name="export" value="Download Data" class="btn btn-success" /></div>
                                    </form>
                                <?php endif; ?>
                                <table class="table table-bordered text-center table-responsive">
                                    <thead class="text-danger">
                                        <th>Sl</th>
                                        <th>Name</th>
                                        <th>Mobile</th>
                                        <th>Company</th>
                                        <th>Position</th>
                                        <?php if(isset($_SESSION['admin_number'])): ?>
                                        <th>Action</th>
                                        <?php else: ?>
                                        <th>View</th>
                                        <?php endif; ?>
                                    </thead>
                                    <tbody class="text-muted">
                                        <?php 
				if(empty($from)){
					$from=0;
				}
				if(empty($total)){
					$total=20;
				}
				if(empty($p)){
					$p=1;
				}
				$total_row=count($select_data);
			$check_signal=$obj->select_limit("job_placement","id","$from,$total","DESC");
if($check_signal>0){
$x=$from+1;
foreach($check_signal as $data){
$name = $data['name'];
$mobile=$data['mobile'];
$position=$data['position'];
$company=$data['company'];
$id=$data['id'];
$photo = $data['photo'];

?>
                                            <tr>
                                                <td>
                                                    <?php echo $x; ?>
                                                </td>
                                                <td>
                                                    <?php echo $name; ?>
                                                </td>
                                                <td>
                                                    <?php echo $mobile; ?>
                                                </td>
                                                <td>
                                                    <?php echo $company; ?>
                                                </td>
                                                <td>
                                                    <?php echo $position; ?>
                                                </td>
                                                <td><?php if(isset($_SESSION['admin_number'])): ?>
                                                    <a href="view-details.php?std_id=<?php echo $id; ?>" class="fa fa-eye"></a> | <a onclick="javascript:return confirm('Do You Want change/update These Record?')"  href="?del=<?php echo $id;?>"><i class="fa fa-trash"></i></a>
                                        <?php else: ?>
                                            <a href="view-details.php?std_id=<?php echo $id; ?>" class="fa fa-eye"></a>
                                        <?php endif; ?>
                                                </td>

                                            </tr>
                                            <?php $x++;}}else{ ?>
                                                <tr>
                                                    <td colspan="7" class="text-center">Currently no records found</td>
                                                </tr>
                                                <?php } ?>
                                    </tbody>
                                </table>
	<?php
		function round_up($value, $places) 
			{
				$mult = pow(@$total, abs($places)); 
				 return $places < 0 ?
				ceil($value / $mult) * $mult :
					ceil($value * $mult) / $mult;
			}
			$total_col=$obj->count_all("job_placement"); 
				$to=$total;
			$total_page=round_up($total_col/$to, 0);
		?>
		<nav aria-label="Page navigation example">
		<section class="table-responsive">
		  <table class="table">
			<tr>
				<td>
		  <ul class="pagination justify-content-end">
		<?php 
			for($x=1;$x<=$total_page;$x++){
				if($x==1){
					$y=1;
				}else{
					$y=$x+1;
				}
				if($y==1){
					$from=0;
				}else{
					$from=$from+$to;
				}
		?>
			<li class="page-item <?php if($p==$x){echo "active";}?>"><a class="page-link" href="index.php?from=<?php echo $from;?>&&total=<?php echo $to; ?>&&p=<?php echo $x; ?>"><?php echo $x; ?></a></li>
			<?php }?>
		  </ul>
		  </td>
		  </tr>
		  </table></section>
			<p class="text-right"><?php if(!empty($p)){ echo "Page ".$p." of ".$total_page." | ";}?>Total Data: <?php echo $total_col; ?></p>
		</nav>
                            </div>
                        </div>
                    </div>
                </section>
                <script>
                        $("input").on("keypress",function(e){
                        var val = $(this).val();
                        var regex = /(<([^>]+)>)/ig;
                        var result = val .replace(regex, "");
                        $(this).val(result);
                
                });
                </script>
                <?php require_once("resource/includes/placement_footer.php");  ?>