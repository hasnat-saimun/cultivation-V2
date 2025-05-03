<?php

	//Website information table
	$front_table=$obj->select_all("frontend_settings");
	if($front_table){
		foreach($front_table as $wi){
			$website_name=$wi['site_name'];
			$website_title=$wi['site_slogan'];
			$website_message=$wi['site_message'];
			$support_email=$wi['support_email'];
			$support_mobile=$wi['support_mobile'];
			$site_creator=$wi['site_creator'];
			$creator_fblink=$wi['creator_fblink'];
			$site_location=$wi['site_location'];
			$fb_profile=$wi['fb_profile'];
			$twitter_profile=$wi['twitter_profile'];
			$linkedin_profile=$wi['linkedin_profile'];
			$website_skype=$wi['skype_id'];
			$website_gmail_account=$wi['gmail_id'];
			$website_admin=$wi['admin_id'];
		}
	}



//user details
if(isset($_SESSION['account_number'])){
$account_number=$_SESSION['account_number'];
$user_details=$obj->fixed_data("register_admin","serial='$account_number' "); 
      if($user_details>0){
        foreach ($user_details as $details) {
          $account_name=$details['user_name'];
          $account_id=$details['serial'];
          $account_email=$details['email'];
          $account_mobile=$details['mobile'];
          $profile_location=$details['picture'];
          $account_country=$details['country'];
          $account_joining=$details['join_date'];
          $account_exp=$details['membership_expired'];
          $account_status=$details['account_status'];
          $status_warn=$details['status_warning'];
          $account_facility=$details['user_facility'];
        }
		$country_check=$obj->fixed_data("countries","country_id='$account_country'");
		if($country_check){
			foreach($country_check as $a_country){
				$country_name=$a_country['country'];
			}
		}
      }
$check_cart=$obj->fixed_data("shopping_cart","order_user='$account_number'");
$check_order=$obj->select_fixed_limit_ordby("active_order","order_user='$account_number'","serial","5","DESC");
$check_signal=$obj->fixed_data("`signal`","user_facility='Signal User' && account_status='Active'");
}
	
//admin details
if(isset($_SESSION['admin_number'])){
$admin_number=$_SESSION['admin_number'];
$admin_id=$_SESSION['login_user'];
$admin_details=$obj->fixed_data("admin_panel","admin_id='$admin_number' "); 
      if($admin_details>0){
        foreach ($admin_details as $admin) {
          $admin_name=$admin['admin_name'];
          $admin_email=$admin['admin_email'];
          $admin_mobile=$admin['admin_cellphone'];
          $admin_joining=$admin['admin_from'];
        }
      }
$total_notice=$obj->count_all("biggopti");
$total_staffs=$obj->count_all("staff_information");
$total_teacher=$obj->count_all("teacher_information");
$total_student=$obj->count_all("student_information");
$total_support_message=$obj->count_fixed("support_data","status='Unread'");;
}


$page_name = basename($_SERVER['PHP_SELF']); // Get script filename without any path information
$page = str_replace( array( '.php', '.htm', '.html' ), '', $page_name ); // Remove extensions
$page = str_replace( array('-', '_'), ' ', $page); // Change underscores/hyphens to spaces
$page = ucwords( $page ); // uppercase first letter of every word
if($page=='Index'){$page="Homepage";}
	