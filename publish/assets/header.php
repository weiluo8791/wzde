<html>
<head>
    <title> <?php echo $WZDE["TITLE"] ?> </title>
    
    <!-- Main CSS -->  
    <link rel="stylesheet" TYPE="text/css" HREF="css/main.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" TYPE="text/css" HREF="css/jquery.dataTables.css">
    <link rel="stylesheet" href="//code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css">
    <!--<link rel="stylesheet" TYPE="text/css" HREF="//cdn.datatables.net/1.10.7/css/jquery.dataTables.css">-->
    
    <!-- jQuery -->
    <script type="text/javascript" charset="utf8" src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>     
    <script type="text/javascript" charset="utf8" src="//code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
    <!-- blockUI -->
    <script type="text/javascript" charset="utf8" src="//malsup.github.io/jquery.blockUI.js"></script>
    <!-- DataTables Script-->
    <script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.7/js/jquery.dataTables.js"></script>
    <!-- wzdePublish -->        
    <script type="text/javascript" charset="utf8" src="scripts/wzdePublish.js"></script>
	
	<!-- Global site tag (gtag.js) - Google Analytics -->
	<script async src="//www.googletagmanager.com/gtag/js?id=UA-22228657-9"></script>

	<!-- gtag JS -->
	<script src="scripts/ga.js"></script>	
    
    <script type="text/javascript">
        var default_site,resource = '';
        if(getQueryVariable("folder")){
            default_site = getQueryVariable("folder");
        }
        else {
            default_site = '<?php echo $WZDE["DEFAULT_SITE"] ?>';
        }
        if(getQueryVariable("resource")){
            resource = getQueryVariable("resource").toLowerCase();
        }
        else {
            resource = '<?php echo $WZDE['resource'] ?>';
        }
        
        //overload function to scroll position by container(div) and id (tr id)
        jQuery.fn.scrollTo = function(elem, speed) { 
            $(this).animate({
                scrollTop:  $(this).scrollTop() - $(this).offset().top + $(elem).offset().top 
                }, speed == undefined ? 1000 : speed); 
            return this; 
        };
        
        $(window).focus(function() {
            $('button').off("focus");
            $('button').focus(function() {
                this.blur();
            });
        });             		
        
        //enable jquery UI tooltips
        $(function() {
            //$( document ).tooltip();
            var dl = 0;
            $(document).tooltip({
                show: {
                    effect: 'fadeIn'
                },
                hide: {
                    effect: "fadeOut"
                },
                delay : dl,
                track: false,
            });            
        });
        
        //code for adding icon to the zone select menu 
        $(function() {
            $.widget( "custom.iconselectmenu", $.ui.selectmenu, {
              _renderItem: function( ul, item ) {
                var li = $( "<li>", { text: item.label } );

                if ( item.disabled ) {
                  li.addClass( "ui-state-disabled" );
                }

                $( "<span>", {
                  style: item.element.attr( "data-style" ),
                  "class": "ui-icon " + item.element.attr( "data-class" )
                })
                  .appendTo( li );

                return li.appendTo( ul );
              }
            });

            $( "#resource" )
                .iconselectmenu()
                .iconselectmenu( "menuWidget")
                .addClass( "ui-menu-icons avatar" );
                
            $('#resource').iconselectmenu({
                change: function( event, ui) {
                    $(this).closest('form').trigger('submit');
					//add animation for loading when select from the zone (resource) menu
					$("body").addClass("loading");
                }
            }); 
        });                
    </script> 
    
</head>
<body>
<div class="title_area">
    <table class="title_area"><tr>
    <td class="title"><p class="title"><?php echo $WZDE["TITLE"] ?></p></td>
    <td class="subtitle">
        <div>
        <a href="https://staff.meditech.com/en/d/wzdedocumentation/homepage.htm" target="_blank">
        <img src="/documentation.png" style="vertical-align:text-top" width="22" height="22" border="0" alt="WZDE Documentation"></a>
        <span class="subtitle">Web Zone Development Environment (WZDE)</span>
        </div>
        <p class="subtitle">Advanced Technology Web</p>    
    </td>
    </tr></table>
</div>

