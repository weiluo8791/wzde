<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

// REQUIRE
require_once 'helper.php';

// Check that the request that’s being made is an Ajax request 
if (is_ajax()) {
	if (!empty($_GET["site"])) {
		$site = $_GET["site"];
		$user_ename = getEname();
		$user_oid = enameToOid($user_ename);    
		
		$masterAccess = getWzdeMasterAccess($user_oid);
		$siteCommitAccess = getWzdeSiteCommitAccess($user_oid,$site);
		$sitePublishAccess = getWzdeSitePublishAccess($user_oid,$site);
		
		$result = array("master"=>$masterAccess, "commit"=>$siteCommitAccess, "publish"=>$sitePublishAccess);
		
		echo json_encode ($result);
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

function getWzdeMasterAccess($user_oid){
    $access = checkWzdeMasterAccess($user_oid);
    if (strpos($access,'wzde_publish') !== false) {
        return true;
    }
    else {
        return false;
    }
}

function getWzdeSiteCommitAccess($user_oid,$site) {
    $access = checkWzdeSiteAccess($user_oid,$site);
    //if the user has wzde_write access to site return true
    if (strpos($access,'wzde_commit') !== false) {
        return true;
    }
    else {
        return false;
    }
}

function getWzdeSitePublishAccess($user_oid,$site) {
    $access = checkWzdeSiteAccess($user_oid,$site);
    //if the user has wzde_write access to site return true
    if (strpos($access,'wzde_publish') !== false) {
        return true;
    }
    else {
        return false;
    }
}