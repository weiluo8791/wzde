<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'setting.php';
require_once 'mysql_pdo.class.php';

$script=$_POST['script'];
$data = $_POST['data'];

$result = updateTable($data,$script);

//return success or error string
if (!$result) {
    $response_array['status'] = 'success';
}
else {
    $response_array['status'] = 'ERROR';
}

//set header to be json
header('Content-type: application/json');
echo json_encode($response_array);

//return error if failed
//data format= array(array([name]=value,...))
function updateTable($data,$script) {
    $query = file_get_contents('sql/'.$script);
    $database = new mysql_pdo();   
    $arrList = [];
    
    //bind users in array
    if ($script ==='deleteUsersFromSite.sql') {
        $arrList = explode("|", $data[0]["users"]);
        $comma = '';
        for($i=0; $i<count($arrList); $i++){
            $query .= $comma.':p'.$i;       // :p0, :p1, ...
            $comma = ',';
        }
        $query .= ')';
    }
    
    //bind sites in array
    if ($script ==='deleteSitesFromUser.sql') {
        $arrList = explode("|", $data[0]["sites"]);
        $comma = '';
        for($i=0; $i<count($arrList); $i++){
            $query .= $comma.':p'.$i;       // :p0, :p1, ...
            $comma = ',';
        }
        $query .= ')';
    }
    
    //bind sites in array
    if ($script ==='editSitesForUser.sql') {
        $arrList = (array) json_decode($data[0]["sites"],true);        
        $comma = '';
        for($i=0; $i<count($arrList); $i++){
            $query .= $comma.':p'.$i;       // :p0, :p1, ...
            $comma = ',';
        }
        $query .= ')';
    }    
    
    //bind sites in array
    if ($script ==='editUsersForSite.sql') {
        $arrList = (array) json_decode($data[0]["users"],true);        
        $comma = '';
        for($i=0; $i<count($arrList); $i++){
            $query .= $comma.':p'.$i;       // :p0, :p1, ...
            $comma = ',';
        }
        $query .= ')';
    }        
    
    //bind sites in array
    if ($script ==='addSitesForUser.sql') {
        $arrList = (array) json_decode($data[0]["sites"],true);        
        $counter = 1;
        $total = count($arrList) * 8;
        foreach ($arrList as $val) {
            $query .= '(';
            for($i=0; $i<8 ; $i++){
                if ($i==0) {
                    $query .= ':user_oid' . ',';
                }
                else {
                    $query .= ':p'.($i + $counter) . ',';       // :p0, :p1, ...           
                }        
            }
            $query = rtrim($query, ",");
            $query .= '), ';
            $counter+=8;
        }
        $query = rtrim($query,", ");
    }    

    //bind sites in array
    if ($script ==='addUsersForSite.sql') {
        $arrList = (array) json_decode($data[0]["users"],true);        
        $counter = 1;
        $total = count($arrList) * 8;
        foreach ($arrList as $val) {
            $query .= '(';
            for($i=0; $i<8 ; $i++){
                if ($i==1) {
                    $query .= ':site_id' . ',';
                }
                else {
                    $query .= ':p'.($i + $counter) . ',';       // :p0, :p1, ...           
                }
            }
            $query = rtrim($query, ",");
            $query .= '), ';
            $counter+=8;
        }
        $query = rtrim($query,", ");
    }        
            
    $database->query($query);
    
    $data = array_filter($data);
    if (!empty($data)) {  
        foreach ($data as $arrVal) {
            foreach ($arrVal as $key=>$val) {
                //bind value if not users or sites
                if ( ($key !== 'users') && ($key !== 'sites') ) {
                    $database->bind(':'.$key, $val);
                }
            }
            
            //for delete
            if ($script ==='deleteUsersFromSite.sql' || $script ==='deleteSitesFromUser.sql' ) {            
                for($i=0; $i<count($arrList); $i++){
                    $database->bind(':p'.$i, $arrList[$i]);
                }
            }
            
            //for add
            else if ($script ==='addUsersForSite.sql') {
                $counter = 1;                                
                foreach($arrList as $val) {
                    for($j=0; $j<8 ; $j++){
                        
                        if ($j==0) {
                            $database->bind(':p'.($j+$counter), $val["id"]);
                         }
                        else if ($j==2) {
                            $database->bind(':p'.($j+$counter), $val["access"]["wzde_write"]);
                        }
                        else if ($j==3) {
                            $database->bind(':p'.($j+$counter), $val["access"]["wzde_publish"]);                            
                        }
                        else if ($j==4) {
                            $database->bind(':p'.($j+$counter), $val["access"]["wzde_commit"]);                            
                        }
                        else if ($j==5) {
                            $database->bind(':p'.($j+$counter), $val["access"]["wzde_read"]);                           
                        }
                        else if ($j==6) {
                            $database->bind(':p'.($j+$counter), $val["access"]["wzde_checkout"]);                            
                        }                        
                        else if ($j==7) {
                            $database->bind(':p'.($j+$counter), $val["access"]["wzde_git"]);                            
                        }                                                       
                    }
                    $counter+=8;
                }                
            }
            //for add
            else if ($script ==='addSitesForUser.sql' ) {
                $counter = 1;                                
                foreach($arrList as $val) {
                    for($j=0; $j<8 ; $j++){
                        
                        if ($j==1) {
                            $database->bind(':p'.($j+$counter), $val["id"]);                            
                        }
                        else if ($j==2) {
                            $database->bind(':p'.($j+$counter), $val["access"]["wzde_write"]);                            
                        }
                        else if ($j==3) {
                            $database->bind(':p'.($j+$counter), $val["access"]["wzde_publish"]);                            
                        }
                        else if ($j==4) {
                            $database->bind(':p'.($j+$counter), $val["access"]["wzde_commit"]);                            
                        }
                        else if ($j==5) {
                            $database->bind(':p'.($j+$counter), $val["access"]["wzde_read"]);                            
                        }
                        else if ($j==6) {
                            $database->bind(':p'.($j+$counter), $val["access"]["wzde_checkout"]);                            
                        }                        
                        else if ($j==7) {
                            $database->bind(':p'.($j+$counter), $val["access"]["wzde_git"]);                            
                        }                                                       
                    }
                    $counter+=8;
                }                
            }            
            // for edit           
            else {
                for($i=0; $i<count($arrList); $i++){
                    $database->bind(':p'.$i, $arrList[$i]["id"]);                    
                    foreach ($arrList[$i]["access"] as $key=>$val) {
                    
                        $database->bind(':'.$key, $val);                        
                    }                    
                }
            }
            //update each row
            $database->execute();                  
        }
    }        
        
    //false equal no error
    if (!$database->getLastError()==='00000'){
        return $database->getLastError();
    }
    else {
        return false;
    }        
}


/* End of file */