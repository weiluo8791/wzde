<?php
/*******************************************************************************
mariadb query/execute function

version 1.0 
date    5/1/2014
by      Wei Qi Luo
*******************************************************************************/

//error_reporting(E_ALL);
//ini_set('display_errors', '1');

require_once 'setting.php';
require_once 'mysql_pdo.class.php';

//POST
if( !empty( $_POST ) ) {
    $query=$_POST['query'];
    //$bindV=json_decode($_POST['bindV']);
    $bindV=json_decode(str_replace('\\u0000', "", $_POST['bindV']));
    echo query_db($query,$bindV,"false",false);
}
//POST (can not use $_POST because it is in raw json and not in key=value pair)
else if (!empty($postData = json_decode(file_get_contents("php://input")))) {
    $query=$postData->query;
    $bindV=$postData->bindV;
    echo query_db($query,$bindV,"false",false,false);
}
//GET
else {
    $query=$_GET['query'];
    //$bindV=json_decode($_GET['bindV']);
    $bindV=json_decode(str_replace('\\u0000', "", $_GET['bindV']));
    echo query_db($query,$bindV,$_GET['multi'],true);
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
        return json_encode($row);   
    }
    else {
       
        if (!$database->getLastError()==='00000'){
            return json_encode($database->getLastError());
        }
    }
    
}

/* End of file mariadb_query.php */


