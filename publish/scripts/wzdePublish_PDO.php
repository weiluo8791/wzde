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

//fields for site
$sites_fields = array (
    'empty',
    'empty',
    'empty',
    'sitename',
    'resource',
    'description',
    'creationdate',
    'commitdate',
);

//fields for file
$wzdeFiles_fields = array (
    'empty',
    'zone',
    'site',
    'path',
    'filename',
    'type',
    'status',
    'creationdate',
    'publishdate',
    'commitdate',
    'actions'
);

//called one time at start up then it will be a ajax refresh
function display_queue() {
    global $wzdeFiles_fields;
    global $WZDE;
    // $a is an accumulator for the output string
    $a  = "<p class=\"subheading\" id=\"wzdeQueue\">WZDE Queue</p>\n";
    $a .= "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" class=\"display\" id=\"wzdeQueueTable\">\n";
    // header of table
    $a .= "<thead>";
    $a .= "<tr>\n"; // row for head 
    foreach ($wzdeFiles_fields as $name) {
            $name = display_dataName($name);
            $a .= "<th>$name</th>\n";
    }
    $a .= "</tr>\n</thead>\n";
    
    $a .= "</table>\n";
    $a .= "<form id=\"action_Form\">";
    $a .= "<button id=\"commit_queue\" class=\"wzdeButton\" type=\"button\" title=\"Commit queued file to Subversion Repository.\"><img src=\"./images/CommitQueue-sm.png\"></button>";
    $a .= "<button id=\"publish_queue\" class=\"wzdeButton\" type=\"button\" title=\"Publish queued file to Live server.\"><img src=\"./images/PublishQueue-sm.png\"></button>";   
    $a .= "<button id=\"publish_clear\" class=\"wzdeButton\" type=\"button\" title=\"Clear everything in the queue.\"><img src=\"./images/ClearQueue-sm.png\"></button></form>";
    queue_content($a);
}

//called one time at start up then it will be a ajax refresh
function display_site($resource,$folder='root') {
    global $sites_fields;
    global $WZDE;
    
    if ($folder==='root'){
        //ATWEB-2257 by WLUO - Autoscroll when moving between Manage and Publish
        // $query = "SELECT sitename,resource,description,creationdate,commitdate FROM webutility.sites 
                    // WHERE resource = :resource and zonecode = 'Y' and wuversion = 'DEV'
                    // order by sitename";
        $query = "SELECT distinct wzde.site as sitename,wu.resource,wu.description,wu.creationdate,wu.commitdate 
                    FROM webutility.sites as wu
                    INNER JOIN webutility.wzde_files AS wzde ON wu.idsites = wzde.idsites
                    WHERE wu.resource = :resource and wu.zonecode = 'Y' and wu.wuversion = 'DEV'
                    order by wzde.site"; 
        $bindV = [
            "resource" => $resource,
        ];
        $result = query_db($query,$bindV,"true",true);
        
        // $a is an accumulator for the output string
        $a = subheadingForm($resource);
        $a .= "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" class=\"display\" id=\"wzdeSiteTable\">\n";
        
        // header of table
        $a .= "<thead>";
        $a .= "<tr>\n"; // row for head 
        //adding column heading for svn commit button
        $a .= "<th></th>\n";
        foreach ($sites_fields as $name) {
                $name = display_dataName($name);
                $a .= "<th>$name</th>\n";
        }
        $a .= "</tr>\n</thead>\n";
        
        $row_count = 0;
        foreach ($result as $val) {
            $a .= data_site_row($val);
            $row_count++;
        }
        //pick the first site as default if any otherwise null
        //$WZDE['DEFAULT_SITE'] = !empty($result) ? $result[0]['sitename'] : '';
        
        //disable default site and set it to empty
        $WZDE['DEFAULT_SITE'] = '';
    }
    
    $a .= "</table>\n";
    site_content($a);
}
    
