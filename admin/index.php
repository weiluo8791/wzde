<?php
//display error
error_reporting(E_ALL);
ini_set('display_errors', '1');

global $WZDE;
$WZDE['TIME_START'] = microtime(true);

require_once "scripts/wzdeAdmin.php";
init_Admin();

//set default starting page
!isset($_GET['page']) ? $page='log' : $page=$_GET['page'];
//switch to correct page
switch_page($page);
//render page
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