/**
 * WZDE Publish Front End 
 * BY Wei Qi Luo
 * 2014
 */

/*jslint browser: true*/
/*global $, jQuery, alert*/

// custom contains function for case insensitive
jQuery.expr[":"].icontains = jQuery.expr.createPseudo(function (arg) {                                                                                                                                                                
    return function (elem) {                                                            
        return jQuery(elem).text().toUpperCase().indexOf(arg.toUpperCase()) >= 0;        
    };                                                                                  
});

// custom textEquals function for exact string match
jQuery.expr[':'].textEquals = jQuery.expr.createPseudo(function(arg) {
    return function( elem ) {
        return jQuery(elem).text().match("^" + arg + "$");
    };
});

var previous = {};
var currentSite,previousSite;
//elfinder specific folder/file characters
var keyStr = "ABCDEFGHIJKLMNOP" +
    "QRSTUVWXYZabcdef" +
    "ghijklmnopqrstuv" +
    "wxyz0123456789-_" +
    ".";
    
$(document).ready(function() {	

    $('#loading-image').show();    
    
	currentSite = default_site;    
    //init wzdeSiteTable Table
	var sTable=$('#wzdeSiteTable').dataTable( {
        "initComplete": function( settings, json ) {
            //console.log( 'DataTables has finished its initialisation.' );
            $("#loading-image").hide();
        },
        // "fnDrawCallback": function( settings ) {
            // console.log( 'DataTables has redrawn the table' );
            // $("#loading-image").hide();
        // },        
        "bFilter": false,
        //default sorting on first column
		"aaSorting": [[ 4, "asc" ]],
        "bLengthChange" : false,
        "bInfo" : false,
        "bPaginate" : false,
        "sScrollY": "130",
        "autoWidth": false,
        //disable sorting on the first column
        "aoColumnDefs" : [
          {
            bSortable: false,
            aTargets: [ 0 ]
          },
          {
            bSortable: false,
            aTargets: [ 1 ]
          },
          {
            bSortable: false,
            aTargets: [ 2 ]
          }          
        ],       
	} );
    
    //init wzdeQueueTable Table    
    var qTable=$('#wzdeQueueTable').dataTable( {		
        "bFilter": false,
        //default sorting on first column
        "aaSorting": [[ 1, "desc" ]],
        "bLengthChange" : false,
        "bPaginate" : false,
        "sScrollY": "100",
        "autoWidth": false,
        //disable sorting on the first column
        "aoColumnDefs" : [
        {
            bSortable: false,
            aTargets: [ 0 ]
        }
        ],       
    } );    
	    
	//init wzdeFileTable Table
	var vTable=intializeDatatable('wzdeFileTable');
    
    $(document).on('click','#wzdeSiteTable tbody tr td.clickable', function(){
        var site = $(this).text();
        //Assign handlers immediately after making the request,
        //and remember the jqxhr object for this request
        if ( $(this).closest('tr').hasClass('selected') ) {
            //$(this).closest('tr').removeClass('selected');
        }
        else {
            sTable.$('tr.selected').removeClass('selected');
            $(this).closest('tr').addClass('selected');
        }        
        var jqxhr = refreshFileTable(site,resource);        
	});
    
    //bind publish whole site
    //.on(event,selector,data,callback)
    $(document).on("click",'#wzdeSiteTable > tbody > tr > td.publishWholeButton > button',{resource: resource}, publishWholeSite );
    
    //bind commit whole site
    $(document).on("click",'#wzdeSiteTable > tbody > tr > td.commitWholeButton > button',{resource: resource}, commitWholeSite );
    
    //bind launch Staging site
    $(document).on("click",'#wzdeSiteTable > tbody > tr > td.stageButton > button',{resource: resource}, launchStage );
    
    //bind manage site
    $(document).on("click",'#wzdeSiteTable > tbody > tr > td.manageSiteButton > button',{resource: resource}, manageWholeSite );   
    
    //bind commit queue
    $(document).on("click",'form#action_Form > button#commit_queue',{resource: resource}, commitQueue );
    
    //bind publish queue
    $(document).on("click",'form#action_Form > button#publish_queue',{resource: resource}, publishQueue );

    //bind clear queue
    $(document).on("click",'form#action_Form > button#publish_clear',{resource: resource}, clearQueue );    
    
    //bind click on directory
    $(document).on('click','#wzdeFileTable tbody tr td.clickable', function(){
        refreshFileTable(currentSite + "/" + $(this).text(),resource);
    });     
    
    if( previousSite ) {
        $('#showPrevious').show();
    }
    else {
        $('#showPrevious').hide();
    }

    $(window).resize( function () {
       sTable.fnAdjustColumnSizing();
       qTable.fnAdjustColumnSizing();
       vTable.fnAdjustColumnSizing();
    } ); 
    
    if (getQueryVariable('folder')) {
       var site = getQueryVariable('folder');		
       $("#wzdeSiteTable tbody tr td.clickable u:textEquals(" + site + ")").closest('td').trigger('click'); 
    }    
	
	// ATWEB-8247 Add waiting indicator
	// Starting jQuery 1.9, AJAX events should be attached to document only.
	// Show loading before ajax
	$(document).ajaxStart(function () {
		$("body").addClass("loading");
		
	});
	//hide loading after ajax
	$(document).ajaxStop(function () {
		$("body").removeClass("loading");
	});		
	
}); // end .ready

