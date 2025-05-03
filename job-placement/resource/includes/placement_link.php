<?php 
	
	extract($_POST);
	extract($_GET);
	$website_url="https://".$_SERVER['HTTP_HOST']."";
	$server_url="https://".$_SERVER['HTTP_HOST']."/job-placement";
	$public_folder=$_SERVER['DOCUMENT_ROOT']."/job-placement";
	$resource_url=$server_url."/resource";
	$resource_folder=$public_folder."/resource";
	
	$css_url=$resource_url."/assets/css";
	$rich_url=$resource_url."/assets/rich-editor";
	$js_url=$resource_url."/assets/js";
	$vendor_url=$resource_url."/assets/vendor";
	
	$media_url=$resource_url."/media";
	$media_folder=$resource_folder."/media";
	
	$include_folder=$resource_folder."/includes";
	$config_folder=$public_folder."/config";
	$database_folder=$public_folder."/database";
	
	include_once("$config_folder/database.php");
	include_once("$config_folder/query.php");
	
	if(empty($website_name)){$website_name="BD Survey";}
	$today=date("Y-m-d H:i:s");
	
?>