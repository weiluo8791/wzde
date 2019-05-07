<?php
/*******************************************************************************
svn commands function

version 1.0 
date    8/1/2014
by      Wei Qi Luo
*******************************************************************************/
// error_reporting(E_ALL);
// ini_set('display_errors', '1');

//Require
require_once 'setting.php';
require_once 'mysql_pdo.class.php';
//config file
define("INIFILE", dirname(__FILE__) . "\\admin.ini");
$ini_array = parse_ini_file(INIFILE,TRUE);
//CONSTANT for SVN
define("VERSION", $ini_array["SYS"]["VERSION"]);
define("ZONE_ROOT", $ini_array["SVN"]["ZONE_ROOT"]);
define("SVNUSER",$ini_array["SVN"]["SVNUSER"]);
define("SVNPASS",$ini_array["SVN"]["SVNPASS"]);
define("COMMAND",$ini_array["SVN"]["COMMAND"]);
define("EVENTLOG", $ini_array["SVN"]["LOG"]);

//POST the SVN command
if( !empty( $_POST ) ) {
    $command=$_POST['command'];
    $checkoutPath=$_POST['cPath'];
    svn_command($command,$checkoutPath);
}

//return 0 for error, 1 for success
function svn_command ($command,$cPath) {
    $checkoutPath = ZONE_ROOT . $cPath;
    
    //check if SVN committed 
    if ($command === 'info') {
        $addition_arg = COMMAND;
        $cmd = "svn info $addition_arg $checkoutPath";
        //$err has value means not committed
        exec($cmd,$out,$err);
        logEvent($cmd,$out,$err);
    }
    //COMMIT whole site
    else if ($command === 'wholeCommit') {
        //if already initial committed just commit the whole site again
        if ($_POST['committed']==1) {
            $message = "commit from WZDE by ". substr( $_SERVER['REMOTE_USER'], strrpos( $_SERVER['REMOTE_USER'], '\\' )+1 );            
            $command = COMMAND;
            $cmd = "svn --force --depth infinity add $command $checkoutPath";
            exec($cmd,$out,$err);
            logEvent($cmd,$out,$err);
            $cmd = "svn commit $command -m \"$message\" $checkoutPath";
            exec($cmd,$out,$err);
            logEvent($cmd,$out,$err);
        }
        //else do the initial commit of the whole site
        else {
            $err = initialCommit($cPath);
        }

    }
    //COMMIT single folder or file
    else if ($command === 'commit') {
        $message = "commit from WZDE by ". substr( $_SERVER['REMOTE_USER'], strrpos( $_SERVER['REMOTE_USER'], '\\' )+1 );            
        $command = COMMAND;
        $cmd = "svn commit $command -m \"$message\" $checkoutPath";
        exec($cmd,$out,$err);
        logEvent($cmd,$out,$err);    
    }
    //ADD file or folder
    else if ($command === 'add') {
        $command = COMMAND;
        $cmd = "svn --force --depth infinity add $command $checkoutPath 2>&1";
        exec($cmd,$out,$err);
        logEvent($cmd,$out,$err);
        writeCommitList($checkoutPath);
    }
    //COMMIT all queued files or folders into 1 revision
    else if ($command === 'commitQueue') {
        $message = "commit from WZDE by ". substr( $_SERVER['REMOTE_USER'], strrpos( $_SERVER['REMOTE_USER'], '\\' )+1 );            
        $command = COMMAND;
        $commitList=$cPath . $_COOKIE["PHPSESSID"] . ".txt";
        $cmd = "svn commit $command --targets $commitList -m \"$message\"";
        exec($cmd,$out,$err);
        logEvent($cmd,$out,$err);        
        
        if (file_exists($commitList)) { 
            $lines = file($commitList);
            foreach ($lines as $line_num => $line) {
                //if this is a directory
                if (is_dir(rtrim($line,"\r\n"))) {
                    $temp = substr(rtrim($line,"\r\n"), strlen(ZONE_ROOT));
                    $temp = rtrim($temp,"/");
                    $path = dirname ($temp);
                    $file = basename ($temp);
                    //update the directory row
                    $updateError = updateFilesTable($path,$file,gmdate('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s'))));
                    //update all the sub directories and files
                    $updateError = updateSubFoldersTable($temp.'%',gmdate('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s'))));
                }
                //if this is a file
                else {
                    $temp = substr($line, strlen(ZONE_ROOT));                    
                    $path = cut_string_using_last('/', $temp, 'left', false);
                    $file = trim(cut_string_using_last('/', $temp, 'right', false),"\r\n");
                    $updateError = updateFilesTable($path,$file,gmdate('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s'))));
                }
            }
            unlink($commitList);            
        }
    }
    
    //echo ($err ? json_encode($out[0]) : 1)
    echo ($err ? 0 : 1);   
    
}