function queueUp() {    
    var seleted_table = $(this).parents('table').attr('id');    
    //queue
    if (seleted_table === 'wzdeFileTable'){
        var table1 = $('#wzdeFileTable').DataTable(),
            table2 = $('#wzdeQueueTable').DataTable(),
            row = table1.row( $(this).parents('tr') ),
            rowNode = row.node(),
            checkoutPath = rowNode.cells[3].innerHTML;
        
        //If not already committed
        if (isCommitted(checkoutPath) == 0) {
        
            $.blockUI({ message: $('#initialCommitQuestion'), css: { width: '500px' } });      
            $('#ic_yes').unbind().click(function() { 
                // update the block message 
				$.blockUI({ message:"<h3>Commit in progress...</h3>" + 
									"<h5>Please check your email for commit/deploy status</h5>",
							timeout:5000
				});
                //remote initial commit call
                $.ajax({
                    type: 'POST',
                    url : "./scripts/svnCommands.php",
                    cache: false,
                    data : {
                        cPath : checkoutPath,
                        committed : 0,
                        command : 'wholeCommit',
                    },
                    complete: function(jqxhr,textStatus) { 
                        // unblock when remote call returns 
                        $.unblockUI();
                        //if success add the file to the queue
                        if (textStatus === 'success') {
                            row.remove().draw();
                            table2.row.add( rowNode ).draw();
                        }                        
                    },                     
                });                
            }); 
     
            $('#ic_no').unbind().click(function() { 
                $.unblockUI(); 
                return false; 
            });             
        }
        //else if already committed just add the file to the queue
        else {
            row.remove().draw();
            table2.row.add( rowNode ).draw();
        }
    }
    //de-queue
    else if (seleted_table === 'wzdeQueueTable') {
        var table1 = $('#wzdeQueueTable').DataTable();
        var row = table1.row( $(this).parents('tr') );
        row.remove().draw();
        refreshFileTable(currentSite,resource);
    }
    
    //remove tr class (for alternate color odd and even row)
    $('table:not(#wzdeSiteTable) tbody tr').removeClass();
    //now add tr class names again
    $('table:not(#wzdeSiteTable) tbody tr:odd').addClass('odd');
    $('table:not(#wzdeSiteTable) tbody tr:even').addClass('even');
 
}

function launchStage(event){
    var site = $(this).context.id,
        resource = event.data.resource;
    window.open('https://wzde.meditech.com:8080/dev_publish/' + resource + '/' + site );
}

function manageWholeSite(event){    
    var site = $(this).context.id,
        resource = event.data.resource,
        host = window.location.origin + "/manage/",
        prefix = getWzdePrefix(resource);
        link = host + prefix + encode64(site);
    window.open(link,"_blank");
}

function getWzdePrefix(resource) {
    var link="";
    switch (resource.toUpperCase()) {
    case 'STAFF':
        link = "#elf_l1_";
        break;
    case 'HOME':
        link = "#elf_l2_";
        break;
    case 'CUSTOMER':
        link = "#elf_l3_";
        break;
    case 'STAFFAPPS':
        link = "#elf_l4_";
        break;
    case 'ATSIGNAGE':
        link = "#elf_l5_";
        break;
    case 'LOGI':
        link = "#elf_l6_";
        break;
    case 'CDN':
        link = "#elf_l7_";
        break;  
    case 'CTS':
        link = "#elf_l8_";
        break;  		
    case 'DB_STORAGE':    
        link = "#elf_m9_";
        break;    
    default :
        return FALSE;
    }
    return link;
}