//will just called one time at start up, any other call will be a ajax refresh
//folder is optional
function display_file($resource,$folder='default') {
    global $wzdeFiles_fields;
    global $WZDE;
    
    //if not default site use the optional $folder argument
    if ($folder==='default') {
        $keyword=$resource . "/" . $WZDE['DEFAULT_SITE'];
        $WZDE['CURRENT_SITE'] = $WZDE['DEFAULT_SITE'];
    }
    else {
        $keyword=$resource . "/" . $folder;
        $WZDE['CURRENT_SITE'] = $folder;
    }
    
    $query = "SELECT idsites,zone,site,path,filename,type,status,creationdate,publishdate,commitdate,hash 
                FROM webutility.wzde_files 
                where path like :keyword 
                order by path";
    $bindV = [
        "keyword" => $keyword,
    ];
    $result = query_db($query,$bindV,"true",true);
            
    // $a is an accumulator for the output string
    $a  = "<p class=\"subheading\" id=\"wzdeFile\">WZDE Path: [". $resource . "] " . $WZDE['CURRENT_SITE'] . "</p>\n";
    
    $a .= "<p class=\"clickable\" id=\"showPrevious\"><u>Up one level</u></p>\n";
    $a .= "<table cellpadding=\"0\" cellspacing=\"0\" border=\"0\" class=\"display\" id=\"wzdeFileTable\">\n";
    
    // header of table
    $a .= "<thead>";
    $a .= "<tr>\n"; // row for head 
    foreach ($wzdeFiles_fields as $name) {
            $name = display_dataName($name);
            $a .= "<th>$name</th>\n";
    }
    $a .= "</tr>\n</thead>\n";
    
    $row_count = 0;
    foreach ($result as $val) {
        $a .= data_file_row($val);
        $row_count++;
    }
    
    $a .= "</table>\n";
    file_content($a);
}

function site_content($s) {
    global $WZDE;
    $WZDE["_STE_ARRAY"][] = "\n<div class=\"site_content\">$s</div>\n";
}

function file_content($s) {
    global $WZDE;
    $WZDE["_FLE_ARRAY"][] = "\n<div class=\"file_content\">$s</div>\n";
}

