<?php
//error_reporting(E_ALL);
//ini_set('display_errors', '1');

global $WZDE;
require_once "wzdePublish_PDO.php";

$folder = $_GET['folder'];
$resource = $_GET['resource'];
// escape underscore and double slash 
$keyword = $resource . "/" . str_replace(array('\\', '_'), array('\\\\', '\\_'), $folder);

$WZDE['SELECTED_SITE'] = $folder;

//$query = "SELECT zone,site,path,filename,type,status,creationdate,publishdate FROM webutility.wzde_files where site = :site and not type ='directory' order by path";
$query = "SELECT zone,site,path,filename,type,status,creationdate,publishdate,commitdate,hash 
            FROM webutility.wzde_files
            where path like :keyword 
            order by path";

//$bindV = array("site"=>$folder,"keyword"=>$keyword);
$bindV = array("keyword"=>$keyword);

$result = query_db($query,$bindV,"true",true);
$access = getAllowedAction($folder);

foreach ($result as $key => $value) {
	//$result[$key]["actions"]=getAllowedAction($folder);
	$result[$key]["actions"] = $access;
}
// if the query returned results...
if (count($result) > 0) {
	//gzip results
	ob_start('ob_gzhandler');
}

//return results
echo json_encode($result);

/* End of file getSiteFiles_json.php */