//commit queued file or folder
function commitQueue(event){
    var table = $('#wzdeQueueTable').DataTable(),
		tableData = table.data(),
		errorData = "";
	
    
    //if something in the queue commit it
    if (tableData.length>0) {    
        $.blockUI({ message: $('#commitQueueMessage'), css: { width: '600px' } });   
        $('#cqm_yes').unbind().click(function() {
            var xhrs = [],
				sitePath = {};
			
			//get all unique site
			$.each(tableData, function (key, value) {
                var checkoutPath = (value[5] === 'directory') ? value[3] + '/' + $(value[4]).text() + '/' : value[3] + '/' + value[4] ;
				sitePath[checkoutPath.split("/").slice(0,2).join("/")] = 1;	
			});
			
			//run cleanup on each site
			$.each(sitePath, function (key, value) {
				var querCleanupJson = $.parseJSON(
					$.ajax({
						type: 'POST',
						url : "./scripts/svnCommands.php",
						data : {
							cPath : key,
							command : 'cleanup',
						},
						async: false,
						beforeSend : function (){
							$.blockUI({ message: "<h3>SVN Cleanup...</h3>" });
						},
						success: function(data) {
							if (data !='1') {
								//if (!errorData) {
									errorData += data.join("<br>");
								//}
								$.blockUI({	message: "<h3>ERROR: </h3>" +
													 "<h5>" + errorData + "</h5>" +
													 "<3>There is problem running SVN CLEANUP.</h3>" +
													 "<h3>Please fix and try again.</h3>",
											css: { width: '600px' }});
								$('.blockOverlay').unbind().click($.unblockUI);
								return false;
							}
							else {							
								$.unblockUI();
							}    
						},
						dataType : 'json'
					}).responseText
				);				
			});
			
			
			//commit each item in the queue
            $.each(tableData, function (key, value) {				
                var checkoutPath = (value[5] === 'directory') ? value[3] + '/' + $(value[4]).text() + '/' : value[3] + '/' + value[4] ;				
                //add each folder or file (if needed)
                var xhr = $.ajax({
                    type: 'POST',
                    url : "./scripts/svnCommands.php",
                    data : {
                        cPath : checkoutPath,
                        command : 'add'
                    },
					async: false,
                    beforeSend : function (){
                        $.blockUI({ message: "<h3>Adding any new file or folder...</h3>" });
                    },
                    success: function(data) {
                        if (data !='1') {
							//if (!errorData) {
								errorData += data.join("<br>");
							//}
                            $.blockUI({ message: "<h3>ERROR: </h3>" +
												 "<h5>" + errorData + "</h5>" +
												 "<3>There is problem adding file or folder to the subversion repository.</h3>" +
                                                 "<h3>Please fix and try again.</h3>",
										css: { width: '600px' }  });
                            //$('.blockOverlay').attr('title','Click to go back').unbind().click($.unblockUI);
							$('.blockOverlay').unbind().click($.unblockUI);
                        }
                        else {							
                            $.unblockUI();
                        }    
                    },
                    dataType : 'json'
                });
                xhrs.push(xhr);
            });
            //When all SVN add are successful commit the queueList (in text file in the format of commitList_$_COOKIE["PHPSESSID"].txt)
            $.when.apply($, xhrs).done(function(){				
                $.ajax({
                    type: 'POST',
                    url : "./scripts/svnCommands.php",
                    data : {
                        cPath   : "commitList_",
                        command : 'commitQueue',
                        message : $("#cqm_message").val()
                    },              
                    beforeSend : function (){
						//remove loading animation
						$("body").removeClass("loading");
						// Auto unblock in 5 seconds
                        $.blockUI({ message:"<h3>Commit in progress...</h3>" + 
											"<h5>Please check your email for commit/deploy status</h5>" +
											"<h5>You can also check <a href=/status target=_blank rel=noopener noreferrer>WZDE Status</a> for more detail.</h5>",
									timeout:5000
						});
                    },
                    success: function(data) {
                        if (data !='1') {
							//if (!errorData) {
								errorData += data.join("<br>");
							//}							
                            $.blockUI({ message: "<h3>ERROR: </h3>" +
												 "<h5>" + errorData + "</h5>" +
												 "<h3>There is problem committing file or folder to the subversion repository.</h3>" +
                                                 "<h3>Please fix and try again.</h3>",
										css: { width: '600px' }
									});
							$('.blockOverlay').unbind().click($.unblockUI); 							
                        }
                        else {
                            $.unblockUI();
                            //clear the queue table and refresh the file table
                            if (tableData.length > 0) {
                                table.clear().draw();
                                refreshFileTable(currentSite,resource);
                            }
                            //clear variable
                            tableData = "";
                            //clear commit message box
                            $('#cqm_message').val('');
                        }    
                    },
                    dataType : 'json'
                });            
            });
        }); 
     
        $('#cqm_no').unbind().click(function() { 
            $.unblockUI();
            //clear commit message box
            $('#cqm_message').val('');            
            return false; 
        });
    }
}