//WZDE site initial SVN COMMIT
//return error (false=success, 1=error) 
function initialCommit($checkoutPath) {
    global $ini_array;
    $command = COMMAND;
    
    //create root folder
    $message = $ini_array["SVN"]["ROOTMESSAGE"] . substr( $checkoutPath, strrpos( $checkoutPath, '/' )+1 ) ;
    $location = $ini_array["SVN"]["LOCATION"] . $checkoutPath;   
    $cmd = "svn mkdir $command -m \"$message\" $location";
    exec($cmd,$out,$err);
    logEvent($cmd,$out,$err);
    if ($err)
        return 1;
    //creat branches folder
    $message = $ini_array["BRANCHES"]["MESSAGE"] . substr( $checkoutPath, strrpos( $checkoutPath, '/' )+1 ) ;
    $location = $ini_array["SVN"]["LOCATION"] . $checkoutPath . $ini_array["BRANCHES"]["NAME"];
    $cmd = "svn mkdir $command -m \"$message\" $location";
    exec($cmd,$out,$err);
    logEvent($cmd,$out,$err);
    if ($err)
        return 1;    
    //creat tags folder
    $message = $ini_array["TAGS"]["MESSAGE"] . substr( $checkoutPath, strrpos( $checkoutPath, '/' )+1 ) ;
    $location = $ini_array["SVN"]["LOCATION"] . $checkoutPath . $ini_array["TAGS"]["NAME"];
    $cmd = "svn mkdir $command -m \"$message\" $location";
    exec($cmd,$out,$err); 
    logEvent($cmd,$out,$err);
    if ($err)
        return 1;    
    //creat trunk folder
    $message = $ini_array["TRUNK"]["MESSAGE"] . substr( $checkoutPath, strrpos( $checkoutPath, '/' )+1 ) ;
    $location = $ini_array["SVN"]["LOCATION"] . $checkoutPath . $ini_array["TRUNK"]["NAME"];
    $cmd = "svn mkdir $command -m \"$message\" $location";
    exec($cmd,$out,$err);
    logEvent($cmd,$out,$err);
    if ($err)
        return 1;    
    //checkout empty trunk folder
    $svnLocation = $ini_array["SVN"]["LOCATION"] . $checkoutPath . $ini_array["TRUNK"]["NAME"];
    $serverLocation = $ini_array["SVN"]["ZONE_ROOT"] . $checkoutPath;
    $cmd = "svn checkout $command $svnLocation $serverLocation";
    exec($cmd,$out,$err);
    logEvent($cmd,$out,$err);
    if ($err)
        return 1;    
    //add all files    
    $location = $ini_array["SVN"]["ZONE_ROOT"] . $checkoutPath;
    $cmd = "svn --force --depth infinity add $command $location";
    exec($cmd,$out,$err);
    logEvent($cmd,$out,$err);    
    if ($err)
        return 1; 
    //commit
    $message = $ini_array["SVN"]["COMMITMESSAGE"] . substr( $checkoutPath, strrpos( $checkoutPath, '/' )+1 ) ;
    $location = $ini_array["SVN"]["ZONE_ROOT"] . $checkoutPath;
    $cmd = "svn commit $command -m \"$message\" $location";
    exec($cmd,$out,$err);
    logEvent($cmd,$out,$err);
    if ($err)
        return 1;

    return FALSE;     
}

