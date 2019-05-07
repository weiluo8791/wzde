<?php
// display error
error_reporting(E_ALL);
ini_set('display_errors', '1');
// REQUIRE
require_once 'assets/scripts/helper.php';

// get wzde worker queue status
if (!getWzdeStatus()) {
    echo "**ERROR** Not able to get WZDE status !";
    header("location:500.php");    
}

// get last publish log entry
getLastPublished();

//render page
page();

function page()
{
    global $WZDE;
    //display head
    require_once "assets/header.php";
    //display body
    require_once "assets/main.php";
    //display footer
    require_once "assets/footer.php";
}



