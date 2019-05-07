<!DOCTYPE html>
<html lang="en">
<head>
    <!--<link rel="STYLESHEET" TYPE="text/css" HREF="css/normalize.css">-->
    <link rel="stylesheet" TYPE="text/css" HREF="../css/system.css">
    <link rel="stylesheet" TYPE="text/css" HREF="//cdn.datatables.net/1.10.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" />
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
}

else {
    $action = $_GET['action'];
    switch ($action) {
	case 'retire' :
        break;
    case 'create_s' :
        break;
    case 'create_z' :
        break;
    case 'down' :
        echo buildDownForm();
        break;       
	default :
		return FALSE;
	}
}

//in : array of status by zone
//out: select form for wzde status
function buildDownForm() {
    // $a is an accumulator for the output string
    $a = <<<EOT
        <div align="center">
            <div class="zone" style="float:left;">
                <label for="all" class="name"><span class="fa fa-globe"></span> All</label>
                    <select id="all" class="status" onchange="allStatus(this)">
                        <option value="UP">Up</option>
                        <option value="DOWN">Down</option>
                        <option value="OVERRIDE">Override</option>
                    </select>&nbsp;
            </div>
            <div class="zone" style="float:left;">
                <label for="staff" class="name"><span class="fa fa-users"></span> Staff</label>
                    <select id="staff" class="status">
                        <option value="UP">Up</option>
                        <option value="DOWN">Down</option>
                    </select>&nbsp;
            </div>
            <div class="zone" style="float:left;">
                <label for="customer" class="name"><span class="fa fa-h-square"></span> Customer</label>                
                    <select id="customer" class="status">
                        <option value="UP">Up</option>
                        <option value="DOWN">Down</option>
                    </select>&nbsp;
            </div>
            <div class="zone" style="float:left;">
                <label for="home" class="name"><span class="fa fa-home"></span> Home</label>                    
                    <select id="home" class="status">
                        <option value="UP">Up</option>
                        <option value="DOWN">Down</option>
                    </select>&nbsp;
            </div>
            <div class="zone" style="float:left;">
                <label for="cdn" class="name"><span class="fa fa-server"></span> Cdn</label>                    
                    <select id="cdn" class="status">
                        <option value="UP">Up</option>
                        <option value="DOWN">Down</option>
                    </select>&nbsp;
            </div>
            <div class="zone" style="float:left;">
                <label for="logi" class="name"><span class="fa fa-signal"></span> Logi</label>                    
                    <select id="logi" class="status">
                        <option value="UP">Up</option>
                        <option value="DOWN">Down</option>
                    </select>&nbsp;
            </div>			
            <div class="zone" style="float:left;">
                <label for="staffapps" class="name"><span class="fa fa-mobile"></span> Staffapps</label>                    
                    <select id="staffapps" class="status">
                        <option value="UP">Up</option>
                        <option value="DOWN">Down</option>
                    </select>&nbsp;
            </div>
            <div class="zone" style="float:left;">                
                <label for="atsignage" class="name"><span class="fa fa-tv"></span> Atsignage</label>                   
                    <select id="atsignage" class="status">
                        <option value="UP">Up</option>
                        <option value="DOWN">Down</option>
                    </select>&nbsp;
            </div>
            <p><button id="submitDown">Submit</button></p>               
        </div>
    <div class='row'>
        <div class='small-12 columns'>
            <div id='messageSubmit'><p class='message'>Tips: Click submit to set WZDE status</p></div>
        </div>
    </div>
EOT;
    
    return $a;    
}

?>

<!-- CDN -->
<script type="text/javascript" charset="utf8" src="//code.jquery.com/jquery-1.11.3.min.js"></script>     
<script type="text/javascript" charset="utf8" src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script type="text/javascript" charset="utf8" src="//malsup.github.io/jquery.blockUI.js"></script>
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.3/moment.min.js"></script>
<script type="text/javascript" charset="utf8" src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js"></script>

<!-- Local -->
<script type="text/javascript" charset="utf8" src="../js/system.js"></script>

<script type="text/javascript">
$(document).ready(function() { 
    applyWzdeStatuses();
});
    
</script>
</body>
</html>