//update commit date into Mariadb table
//in :relative path, commit date
//out:false for success, errorCode for failed
function updateFilesTable($path,$file,$cDate) {
    $bindV = array(
        'cdate' => $cDate,
        'status' => "COMMITTED",
        'path' => $path,
        'file' => $file
    );
    $form_fields = array(
                     'query' => "update webutility.wzde_files set commitdate = :cdate, status = :status where path = :path and filename = :file",
                     'bindV' => $bindV,
                     );
                     
    $query=$form_fields['query'];
    $bindV=$form_fields['bindV'];
    
    $database = new mysql_pdo();
    
    $database->query($query);
    
    foreach ($bindV as $key=>$val) {
        $database->bind(':'.$key, $val);
    }
    $database->execute();  

    //false equal no error
    if (!$database->getLastError()==='00000'){
        return json_encode($database->getLastError());
    }
    else {
        return false;
    }
}

//update commit date into Mariadb table for sub-folders and files
//in :relative path, filename, commit date
//out:false for success, errorCode for failed
function updateSubFoldersTable($path,$cDate) {
    $bindV = array(
        'cdate' => $cDate,
        'status' => "COMMITTED",
        'path' => $path
    );
    $form_fields = array(
                     'query' => "update webutility.wzde_files set commitdate = :cdate, status = :status where path like :path",
                     'bindV' => $bindV,
                     );
                     
    $query=$form_fields['query'];
    $bindV=$form_fields['bindV'];
    
    $database = new mysql_pdo();
    
    $database->query($query);
    
    foreach ($bindV as $key=>$val) {
        $database->bind(':'.$key, $val);
    }
    $database->execute();  

    //false equal no error
    if (!$database->getLastError()==='00000'){
        return json_encode($database->getLastError());
    }
    else {
        return false;
    }
}

//
function logEvent($cmd,$output,$error) {
	
	if (!$handle = fopen(EVENTLOG, 'ab')) {
		echo "Cannot open file ".EVENTLOG;
		exit;
	}
	
	$timeStamp = date('YmdHis');
	$logEntry = $timeStamp . "|" . $cmd . "|" . print_r($output, true) . "|" . $error . "\r\n";
	
	// Write $logEntry to our opened file.
	if (fwrite($handle, $logEntry) === FALSE) {
		echo "Cannot write to file ".EVENTLOG;
		exit;
	}
    fwrite($handle,"\r\n\r\n");
	fclose($handle);
}

function writeCommitList($cPath) {
    $commitList = "commitList_".$_COOKIE["PHPSESSID"].".txt";	
	if (!$handle = fopen($commitList, 'ab')) {
		echo "Cannot open file ".$commitList;
		exit;
	}
	// Write $logEntry to our opened file.
	if (fwrite($handle, $cPath) === FALSE) {
		echo "Cannot write to file " . $commitList;
		exit;
	}
    fwrite($handle,"\r\n");
	fclose($handle);
}

function cut_string_using_last($character, $string, $side, $keep_character=true) { 
    $offset = ($keep_character ? 1 : 0); 
    $whole_length = strlen($string); 
    $right_length = (strlen(strrchr($string, $character)) - 1); 
    $left_length = ($whole_length - $right_length - 1); 
    switch($side) { 
        case 'left': 
            $piece = substr($string, 0, ($left_length + $offset)); 
            break; 
        case 'right': 
            $start = (0 - ($right_length + $offset)); 
            $piece = substr($string, $start); 
            break; 
        default: 
            $piece = false; 
            break; 
    } 
    return($piece); 
} 
