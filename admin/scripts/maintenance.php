<?php
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache"); // HTTP/1.0
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!--<link rel="STYLESHEET" TYPE="text/css" HREF="css/normalize.css">-->
    <link rel="stylesheet" TYPE="text/css" HREF="../css/main.css">
    <link rel="stylesheet" TYPE="text/css" HREF="//cdn.datatables.net/1.10.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" />
    <link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css" rel="stylesheet" />
   
    <title> <?php echo $WZDE["TITLE"] ?> </title>
</head>

<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'wzdeAdmin.php';

global $WZDE;
$WZDE['ZONE'] = array ('staff','customer','home','cdn','logi','staffapps','atsignage') ; 

if (!isset($_GET['action'])) {
    header("location:invalid.php");
    exit;
}
else {
    $action = $_GET['action'];
    switch ($action) {
	case 'sync' :
        //list($total,$fileList) = ListAllFiles('staff','dev_workhere');
        echo buildSync();
        break;
    case 'deploy' :
        echo buildDeploy();
        break;
    case 'hash' :
        echo buildHash();
        break;
	default :
		return FALSE;
	}
}

//Deploy DiV
function buildDeploy(){
    // $a is an accumulator for the output string
    $a =  <<<EOT
                <div class="row">
                    <div class="small-12 columns">
                        <div id="deployList"><p class="message process">Loading...</p></div>
                    </div>
                </div>
            <div class="row">
                <div class="small-12 columns">
                    <div id="deploy_message" style="overflow:auto;height:150px;">
                        <p class="message">Tips: Submit a site to deploy.</p>
                    </div>
                </div>
            </div>                
EOT;
   
   return $a;
}

//Sync Div
function buildSync(){
    // $a is an accumulator for the output string
    $a =  <<<EOT
                <div class="row">
                    <div class="small-12 columns">
                        <div id="syncList"><p class="message process">Loading...</p></div>
                    </div>
                </div>
            <div class="row">
                <div class="small-12 columns">
                    <div id="sync_message" style="overflow:auto;height:300px;">
                        <p class="message">Tips: Submit a site to check/fix Sync issue.</p>
                    </div>
                </div>
            </div>                
EOT;
   
   return $a;
}

//Hash Div
function buildHash(){
    // $a is an accumulator for the output string
    $a = <<<EOT
            <div class="row" style="margin-top: 15px;">
                <div class="small-12 medium-8 large-8 columns">
                    <p class="body_nav">
                        <button id="staff_hash"><span class="fa fa-users fa-lg"> Staff</span></button>
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="small-12 medium-8 large-8 columns">
                    <p class="body_nav">
                        <button id="customer_hash"><span class="fa fa-hospital-o fa-lg"> Customer</span></button>
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="small-12 medium-8 large-8 columns">
                    <p class="body_nav">
                        <button id="home_hash"><span class="fa fa-home fa-lg"> Home</span></button>
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="small-12 medium-8 large-8 columns">
                    <p class="body_nav">
                        <button id="staffapps_hash"><span class="fa fa-briefcase fa-lg"> Staffapps</span></button>
                    </p>
                </div>
            </div>
                        <div class="row">
                <div class="small-12 medium-8 large-8 columns">
                    <p class="body_nav">
                        <button id="atsignage_hash"><span class="fa fa-skyatlas fa-lg"> Atsignage</span></button>
                    </p>
                </div>
            </div>
            <div class="row">
                <div class="small-12 columns">
                    <div id="hash_message" style="overflow:auto;height:150px;">
                        <p class="message">Tips: Click each zone.. to rebuild hash value for all files.</p>
                    </div>
                </div>
            </div>            
EOT;
    
    return $a;    
}

?>

<div id="deployWholeQuestion" style="display:none; cursor: default"> 
        <h2>This action will Commit and Deploy the site you have selected.</h2>
        <h3>Do you want to continue ?</h3> 
        <input type="button" id="dw_yes" value="Yes" /> 
        <input type="button" id="dw_no" value="No" /> 
</div>

<div id="fixSyncWholeQuestion" style="display:none; cursor: default"> 
        <h2>This action will check and fix sync issue on the site you have selected.</h2>
        <h3>Do you want to continue ?</h3> 
        <input type="button" id="sw_yes" value="Yes" /> 
        <input type="button" id="sw_no" value="No" /> 
</div>

<!-- CDN -->
<script type="text/javascript" charset="utf8" src="//code.jquery.com/jquery-1.11.3.min.js"></script>     
<script type="text/javascript" charset="utf8" src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script type="text/javascript" charset="utf8" src="//malsup.github.io/jquery.blockUI.js"></script>
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.3/moment.min.js"></script>
<script type="text/javascript" charset="utf8" src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js"></script>

<!-- Local -->
<script type="text/javascript" charset="utf8" src="../js/maintenance.js"></script>

<script type="text/javascript">
$(document).ready(function() {
    $.ajaxSetup({ cache:false });
    
	//Global variable to share
	selectAction = "";
	
    var siteList = getSitesList(),
        deployList = $('#deployList'),
        syncList = $('#syncList'),
        html = buildSelectForm([],siteList);
    
    if (getQueryVariable('action')==='deploy') {
        selectAction = 'deploy';
        deployList.html(html);    
        loadMultiSelect();         
    }
    else if (getQueryVariable('action')==='sync') {
        selectAction = 'sync';
        syncList.html(html);    
        loadMultiSelect();         
    }

    
    //alert(getQueryVariable('action'));
    
    function getQueryVariable(variable) {
        var query = window.location.search.substring(1),
        vars = query.split("&"),
        i,
        pair;
        for (i = 0; i < vars.length; i += 1) {
            pair = vars[i].split("=");
            if (pair[0] === variable) {
                return pair[1];
            }
        }
        return false;
    }    
});
    
</script>
</body>
</html>

