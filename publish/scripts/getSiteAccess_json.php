<?php
// error_reporting(E_ALL);
// ini_set('display_errors', '1');

global $WZDE;
require_once 'helper.php';

$WZDE['USER_NAME'] = getEname();
$WZDE['USER_OID'] = enameToOid($WZDE['USER_NAME']);

$site=$_GET['site'];

if ((strpos(checkWzdeSiteAccess($WZDE['USER_OID'],$site),'wzde_publish') !== false) || 
    (strpos(checkWzdeSiteAccess($WZDE['USER_OID'],$site),'wzde_commit') !== false) || 
    (strpos(checkWzdeMasterAccess($WZDE['USER_OID']),'wzde_publish') !== false)) {
    $result=1;
}
else {
    $result=0;
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
//return results
echo json_encode($result);
