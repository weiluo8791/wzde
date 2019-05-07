<?php

//REQUIRE
require_once 'setting.php';
require_once 'ldap.php';
require_once 'mysql_pdo.class.php';

// Return user's ename.
function getEname() {
 $remoteUser = strtolower($_SERVER['REMOTE_USER']);
 $ename = str_replace("meditech\\","",$remoteUser);
 return $ename;
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

// Return user's master access based on provided OID 
function checkWzdeMasterAccess($oid) {
    if (HOST_PORT==8080) {
        $wzdeversion='DEV';
    }
    else {
        $wzdeversion='LIVE';
    }
    $access = "";
    $connection = mysqli_connect(DB_LIVE,DB_USER,DB_PASS,DB_NAME);
    if (mysqli_connect_errno()) {
        die("Database connection failed: " . mysqli_connect_error() . " (" . mysqli_connect_errno() . ")");
    } 
    else {
        $query  = "SELECT * ";
        $query .= "FROM access_master ";
        $query .= "WHERE user_oid='$oid' AND wuversion='$wzdeversion'";
        $result = mysqli_query($connection,$query);
        if (!$result) {
            die("Database query failed.");
        } 
        else {
            $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
            // Build access string based on whether or not access value is equal to 1.
            foreach ($row AS $key => $value) {
                if (($value == 1) AND (strpos($key,'wzde') !== false) ) {
                    $access .= $key . "|";
                }
            }
            mysqli_free_result($result);
       }
    }
    mysqli_close($connection);
    return rtrim($access,"|");    
}

function checkWzdeSiteAccess($oid,$site) {
    $access = "";
    $connection = mysqli_connect(DB_LIVE,DB_USER,DB_PASS,DB_NAME);
    if (mysqli_connect_errno()) {
        die("Database connection failed: " . mysqli_connect_error() . " (" . mysqli_connect_errno() . ")");
    } 
    else {
      // Check site access in database.
      $query = "SELECT * ";
      $query .= "FROM access_site A ";
      $query .= "INNER JOIN sites B ON A.site_id = B.idsites ";
      $query .= "WHERE A.user_oid='$oid' AND B.sitename='$site'";
      $result = mysqli_query($connection,$query);

      if (!$result) {
         die("Database query failed.");
      }
      else {
         $row = mysqli_fetch_array($result,MYSQLI_ASSOC);
         // Build access string based on whether or not access value is equal to 1.
         if ($row) {
            foreach ($row AS $key => $value) {
               if ($value == 1) {
                  $access .= $key . "|";
               }
            }
         }
         mysqli_free_result($result);
      }
    }
    mysqli_close($connection);
    return rtrim($access,"|"); 
}

function getTotalFolderContents ($zone,$folder) {
    $fi = new FilesystemIterator(__DIR__, FilesystemIterator::SKIP_DOTS);
    printf("There were %d Files", iterator_count($fi));
    
}

// Check if user is admin.
function isAdmin($ename) {
	// Define variables.
	$ldap = new MeditechLDAP();
	$groups = array('Web Admin');
	$admin = false;
	
	// Check if user is in allowed groups.
	if ($ldap->isMember($ename,$groups)) {
		$admin = true;
	}	
	
	return $admin;

}
    