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
    <!--<link rel="STYLESHEET" TYPE="text/css" HREF="css/normalize.css">
    <link rel="stylesheet" TYPE="text/css" HREF="../css/main.css">-->
    <link rel="stylesheet" TYPE="text/css" HREF="//cdn.datatables.net/1.10.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" />
    <link href="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/css/select2.min.css" rel="stylesheet" />
   
    <title> <?php echo $WZDE["TITLE"] ?> </title>
</head>

<?php
require_once 'wzdeAdmin.php';

global $WZDE;
$WZDE['ZONE'] = array ('staff','customer','home','staffapps','atsignage') ; 

if (!isset($_POST['sites']) && !isset($_POST['types'])) {
    header("location:invalid.php");
    exit;
}
else {
    $sites = $_POST['sites'];
    $types = $_POST['types'];
    
    $file = '../../manage/log/'.date(m.Y) . '_log.txt';
    // get the file contents, assuming the file to be readable (and exist)
    $contents = file_get_contents($file);
            // get the file contents, assuming the file to be readable (and exist)
    foreach ($sites as $val) {

        $searchfor = $val["name"];
        echo "Site :".$searchfor. "</br>";
        // the following line prevents the browser from parsing this as HTML.
        header('Content-Type: text/plain');

        // escape special characters in the query
        $pattern = preg_quote($searchfor, '/');
        // finalise the regular expression, matching the whole line
        $pattern = "/^.*$pattern.*\$/m";
        // search, and store all matching occurences in $matches
        if(preg_match_all($pattern, $contents, $matches)){
           echo "<font size=\"1\">";
           echo implode("</font></br><font size=\"1\">", $matches[0]);
           if (count($matches[0])<2){
               echo "</font></br>";
           }
        }
        else{
           echo "No matches found";
        }
    }

}
    

?>


<!-- CDN
<script type="text/javascript" charset="utf8" src="//code.jquery.com/jquery-1.11.3.min.js"></script>     
<script type="text/javascript" charset="utf8" src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
<script type="text/javascript" charset="utf8" src="//malsup.github.io/jquery.blockUI.js"></script>
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" charset="utf8" src="//cdnjs.cloudflare.com/ajax/libs/moment.js/2.10.3/moment.min.js"></script>
<script type="text/javascript" charset="utf8" src="//cdnjs.cloudflare.com/ajax/libs/select2/4.0.0/js/select2.min.js"></script> -->

<!-- Local
<script type="text/javascript" charset="utf8" src="js/log.js"></script> -->

<script type="text/javascript">
$(document).ready(function() {
    $.ajaxSetup({ cache:false });

    
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

