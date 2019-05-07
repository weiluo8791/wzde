<?php
//REQUIRE
require_once 'setting.php';
require_once 'mysql_pdo.class.php';

// Return user's ename.
function getEname() {
    // Define variables.
    $remoteUser = (isset($_SERVER['REMOTE_USER']) ? strtolower($_SERVER['REMOTE_USER']) : "");
    $httpRemoteUser = (isset($_SERVER['HTTP_REMOTEUSER']) ? strtolower($_SERVER["HTTP_REMOTEUSER"]) : "");
    $ename = "";

    // Get the user's ename.
    if ($remoteUser) {
        $ename = str_replace("meditech\\","",$remoteUser);
    }
    if ($httpRemoteUser) {
        $ename = str_replace("@meditech.com","",$httpRemoteUser);
    }

    // Return ename.
    return $ename;
}

function query_db($query,$bindV,$multi="false",$result=false,$debug=false) {
    $database = new mysql_pdo();
        
    $database->query($query);
    foreach ($bindV as $key=>$val) {
        $database->bind(':'.$key, $val);
    }
    $database->execute();
    
    if ($debug) {
        ob_start();
        $database->debugDumpParams();
        $d = ob_get_contents();
        if (($fp = fopen("query_db.txt", 'wb'))) {
            fwrite($fp,$d);
            fclose($fp);
        }
        ob_end_clean();
    }
       
    if ($result) {
        if ($multi === "true") {
            $row = $database->resultset();
        }
        else {
            $row = $database->single();
        }
        return $row;
    }
    else {
       
        if (!$database->getLastError()==='00000'){
            return json_encode($database->getLastError());
        }
    }
    
}

// Return oid based on provided ename.
function enameToOid($ename) {

    $query = "SELECT master.oid FROM coredata_read.coreUser_master AS master 
              INNER JOIN coredata_read.coreUser_name AS name ON name.oid = master.oid 
              WHERE name.nameValue=:ename  AND name.nameType='ename' AND userStatus='Active'";
                
    $bindV = array("ename"=>$ename);
    $result = query_db($query,$bindV,"false",true);
    
    return addslashes($result['oid']);

}

// Return user's mater access based on provided OID 
// in : 0 = user oid
// out: master access string
function checkWzdeMasterAccess($oid) {
    $access = "";
    if (HOST_PORT==8080) {
        $wzdeversion='DEV';
    }
    else {
        $wzdeversion='LIVE';
    }
	// Query string
	$query  = "SELECT * ";
	$query .= "FROM access_master ";
	$query .= "WHERE user_oid='$oid' AND wuversion='$wzdeversion'";    
	// PDO
    $bindV = array("user_oid"=>$oid);
    $result = query_db($query,$bindV,"false",true);
	
	if ($result) {
		foreach ($result AS $key => $value) {
			if (($value == 1) AND (strpos($key,'wzde') !== false) ) {
				$access .= $key . "|";
			}
		}
	}	
	return rtrim($access,"|");      
}

// Return user's site access based on provided OID and site
// in : 0 = user oid 1 = site
// out: site access string
function checkWzdeSiteAccess($oid,$site) {	
    $access = "";
	// Query string
	$query = "SELECT * ";
	$query .= "FROM access_site A ";
	$query .= "INNER JOIN sites B ON A.site_id = B.idsites ";
	$query .= "WHERE A.user_oid='$oid' AND B.sitename='$site'";
    
	// PDO
	$bindV = array("oid"=>$oid,"site"=>$site);	
    $result = query_db($query,$bindV,"false",true);
	
	if ($result) {
		foreach ($result AS $key => $value) {
			if (($value == 1) AND (strpos($key,'wzde') !== false) ) {
				$access .= $key . "|";
			}
		}
	}
    return rtrim($access,"|"); 
}

// query git status
// in :0=resource 1=sitename
// out:json 
function queryGit($resource,$site,$type = 'canGit'){
    // default value
    $canGit = false;
    $isGit = false;
    // get user oid and git status
    $user_ename = getEname();
    $user_oid = enameToOid($user_ename);    
	$canGit = getWzdesiteGitAccess($user_oid,$site);
	$isGit = isGit($resource,$site);
    
    $result = ["canGit"=>$canGit, "isGit"=>$isGit];
    echo json_encode($result);   
}

function checkIsGit($resource,$site) {	
    $query = "SELECT isgit from webutility.sites 
              WHERE resource=:resource AND sitename=:site";
			  
    $bindV = array("resource"=>$resource,"site"=>$site);
    $result = query_db($query,$bindV,"false",true);
    
    return $result['isgit'];		
}

function getWzdesiteGitAccess($user_oid,$site) {
    $access = checkWzdeSiteAccess($user_oid,$site);
    //if the user has wzde_write access to site return true
    if (strpos($access,'wzde_git') !== false) {
        return TRUE;
    }
    else {
        return FALSE;
    }
}

// Check if git repo already created
// Return True if git repo created 
// Return False otherwise
function isGit($resource,$site) {
	
    //$error=[];
    //chdir("../../zone/". $resource . "/" . $site);
    //exec('"C:\Program Files\Git\cmd\git.exe" rev-parse --verify master 2>&1', $output, $error[0]);
    //exec('"C:\Program Files\Git\cmd\git.exe" ls-remote -q --refs 2>&1', $output, $error[1]);    
	$result = checkIsGit($resource,$site);
	
    if ($result == "Y")
        return TRUE;
    else 
        return FALSE;        
}

// Function to check if the request is an AJAX request
function is_ajax() {
  return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

if(!function_exists('mime_content_type')) {

    function mime_content_type($filename) {

        $mime_types = array(

            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );
		$ext = explode('.',$filename);
        $ext = strtolower(array_pop($ext));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        }
        elseif (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        }
        else {
            return 'application/octet-stream';
        }
    }
}

function iso_8601_utc_time($precision = 0)
{
    $time = gettimeofday();

    if (is_int($precision) && $precision >= 0 && $precision <= 6) {
        $total = (string) $time['sec'] . '.' . str_pad((string) $time['usec'], 6, '0', STR_PAD_LEFT);
        $total_rounded = bcadd($total, '0.' . str_repeat('0', $precision) . '5', $precision);
        @list($integer, $fraction) = explode('.', $total_rounded);
        $format = $precision == 0
            ? "Y-m-d H:i:s"
            : "Y-m-d H:i:s.".$fraction;
        return gmdate($format, $integer);
    }

    return false;
}

// log cmd event
function logEvent($cmd,$output,$error) {
	$logfile = '../log/' . date('Y'.'m') . '_svnOps.txt';
	
	if (!$handle = fopen($logfile, 'ab')) {
		echo "Cannot open file " . $logfile;
		exit;
	}
	
	$timeStamp = date('YmdHis');
	$logEntry = $timeStamp . "|" . $cmd . "|" . print_r($output, true) . "|" . $error . "\r\n";
	
	// Write $logEntry to our opened file.
	if (fwrite($handle, $logEntry) === FALSE) {
		echo "Cannot write to file " . $logfile;
		exit;
	}
    fwrite($handle,"\r\n\r\n");
	fclose($handle);
}

//end of php    