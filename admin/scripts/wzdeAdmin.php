<?php
/*******************************************************************************
WZDE Publish PDO mySQL function

1.0 06/2014
by Wei Qi Luo
*******************************************************************************/
//REQUIRE
require_once 'setting.php';
require_once 'helper.php';
require_once 'mysql_pdo.class.php';
//GLOBAL
global $WZDE;
$WZDE['USER_NAME'] = getEname();
$WZDE['USER_OID'] = enameToOid($WZDE['USER_NAME']);

if( isset($_GET["site"]) && isset($_GET["resource"]) ) {
    header('Content-type: application/json');
    ob_start('ob_gzhandler');
    echo json_encode(getAllFileBySite($_GET["resource"],$_GET["site"]));
}
else if (isset($_GET["resource"])){
    header('Content-type: application/json');
    ob_start('ob_gzhandler');
    echo json_encode(getAllFileByZone($_GET["resource"]));
}



//in :zone
//out:array of associated array [array(path=>path,filename=>filename,checksum=>hash,...)]
function getAllFileByZone($zone) {
    $dir = listDirectory($zone);
    foreach ($dir as $val) {
        list($total,$fileList) = ListAllFiles($zone,$val);
        foreach ($fileList as $key=>$val) {
            if ($val!=='DIRECTORY') {
                $files[]= array("path"=>dirname($key),"filename"=>basename($key),"checksum"=>$val);
            } 
        }
    }
    return $files;    
}


//in :site
//out:array of associated array [array(path=>path,filename=>filename,checksum=>hash,...)]
function getAllFileBySite($zone,$site) {
    list($total,$fileList) = ListAllFiles($zone,$site);
    foreach ($fileList as $key=>$val) {
        if ($val!=='DIRECTORY') {
            $files[]= array("path"=>dirname($key),"filename"=>basename($key),"checksum"=>$val);
        } 
    }
    return $files;    
}

//initialize mySQL database
//in : nothing
//out: nothing
function init_database() {
    global $WZDE;
    // connect to the database
	try {
        $WZDE['dbh'] = new PDO('mysql:host=ATDMariaDBLive.meditech.com;dbname=' . DB_NAME, DB_USER, DB_PASS, array (
            PDO :: ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION
            )
        );
    }
	catch( PDOException $e ) {
		die( "Error connecting to SQL Server" . $e); 
	}
}

function admin_content($s) {
    global $WZDE;
    $WZDE["_ADM_ARRAY"][] = "\n<div class=\"admin_content\">$s</div>\n";
}

function error($s) {
    error_message($s);
}

function error_message($s = "") {
    global $WZDE;
    if ($s)
        $WZDE["_ERR_ARRAY"][] = "<p class=\"error_message\">$s</p>\n";
    return isset ($WZDE["_ERR_ARRAY"]) ? $WZDE["_ERR_ARRAY"] : array ();
}

function init_Admin() {
    global $WZDE;

    // initialize display vars
    foreach (array (
                'MESSAGES',
                'ERRORS',
                'ADMIN_CONTENT',
            ) as $v)
    $WZDE[$v] = "";
    
    $WZDE['TITLE'] = "WZDE Admin Functions";
    $WZDE['SELF'] = $_SERVER["SCRIPT_NAME"];
    
    $a = session_id();
    if(empty($a)) session_start();
}

function set_vars() {
    global $WZDE;
    if (isset ($WZDE["_MSG_ARRAY"]))
            foreach ($WZDE["_MSG_ARRAY"] as $m)
                    $WZDE["MESSAGES"] .= $m;
    if (isset ($WZDE["_ERR_ARRAY"]))
            foreach ($WZDE["_ERR_ARRAY"] as $m)
                    $WZDE["ERRORS"] .= $m;
    if (isset ($WZDE["_ADM_ARRAY"]))
            foreach ($WZDE["_ADM_ARRAY"] as $m)
                    $WZDE["ADMIN_CONTENT"] .= $m;                                     
}

function switch_page($page) {
    global $WZDE;
    $WZDE['ID']=$page; 
    // $a is an accumulator for the output string
    $a = <<<EOT
    <div class='row'>
     <div class='small-12 columns'>
      <div id='list'><p class='message process'>Loading...</p></div>
     </div>
    </div>
EOT;
    admin_content($a);
}


function convert_smart_quotes($string) 
{ 
    $search = array(chr(145), 
                    chr(146), 
                    chr(147), 
                    chr(148), 
                    chr(151)); 

    $replace = array("'", 
                     "'", 
                     '"', 
                     '"', 
                     '-'); 

    return str_replace($search, $replace, $string); 
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

function listDirectory($resource) {    
    $folderPath=$_SERVER["APPL_PHYSICAL_PATH"] . 'zone/' . $resource;
    if ($handle = opendir($folderPath)) {
        $blacklist = array('.', '..', '.quarantine', '.tmb');
        while (false !== ($file = readdir($handle))) {
            if (!in_array($file, $blacklist)) {
                $dir[]=$file;
            }
        }
        closedir($handle);
    }
    return $dir;
}

//return total number of files and array of filename->hash
function listAllFiles ($resource,$site) {
    $folderPath=$_SERVER["APPL_PHYSICAL_PATH"] . 'zone/' . $resource. '/' . $site . '/';
    
    If (is_dir($folderPath)) {
        $iterator = new RecursiveDirectoryIterator($folderPath);
        $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
        //filter excluded folder
        $filter = new MyRecursiveFilterIterator($iterator);
        $all_files  = new RecursiveIteratorIterator($filter,RecursiveIteratorIterator::SELF_FIRST);

        $total=0;
        $dir_content=array();
        foreach ($all_files as $filePath => $fileInfo) {
        $dir_content[str_replace(
                    str_replace('\\', '/', $_SERVER["APPL_PHYSICAL_PATH"] . 'zone/'),
                    '',
                    str_replace('\\', '/', $fileInfo->getPathname()))] = is_dir($fileInfo->getPathname()) ? 'DIRECTORY' : hash('sha512',$filePath);
        $total++;
        }       
        return array ($total,$dir_content);       
    }  
}

//customer class extend RecursiveFilterIterator to filter out folder that we want to exclude
class MyRecursiveFilterIterator extends RecursiveFilterIterator {
    
    public static $FILTERS = array(
        '.svn',
    );

    public function accept() {
        return !in_array(
            $this->current()->getFilename(),
            self::$FILTERS,
            true
        );
    }
}        

//recursive fucntion to get all files count
function getFileCount($path) {
    $size = 0;
    $ignore = array('.','..','.svn');
    $files = scandir($path);
    foreach($files as $t) {
        if(in_array($t, $ignore)) continue;
        if (is_dir(rtrim($path, '/') . '/' . $t)) {
            $size += getFileCount(rtrim($path, '/') . '/' . $t);
        } else {
            $size++;
        }   
    }
    return $size;
}


?>
