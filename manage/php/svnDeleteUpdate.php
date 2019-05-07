<?php
// REQUIRE
require_once 'helper.php';

// Check that the request that’s being made is an Ajax request 
if (is_ajax()) {
	
	$site = $_GET['site'];
	$resource = $_GET['resource'];
	
	// Change path to the current site directory
	chdir("../../zone/". $resource . "/" . $site);
	$cmd="E:\wzde_dev\manage\php\svndel.bat";
	exec($cmd,$out,$err);

}
else {
    echo json_encode (new stdClass);
    exit;
}