//publish queued file or folder
function publishQueue(event){
    var table = $('#wzdeQueueTable').DataTable(),
		tableData = table.data(),
		errorData = "";
    
    //if something in the queue publish it
    if (tableData.length>0) {
        $.blockUI({ message: $('#publishQueueMessage'), css: { width: '650px' } });      
        $('#pqm_yes').unbind().click(function() {
            var xhrs = [];
			var sitePath = {};
			
			//get all unique site
			$.each(tableData, function (key, value) {
                var checkoutPath = (value[5] === 'directory') ? value[3] + '/' + $(value[4]).text() + '/' : value[3] + '/' + value[4] ;
				sitePath[checkoutPath.split("/").slice(0,2).join("/")] = 1;	
			});
			
			//run cleanup on each site
			$.each(sitePath, function (key, value) {
				var querCleanupJson = $.parseJSON(
				$.ajax({
					type: 'POST',
					url : "./scripts/svnCommands.php",
					data : {
						cPath : key,
						command : 'cleanup',
					},
					async: false,
					beforeSend : function (){
						$.blockUI({ message: "<h3>SVN Cleanup...</h3>" });
					},
					success: function(data) {
						if (data !='1') {
							//if (!errorData) {
								errorData = data.join("<br>");
							//}
							$.blockUI({ message: "<h3>ERROR: </h3>" +
												 "<h5>" + errorData + "</h5>" +
												 "<3>There is problem running SVN CLEANUP.</h3>" +
												 "<h3>Please fix and try again.</h3>",css: { width: '600px' }  });
							$('.blockOverlay').unbind().click($.unblockUI);
							return false;
						}
						else {							
							$.unblockUI();
						}    
					},
					dataType : 'json'
				}).responseText);														
			});
			
            $.each(tableData, function (key, value) {
                var checkoutPath = (value[5] === 'directory') ? value[3] + '/' + $(value[4]).text() + '/' : value[3] + '/' + value[4];				
                //var access = value[10].split(",");
                
                // DO NOT check commit access, any publish should automatic commit first 
                //add each folder or file (if needed)              
                var xhr = $.ajax({
                            type: 'POST',
                            url : "./scripts/svnCommands.php",
                            data : {
                                cPath : checkoutPath,
                                command : 'add'
                            },
							async: false,
                            beforeSend : function (){
                                $.blockUI({ message: "<h3>Adding any new file or folder...</h3>" });
                            },
                            success: function(data) {
                                if (data !='1') {
									errorData = data.join("<br>");
                                    $.blockUI({ message: "<h3>ERROR: </h3>" +
														 "<h3>There is problem adding file or folder to the subversion repository.</h3>" +
                                                         "<h3>Please fix and try again.</h3>",css: { width: '600px' }  });
									$('.blockOverlay').unbind().click($.unblockUI); 									
                                }
                                else {
                                    $.unblockUI();
                                }                    
                            },                     
                            dataType : 'json'
                        });
                
                // //add folder or file to the publish queue
                // if(value[5] === 'directory' && access.indexOf("p") >= 0 ) {
                    // //publish folder 
                    // addDir(value[3] + '/' + $(value[4]).text() + '%');
                // }
                // else if ( access.indexOf("p") >= 0 ) {
                    // //publish file
                    // addFile(tableData.row(key).node().id);
                // }
                xhrs.push(xhr);
            });
            
            //When all SVN add are successful commit the queueList (in text file in the format of commitList_$_COOKIE["PHPSESSID"].txt)
            //This file is generated from SVN add as a by session text file
            $.when.apply($, xhrs).done(function() {              
                $.ajax({
                    type: 'POST',
                    url : "./scripts/svnCommands.php",
                    data : {
                        cPath : "commitList_",
                        command : 'commitQueue',
                        message : $("#pqm_message").val()
                    },              
                    beforeSend : function (){
						//remove loading animation
						$("body").removeClass("loading");
						//Auto unblock in 5 seconds
                        $.blockUI({ message:"<h3>Commit in progress...</h3>" +
											"<h5>Please check your email for commit/deploy status</h5>" +
											"<h5>You can also check <a href=/status target=_blank rel=noopener noreferrer>WZDE Status</a> for more detail.</h5>",								
									timeout:5000
						});
                    },
                    success: function(data) {					
                        if (data !='1') {
							//if (!errorData) {
								errorData = data.join("<br>");
							//}								
                            $.blockUI({ message: "<h3>ERROR: </h3>" +
												 "<h5>" + errorData + "</h5>" +
												 "<h3>There is problem committing file or folder to the subversion repository.</h3>" +
                                                 "<h3>Please fix and try again.</h3>",css: { width: '600px' }  });
							$('.blockOverlay').unbind().click($.unblockUI); 
                        }
                        else {
							//After successful commit, do publish by adding the file into the publish queue
							$.each(tableData, function (key, value) {
								//var access = value[10].split(",");
								var access = value[10];
								//add folder or file to the publish queue
								if(value[5] === 'directory' && access.indexOf("Publish-sm.png") >= 0 ) {
									// publish folder 
									// ATWEB-7453 by WLUO - Fix site name is a substring in other site causing problem, add site as agrument 
									addDir(value[2],value[3] + '/' + $(value[4]).text() + '%');
								}
								else if ( access.indexOf("Publish-sm.png") >= 0 ) {
									//publish file
									addFile(tableData.row(key).node().id);
								}				
							});		
							
							$.unblockUI();
							//clear queue 
							if (tableData.length > 0) {
								table.clear().draw();
								refreshFileTable(currentSite,resource);  
							}
							tableData = "";
							//clear commit message box
							$('#pqm_message').val('');                            
                        }    
                    },
                    dataType : 'json'
                });
				
            });
        });
        
        $('#pqm_no').unbind().click(function() { 
            $.unblockUI(); 
            //clear commit message box
            $('#pqm_message').val('');            
            return false; 
        });
    }        
}

