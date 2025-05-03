<?php 
		
	$host="localhost";
	$hostuser="bsiedu2021_emsadmin";
	$hostpassword="54IPJOLAIiZE";
	$database="bsiedu2021_ems"; 
	
class fibocms{
	//Connect with mysql server
	public function cmsconnect(){
		global $host;
		global $hostuser;
		global $hostpassword;
		global $database; 
		$dbwithuser= new PDO("mysql:host=$host;dbname=$database","$hostuser","$hostpassword");
		return $dbwithuser;
	}
	//For disconnect from mysql server
	public function cmsdisconnect(){
		//Delete the catch and destroy all session.
		$this->dbwithuser=null;
		//DB User exit from database
		$this->dbwithuser=exit();
	}
		
	//data insertion in table
	public function add_data($tbl_name,$fields,$values){
		//Mysql query. Example (insert into education (`name`,`email',`mobile`) values('yousuf','cip2015@gmail.com','01678909091'));
		$sqlString="INSERT INTO $tbl_name($fields) values ($values)";
		
		//echo $sqlString; die();
		
		
		$sql=$this->cmsconnect()->prepare($sqlString);
		
		//check if sql query execuation
		if($sql->execute()==1){
			//if sql execuation true then return data
			return 1;
		}else{
			//otherwise return null or 0
			return 0;
	    }
	}
	
		//data insertion in table
	public function del_database($database_name){
		//Mysql query. Example (insert into education (`name`,`email',`mobile`) values('yousuf','cip2015@gmail.com','01678909091'));
		$sql=$this->cmsconnect()->prepare("DROP DATABASE $database_name");
		
		//check if sql query execuation
		if($sql->execute()==1){
			//if sql execuation true then return data
			return 1;
		}else{
			//otherwise return null or 0
			return 0;
	    }
	}
	
	
	//data updating in table
	public function up_data($tbl_name,$fieldwithvalue,$basefieldwithvalue){
		//mysql query. Example (update education set name='Yousuf' && email='creativeitpark2015@gmail.com' where serial=1)
		$sql=$this->cmsconnect()->prepare("UPDATE $tbl_name SET $fieldwithvalue WHERE $basefieldwithvalue");
		
		//check if sql query execuation
		if($sql->execute()==true){
			//if sql execuation true then return data
			return 1;
		}else{
			//otherwise return null or 0
			return 0;
		}
	}
	
	
	
	//check fixed data from table
	public function fixed_data($tbl_name,$basefieldwithvalue){
		//mysql query. Example (select * from education where name='Yousuf' && email='creativeitpark2015@gmail.com')
		$sql=$this->cmsconnect()->prepare("SELECT * FROM $tbl_name WHERE $basefieldwithvalue");
		
		//check if sql query execuation
		if($sql->execute()){
			//if mysql query execute then count all row of the table 
			$datacount=$sql->rowCount();
			if($datacount!=0){
				//if rowcount true all data fetch from table.
				return $sql->fetchAll();
			}else{
				//otherwise return null or 0
				return 0;
			}
		}else{
			//otherwise return null or 0
			return 0;
		}
	}
	
	
	//check duplicate data from table
	public function duplicate_one($tbl_name,$distrinct_value,$basefieldwithvalue){
		//mysql query. Example (select * from education where name='Yousuf' && email='creativeitpark2015@gmail.com')
		$sql=$this->cmsconnect()->prepare("SELECT DISTINCT ($distrinct_value) FROM $tbl_name WHERE $basefieldwithvalue");
		
		//check if sql query execuation
		if($sql->execute()){
			//if mysql query execute then count all row of the table 
			$datacount=$sql->rowCount();
			if($datacount!=0){
				//if rowcount true all data fetch from table.
				return $sql->fetchAll();
			}else{
				//otherwise return null or 0
				return 0;
			}
		}else{
			//otherwise return null or 0
			return 0;
		}
	}
	
	
	
