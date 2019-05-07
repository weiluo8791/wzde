<?php
require_once 'setting.php';
require_once 'mysql_pdo.class.php';

$script=$_POST['script'];
$data = $_POST['data'];

$result = readTable($data,$script);

//set header to be json
header('Content-type: application/json');

//return result if success or error if fail
if (is_array($result)) {
    ob_start('ob_gzhandler');
    echo json_encode($result);     
}
else {
    $response_array['status'] = 'error';
    $response_array['error'] = $result;
    echo json_encode($response_array);    
}

//in : bind data, sql script name
//out: error string or result in array
//data format = array(array([name]=value,...))
function readTable($data,$script) {
    $query = file_get_contents('sql/'.$script);
    $database = new mysql_pdo();   
    $database->query($query);
    $data = array_filter($data);
    if (!empty($data)) {
        foreach ($data as $arrVal) {
            foreach ($arrVal as $key=>$val) {
                $database->bind(':'.$key, $val);
            }    
        }
    }
     
    $rows = $database->resultset();    
    
    if (!$database->getLastError()==='00000'){
        return $database->getLastError();
    }
    else {
        return $rows;
    } 
}


/* End of file */