function clearQueue(event){
    var table = $('#wzdeQueueTable').DataTable();
    var tableData = table.data();
    if (tableData.length > 0) {
        table.clear().draw();    
        refreshFileTable(currentSite,resource);
    }
    tableData = "";
}

// add directory to publish queue
// in : 0=site 1=path
// out: nothing
function addDir (site, dir) {
	// ATWEB-7453 by WLUO - Fix site name is a substring in other site causing problem, adding site condition
    var query = "select path,filename,type,'add'as ops from webutility.wzde_files where path like :dir and site=:site";
    var bindV = {"dir" : dir,
				"site" : site};
    var queryDataJson = $.parseJSON(
            $.ajax({
                url : "./scripts/mariadb_query.php",
                data : {
                    query : query,
                    bindV : JSON.stringify(bindV),
                    multi : true
                },
                async : false,
                dataType : 'json'
            }).responseText);
            
    //add and push the parent folder into the queue
    var tempObj = {"path": dir.substring(0, dir.lastIndexOf('/')),
                   "filename": dir.substring(dir.lastIndexOf('/')+1,dir.length-1),
                   "type": 'directory',
                   "ops": 'add'};
    queryDataJson.push( tempObj );
            
    //slice array into 50 chunk and queue up files
    var k,l,temparray,chunk = 50;
    for (k=0,l=queryDataJson.length; k<l; k+=chunk) {
        temparray = queryDataJson.slice(k,k+chunk);
        $.ajax({
            type: 'POST',
            crossDomain: true,
            url : "https://staffappslx.meditech.com/wluo/php_Q/add_to_queue_dev.php",
            data : {
                filelist : JSON.stringify(temparray),
            }
        })
        .done(function () {
            //alert('added');
        })
        .fail(function (jqxhr, textStatus, error) {
            //do thing when fail
            var err = textStatus + ', ' + error;
            console.log("Request Failed: " + err);
        });
    } //end for
       
}    

//add single file to publish queue
function addFile(hash) {
    var query = "select path,filename,type,'add'as ops from webutility.wzde_files where hash = :hash";
    var bindV = {"hash" : hash};
    //get single file path
    var queryDataJson = $.parseJSON(
        $.ajax({
            url : "./scripts/mariadb_query.php",
            data : {
                query : query,
                bindV : JSON.stringify(bindV),
                multi : true
            },
            async : false,
            dataType : 'json'
        }).responseText);
    //queue single file      
    $.ajax({
        type: 'POST',
        crossDomain: true,
        url : "https://staffappslx.meditech.com/wluo/php_Q/add_to_queue_dev.php",
        data : {
            filelist : JSON.stringify(queryDataJson),
        }
    })
    .done(function () {
        //alert('added');
    })
    .fail(function (jqxhr, textStatus, error) {
        //do thing when fail
        var err = textStatus + ', ' + error;
        console.log("Request Failed: " + err);
    });                
}