	//check fixed data from table
	public function fixed_order_data($tbl_name,$basefieldwithvalue,$order_field){
		//mysql query. Example (select * from education where name='Yousuf' && email='creativeitpark2015@gmail.com')
		$sql=$this->cmsconnect()->prepare("SELECT * FROM $tbl_name WHERE $basefieldwithvalue ORDER BY $order_field ASC");
		
		//check if sql query execuation
		if($sql->execute()){
			//if mysql query execute then count all row of the table 
			$datacount=$sql->rowCount();
			if($datacount!=0){
				//if rowcount true all data fetch from table.
				return $sql->fetchAll();
			}else{
				//otherwise return null or 0
				return 0;
			}
		}else{
			//otherwise return null or 0
			return 0;
		}
	}
	
	//get total summation of a single column
	public function sum_all_column($sumfield,$newsumfield,$tablename){
		//mysql query. Example (SELECT SUM(orders) AS totalsum FROM CustomerOrders);
		$sql=$this->cmsconnect()->prepare("SELECT SUM($sumfield) AS $newsumfield FROM $tablename");
		
		//check if sql query execuation
		if($sql->execute()){
			//if mysql query execute then count all row of the table 
			$datacount=$sql->rowCount();
			if($datacount!=0){
				//if rowcount true all data fetch from table.
				return $sql->fetchAll();
			}else{
				//otherwise return null or 0
				return 0;
			}
		}else{
			//otherwise return null or 0
			return 0;
		}
	}
	
	//get total summation of a single column with where statement
	public function sum_fixed_column($sumfield,$newsumfield,$tablename,$fieldwithvalue){
		//mysql query. Example (SELECT SUM(orders) AS totalsum FROM CustomerOrders);
		$sql=$this->cmsconnect()->prepare("SELECT SUM($sumfield) AS $newsumfield FROM $tablename WHERE $fieldwithvalue");
		
		//check if sql query execuation
		if($sql->execute()){
			//if mysql query execute then count all row of the table 
			$datacount=$sql->rowCount();
			if($datacount!=0){
				//if rowcount true all data fetch from table.
				return $sql->fetchAll();
			}else{
				//otherwise return null or 0
				return 0;
			}
		}else{
			//otherwise return null or 0
			return 0;
		}
	}
	
	
	
	//count total data from table
	public function count_all($tbl_name){
		//mysql query. Example (select * from education)
		$sql=$this->cmsconnect()->prepare("SELECT * FROM $tbl_name");
		
		//check if sql query execuation
		if($sql->execute()){
			//if mysql query execute then count all row of the table 
			$datacount=$sql->rowCount();
			if($datacount!=0){
				//if rowcount true all data fetch from table.
				$rows=$sql->fetchAll();
				return  count($rows);
			}else{
				//otherwise return null or 0
				return 0;
			}
		}else{
			//otherwise return null or 0
			return 0;
		}
	}
	
	//count total data from table with fixed query
	public function count_fixed($tbl_name,$fieldwithvalue){
		//mysql query. Example (select * from education)
		$sql=$this->cmsconnect()->prepare("SELECT * FROM $tbl_name  WHERE $fieldwithvalue");
		
		//check if sql query execuation
		if($sql->execute()){
			//if mysql query execute then count all row of the table 
			$datacount=$sql->rowCount();
			if($datacount!=0){
				//if rowcount true all data fetch from table.
				$rows=$sql->fetchAll();
				return  count($rows);
			}else{
				//otherwise return null or 0
				return 0;
			}
		}else{
			//otherwise return null or 0
			return 0;
		}
	}
	
	//select all data from table
	public function select_all($tbl_name){
		//mysql query. Example (select * from education)
		$sql=$this->cmsconnect()->prepare("SELECT * FROM $tbl_name");
		
		//check if sql query execuation
		if($sql->execute()){
			//if mysql query execute then count all row of the table 
			$datacount=$sql->rowCount();
			if($datacount!=0){
				//if rowcount true all data fetch from table.
				return $sql->fetchAll();
			}else{
				//otherwise return null or 0
				return 0;
			}
		}else{
			//otherwise return null or 0
			return 0;
		}
	}
	
