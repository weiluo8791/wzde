<html>
<head>
    <title>WZDE 1.5 </title>    
    <!-- Main CSS -->  
    <link rel="stylesheet" TYPE="text/css" HREF="main.css">   
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">      
</head>
<body>
<div class="header">
<div class="title_area">
    <table class="title_area" id="status"><tr>
    <td class="title"><p class="title">Welcome to WZDE</p></td>
    <td class="subtitle">    
    <button class="btn-hover" id="wzde_status"><i class="fa fa-server fa-2x fa-fw" aria-hidden="true" title="Server Details"></i>
        <p id="WZDE_Status" class="SystemStatusDefault"></p>
    </button>    
    <button class="btn-hover" id="svn_status"><i class="fa fa-code-fork fa-2x fa-fw" aria-hidden="true" title="Repository Status"></i>
        <p id="Svn_Status" class="SystemStatusDefault"></p>
    </button>    
    <button class="btn-hover" id="queue_status"><i class="fa fa-arrow-circle-up fa-2x fa-fw" aria-hidden="true" title="Publish Queue Status"></i>
        <p id="Queue_Status" class="SystemStatusDefault"></p>
    </button>           
    </td>      
    </tr></table>
</div>
</div>

<div id="divBody" style= "display:block;width:100%;height:500px;">

    <table id="wzdeMenuTable" align="center" style="margin-top:100px;">
        <tr>
            <td><a href="/manage" title="Manage files. Upload and edit.">
                <figure>
                    <img src="Manage.png" width="250" height="250" border="0" alt="WZDE Manage">
                    <figcaption>Manage</figcaption>
                </figure>
            </a></td>
            
            <td><a href="/publish" title="Commit and Publish Code. Launch Test Site.">
                <figure>
                    <img src="Publish.png" width="250" height="250" border="0" alt="WZDE Publish">
                    <figcaption>Publish</figcaption>
                </figure>
            </a></td>
            
            <td><a href="https://staff.meditech.com/en/d/wzdedocumentation/homepage.htm" target="_blank" title="End User Help Documentation">
                <figure>
                    <img src="documentation.png" width="250" height="250" border="0" alt="WZDE Documentation">
                    <figcaption>Documentation</figcaption>
                </figure>
            </a></td>            
        </tr>
    </table>
    <div class="footer" style="margin-top:200px;">
        <p class="copyright">Web Zone Development Environment  |  Version 1.5<br></p>
    </div>
    
</div>

<div id="JQUI_dialog_container" style="z-index:1000;"></div>

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="//www.googletagmanager.com/gtag/js?id=UA-22228657-9"></script>

<script type="text/javascript" charset="utf8" src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script type="text/javascript" charset="utf8" src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>    
<script type="text/javascript" charset="utf8" src="https://use.fontawesome.com/c1367ca9e5.js"></script>
<script>

window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'UA-22228657-9', { 'anonymize_ip': true });

$(document).ready(function () {
    $.ajaxSetup({
        cache : false
    });
   
    statusByGroup();
   
    $("#wzde_status").on("click", function () {
        //opendialog('WZDE Solar Page', 'http://meditech-netmon:8787/Orion/NetPerfMon/NodeDetails.aspx?AccountID=DCM-Dashboard&NetObject=N:2948');
        window.open('http://meditech-netmon:8787/Orion/NetPerfMon/NodeDetails.aspx?AccountID=DCM-Dashboard&NetObject=N:2948','_blank');
    });

    $("#svn_status").on("click", function () {
        opendialog('WZDE STATUS', 'status/');
    });

    $("#queue_status").on("click", function () {
        opendialog('WZDE STATUS', 'status/');
    });
   
});    

function opendialog(action, page) {

    var $dialog = $('#JQUI_dialog_container')
        .html('<iframe style="border: 0px;position: absolute; " src="' + page + '" width="97%" height="97%"></iframe>')
        .dialog({
            title : action,
            autoOpen : false,
            dialogClass : 'dialog_fixed,ui-widget-header',
            modal : true,
            height : ($(window).height()) * 0.8,
            width : ($(window).width()) * 0.9,
            minWidth : 800,
            minHeight : 600,
            draggable : true
        });
    $dialog.dialog('open');
}


function statusByGroup() {
//serialize the array
var groups=['3489','1089','1090','1174','2897','2948'].join(',');
var id="";

var jqxhr = $.getJSON( "../admin/js/getStatus_JSON.php",{ groups: groups})
        .done(function( json_data ) {
            $.each(json_data, function(key, value){                
                switch (value["NodeID"])
                {
                    case '3489' :
                        id="Staff_Status";
                        break;
                    case '1089' :
                        id="Home_Status";
                        break;                          
                    case '1090' :
                        id="Customer_Status";
                    case '1174' :
                        id="Svn_Status";                        
                        break;
                    case '2897' :
                        id="Queue_Status";                        
                        break;                        
                    case '2948' :
                        id="WZDE_Status";
                        break;
                    default :
                        id="xxxx";
                        break;
                }
                //console.log("p#"+id);
                switch(value["Status"].trim())
                {
                    case "0" :
                        $("#"+id).addClass('SystemStatusGrey').removeClass('SystemStatusDefault');
                        break;
                    case "1" :
                        $("#"+id).addClass('SystemStatusGreen').removeClass('SystemStatusDefault');
                        break;
                    case "2" :
                        $("#"+id).addClass('SystemStatusRed').removeClass('SystemStatusDefault');
                        break;
                    case "3" :
                        $("#"+id).addClass('SystemStatusYellow').removeClass('SystemStatusDefault');
                        break;
                    case "14" :
                        $("#"+id).addClass('SystemStatusRed').removeClass('SystemStatusDefault');
                        break;
                    default : 
                        $("#"+id).addClass('SystemStatusBlack').removeClass('SystemStatusDefault');
                        break;
                }
            });
        })
        .always(function(){
            //query every 300 seconds (5min)
            setTimeout(statusByGroup, 300000);
        })
        .fail(function( jqxhr, textStatus, error ) {
            var err = textStatus + ', ' + error;
            console.log( "Request Failed: " + err);
        });
};  

</script>

</body></html>