//Check if the root site is committed
function isCommitted (path) {
    //only take the resource and root
    var path = path.split("/").slice(0,2).join("/");
    var queryDataJson = $.parseJSON(
        $.ajax({
            type: 'POST',
            url : "./scripts/svnCommands.php",
            data : {
                cPath : path,
                command : 'info',
            },
            async : false,
            dataType : 'json'
        }).responseText);
    
    return queryDataJson;
}

function commitWholeSite(event) {
    var site = $(this).context.id,
        resource = event.data.resource,
        checkoutPath = resource + '/' + site,
		// ATWEB-7453 by WLUO - Fix site name is a substring in other site causing problem, adding site condition        
        query = "update webutility.wzde_files set commitdate = :cdate, status = :status where path like :path and site = :site",
        gmt = new Date().toISOString().slice(0, 19).replace('T', ' ');
        bindV = {"cdate" : gmt,
                "status" : "COMMITTED",
                "path" : checkoutPath + "%",
				"site" : site};
    
    $.blockUI({ message: $('#commitWholeMessage'), css: { width: '600px' } });
    $('#cwm_yes').unbind().click(function() { 
        //Commit the whole site
        $.ajax({
            type: 'POST',
            url : "./scripts/svnCommands.php",
            cache: false,
            data : {
                cPath : checkoutPath,
                committed   : isCommitted(checkoutPath),
                command     : 'wholeCommit',
                message     : $("#cwm_message").val()
            },
            beforeSend : function (){
				//remove loading animation
				$("body").removeClass("loading");
				//Auto unblock in 5 seconds				
				$.blockUI({ message:"<h3>Commit in progress...</h3>" + 
									"<h5>Please check your email for commit/deploy status</h5>" +
									"<h5>You can also check <a href=/status target=_blank rel=noopener noreferrer>WZDE Status</a> for more detail.</h5>",
							timeout:5000
				});
            },
            complete: function(jqxhr,textStatus) { 
                // unblock when remote call returns 
                $.unblockUI();
                //clear commit message box
                $("#cwm_message").val('');
            },            
            dataType : 'json'
        });
        
        //update MariaDB with committed status
        $.ajax({
            type: 'POST',
            url : "./scripts/mariadb_query.php",
            cache: false,
            data : {
                query : query,
                bindV : JSON.stringify(bindV),
            },
            beforeSend : function (){
                $.blockUI({ message: "<h3>Updating Database...</h3>" });
            },
            complete: function(jqxhr,textStatus) { 
                // unblock when remote call returns 
                $.unblockUI();                       
            },            
            //async : false,
            dataType : 'json'
        });
    }); 
 
    $('#cwm_no').unbind().click(function() { 
        $.unblockUI();
        //clear commit message box
        $("#cwm_message").val('');        
        return false; 
    });
}

