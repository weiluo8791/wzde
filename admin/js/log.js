// When the DOM is ready, get the party started!
$(document).ready(function() {
    $.ajaxSetup({ cache:false });
    var siteList = getSitesList();
    var logList = [{"id" : 1,
                        "name" : "Log"},
                    {"id" : 2,
                        "name" : "Audit"},                    
                    ];
    var list = $('#list');
    var html = buildSearchForm([],[],siteList,logList);    
    list.html(html);    
    loadMultiSelect();
});

// Get list of applications.
function getSitesList() {
    var query = "SELECT distinct idsites id, site name,zone FROM webutility.wzde_files order by site asc",
        bindV = {};
        queryDataJson = $.parseJSON(
        $.ajax({
            url : "scripts/mariadb_query.php",
            cache: false,
            data : {
                query : query,
                bindV : JSON.stringify(bindV),
                multi : true
            },                  
            async : false,
            dataType : 'json'
        }).responseText);
    return queryDataJson;
}

function loadMultiSelect() {
    
    // Multiselect resources.
    $('#searchSite').select2({
        templateSelection: function(selection) {
            var $selection = $('<span id="'+selection.id+'" class="site">'+selection.text+'</span>');
            return $selection;
        }
    });
    //select all 
    $("#allSite").click(function(){
        if($("#allSite").is(':checked') ){
            $("#searchSite > option").prop("selected","selected");
            $("#searchSite").trigger("change");
        }else{
            $("#searchSite > option").removeAttr("selected");
             $("#searchSite").trigger("change");
         }
    });    
    // Multiselect used by.
    $('#logType').select2({
        templateSelection: function(selection) {
            var $selection = $('<span id="'+selection.id+'" class="type">'+selection.text+'</span>');
            return $selection;
        }
    });
    //select all
    $("#allType").click(function(){
        if($("#allType").is(':checked') ){
            $("#logType > option").prop("selected","selected");
            $("#logType").trigger("change");
        }else{
            $("#logType > option").removeAttr("selected");
             $("#logType").trigger("change");
         }
    });
    
    $('#submitSearch').on('click',submitSearch);    
}

function submitSearch(e) {
    e.preventDefault();
    var payload = {},
        site = [],
        type = [];
        
    $('.site').each(function() {      
      var entry = $(this),
          id = entry.attr('id');
          name = entry.text();          
      if (id) {
       site.push({id:id,name:name});
      }
     });
     
    $('.type').each(function() {
      var entry = $(this),
          id = entry.attr('id');
          name = entry.text(); 
      if (id) {
       type.push({id:id,name:name});
      }
     });
    payload.site = site;
    payload.type = type;
    
    var url = 'scripts/log.php';
    
    var $dialog = $('#JQUI_dialog_container').load(url, {"sites": payload.site, "types": payload.type})
    		.dialog({
			title : 'Log Viewer',
			autoOpen : false,
			dialogClass : 'dialog_fixed,ui-widget-header',
			modal : true,
			height : ($(window).height()) * 0.9,
			width : ($(window).width()) * 0.8,
			minWidth : 800,
			minHeight : 600,
			draggable : true,
		});
        $dialog.dialog('open');
}