	//select limit data from table
	public function select_limit($tbl_name,$order_field,$limit_value,$limit_type){
		//mysql query. Example (select * from education)
		$sql=$this->cmsconnect()->prepare("SELECT * FROM $tbl_name ORDER BY $order_field $limit_type limit $limit_value");
		
		//check if sql query execuation
		if($sql->execute()){
			//if mysql query execute then count all row of the table 
			$datacount=$sql->rowCount();
			if($datacount!=0){
				//if rowcount true all data fetch from table.
				return $sql->fetchAll();
			}else{
				//otherwise return null or 0
				return 0;
			}
		}else{
			//otherwise return null or 0
			return 0;
		}
	}
	
	
	
	//select limit data from table for fixed user
	public function select_fixed_limit($tbl_name, $fieldwithvalue,$order_field,$limit_value){
		//mysql query. Example (select * from education)
		$sql=$this->cmsconnect()->prepare("SELECT * FROM $tbl_name WHERE $fieldwithvalue ORDER BY $order_field DESC limit $limit_value");
		
		//check if sql query execuation
		if($sql->execute()){
			//if mysql query execute then count all row of the table 
			$datacount=$sql->rowCount();
			if($datacount!=0){
				//if rowcount true all data fetch from table.
				return $sql->fetchAll();
			}else{
				//otherwise return null or 0
				return 0;
			}
		}else{
			//otherwise return null or 0
			return 0;
		}
	}
	
	
	
	//select limit data from table for fixed user
	public function select_fixed_limit_ordby($tbl_name, $fieldwithvalue,$order_field,$limit_value,$ord_type){
		//mysql query. Example (select * from education)
		$sql=$this->cmsconnect()->prepare("SELECT * FROM $tbl_name WHERE $fieldwithvalue ORDER BY $order_field $ord_type limit $limit_value");
		
		//check if sql query execuation
		if($sql->execute()){
			//if mysql query execute then count all row of the table 
			$datacount=$sql->rowCount();
			if($datacount!=0){
				//if rowcount true all data fetch from table.
				return $sql->fetchAll();
			}else{
				//otherwise return null or 0
				return 0;
			}
		}else{
			//otherwise return null or 0
			return 0;
		}
	}
	
	//data delete from table
	public function del_data($tbl_name,$basefieldwithvalue){
		//mysql query. Example (delete from education where serial=1)
		$sql=$this->cmsconnect()->prepare("DELETE FROM $tbl_name WHERE $basefieldwithvalue");
		
		//check if sql query execuation
		if($sql->execute()==true){
			//if sql execuation true then return data
			return 1;
		}else{
			//otherwise return null or 0
			return 0;
		}
	}
	
	//page redirecting
	public function redirect_page($url,$permanent){
		header('Location: ' . $url, true, ($permanent === true) ? 301 : 1);
		exit();
	}
	
	public function passCreate($pass){
		$ccode='044';
		$salt=base64_encode($ccode);
		$hash=hash('sha512',$pass.$salt);
		return md5($hash);
	}
	
	
	
		//get Extention
		public function getExtension($file){
			$fileExtension = pathinfo($file,PATHINFO_EXTENSION);
			return $fileExtension;
		}
		
