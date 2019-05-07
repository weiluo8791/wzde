var allZone = ["staff","customer","home","cdn","logi","staffapps","atsignage"];

// When the DOM is ready, get the party started!
$(document).ready(function() {
    $.ajaxSetup({ cache:false });
    var list = $('#list');
    var html = buildFunctionList();  
    list.html(html);    

    $( "#s_retire" ).on( "click", function() {
      opendialog('Retire Site','scripts/system.php?action=retire');
    });

    $( "#s_createS" ).on( "click", function() {
      opendialog('Create Independent Site','scripts/system.php?action=create_s');
    });  

    $( "#s_createZ" ).on( "click", function() {
      opendialog('Create Independent Zone','scripts/system.php?action=create_z');
    });

    $( "#s_down" ).on( "click", function() {
      opendialog('WZDE Down Time','scripts/system.php?action=down');
    });

    $(document).on("click","#submitDown",submitDown);
    
});


function applyWzdeStatuses() {
    var status;
    // Retrieve  statuses from database.
    $.ajax({
        url: "read.php",
        type: "POST",
        data: {data: [],script:'readWzdeStatus.sql'},
        dataType: "json",
    })
    .done(function(data) {
        $.each(data,function(key,value) {
            var name = value["zone"],
                status = value["status"];
            $("#"+name).val(status);
        });        
        //set other zone if all is not in OVERRIDE
        if ($("#all").val()!="OVERRIDE") {
            var all = $("#all").val();
            $.each(allZone,function( key, value ) {
                $("#"+value).val(all);
                $("select#"+value).attr("disabled","disabled");
            });
        }        
    })    
    .fail(function(jqxhr,textStatus,error) {
        var err = textStatus + ', ' + error;
        console.log("Request Failed: " + err);
    });
    
} 

//onchange for select all - set other zone if all is not in OVERRIDE
//in : value of select all
function allStatus(sel){
    if (sel.value!="OVERRIDE") {
        $.each(allZone,function( key, value ) {
            $("#"+value).val(sel.value);
            $("select#"+value).attr("disabled","disabled");
        });        
    }
    else {
        $.each(allZone,function( key, value ) {
            $("#"+value).val(sel.value);
            $("select#"+value).attr("disabled",false);
        });        
    }
}
 
function submitDown(event){
    var data = [],
    zone = $(".zone"),
    message = $("#messageSubmit");
    zone.each(function() {        
        var name = $(".name",$(this)).attr("for"),
        status = $(".status",$(this)).val();
        if (status) {
            data.push({zone:name,status:status});
        }
    });
        
    // Save status settings to database if there's actually data to save.
    if (data.length > 0) {
        message.removeClass().addClass("message process").html('<p>Saving...</p>'); 
        
        $.ajax({
            url: "update.php",
            type: "POST",
            data: {data: data,script:'updateWzdeStatus.sql'},
            dataType: "json",
            success: function() {
                message.removeClass().addClass("message process").html('<p>Done</p>'); 
            },
            error: function() {
              message.removeClass().addClass("message process").html('<p>There was an error. Try again please!</p>');
            }
        })
        .fail(function(jqxhr,textStatus,error) {
             var err = textStatus + ', ' + error;
             console.log("Request Failed: " + err);   
        });
    }    
 
}

function opendialog(action,page) {

  var $dialog = $('#JQUI_dialog_container')
  .html('<iframe style="border: 0px;position: absolute; " src="' + page + '" width="97%" height="97%"></iframe>')
  .dialog({
    title: action,
    autoOpen: false,
    dialogClass: 'dialog_fixed,ui-widget-header',
    modal: true,
    height: ($(window).height()) * 0.9,
    width: ($(window).width()) * 0.8,
    minWidth: 800,
    minHeight: 600,
    draggable:true,
  });
  $dialog.dialog('open');
} 

function buildFunctionList(){
    
    var html= '<div class="row">';
    
            html += '<div class="small-12 medium-8 large-8 columns">';
            html += '<p class="body_nav">';
            html += '<button id="s_retire"><span class="fa fa-trash-o fa-lg"> Retire</span></button>';
            html += '&nbsp;<span class="description">Retire or Disable Site.</span>';
            html += '</p>';
            html += '</div>';
        html += '</div>';
    
        html += '<div class="row">';
            html += '<div class="small-12 medium-8 large-8 columns">';
            html += '<p class="body_nav">';
            html += '<button id="s_createS"><span class="fa fa-plus fa-lg"> Create Site</span></button>';
            html += '&nbsp;<span class="description">Create Independent Site.</span>';
            html += '</p>';
            html += '</div>';
        html += '</div>';
        
        html += '<div class="row">';
            html += '<div class="small-12 medium-8 large-8 columns">';
            html += '<p class="body_nav">';
            html += '<button id="s_createZ"><span class="fa fa-plus-circle fa-lg"> Create Zone</span></button>';
            html += '&nbsp;<span class="description">Create Independent Zone.</span>';
            html += '</p>';
            html += '</div>';
        html += '</div>';        

        html += '<div class="row">';
            html += '<div class="small-12 medium-8 large-8 columns">';
            html += '<p class="body_nav">';
            html += '<button id="s_down"><span class="fa fa-stop fa-lg"> Downtime</span></button>';            
            html += '&nbsp;<span class="description">Start/Stop WZDE Downtime.</span>';
            html += '</p>';
            html += '</div>';
        html += '</div>';         

    return html;    
}