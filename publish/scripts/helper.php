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

// Return oid based on provided ename.
function enameToOid($ename) {

    $query = "SELECT master.oid FROM coredata_live.coreUser_master AS master 
              INNER JOIN coredata_live.coreUser_name AS name ON name.oid = master.oid 
              WHERE name.nameValue=:ename  AND name.nameType='ename' AND userStatus='Active'";
                
    $bindV = array("ename"=>$ename);
    $result = query_db($query,$bindV,"false",true);
    
    return addslashes($result['oid']);

}

// Return user's mater access based on provided OID 
function checkWzdeMasterAccess($oid) {
    if (HOST_PORT==8080) {
        $wzdeversion='DEV';
    }
    else {
        $wzdeversion='LIVE';
    }
    $access = "";
    $connection = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
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
            if (is_array($row) || is_object($row)) {
                // Build access string based on whether or not access value is equal to 1.
                foreach ($row AS $key => $value) {
                    if (($value == 1) AND (strpos($key,'wzde') !== false) ) {
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

function checkWzdeSiteAccess($oid,$site) {
    $access = "";
    $connection = mysqli_connect(DB_HOST,DB_USER,DB_PASS,DB_NAME);
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
    