		//slider upload function
		public function bigImageUpload($htmlInputNameVariable,$destination){
			$fieldName = $_FILES["$htmlInputNameVariable"]['name'];
			$fileSize = $_FILES["$htmlInputNameVariable"]['size'];
			$fileType = $this->getExtension($fieldName);
			
			if(empty($fieldName)){
				echo "<div class='alert alert-warning'>Upload a image</div>";
				return 0;
			}else{
				/** allow certain file formats
				if($fileType != "jpg" && $fileType !="ppt" && $fileType !="PPT" && $fileType !="zip" && $fileType !="ZIP" && $fileType !="pptx" && $fileType !="PPTXS" && $fileType !="xls" && $fileType !="XLS" && $fileType !="xlss" && $fileType !="XLSS" && $fileType !="doc" && $fileType !="DOC" && $fileType !="docs" && $fileType !="DOCS" && $fileType != "jpeg" && $fileType != "png" && $fileType != "pdf" && $fileType != "JPG" && $fileType != "JPEG" && $fileType != "PNG" && $fileType != "PDF"){
					echo "Only allow jpg, jpeg, png, doc, xls, ppt, zip or pdf File.";
					exit();
				}**/
				if($fileType != "jpg" && $fileType != "jpeg" && $fileType != "png" && $fileType != "JPG" && $fileType != "JPEG" && $fileType != "PNG"){
					echo "<div class='alert alert-warning'>Only allow jpg, jpeg, png File.</div>";
				    return 0;
				}
				//limit file size 
				if($fileSize>1000000){
					echo "<div class='alert alert-warning'>Maximum 100KB file allowed</div> ";
				    return 0;
				}
				// check exist file
				if(file_exists($destination."/".sha1($fieldName).date("mdHis"))){
					echo "<div class='alert alert-warning'>File Already Exists</div>";
				    return 0;
				}
				else{
					move_uploaded_file($_FILES["$htmlInputNameVariable"]["tmp_name"],$destination."/".sha1($fieldName).date("mdHis").".$fileType");
				    return 1;
				}
			}
		}
		
		public function media($htmlInputNameVariable,$destination){
			move_uploaded_file($_FILES["$htmlInputNameVariable"]["tmp_name"],$destination."/".sha1($fieldName).date("mdHis").".$fileType");
		}
		//slider upload function
		public function media_upload($htmlInputNameVariable,$destination){
			$fieldName = $_FILES["$htmlInputNameVariable"]['name'];
			$fileSize = $_FILES["$htmlInputNameVariable"]['size'];
			$fileType = $this->getExtension($fieldName);
			
			if(empty($fieldName)){
				echo "<div class='alert alert-warning'>Upload a image</div>";
				return 0;
				
			}else{
				// allow certain file formats
				if($fileType != "jpg" && $fileType != "jpeg" && $fileType != "png" && $fileType != "pdf" && $fileType != "JPG" && $fileType != "JPEG" && $fileType != "PNG"){
					echo "<div class='alert alert-warning'>Only allow jpg, jpeg, png File.</div>";
					return 0;
				}
				// Check if image file is a actual image or fake image
				$check = @getimagesize($_FILES["$htmlInputNameVariable"]['tmp_name']);
				if($check != true){
					echo "<div class='alert alert-warning'>Sorry ! File is not an image</div>";
					return 0;
				} 
				//limit file size 
				if($fileSize>1000000){
					echo "<div class='alert alert-warning'>Maximum 100KB file allowed</div> ";
					return 0;
				}
				// check exist file
				if(file_exists($destination."/".sha1($fieldName).date("mdHis"))){
					echo "<div class='alert alert-warning'>File Already Exists</div>";
					return 0;
				}
				else{
					move_uploaded_file($_FILES["$htmlInputNameVariable"]["tmp_name"],$destination."/".sha1($fieldName).date("mdHis").".$fileType");
				    return 1;
				}
			}
		}
	
		//insert image location database
		public function imageLocation($htmlInputNameVariable,$destination){
			$fieldName = $_FILES["$htmlInputNameVariable"]['name'];
			$fileSize = $_FILES["$htmlInputNameVariable"]['size'];
			$fileType = $this->getExtension($fieldName);
			$location = $destination."/".sha1($_FILES["$htmlInputNameVariable"]['name']).date("mdHis")."."."$fileType";
			return $location;
		}
	
		public function SQLInjection($str){
			return str_replace(array(" ","@","(",")","-","_","#","$","'",'"',"'",'"',"<",">","&"), array(" ","@","(",")","-","_","#","$","&#39;","&quot;","&#39;","&quot;","&#60;","&#62;","&"), $str);
		}
		public function string_replace($str){
			return str_replace(array(" "), array("_"), $str);
		}
		public function string_replace_qut($str){
			return str_replace(array("'"), array("`"), $str);
		}
		
		public function string_replace_blank($str){
			return str_replace(array("_"), array(" "), $str);
		}
		
