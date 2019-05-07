<?php
//display error
error_reporting(E_ALL);
ini_set('display_errors', '1');

global $WZDE;
$WZDE['TIME_START'] = microtime(true);

require_once "scripts/wzdePublish_PDO.php";
init_WZDE();

//if no status something wrong with connection to mariadb
if(!isset($WZDE['STATUS'])){
    header("location:500.php");
}

//extract status by zone
foreach ($WZDE['STATUS'] as $key=>$val) {
    $WZDE['ZONE'][$val['zone']]=$val['status'];
}

//if all is not UP or OVERRIDE (by zone) redirect to a 503 page
if ( ($WZDE['ZONE']['all']!=="UP") && ($WZDE['ZONE']['all']!=="OVERRIDE") ) {
    header("location:503.php?resource=all");
    exit;
}

//default to staff zone if not set
$WZDE['resource'] = empty($_GET['resource']) ? 'staff': strtolower($_GET['resource']);

//if the zone is not UP redirect to a 503 page
if ($WZDE['ZONE'][$WZDE['resource']]!=="UP"){
    header("location:503.php?resource=".$WZDE['resource']);
    exit;
}

display_queue();

//If no folder grab the first site
if( !empty( $_GET['folder'] ) ) {
    $folder = $_GET['folder'];
    display_site($WZDE['resource']);
    display_file($WZDE['resource'],$folder);
}
else {  
    display_site($WZDE['resource']);
    display_file($WZDE['resource']);
}

$WZDE['TIME_END'] = microtime(true);
$WZDE['TIME'] = $WZDE['TIME_END'] - $WZDE['TIME_START'];
page();


function page( )
{
    global $WZDE;
    set_vars();    
    //display head
    require_once "assets/header.php";
    //display body
    require_once "assets/main.php";
    //display footer
    require_once "assets/footer.php";
}