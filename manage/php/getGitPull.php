<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// REQUIRE
require_once 'helper.php';

// Check that the request that’s being made is an Ajax request 
if (is_ajax()) {
    if (!empty($_POST["resource"]) && !empty($_POST["site"]) && !empty($_POST["type"])) {        
        $resource = $_POST['resource'];        
        $site = $_POST['site'];
        $type = $_POST['type'];
        
        // Change path to the current site directory
        chdir("../../zone/". $resource . "/" . $site);
        
        if ($type == 'pull') {
            if (isGit($resource,$site)) {            
                gitPull($resource,$site);
            }
            // not exists
            else {
                echo json_encode(999);
            }
        }
        else if ($type == 'query') {
            queryGit($resource,$site);
        }
    }
    else {
        echo json_encode (new stdClass);
        exit;
    }
}
else {
    echo json_encode (new stdClass);
    exit;
}

function gitPull($resource,$site){

	// Array of action status
    // [0] = git fetch origin 
    // [1] = git diff --name-status master origin/master
    // [2] = git merge remotes/origin/master
    // [3] = updated files process
    // value of 0 means no error, anything else means 
    $return_var = array();
	$fileList = array();
	$merge = array();
	$fetch = array();
	
	// fetch from remote origin
    exec('"C:\Program Files\Git\cmd\git.exe" fetch origin 2>&1', $fetch, $return_var[0]);
	
	exec('"C:\Program Files\Git\cmd\git.exe" diff --name-status master origin/master 2>&1', $fileList, $return_var[1]);
	//file_put_contents('fileList.txt', print_r($fileList, true), LOCK_EX);
	//$fileList = array(" A	Dog-2018.png"," A	images/Dog.png"," D	Thumbs.db"," M	index.php"," A	New folder/New folder/New folder/test.php");
	//$fileList = array(" R100	Dog-2018.png");
	
	//if there is [RENAME] exit out with code 998 and abort git pull
	foreach ($fileList as $key => $val) {
		$file = explode("\t", $val);
		$command = trim($file[0]);
		if (fnmatch('R*', $command)){			
			echo json_encode(998);
			exit;
		}
	}
	
	exec('"C:\Program Files\Git\cmd\git.exe" merge remotes/origin/master 2>&1', $merge, $return_var[2]);
	
	$return_var[3] = processFiles($resource,$site,$fileList);

	// Return the sum of error code, if no error in any of the previous steps the sum equal 0 
    echo json_encode(array_sum($return_var));
	
}


function processFiles($resource,$site,$fileList) {

    $selectQuery = "SELECT idsites FROM webutility.sites where resource = :resource and sitename = :sitename";
			  
    $bindV = array("resource"=>$resource,"sitename"=>$site);
    $siteId = query_db($selectQuery,$bindV,"false",true);
		
	foreach ($fileList as $key => $val) {
		
		$file = explode("\t", $val);
		$path_parts = pathinfo($file[1]);
		$path_parts['dirname'] = ($path_parts['dirname'] == '.' ? "" : $path_parts['dirname']);
		$addQuery = "insert ignore into webutility.wzde_files (idsites,zone,site,path,filename,type,hash,status,creationdate,publishdate) VALUES (:idsites,:zone,:site,:path,:filename,:type,:hash,:status,:creationdate,:publishdate)";   
		$modifyQuery = "update webutility.wzde_files set status=:status where hash=:hash";
		$deleteQuery = "delete from webutility.wzde_files where hash like :hash";
		$renameQuery = "";
		
		// Add 
		if(trim($file[0]) == 'A') {
			//if we have multiple level deep file insert row for each directory level
			if ($path_parts['dirname']) {
				$directory = explode("/", $path_parts['dirname']);
				$path  = "";
				foreach ($directory as $key => $val) {
					$bindV = array(
						"idsites" => $siteId["idsites"],
						"zone" => strtolower($resource),
						"site" => $site,
						//replace backslash with forward slash
						"path" => strtolower($resource) . "/" . $site . $path,
						"filename" => $val,
						"type" => "directory",
						"hash" => getPrefix($resource) . rtrim(strtr(base64_encode($site . $path . "\\" . $val), '+/=', '-_.'), '.'),
						"status" => "draft",
						"creationdate" => iso_8601_utc_time(),
						"publishdate" => NULL
					);
					$path .= "/" .$val;					
					updateTable($addQuery,$bindV);
				}
			}
			// then we insert new file			
			$bindV = array(
				"idsites" => $siteId["idsites"],
				"zone" => strtolower($resource),
				"site" => $site,
				//replace backslash with forward slash
				"path" => strtolower($resource) . "/" . $site . ($path_parts['dirname'] ? "/" . $path_parts['dirname'] : ""),
				"filename" => $path_parts['basename'],
				"type" => mime_content_type($file[1]),
				"hash" => getPrefix($resource) . rtrim(strtr(base64_encode($site . "\\" . ($path_parts['dirname'] ? $path_parts['dirname'] . "\\" : "") . $path_parts['basename']), '+/=', '-_.'), '.'),
				"status" => "draft",
				"creationdate" => iso_8601_utc_time(),
				"publishdate" => NULL
			);
			updateTable($addQuery,$bindV);
		}
		// Delete 
		else if(trim($file[0]) == 'D') {
			
			$bindV = array(
				"hash" => getPrefix($resource) . rtrim(strtr(base64_encode($site . "\\" . ($path_parts['dirname'] ? $path_parts['dirname'] . "\\" : "") . $path_parts['basename']), '+/=', '-_.'), '.') . "%"
			);		
			updateTable($deleteQuery,$bindV);
		}
		// Modify 
		else if (trim($file[0]) == 'M') {
			
			$bindV = array(
				"hash" => getPrefix($resource) . rtrim(strtr(base64_encode($site . "\\" . ($path_parts['dirname'] ? $path_parts['dirname'] . "\\" : "") . $path_parts['basename']), '+/=', '-_.'), '.'),
				"status" => "draft"
			);  			
			updateTable($modifyQuery,$bindV);			
		}
		// Rename disabled (not allow in wzde manage)
		// else if(trim($file[0]) == 'R100') {
			// $original = $file[1];
			// $new = $file[2];
		// }
		//everything else
		else{		
		
		}

	}		
}

function getPrefix($resource) {
    $link = "";
	
    switch (strtoupper($resource)) {
    case 'STAFF':
        $link = "l1_";
        break;
    case 'HOME':
        $link = "l2_";
        break;
    case 'CUSTOMER':
        $link = "l3_";
        break;
    case 'STAFFAPPS':
        $link = "l4_";
        break;
    case 'ATSIGNAGE':
        $link = "l5_";
        break;
    case 'LOGI':
        $link = "l6_";
        break;                
    case 'DB_STORAGE':    
        $link = "m7_";
        break;    
    default :
        return FALSE;
    }
    return $link;
}

//out : 0 for success, 1 for error
function updateTable($query,$bindV){

    $database = new mysql_pdo();
    $database->query($query);
	
    foreach ($bindV as $key=>$val) {
        $database->bind(':'.$key, $val);
    }
    $database->execute();  

    //false equal no error
    if (!$database->getLastError()==='00000'){
        return 1;
    }
    else {
        return 0;
    }

}


//end of php