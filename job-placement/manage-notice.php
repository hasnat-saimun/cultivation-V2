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
			<a class="btn btn-default text-danger" href="create-notice.php">Create New</a> <a class="btn btn-default" href="manage-notice.php">Manage Notice</a>
	<table class="table table-bordered">
		<thead class="text-danger">
			<th>Sl</th>
			<th>Notice Title</th>
			<th>Notice Picture</th>
			<th>Publish Date</th>
			<th>Action</th>
		</thead>
		<tbody class="text-muted">
		<?php 
				if(isset($del)){
					$delete=$obj->del_data("biggopti","Sl_no='$del'");
					if($delete>0){
						echo "<div class='alert alert-success p-2' role='alert'>
							<button type='button' class='close' data-dismiss='alert' aria-label='Close'>
							  x
							</button>
							Successfully delete notice</div>";
							echo "<script type='text/javascript'>
								window.location.href='$website_url/cpi-cms/manage-notice.php';
							</script>";
					}else{
						echo "<div class='alert alert-danger p-2' role='alert'>
							<button type='button' class='close' data-dismiss='alert' aria-label='Close'>
							  x
							</button>
							Notice deletion failed</div>";
					}
				}
				if(empty($from)){
					$from=0;
				}
				if(empty($total)){
					$total=25;
				}
				if(empty($p)){
					$p=1;
				}
				$total_row=count($select_data);
			$check_signal=$obj->select_limit("biggopti","Sl_no","$from,$total","DESC");
			if($check_signal>0){
				$x=1;
				foreach($check_signal as $data){
						$title=$data['Notice_title'];
						$location=$data['Notice_location'];
						$publish=$data['Publish_date'];
						$id=$data['Sl_no'];
					$x++;
					
		?>
			<tr>
				<td><?php echo $x; ?></td>
				<td><?php echo $title; ?></td>
				<td><img width="20%" src="<?php echo $media_url.'/'.$location; ?>" alt="" /></td>
				<td><?php echo $publish; ?></td>
				<td><a onclick="javascript:return confirm('Do You Want change/update These Record?')"  href="create-notice.php?edit=<?php echo $id; ?>" class="fa fa-pencil-square-o"></a> <a onclick="javascript:return confirm('Do You Want change/update These Record?')"  href="?del=<?php echo $id;?>"><i class="fa fa-trash"></i></a></td>

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
			$total_col=$obj->count_all("biggopti"); 
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
			<li class="page-item <?php if($p==$x){echo "active";}?>"><a class="page-link" href="manage_notice.php?from=<?php echo $from;?>&&total=<?php echo $to; ?>&&p=<?php echo $x; ?>"><?php echo $x; ?></a></li>
			<?php }?>
		  </ul>
		  </td>
		  </tr>
		  </table></section>
			<p class="text-right"><?php if(!empty($p)){ echo "Page ".$p." of ".$total_page." | ";}?>Total Data: <?php echo $total_col; ?></p>
		</nav>
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