		function findStart($limit)
	{
		if ((!isset($page)) || ($page == "1"))
		{
			$start = 0;
			$page = 1;
		}
		else
		{
			$start = ($page-1) * $limit;
		}
	
	return $start;
	}
	/***********************************************************************************
	* int findPages (int count, int limit)
	* Returns the number of pages needed based on a count and a limit
	***********************************************************************************/
	function findPages($count, $limit)
	{
		$pages = (($count % $limit) == 0) ? $count / $limit : floor($count / $limit) + 1;
		return $pages;
	}
	/***********************************************************************************
	* string pageList (int curpage, int pages)
	* Returns a list of pages in the format of "« < [pages] > »"
	***********************************************************************************/
	function pageList($curpage, $pages, $url_parts="")
	{
		$page_list = "";
		
		/* Print the first and previous page links if necessary */
		if (($curpage != 1) && ($curpage))
		{
			$first_page = " <a href=\"".$website_url."/performance?page=1".$url_parts."\" title=\"First Page\">First</a> ";
		}
		
		if (($curpage-1) > 0)
		{
			$previous = "&laquo; <a href=\"".$website_url."/performance?page=".($curpage-1).$url_parts."\" title=\"Previous Page\">previous</a> &ndash;";
		}
		else
		{
			$previous = "&laquo; previous &ndash;";
		}
		
		/* Print the numeric page list; make the current page unlinked and bold */
		for ($i=1; $i<=$pages; $i++)
		{
			if ($i == $curpage)
			{
				$page_list .= "<strong>".$i."</strong>";
			}
			else
			{
				$page_list .= "<a href=\"".$website_url."/performance?page=".$i.$url_parts."\" title=\"Page ".$i."\">".$i."</a>";
			}
			$page_list .= " ";
		}
		
		/* Print the Next and Last page links if necessary */
		if (($curpage+1) <= $pages)
		{
			$next = "&ndash; <a href=\"".$website_url."/performance?page=".($curpage+1).$url_parts."\" title=\"Next Page\">next</a> &raquo";
		}
		else
		{
			$next = "&ndash; next &raquo";
		}
		
		if (($curpage != $pages) && ($pages != 0))
		{
			$last_page = "<a href=\"".$website_url."/performance?page=".$pages.$url_parts."\" title=\"Last Page\">Last</a> ";
		}
		$page_list .= "</td>\n";
		
		$return_paging['first_page'] 	= $first_page;
		$return_paging['last_page'] 	= $last_page;
		$return_paging['previous'] 		= $previous;
		$return_paging['next']			= $next;
		$return_paging['page_list']		= $page_list;
		
		
		
		return $return_paging;
	}
	/***********************************************************************************
	* string nextPrev (int curpage, int pages)
	* Returns "Previous | Next" string for individual pagination (it's a word!)
	***********************************************************************************/
	function nextPrev($curpage, $pages, $url_parts="")
	{
		$next_prev = "";
		$prev = "";
		$next = "";
		
		if (($curpage-1) <= 0)
		{
			$next_prev .= "&lt;&nbsp;Previous";
			$prev .= "&lt;&nbsp;Previous";
		}
		else
		{
			$next_prev .= "<a href=\"".$website_url."/performance?page=".($curpage-1).$url_parts."\">&lt;&nbsp;Previous</a>";
			$prev .= "<a href=\"".$website_url."/performance?page=".($curpage-1).$url_parts."\">&lt;&nbsp;Previous</a>";
		}
		
		$next_prev .= " | ";
		
		if (($curpage+1) > $pages)
		{
			$next_prev .= "Next&nbsp;&gt;";
			$next .= "Next&nbsp;&gt;";
		}
		else
		{
			$next_prev .= "<a href=\"".$website_url."/performance?page=".($curpage+1).$url_parts."\">Next&nbsp;&gt;</a>";
			$next .= "<a href=\"".$website_url."/performance?page=".($curpage+1).$url_parts."\">Next&nbsp;&gt;</a>";
		}
		
		$return_array['prev'] 		= $prev;
		$return_array['next'] 		= $next;
		$return_array['next_prev'] 	= $next_prev;
		return $return_array;
	}

}

$obj = new fibocms ();	