function publishWholeSite(event) {
    var site = $(this).context.id,
        resource = event.data.resource,
        checkoutPath = resource + '/' + site,
		// ATWEB-7453 by WLUO - Fix site name is a substring in other site causing problem, adding site condition
        query = "select path,filename,type,'add'as ops from webutility.wzde_files where path like :dir and site = :site",
        dir = resource + '/' + site + '%',
        bindV = {"dir" : dir,"site":site};

        $.blockUI({ message: $('#publishWholeMessage'), css: { width: '600px' } });
  
        $('#pwm_yes').unbind().click(function() { 
            //commit whole site first
            $.ajax({
                type: 'POST',
                url : "./scripts/svnCommands.php",
                cache: false,
                data : {
                    cPath : checkoutPath,
                    committed   : isCommitted(checkoutPath),
                    command     : 'wholeCommit',
                    message     : $("#pwm_message").val()
                },
                beforeSend : function (){
					//Remove loading animation
					$("body").removeClass("loading");
					//Auto unblock in 5 seconds
					$.blockUI({ message:"<h3>Commit in progress...</h3>" + 
										"<h5>Please check your email for commit/deploy status</h5>" +
										"<h5>You can also check <a href=/status target=_blank rel=noopener noreferrer>WZDE Status</a> for more detail.</h5>",
								timeout:5000
					});
                },                
                dataType : 'json'
            });              
                
            //get list of file need to be published
            var queryDataJson = $.parseJSON(
                $.ajax({
                    url : "./scripts/mariadb_query.php",
                    cache: false,
                    data : {
                        query : query,
                        bindV : JSON.stringify(bindV),
                        multi : true
                    },
                    beforeSend : function (){
                        $.blockUI({ message: "<h3>Gathering files...</h3>" });
                    },                    
                    async : false,
                    dataType : 'json'
                }).responseText);
                    
            //add and push the parent folder into the queue
            var tempObj = {"path": dir.substring(0, dir.lastIndexOf('/')),
                           "filename": dir.substring(dir.lastIndexOf('/')+1,dir.length-1),
                           "type": 'directory',
                           "ops": 'add'};
            queryDataJson.push( tempObj );
                    
            //slice array into 50 chunk and queue up files
            var k,l,temparray,chunk = 50;
            for (k=0,l=queryDataJson.length; k<l; k+=chunk) {
                temparray = queryDataJson.slice(k,k+chunk);
                $.ajax({
                    type: 'POST',
                    crossDomain: true,
                    url : "https://staffappslx.meditech.com/wluo/php_Q/add_to_queue_dev.php",
                    cache: false,
                    data : {
                        filelist : JSON.stringify(temparray),
                    },
                    beforeSend : function (){
                        $.blockUI({ message: "<h3>Publish in progress...</h3>" });
                    },                    
                })
                .done(function () {
                    $.unblockUI();
                    //clear commit message box
                    $("#pwm_message").val('');
                    
                })
                .fail(function (jqxhr, textStatus, error) {
                    //do thing when fail
                    var err = textStatus + ', ' + error;
                    $.unblockUI();
                    console.log("Request Failed: " + err);
                });
            } //end for 
            
        }); 
 
        $('#pwm_no').unbind().click(function() { 
            $.unblockUI();
            //clear commit message box
            $("#pwm_message").val('');
            return false; 
        });
}

function restorePrevious(previousLink) {
    currentSite = previousLink;
    previousSite = currentSite.substr(0, currentSite.lastIndexOf("/"));
    destoryDatatable('wzdeFileTable');
    $("#wzdeFileTable tbody").replaceWith(previous[previousLink]);
    intializeDatatable('wzdeFileTable');
    $('p#wzdeFile.subheading').html('WZDE Path: [' + resource.toUpperCase() + '] ' + currentSite);
    delete previous[previousLink];
    if( previousSite ) {
        $('#showPrevious').show();
    }
    else {
        $('#showPrevious').hide();
    }  
}

//return superset of the XMLHTTPRequest object (Deferred Object DONE)
function refreshFileTable(site,resource) {
    currentSite = site;
    previousSite = currentSite.substr(0, currentSite.lastIndexOf("/"));      
    //site_access return 1 for wzde_publish access, 0 for no wzde_access
    var site_access = $.ajax({
        url : "./scripts/getSiteAccess_json.php",
        data : {
            site : site.split("/")[0]
        },
        async : false,
        dataType : 'json'
    }).responseText;
    
    var jqxhr_done = $.getJSON( "scripts/getSiteFiles_json.php",
            { folder: site,resource: resource},
            function(json){
                if ( json.length == 0 ) {
                    console.log("getSiteFiles_json return NO DATA!");
                }
            })
            .done(function( json_data ) {
                var items = [];
                $.each(json_data, function(key, value){
                    //use the hash for tr id
                    var hash =  value.hash;
                    //delete hash from associative array
                    delete value.hash;
                    
                    //skip showing if already in Queue
                    if( $("#wzdeQueueTable tr[id*='" + hash + "']").length == 0 ) {                        
                        items.push('<tr id="' + hash + '">');
                        //disable file that you do not have wzde_publish access
                        if(site_access==="1") {
                            items.push('<td><input class="queueUpClick" type="checkbox"></td>');
                        }
                        else {
                            items.push('<td><input class="queueUpClick" type="checkbox" disabled></td>');
                        }
						//display each row
                        $.each(value, function(key, val){
                            if (value.type === 'directory'){
                                if(key === 'filename'){
                                    items.push('<td class=clickable><u>' + val + '</u></td>');
                                }
                                else {
                                    items.push('<td>' + val + '</td>');
                                }
                            }
                            else {
                                items.push('<td>' + val + '</td>');
                            }
                        });
                        items.push('</tr>');
                    }
                });
                    
                //destory wzdeFileTable Table
                destoryDatatable('wzdeFileTable');
                //replace tbody                               
                previous[previousSite] = $('#wzdeFileTable tbody').clone().detach();
                $('#wzdeFileTable tbody').empty();                                
                $('#wzdeFileTable tbody').html(items.join());
                //replace heading
                $('p#wzdeFile.subheading').html('WZDE Path: [' + resource.toUpperCase() + '] ' + currentSite);                
                //reinit wzdeFileTable Table
                intializeDatatable('wzdeFileTable');
                
                if( previousSite ) {
                    $('#showPrevious').show();
                }
                else {
                    $('#showPrevious').hide();
                }
                
                //remove click handlers on up one level before reattach
                $(document).off('click','p#showPrevious.clickable u');
                $(document).on('click','p#showPrevious.clickable u', function(){
                    restorePrevious(previousSite);
                });
                
                //remove click handlers on directory before reattach
                $(document).off('click','#wzdeFileTable tbody tr td.clickable');
                //bind click on directory
                $(document).on('click','#wzdeFileTable tbody tr td.clickable', function(){
                    refreshFileTable(currentSite + "/" + $(this).text(),resource);
                });   
            });
    return jqxhr_done;
}

