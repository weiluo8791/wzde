<?php
// REQUIRE
require_once 'helper.php';
// Constant for user
define("DEFAULT_USER",substr( $_SERVER['REMOTE_USER'], strrpos( $_SERVER['REMOTE_USER'], '\\' )+1));
// SVN defualt argument
$command = "--config-dir E:/Subversion --trust-server-cert --non-interactive --username ratservice --password GEODE";

// Check that the request that’s being made is an Ajax request 
if (is_ajax()) {
	
	$commitPath = $_SERVER["APPL_PHYSICAL_PATH"] . "zone\\" . $_POST['commitPath'];
	
	// Change path to the current site directory
	//chdir("../../zone/". $resource . "/" . $site);
	
	$cmd="svn commit $command -m \"propagate delete\" \"$commitPath\"";
	exec($cmd,$out,$err);
	logEvent($cmd,$out,$err);
	
	// Get revision from last commit
	$revision = end(preg_replace('/[^0-9]/', '', $out));
	if (!empty($revision)) {
		$author = DEFAULT_USER;
		// propset author on the last revision
		$cmd = "svn propset $command --revprop -r $revision svn:author $author";
		exec($cmd,$out,$err);
		logEvent($cmd,$out,$err);
	}	
	echo ($err ? json_encode($out) : 1); 
}
else {
    echo json_encode (new stdClass);
    exit;
}