function queue_content($s) {
    global $WZDE;
    $WZDE["_QUE_ARRAY"][] = "\n<div class=\"queue_content\">$s</div>\n";
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

function subheadingForm($resource) {
    global $WZDE;
    $form = "<form action=\"". $WZDE["SELF"] ."\" method=\"get\" target=\"_self\">";
    $form.= "<p class=\"subheading\">WZDE Zone: ";
    $form.= "<select size=\"1\" name=\"resource\" id=\"resource\" onchange=\"this.form.submit()\">";
    $form.= "<option data-class=\"avatar\" data-style=\"background-image:url(&apos;./images/resource_staff_sm.png&apos;)\"" . (strtoupper($resource)==='STAFF'?'selected':'') . ">STAFF</option>";
    $form.= "<option data-class=\"avatar\" data-style=\"background-image:url(&apos;./images/resource_customer_sm.png&apos;)\"" . (strtoupper($resource)==='CUSTOMER'?'selected':'') . ">CUSTOMER</option>";
    $form.= "<option data-class=\"avatar\" data-style=\"background-image:url(&apos;./images/resource_home_sm.png&apos;)\"" . (strtoupper($resource)==='HOME'?'selected':'') . ">HOME</option>";
    $form.= "<option data-class=\"avatar\" data-style=\"background-image:url(&apos;./images/resource_staffapps_sm.png&apos;)\"" . (strtoupper($resource)==='STAFFAPPS'?'selected':'') . ">STAFFAPPS</option>";
    $form.= "<option data-class=\"avatar\" data-style=\"background-image:url(&apos;./images/resource_atsignage_sm.png&apos;)\"" . (strtoupper($resource)==='ATSIGNAGE'?'selected':'') . ">ATSIGNAGE</option>";
    $form.= "<option data-class=\"avatar\" data-style=\"background-image:url(&apos;./images/resource_logi_sm.png&apos;)\"" . (strtoupper($resource)==='LOGI'?'selected':'') . ">LOGI</option>";
    $form.= "<option data-class=\"avatar\" data-style=\"background-image:url(&apos;./images/resource_cdn_sm.png&apos;)\"" . (strtoupper($resource)==='CDN'?'selected':'') . ">CDN</option>";
	$form.= "<option data-class=\"avatar\" data-style=\"background-image:url(&apos;./images/resource_cts_sm.png&apos;)\"" . (strtoupper($resource)==='CTS'?'selected':'') . ">CTS</option>";
    $form.= "</select></p></form>";
    
    $loadingImage ="<center><img src=\"./images/iconAjaxLoader.gif\" id=\"loading-image\" alt=\"Loading...\" title=\"Loading\" border=0></center>";
    
    //return "<p class=\"subheading\">$form</p>$loadingImage\n";
	return "$form \n $loadingImage";    
}

function display_dataName($n) {
    if ($n == 'empty')
            //return '<input class="queueUpAll" type="checkbox">';
            return '';
    if ($n == 'sitename')
            return 'Site Name';
    if ($n == 'resource')
            return 'Resource';
    if ($n == 'wuversion')
            return 'WU Version';
    if ($n == 'template')
            return 'Template';
    if ($n == 'zonecode')
            return 'Zone Code';
    if ($n == 'description')
            return 'Site Description';
    if ($n == 'creationdate')
            return 'Last Modified';
    if ($n == 'commitdate')
            return 'Commit Date';
    if ($n == 'actions')
            return 'Actions';        
    if ($n == 'zone')
            return 'Resource';
    if ($n == 'site')
            return 'Site';
    if ($n == 'path')
            return 'Path';
    if ($n == 'filename')
            return 'File';
    if ($n == 'type')
            return 'Type';
    if ($n == 'hash')
            return 'Hash'; 
    if ($n == 'status')
            return 'Status';
    if ($n == 'publishdate')
            return 'Publish Date';
    if ($n == 'name')
            return 'File Name';
    if ($n == 'last')
            return 'Last Publish Date';           

    return $n;
}

function data_site_row($row) {
    global $WZDE;
	$a = "<tr id=" . $row['sitename']. "_rowID>\n";
    //manage button
    $a .= "<td width=\"1%\" class=\"manageSiteButton\" align=\"center\">" . "<button id=\"" . $row['sitename'] . "\" type=\"button\" title=\"Manage this site.\"><img src=\"./images/WZDE-sm.png\"></button>" . "</td>\n";
    //commit button
    if ((strpos(checkWzdeSiteAccess($WZDE['USER_OID'],$row['sitename']),'wzde_commit') !== false) || 
        (strpos(checkWzdeMasterAccess($WZDE['USER_OID']),'wzde_publish') !== false)) {
        $a .= "<td width=\"1%\" class=\"commitWholeButton\" align=\"center\">" . "<button id=\"" . $row['sitename'] . "\" type=\"button\" title=\"Commit whole site to subversion repository.\"><img src=\"./images/Commit-sm.png\"></button>" . "</td>\n";
    }
    else {
        $a .= "<td width=\"1%\" class=\"commitWholeButton\" align=\"center\">" . "<button id=\"" . $row['sitename'] . "\" type=\"button\" title=\"Commit whole site to subversion repository.\" disabled><img src=\"./images/Commit-sm.png\" class=\"disable\"></button>" . "</td>\n";
    }
    //launch button
    $a .= "<td width=\"1%\" class=\"stageButton\" align=\"center\">" . "<button id=\"" . $row['sitename'] . "\" type=\"button\" title=\"Launch site in staging area.\"><img src=\"./images/Deploy-sm.png\"></button>" . "</td>\n";    
    //publish button
    if ((strpos(checkWzdeSiteAccess($WZDE['USER_OID'],$row['sitename']),'wzde_publish') !== false) ||
        (strpos(checkWzdeMasterAccess($WZDE['USER_OID']),'wzde_publish') !== false)) {
        $a .= "<td width=\"1%\" class=\"publishWholeButton\" align=\"center\">" . "<button id=\"" . $row['sitename'] . "\" type=\"button\" title=\"Publish whole site to Live server.\"><img src=\"./images/Publish-sm.png\"></button>" . "</td>\n";
    }
    else {
        $a .= "<td width=\"1%\" class=\"publishWholeButton\" align=\"center\">" . "<button id=\"" . $row['sitename'] . "\" type=\"button\" title=\"Publish whole site to Live server.\" disabled><img src=\"./images/Publish-sm.png\" class=\"disable\"></button>" . "</td>\n";
    }
     
    $a .= "<td class=\"clickable\"><u>" . $row['sitename'] . "</u></td>\n";
    $a .= "<td>" . $row['resource'] . "</td>\n";
    //$a .= "<td>" . $row['wuversion'] . "</td>\n";
    //$a .= "<td>" . $row['template'] . "</td>\n";
    //$a .= "<td>" . $row['zonecode'] . "</td>\n";
    $a .= "<td>" . $row['description'] . "</td>\n";
    $a .= "<td>" . $row['creationdate'] . "</td>\n";
    $a .= "<td>" . $row['commitdate'] . "</td>\n";
    $a .= "</tr>\n";
    return $a;
}

function data_file_row($row) {
    global $WZDE;
    //$a = "<tr id=\"".strtoupper($row['zone'])."/".$row['site']."/".$row['filename']."\">\n";
    $a = "<tr id=\"".$row['hash']."\">\n";
    
    if ((strpos(checkWzdeSiteAccess($WZDE['USER_OID'],$row['site']),'wzde_publish') !== false) ||
        (strpos(checkWzdeSiteAccess($WZDE['USER_OID'],$row['site']),'wzde_commit') !== false) ||
        (strpos(checkWzdeMasterAccess($WZDE['USER_OID']),'wzde_publish') !== false)) {    
        $a .= "<td><input class=\"queueUpClick\" type=\"checkbox\"></td>\n";
    }
    else {
        $a .= "<td><input class=\"queueUpClick\" type=\"checkbox\" disabled></td>\n";
    }
    
    $a .= "<td>" . $row['zone'] . "</td>\n";
    $a .= "<td>" . $row['site'] . "</td>\n";
    $a .= "<td>" . $row['path'] . "</td>\n";
    if($row['type'] === 'directory') {
        $a .= "<td class=clickable><u>" . $row['filename'] . "</u></td>\n";
    }
    else {
        $a .= "<td>" . $row['filename'] . "</td>\n";
    }
    $a .= "<td>" . $row['type'] . "</td>\n";
    $a .= "<td>" . $row['status'] . "</td>\n";
    $a .= "<td>" . $row['creationdate'] . "</td>\n";
    $a .= "<td>" . $row['publishdate'] . "</td>\n";
    $a .= "<td>" . $row['commitdate'] . "</td>\n";
    //$a .= "<td>" . implode(",",getAllowedAction($row['site'])) . "</td>\n";
	$a .= "<td>" . getAllowedAction($row['site']) . "</td>\n";
    $a .= "</tr>\n";
    return $a;
}

function getAllowedAction($site){
    $site = explode( '/', $site );
    global $WZDE;
    $allowedActions = array();
	//allow commit
    if ((strpos(checkWzdeSiteAccess($WZDE['USER_OID'],$site[0]),'wzde_commit') !== false) ||
		(strpos(checkWzdeMasterAccess($WZDE['USER_OID']),'wzde_publish') !== false)) {
        //array_push($allowedActions,"c");
		array_push($allowedActions,"<img src=\"./images/Commit-sm.png\" height=\"20\" width=\"20\" >");
		
    }
	//allow publish
    if ((strpos(checkWzdeSiteAccess($WZDE['USER_OID'],$site[0]),'wzde_publish') !== false) || 
		(strpos(checkWzdeMasterAccess($WZDE['USER_OID']),'wzde_publish') !== false)) {  		
        //array_push($allowedActions,"p");
		array_push($allowedActions,"<img src=\"./images/Publish-sm.png\" height=\"20\" width=\"20\">");
		
    }
	//no action allow
    if ((strpos(checkWzdeSiteAccess($WZDE['USER_OID'],$site[0]),'wzde_commit') == false) && 
		(strpos(checkWzdeSiteAccess($WZDE['USER_OID'],$site[0]),'wzde_publish') == false) &&
		(strpos(checkWzdeMasterAccess($WZDE['USER_OID']),'wzde_publish') == false)) {  		
        array_push($allowedActions,"none");	
    }
	
    return implode($allowedActions);
}

function init_WZDE() {
    global $WZDE;

    // initialize display vars
    foreach (array (
                'MESSAGES',
                'ERRORS',
                'QUEU_CONTENT',
                'SITE_CONTENT',
                'FILE_CONTENT',
            ) as $v)
    $WZDE[$v] = "";
    
    $WZDE['TITLE'] = "WZDE Publish";
    $WZDE['SELF'] = $_SERVER["SCRIPT_NAME"];
    
    $a = session_id();
    if(empty($a)) session_start();

    $query = "SELECT zone,status FROM webutility.wzde_admin";
    $WZDE['STATUS'] = query_db($query,[],"true",true);   
}

function set_vars() {
    global $WZDE;
    if (isset ($WZDE["_MSG_ARRAY"]))
            foreach ($WZDE["_MSG_ARRAY"] as $m)
                    $WZDE["MESSAGES"] .= $m;
    if (isset ($WZDE["_ERR_ARRAY"]))
            foreach ($WZDE["_ERR_ARRAY"] as $m)
                    $WZDE["ERRORS"] .= $m;
    if (isset ($WZDE["_QUE_ARRAY"]))
            foreach ($WZDE["_QUE_ARRAY"] as $m)
                    $WZDE["QUEU_CONTENT"] .= $m;                    
    if (isset ($WZDE["_STE_ARRAY"]))
            foreach ($WZDE["_STE_ARRAY"] as $m)
                    $WZDE["SITE_CONTENT"] .= $m;
    if (isset ($WZDE["_FLE_ARRAY"]))
            foreach ($WZDE["_FLE_ARRAY"] as $m)
                    $WZDE["FILE_CONTENT"] .= $m;                    
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
?>
