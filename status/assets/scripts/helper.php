<?php
// display error
error_reporting(E_ALL);
ini_set('display_errors', '1');

//REQUIRE
require_once 'assets/scripts/setting.php';

// Return user's ename.
function getEname() {
 $remoteUser = strtolower($_SERVER['REMOTE_USER']);
 $ename = str_replace("meditech\\","",$remoteUser);
 return $ename;
}

//in : nothing
//out: TRUE = successful FALSE = error
function getWzdeStatus() {
    global $WZDE;
   
    //curl setting
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, WZDE_STATUS_URL);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    //SSL 
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    //tls1.2
	curl_setopt($ch, CURLOPT_SSLVERSION, 6);
    //Execute cURL
	$result = curl_exec ($ch);
    //parse json
    $json = json_decode($result, true);   
    //set status value
    $WZDE['Q_SIZE'] = $json["msg_qnum"];
    $WZDE['LAST_Q'] = $json["msg_stime"];
    $WZDE['LAST_P'] = $json["msg_rtime"];    
    //get error if any
    $error_no = curl_errno($ch);
	$error_msg = curl_error($ch);	
	$info = curl_getinfo($ch, CURLINFO_HTTP_CODE);	
	//close curl and handle;
	curl_close ($ch);

	if ($error_no == 0) {
		return TRUE;
	} else {
		return FALSE;
	}    
}

//in: nothing
//out:nothing
//set $WZDE["LAST_PUBLISHED"] with content from wzdePublishStatus table
function getLastPublished() {
    global $WZDE;
    $log_array = explode("\n", tailCustom(WZDE_LOG,WZDE_LP_COUNT));
    $a = "";
    foreach ($log_array as $val){
        $eachLine = explode("|", $val);
        $a.= "<tr>";
        $a.= "<td>" . date("Y-m-d H:i:s", strtotime($eachLine[0])) . "</td>";
        $a.= "<td>" . $eachLine[1] . "</td>";
        $a.= "<td>" . $eachLine[2] . "</td>";
        $a.= "<td>" . $eachLine[3] . "</td>";
        $a.= "<td>" . str_replace("E:/inetpub/wwwroot/zone/","",$eachLine[4]) . "</td>";
        $a.= "</tr>";
    }
    
    $WZDE["LAST_PUBLISHED"] = $a;
}

/**
 * Slightly modified version of http://www.geekality.net/2011/05/28/php-tail-tackling-large-files/
 * @author Torleif Berger, Lorenzo Stanco
 * @link http://stackoverflow.com/a/15025877/995958
 * @license http://creativecommons.org/licenses/by/3.0/
 */
function tailCustom($filepath, $lines = 1, $adaptive = true) {
    // Open file
    $f = @fopen($filepath, "rb");
    if ($f === false) return false;
    // Sets buffer size, according to the number of lines to retrieve.
    // This gives a performance boost when reading a few lines from the file.
    if (!$adaptive) $buffer = 4096;
    else $buffer = ($lines < 2 ? 64 : ($lines < 10 ? 512 : 4096));
    // Jump to last character
    fseek($f, -1, SEEK_END);
    // Read it and adjust line number if necessary
    // (Otherwise the result would be wrong if file doesn't end with a blank line)
    if (fread($f, 1) != "\n") $lines -= 1;
    
    // Start reading
    $output = '';
    $chunk = '';
    // While we would like more
    while (ftell($f) > 0 && $lines >= 0) {
        // Figure out how far back we should jump
        $seek = min(ftell($f), $buffer);
        // Do the jump (backwards, relative to where we are)
        fseek($f, -$seek, SEEK_CUR);
        // Read a chunk and prepend it to our output
        $output = ($chunk = fread($f, $seek)) . $output;
        // Jump back to where we started reading
        fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
        // Decrease our line counter
        $lines -= substr_count($chunk, "\n");
    }
    // While we have too many lines
    // (Because of buffer size we might have read too many)
    while ($lines++ < 0) {
        // Find first newline and remove all text before that
        $output = substr($output, strpos($output, "\n") + 1);
    }
    // Close file and return
    fclose($f);
    return trim($output);
}