//return dataTable object
function intializeDatatable (tableId){
		return $('#'+tableId).dataTable({
            //default sorting on first column
			"aaSorting": [[ 4, "asc" ]],
            "bScrollCollapse" : true,
            "bLengthChange" : false,
            "bPaginate" : false,
            "sScrollY": "200",
            "autoWidth": false,
            //disable sorting on the first column
            "aoColumnDefs" : [
              {
                 bSortable: false,
                 aTargets: [ 0 ]
              }
            ],
            "fnDrawCallback": function() {
                //remove click handlers before reattach
                $('.queueUpClick').unbind('click',queueUp);
                //bind the click handler script to the elements held in the table
                $('.queueUpClick').bind('click',queueUp);
            }            
		});
}

//return nothing
function destoryDatatable (tableId){
	$(document).ready(function() {
		var oTable = $('#'+tableId).dataTable();
		oTable.fnDestroy();
	});
}

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

//encode elfinder folder/file name
function encode64(input) {
    input = escape(input);
    var output = "";
    var chr1,
    chr2,
    chr3 = "";
    var enc1,
    enc2,
    enc3,
    enc4 = "";
    var i = 0;

    do {
        chr1 = input.charCodeAt(i++);
        chr2 = input.charCodeAt(i++);
        chr3 = input.charCodeAt(i++);

        enc1 = chr1 >> 2;
        enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
        enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
        enc4 = chr3 & 63;

        if (isNaN(chr2)) {
            enc3 = enc4 = 64;
        } else if (isNaN(chr3)) {
            enc4 = 64;
        }

        output = output +
            keyStr.charAt(enc1) +
            keyStr.charAt(enc2) +
            keyStr.charAt(enc3) +
            keyStr.charAt(enc4);
        chr1 = chr2 = chr3 = "";
        enc1 = enc2 = enc3 = enc4 = "";
    } while (i < input.length);

    return output;
}

//decode elfinder folder/file name
function decode64(input) {
    var output = "";
    var chr1,
    chr2,
    chr3 = "";
    var enc1,
    enc2,
    enc3,
    enc4 = "";
    var i = 0;

    // remove all characters that are not A-Z, a-z, 0-9, +, /, or =
    var base64test = /[^A-Za-z0-9\+\/\=]/g;
    if (base64test.exec(input)) {
        alert("There were invalid base64 characters in the input text.\n" +
            "Valid base64 characters are A-Z, a-z, 0-9, '+', '/',and '='\n" +
            "Expect errors in decoding.");
    }
    input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

    do {
        enc1 = keyStr.indexOf(input.charAt(i++));
        enc2 = keyStr.indexOf(input.charAt(i++));
        enc3 = keyStr.indexOf(input.charAt(i++));
        enc4 = keyStr.indexOf(input.charAt(i++));

        chr1 = (enc1 << 2) | (enc2 >> 4);
        chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
        chr3 = ((enc3 & 3) << 6) | enc4;

        output = output + String.fromCharCode(chr1);

        if (enc3 != 64) {
            output = output + String.fromCharCode(chr2);
        }
        if (enc4 != 64) {
            output = output + String.fromCharCode(chr3);
        }

        chr1 = chr2 = chr3 = "";
        enc1 = enc2 = enc3 = enc4 = "";

    } while (i < input.length);

    return unescape(output);
}
