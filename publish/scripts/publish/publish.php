<?php
/*******************************************************************************
Get POSTed File to publish via ftps

filename: publish.php

version 1.0 
date    5/1/2014
by      Wei Qi Luo
*******************************************************************************/
require_once "transferFTPS.php";
define("INIFILE", dirname(__FILE__) . "\\publish.ini");
$ini_array = parse_ini_file(INIFILE,TRUE);
//Define CONSTANT from ini
define("VERSION", $ini_array["SYS"]["VERSION"]);
define("WZDE_SOURCE", $ini_array["WZDE"]["SOURCE"]);
define("EVENTLOG", $ini_array["WZDE"]["EVENTLOG"]);
define("DEBUGLOG", $ini_array["WZDE"]["DEBUG"]);
define("FTPUSER",$ini_array["FTP"]["FTPUSER"]);
define("FTPPASS",$ini_array["FTP"]["FTPPASS"]);

//enable cross domain post from staffappslx by setting the server response header
switch ($_SERVER['HTTP_ORIGIN']) {
    case 'http://staffappslx.meditech.com': case 'https://staffappslx.meditech.com':
    header('Access-Control-Allow-Origin: '.$_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Access-Control-Max-Age: 1000');
    header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
    break;
}
//--------------------------------------------------------------------------

//get POST (can not use $_POST because it is in raw json and not in key=value pair)
$fileList = json_decode(file_get_contents("php://input"));

//trap for list of upload file
file_put_contents('publishList.txt', print_r($fileList, true), FILE_APPEND | LOCK_EX);

//upload file in batch (50max from each queue message)
ftpsOperation_multi($fileList);
