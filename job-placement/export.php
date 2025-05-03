<?php
    require_once("resource/includes/placement_link.php");
if(isset($_POST["export"])):$connect = mysqli_connect("localhost", "bsiedugo_jobbsi", "lpM~0HQ;ZlE#", "bsiedugo_bsidb");header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename=data.csv');  
      $output = fopen("php://output", "w");  
      fputcsv($output, array('ID','Student Name','Email Address','Mobile Number','Roll Number','Session','Position/Title','Company Name','Job Details','Photos'));  
      $query = "SELECT * from job_placement ORDER BY id DESC";  
      $result = mysqli_query($connect, $query);  
      while($row = mysqli_fetch_assoc($result))  
      {  
           fputcsv($output, $row);  
      }  
      fclose($output);  
    endif;