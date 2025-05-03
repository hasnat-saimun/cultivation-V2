<?php require_once("resource/includes/placement_header.php"); ?>
    <div class="page-content d-flex align-items-stretch">
        <?php require_once("resource/includes/placement_sidebar.php"); ?>
            <div class="content-inner">
                <!-- Placement Form Header -->
                <section class="dashboard-header">
                    <div class="container-fluid">
        				<div class="row">
                            <div class="col-md-12 bg-white p-4 rounded">
                            <?php
                                if(isset($std_id)):
                                    $chk_profile = $obj->fixed_data("job_placement","id='$std_id'");
                                    if(!empty($chk_profile)):
                    					foreach($chk_profile as $info):
                    						$student_name = $info['name'];
                    						$photos=$info['photo'];
                    						$session=$info['session'];
                    						$roll_number=$info['roll_number'];
                    						$mobile=$info['mobile'];
                    						$email=$info['email'];
                    						$company=$info['company'];
                    						$position=$info['position'];
                    						$details=$info['details'];
                    					endforeach;
                						if(empty($photos)):
                							$photos="$media_folder/placement/user.jpg";
                						endif;
                            ?>
                                <h4 class="mb-4"> <i class="icon-bill"></i> <span class="">Job Placement Information for <?php echo "$student_name"; ?></span> </h4>
                                <div class="row">
                					<div class="col-md-10 col-12 mx-auto row">
                						<div class="col-md-2 col-8 mx-auto">
                							<p class="student-text text-center"> <img src='<?php echo "$media_url"."/"."$photos"; ?>' alt="Smiley face" class="img-fluadmission_number img-thumbnail"></p>
                						</div>
                						<div class="col-md-10 pro-text p-3">
                							<table class="table table-bordered table-sm text-center"> 
                								<tbody>
                									<tr>
                										<td>Name</td>
                										<td><?php echo $student_name ?></td>
                									</tr>
                									<tr>
                										<td>Roll Number</td>
                										<td><?php echo $roll_number ?></td>
                									</tr>
                									<tr class="bg-light">
                										<td>Session</td>
                										<td><?php echo $session ?></td>
                									</tr>			
                								</tbody>
                							</table>
                						</div>
                					</div>
                				</div>
    				        <div class="row no-gutters">
                				<div class="col-md-10 pro-text mx-auto">
                					<table class="table table-bordered table-sm mt-3"> 
                						<tbody>
                						<div class="bg-green pl-3 pt-2 pb-2"> <i class="far fa-arrow-alt-circle-down"></i> Details</div>
                							<tr class="bg-light">
                								<td>Company</td>
                								<td><?php echo $company ?></td>
                							</tr>
                							<tr>
                								<td>Position/Job Title</td>
                								<td><?php echo $position ?></td>
                							</tr>
                							<tr class="bg-light">
                								<td>Email</td>
                								<td><?php echo $email ?></td>
                							</tr>
                							<tr>
                								<td>Mobile</td>
                								<td><?php echo $mobile ?></td>
                							</tr>
                							<tr class="bg-light">
                								<td>Job Details</td>
                								<td><?php echo $details ?></td>
                							</tr>
                						</tbody>
                					</table>
                					<a href="/job-placement" class="btn btn-primary">Back</a>
                				</div>
                			<?php else: ?>
                			    <div class="alert alert-info">No student found with your query</div>
                			<?php endif; endif; ?>
                			</div>
                		</div>
                    </div>
                </section>
                <?php require_once("resource/includes/placement_footer.php");  ?>