<div id="initialCommitQuestion" style="display:none; cursor: default"> 
        <h2>This site is not in WZDE repository !</h2>
        <p>Adding file will trigger initial commit for the WHOLE site.</p>
        <p>Do you want to continue ?</p>
        <hr class="blockMsg-hr"> 
        <input type="button" id="ic_yes" value="OK" /> 
        <input type="button" id="ic_no" value="Cancel" /> 
</div> 

<div id="publishWholeQuestion" style="display:none; cursor: default"> 
        <h2>This action will Commit and Publish the whole site.</h2>
        <p>Do you want to continue ?</p>
        <hr class="blockMsg-hr"> 
        <input type="button" id="pw_yes" value="Yes" /> 
        <input type="button" id="pw_no" value="No" /> 
</div>

<div id="commitWholeQuestion" style="display:none; cursor: default"> 
        <h2>This action will Commit the whole site.</h2>
        <p>Do you want to continue ?</p>
        <hr class="blockMsg-hr"> 
        <input type="button" id="cw_yes" value="Yes" /> 
        <input type="button" id="cw_no" value="No" /> 
</div>

<div id="publishQueueQuestion" style="display:none; cursor: default"> 
        <h2>This action will Commit (if needed) and Publish the queue.</h2>
        <p>Do you want to continue ?</p> 
        <hr class="blockMsg-hr">
        <input type="button" id="pq_yes" value="Yes" /> 
        <input type="button" id="pq_no" value="No" /> 
</div> 

<div id="commitQueueQuestion" style="display:none; cursor: default"> 
        <h2>This action will Commit the queue.</h2>
        <p>Do you want to continue ?</p>
        <hr class="blockMsg-hr"> 
        <input type="button" id="cq_yes" value="Yes" /> 
        <input type="button" id="cq_no" value="No" /> 
</div>  

<!-- ATWEB-5010 by WLUO - Allow custom comment and prop change to reflect actual author -->
<div id="publishWholeMessage" style="display:none; cursor: default"> 
        <h2>This will COMMIT (if needed) and PUBLISH the whole site.</h2>
        <textarea style="resize:none" name="pwm_message" id ="pwm_message" rows="4" cols="50" placeholder="Commit Message is required..."></textarea>        
        <p>Do you want to continue ?</p>
        <hr class="blockMsg-hr"> 
        <input type="button" id="pwm_yes" value="OK" /> 
        <input type="button" id="pwm_no" value="Cancel" /> 
</div>

<div id="commitWholeMessage" style="display:none; cursor: default"> 
        <h2>This will COMMIT the whole site.</h2>
        <textarea style="resize:none" name="cwm_message" id ="cwm_message" rows="4" cols="50" placeholder="Commit Message is required..."></textarea>
        <p>Do you want to continue ?</p>
        <hr class="blockMsg-hr"> 
        <input type="button" id="cwm_yes" value="OK" /> 
        <input type="button" id="cwm_no" value="Cancel" /> 
</div>

<div id="publishQueueMessage" style="display:none; cursor: default"> 
        <h2>This will COMMIT (if needed) and PUBLISH everything in the queue.</h2>
		<h3>Only allowed actions will be performed.</h3>
        <textarea style="resize:none" name="pqm_message" id ="pqm_message" rows="4" cols="50" placeholder="Commit Message is required..."></textarea>
        <p>Do you want to continue ?</p>
        <hr class="blockMsg-hr"> 
        <input type="button" id="pqm_yes" value="OK" /> 
        <input type="button" id="pqm_no" value="Cancel" /> 
</div> 

<div id="commitQueueMessage" style="display:none; cursor: default">
        <h2>This will COMMIT everything in the queue.</h2>
		<h3>Only allowed actions will be performed.</h3>
        <textarea style="resize:none" name="cqm_message" id ="cqm_message" rows="4" cols="50" placeholder="Commit Message is required..."></textarea>
        <p>Do you want to continue ?</p>
        <hr class="blockMsg-hr">         
        <input type="button" id="cqm_yes" value="OK" /> 
        <input type="button" id="cqm_no" value="Cancel" /> 
</div> 
<!-- End of ATWEB-5010 -->

<!-- ATWEB-8247 Add waiting indicator -->
<div class="modal"><!-- Place at bottom of page --></div>