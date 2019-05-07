<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

//REQUIRE
require_once 'scripts/setting.php';
require_once 'scripts/helper.php';
require_once 'scripts/mysql_pdo.class.php';
//GLOBAL
global $WZDE;
//set start timer
$WZDE['TIME_START'] = microtime(true);

//initialize page
init_503();
foreach ( $WZDE['STATUS'] as $val ) {	
	// if zone is available redirect back to normal page and exit
	if ($val["zone"] === $_GET["resource"] && $val["status"]==="UP" ) {
		header("location:index.php?resource=" . $_GET["resource"]);
		exit;
	}
}
//set header to 503
header("HTTP/1.1 503 Service Temporarily Unavailable");
header("STatus: Service Temporarily Unavailable");
header("Retry-After: 7200");
//get main content
get_main();
//display page
page();


function page()
{
    global $WZDE;
	set_vars();
    //display header
    require_once "assets/header.php";
	//display main content
	echo $WZDE["MAIN_CONTENT"];
	//calculate render time
	$WZDE['TIME_END'] = microtime(true);
	$WZDE['TIME'] = $WZDE['TIME_END'] - $WZDE['TIME_START'];	
    //display footer
    require_once "assets/footer.php";
}

function get_main() {
	global $WZDE;
	//VARIABLE
	$available_zone = "";
	$main_content = "";
	
	$main_content .= "<h2>WZDE ";
	$main_content .= $_GET["resource"]!=="all" ? "[" . $_GET["resource"] . "]" : "" ;
	$main_content .= " is temporarily unavailable due to service maintenance.</h2>";
	$main_content .= "<p>We expect to have it back up soon. Please try again later.</p>";
	$main_content .= "<p>Currently available zone(s):</p>";

	//get all available zone
	foreach ( $WZDE['STATUS'] as $val ) {		
		// Check if zone is available, if so append link to available_zone variable
		switch ($val["zone"]) {
			case "atsignage" :
				if ($val["status"] ==="UP"){
					$available_zone .= "<li class=zonelist><a href=" . ($_SERVER["HTTPS"] ? "https://" : "http://" ) . $_SERVER["HTTP_HOST"] . "/publish/index.php?resource=atsignage" . " target='_blank'><img src=./images/resource_atsignage_sm.png> Atsignage</a></li>";
				}
				break;
			case "cdn" : 
				if ($val["status"] ==="UP"){
					$available_zone .= "<li class=zonelist><a href=" . ($_SERVER["HTTPS"] ? "https://" : "http://" ) . $_SERVER["HTTP_HOST"] . "/publish/index.php?resource=cdn" . " target='_blank'><img src=./images/resource_cdn_sm.png> Cdn</a></li>";
				}
				break;
			case "customer" : 
				if ($val["status"] ==="UP"){
					$available_zone .= "<li class=zonelist><a href=" . ($_SERVER["HTTPS"] ? "https://" : "http://" ) . $_SERVER["HTTP_HOST"] . "/publish/index.php?resource=customer" . " target='_blank'><img src=./images/resource_customer_sm.png> Customer</a></li>";
				}
				break;		
			case "home" : 
				if ($val["status"] ==="UP"){
					$available_zone .= "<li class=zonelist><a href=" . ($_SERVER["HTTPS"] ? "https://" : "http://" ) . $_SERVER["HTTP_HOST"] . "/publish/index.php?resource=home" . " target='_blank'><img src=./images/resource_home_sm.png> Home</a></li>";
				}
				break;		
			case "logi" : 
				if ($val["status"] ==="UP"){
					$available_zone .= "<li class=zonelist><a href=" . ($_SERVER["HTTPS"] ? "https://" : "http://" ) . $_SERVER["HTTP_HOST"] . "/publish/index.php?resource=logi" . " target='_blank'><img src=./images/resource_logi_sm.png> Logi</a></li>";
				}
				break;		
			case "staff" : 
				if ($val["status"] ==="UP"){
					$available_zone .= "<li class=zonelist><a href=" . ($_SERVER["HTTPS"] ? "https://" : "http://" ) . $_SERVER["HTTP_HOST"] . "/publish/index.php?resource=staff" . " target='_blank'><img src=./images/resource_staff_sm.png> Staff</a></li>";
				}
				break;		
			case "staffapps" : 
				if ($val["status"] ==="UP"){
					$available_zone .= "<li class=zonelist><a href=" . ($_SERVER["HTTPS"] ? "https://" : "http://" ) . $_SERVER["HTTP_HOST"] . "/publish/index.php?resource=staffapps" . " target='_blank'><img src=./images/resource_staffapps_sm.png> StaffApps</a></li>";
				}
				break;		
			default:
				break;		
		}
	}
	$main_content .= $available_zone;	
	main_content($main_content);
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

function init_503() {
    global $WZDE;

    // initialize display vars
    foreach (array ('MAIN_CONTENT') as $v)
		$WZDE[$v] = "";
    
	$WZDE["TITLE"] = 'WZDE is Temporarily Unavailable.';
    $WZDE['SELF'] = $_SERVER["SCRIPT_NAME"];
    
    $a = session_id();
    if(empty($a)) session_start();

	$query = "SELECT zone,status FROM webutility.wzde_admin";
	$WZDE['STATUS'] = query_db($query,[],"true",true);	
}


function set_vars() {
    global $WZDE;
    if (isset ($WZDE["_MAIN_ARRAY"]))
            foreach ($WZDE["_MAIN_ARRAY"] as $m)
                    $WZDE["MAIN_CONTENT"] .= $m;                  
}

function main_content($s) {
    global $WZDE;
    $WZDE["_MAIN_ARRAY"][] = "\n<div class=\"main_content\">$s</div>\n";
}

?>