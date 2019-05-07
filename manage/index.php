<?php
//display error
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once 'php/helper.php';
global $WZDE;

$query = "SELECT zone,status FROM webutility.wzde_admin";
$WZDE['STATUS'] = query_db($query,[],"true",true); 

//if no status something wrong with connection to mariadb
if(!isset($WZDE['STATUS'])){
    header("location:500.php");
}

//extract status by zone
foreach ($WZDE['STATUS'] as $key=>$val) {
    $WZDE['ZONE'][$val['zone']]=$val['status'];
}

//if all is not UP or OVERRIDE (by zone) redirect to a 503 page
if ( ($WZDE['ZONE']['all']!=="UP") && ($WZDE['ZONE']['all']!=="OVERRIDE") ) {
    header("location:503.php");
}
  
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>WZDE Manage</title>



<!-- jQuery and jQuery UI (REQUIRED) -->
<link rel="stylesheet" type="text/css" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
<script src="//code.jquery.com/jquery-1.11.3.min.js"></script>
<script src="//code.jquery.com/ui/1.11.4/jquery-ui.min.js"></script>

<!-- blockUI (REQUIRED) -->
<script type="text/javascript" charset="utf8" src="//malsup.github.io/jquery.blockUI.js"></script>

<!-- HTML Hint -->
<script type="text/javascript" charset="utf8" src="js/htmlhint.js"></script>

<!-- HTML Tidy -->
<script type="text/javascript" charset="utf8" src="js/tidy.js"></script>

<!-- elFinder CSS (REQUIRED) -->
<link rel="stylesheet" type="text/css" href="css/elfinder.full.css">
<link rel="stylesheet" type="text/css" href="css/theme.css">

<!-- Main CSS -->  
<link rel="STYLESHEET" TYPE="text/css" HREF="css/main.css">

<!-- elFinder JS (REQUIRED) -->
<script src="js/elfinder.full.js"></script>

<!-- custom tools JS -->
<script src="js/customTools.js"></script>

<!-- custom command for Launch JS -->
<script src="js/customLaunch.js"></script>

<!-- custom command for Publish JS -->
<script src="js/customPublish.js"></script>

<!-- custom command for GIT JS -->
<script src="js/customGit.js"></script>

<!-- custom command for GIT Pull JS -->
<script src="js/customGitPull.js"></script>

<!-- custom command for Lint JS -->
<script src="js/customLint.js"></script>

<!-- JSZip -->
<script src="js/jszip.js"></script>

<!-- FileSaver -->
<script src="js/FileSaver.js"></script>

<!-- codemirror style (REQUIRED) -->
<!-- <link rel="stylesheet" TYPE="text/css" HREF="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.26.0/codemirror.min.css"> -->
<!-- codemirror xq-dark theme style -->
<!-- <link rel="stylesheet" TYPE="text/css" HREF="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.26.0/theme/xq-dark.min.css"> -->

<!-- codemirror JS (REQUIRED) -->
<!-- <script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.26.0/codemirror.min.js"></script> -->

<!-- codemirror programming language support-->
<!-- 
<script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.26.0/mode/javascript/javascript.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.26.0/mode/vbscript/vbscript.min.js"></script>
<script src="//cdnjs.cloudflare.com/ajax/libs/codemirror/5.22.2/mode/php/php.min.js"></script>
-->


<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="//www.googletagmanager.com/gtag/js?id=UA-22228657-9"></script>

<!-- gtag JS -->
<script src="js/ga.js"></script>

<!-- elFinder initialization (REQUIRED) -->
<script type="text/javascript" charset="utf-8">

//function for move the scroll bar to site
jQuery.fn.scrollTo = function(elem, speed) { 
    $(this).animate({
        scrollTop:  $(this).scrollTop() - $(this).offset().top + $(elem).offset().top 
    }, speed == undefined ? 1000 : speed); 
    return this; 
};

// CWT-8853 - issue #1, added function
// Check if the path is committed (workingCopy)
function isWorkingCopy (path) {
    //only take the resource and root
    var path = path.split("\\").slice(0,2).join("\\"),
		queryDataJson = $.parseJSON(
        $.ajax({
            type: 'POST',
            url : "../publish/scripts/svnCommands.php",
            data : {
                cPath : path,
                command : 'info',
            },
            async : false,
            dataType : 'json'
        }).responseText);
		
    return queryDataJson;
}

// Documentation for client options:
// https://github.com/Studio-42/elFinder/wiki/Client-configuration-options
$(document).ready(function () {
    elFinder.prototype.i18.en.messages['cmdlaunch'] = 'Launch site';
    elFinder.prototype._options.commands.push('launch');
    elFinder.prototype.i18.en.messages['cmdpublish'] = 'Go to publish';
    elFinder.prototype._options.commands.push('publish');    
    elFinder.prototype.i18.en.messages['cmdgit'] = 'Create Git Repo';
    elFinder.prototype._options.commands.push('git');
    elFinder.prototype.i18.en.messages['cmdlint'] = 'Html Lint';
    elFinder.prototype._options.commands.push('lint');
    elFinder.prototype.i18.en.messages['cmdgitpull'] = 'Git Pull';
    elFinder.prototype._options.commands.push('gitpull');		
    //elFinder.prototype.i18.en.messages['cmdqueue'] = 'Add to Publish';
    //elFinder.prototype._options.commands.push('queue');
	
    //init global temp array
    var tempG = [],
		updateSite = false;
    //init elfinder
		elf = $('#elfinder').elfinder({
			//option
			url : 'php/connector.minimal.php', // connector URL (REQUIRED)
			height : 700,
			handlers : {              
				select : function(event, instance) {
					// called on file(s) select/unselect
					var selected = event.data.selected;
					if (selected.length) {
						//console.log(selected);
					}
				},
				get : function (event,instance) {
					// on get
					//console.log('get event ', event, instance);									
				},
				contextmenu : function (event,instance) {
					// on contextmenu
					//console.log('contextmenu event ', event, instance);
				},
				hover : function (event,instance) {
					// on hover
					//console.log('hover event ', event, instance);
				},				
				change : function (event,instance) {
					// on change
					//console.log('change event ', event, instance);
					
					// append the new event into the global temp array
					tempG.push(event);                    
					// This is needed because the change event fired twice but we only want to update once
					// Only update on the last (second) change event
					if (typeof tempG !== 'undefined' && tempG.length > 1) {                      
						var modifiedDate = new Date(instance.file(event.data.changed[0].hash).ts * 1000),
							modifiedDate = modifiedDate.toISO8601String(5),
							hash = event.data.changed[0].hash,
							query = "select filename from webutility.wzde_files where hash=:hash",                                                    
							bindV = {'hash' : hash};
						// make sure file exists in mariadb table
						var isExistsJson = $.parseJSON(
							$.ajax({
								url : "./php/mariadb_query.php",
								data : {
									query : query,
									bindV : JSON.stringify(bindV),
									multi : false
								},
								async : false,
								dataType : 'json'
							}).responseText);   
						// update file status to DRAFT on edit    						
						if (isExistsJson) {
							// ATWEB-7614 by WLUO - Fix last modified date/time doesn't update if file is edited/uploaded in WZDE Manage, add update creationdate
							query = "update webutility.wzde_files set status=:status, creationdate=:creationdate where hash=:hash";
							bindV = {
								'hash' : hash,
								'status' : "draft",
								'creationdate' : modifiedDate
							};                                                        
							$.ajax({
								type: "POST",
								url : "./php/mariadb_query.php",
								data : {
									query : query,
									bindV : JSON.stringify(bindV)
								},
								//async : false,
								dataType : 'json'
							});
						}
						tempG = [];
					}                    
				},                 
				open : function (event) {
					// on open
					//console.log('open event ',event);
				},
				paste : function (event) {
					// on paste
					//console.log('paste event ',event);
				},
				changeclipboard : function (event) {
					// on changeclipboard
					//console.log('changeclipboard event ',event);
				},                  
				add : function (event, instance) {
					// called when file(s) added (remove,select,add,select)
					var added = event.data.added;
					//console.log('add event ', event, instance);                    
					if (added.length) {
						//GET idsites from sitename
						var site = event.data.added[0].phash,                     
							zone = site.substring(0, site.indexOf('_')) + "_XA",
							zone = instance.file(zone).name,                                             
							site = decode64(site.substring(site.indexOf("_") + 1)),
							site = site.substring(0, site.indexOf('\\')<0 ? site.length : site.indexOf('\\'));
						
						
						if (site !== "") {
							var query = "SELECT idsites FROM webutility.sites where sitename = :sitename";
							var bindV = {"sitename" : site};
							var queryDataJson = $.parseJSON(
									$.ajax({
										url : "./php/mariadb_query.php",
										data : {
											query : query,
											bindV : JSON.stringify(bindV),
											multi : false
										},
										async : false,
										dataType : 'json'
									}).responseText);                                    
							
							//POST file(s) into table
							for (i in added) {
								var date = new Date(instance.file(added[i].hash).ts * 1000),
									file = event.data.added[i].name,
									type = event.data.added[i].mime,
									hash = event.data.added[i].hash,
									//decode path from hash
									path = decode64(hash.substring(hash.indexOf("_") + 1));
									//strip filename for path
									path = zone.toLowerCase() + "\\" + path.substring(0, path.lastIndexOf("\\"));
									//convert timestamp to current time (GMT)
									date = date.toISO8601String(5);

								var query = "select filename from webutility.wzde_files where hash=:hash";                                                    
								var bindV = {'hash' : hash};       
								var isUploadJson = $.parseJSON(
										$.ajax({
											url : "./php/mariadb_query.php",
											data : {
												query : query,
												bindV : JSON.stringify(bindV),
												multi : false
											},
											async : false,
											dataType : 'json'
										}).responseText);   
								// Do update table if it is a upload of a existent file   
								if (isUploadJson) {
									query = "update webutility.wzde_files set status=:status where hash=:hash";
									bindV = {
										'hash' : hash,
										'status' : "draft"
									};                                
								}
								// Do insert table if it is a new upload
								else {
									query = "INSERT into webutility.wzde_files (idsites,zone,site,path,filename,type,hash,status,creationdate,publishdate) VALUES (:idsites,:zone,:site,:path,:filename,:type,:hash,:status,:creationdate,:publishdate)";                            
									bindV = {
										'idsites' : queryDataJson.idsites,
										'zone' : zone.toLowerCase(),
										'site' : site,
										//replace backslash with forward slash
										'path' : path.replace(/\\/g,"/"),
										'filename' : file,
										'type' : type,
										'hash' : hash,
										'status' : "draft",
										'creationdate' : date,
										'publishdate' : null
									};
								}
								$.ajax({
									type: "POST",
									url : "./php/mariadb_query.php",
									data : {
										query : query,
										bindV : JSON.stringify(bindV)
									},
									//async : false,
									dataType : 'json'
								});                                                        
							}
						}
					}
				},
				dblclick : function (event, instance) {
					event.preventDefault();
					instance.exec('getfile')
					.done(function () {
						instance.exec('quicklook');
					})
					.fail(function () {
						instance.exec('open');
					});
				},
				upload : function (event, instance) {				
					// ATWEB-7614 by WLUO - Fix last modified date/time doesn't update if file is edited/uploaded in WZDE Manage, add update creationdate
					var modifiedDate = new Date(instance.file(event.data.changed[0].hash).ts * 1000),
						modifiedDate = modifiedDate.toISO8601String(5),
						uploadedFiles = event.data.added,
						rootMap = {};					
					// update each upload file and change staus to draft
					$.each(uploadedFiles, function (key, value) {																
						var hash = value.hash,
							query = "select filename from webutility.wzde_files where hash=:hash",                                                    
							bindV = {'hash' : hash},						
							// make sure file already added from the add event and already exists in mariadb table
							isExistsJson = $.parseJSON(
								$.ajax({
									url : "./php/mariadb_query.php",
									data : {
										query : query,
										bindV : JSON.stringify(bindV),
										multi : false
									},
									async : false,
									dataType : 'json'
								}).responseText);											
						// update file status to draft and update modified time       						
						if (isExistsJson) {
							// ATWEB-7614 by WLUO - Fix last modified date/time doesn't update if file is edited/uploaded in WZDE Manage, add update creationdate
							query = "update webutility.wzde_files set status=:status, creationdate=:creationdate where hash=:hash";
							bindV = {
								'hash' : hash,
								'status' : "draft",
								'creationdate' : modifiedDate
							};                                                        
							$.ajax({
								type: "POST",
								url : "./php/mariadb_query.php",
								data : {
									query : query,
									bindV : JSON.stringify(bindV)
								},
								//async : false,
								dataType : 'json'
							});
						}
					});
					
					// CWT-8853 - issue #1
					// Do a SVN UPDATE on the site level to make sure we have all the latest code from repo
					// Make sure we only do this once because upload event will be called multiple times
					if (!updateSite) {
						updateSite = true;						
						var site = uploadedFiles[0].phash,                     
							zone = site.substring(0, site.indexOf('_')) + "_XA",
							zone = instance.file(zone).name,                                             
							site = decode64(site.substring(site.indexOf("_") + 1)),
							site = site.substring(0, site.indexOf('\\')<0 ? site.length : site.indexOf('\\')),
							cPath= zone.toLowerCase() + "\\" + site;
						// only do SVN UPDATE when the site is already a working copy (already committed)
						if (isWorkingCopy(cPath) === 1) {																
							var querUpdateJson = $.parseJSON(
								$.ajax({
									type: 'POST',
									url : "../publish/scripts/svnCommands.php",
									data : {
										cPath : cPath,
										command : 'update',
									},
									async: false,
									beforeSend : function (){
										$.blockUI({ message: "<h3>SVN Update...</h3>" });
									},
									success: function(data) {
										if (data !='1') {
											if (!errorData) {
												errorData = data.join("<br>");
											}
											$.blockUI({ message: "<h3>ERROR: </h3>" +
																 "<h5>" + errorData + "</h5>" +
																 "<3>There is problem running SVN UPDATE.</h3>" +
																 "<h3>Please fix and try again.</h3>",css: { width: '600px' }  });
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
						}
					}					
				},
				extract : function (event, instance) {
					//console.log('extract ' + event.data);
				},
				remove : function (event, instance) {			
					// called when file(s) removed (select,remove,select)                    
					var removed = event.data.removed,
						added = event.data.added,
						add_mapG = {},
						propagate_arrFolder = [],
						propagate_arrFile = [],
						propagate_folderContent = [],
						propagate_fileContent = [],
						propagate_folderContent_test = [],
						propagate_fileContent_test = [],						
						propagate = false,				
						site = event.data.changed[0].hash,   
						zone = instance.file(site.substring(0, site.indexOf('_')) + "_XA").name.toLowerCase(),                                             
						site = decode64(site.substring(site.indexOf("_") + 1)).replace(/\0[\s\S]*$/g,'');
						site = site.substring(0, site.indexOf('\\')<0 ? site.length : site.indexOf('\\'));		
					
					// Run svn delete to update all the deleted files (nothing committed yet)
					var jqxhr_done = $.getJSON( "php/svnDeleteUpdate.php",{ site: site,resource: zone},
						function(json){
							if ( json.length == 0 ) {
								console.log("svnDeleteUpdate return NO DATA!");
							}
					}).done(function( json_data ) {
						//console.log(json_data);
					});
					

					// add each hash into the hash map
					if (typeof added !== 'undefined' && added.length > 0) {					
						$.each( added, function( key, value ) {					
							add_mapG[value.hash] = 1;
						});
					}
					// filter removed array for duplicate.
					var uniqueRemoved = [];
					$.each(removed, function(i, el){
						if($.inArray(el, uniqueRemoved) === -1) uniqueRemoved.push(el);
					});
					// use only unique removed hash
					if (uniqueRemoved.length) {												
						// POST and delete file(s) from mariaDB table
						for (i in uniqueRemoved) {		
							// If we have added and removed and the hash is the same then it is a upload not a real delete skip the delete.
							// If we have added and removed and the hash is different then we still want to delete the old and insert the new.
							if (typeof event.data.added !== 'undefined' && event.data.added.length > 0 && array_search(uniqueRemoved[i],add_mapG)) {
								// do something for upload
							}
							// Only delete from table if it is a solo remove action (real delete)
							// deleted file from mariaDB (including all subfolders and files)   
							else {
								var path = zone.toLowerCase() + "\\" + decode64(uniqueRemoved[i].substring(uniqueRemoved[i].indexOf('_')+1)).replace(/\0[\s\S]*$/g,''),
									query = "select type from webutility.wzde_files where hash=:hash",
									bindV = {'hash' : uniqueRemoved[i]},									
									// ajax call to see if a path is directory
									isDirectoryJson = $.parseJSON(
										$.ajax({
											url : "./php/mariadb_query.php",
											data : {
												query : query,
												bindV : JSON.stringify(bindV),
												multi : false
											},
											async : false,
											dataType : 'json'
										}).responseText);  								
								// only set propagate when it is not already true
								if (!propagate) {
									propagate = true;
								}
								// If directory delete all sub-folder and files
								if (isDirectoryJson.type === 'directory') {							
									// push directory that need to do delete propagation
									propagate_arrFolder.push(path);									
									// get deleted folder content (including sub file and sub folder) for publish location
									propagate_folderContent.push(getDeletedFolderContent(path.replace(/\\/g,"/"),true));
									// get deleted folder content (including sub file and sub folder) for test location
									propagate_folderContent_test.push(getDeletedFolderContent(path.replace(/\\/g,"/"),false));
									
									// delete subfolder/files from mysql table
									var fuzzyPath = path + "/%";
									query = "delete from webutility.wzde_files where path = :path or path like :fuzzyPath";									
									bindV = {'path' : path.replace(/\\/g,"/"), 'fuzzyPath' : fuzzyPath.replace(/\\/g,"/")};							
									$.ajax({
										type: "POST",
										url : "./php/mariadb_query.php",
										data : {
											query : query,
											bindV : JSON.stringify(bindV)
										},
										async : false,
										dataType : 'json'
									});
									
									// delete parent folder from mysql table											
									query = "delete from webutility.wzde_files where hash=:hash"; 
									bindV = {'hash' : uniqueRemoved[i]};																		
									$.ajax({
										type: "POST",
										url : "./php/mariadb_query.php",
										data : {
											query : query,
											bindV : JSON.stringify(bindV)
										},
										dataType : 'json'
									});									
								}
								// else just delete the file
								else {
									query = "delete from webutility.wzde_files where hash=:hash"; 
									bindV = {'hash' : uniqueRemoved[i]};
									
									// push file that need to do delete propagation 
									propagate_arrFile.push(path);
									// get deleted file content for publish location
									propagate_fileContent.push(getFileDetailByHash(uniqueRemoved[i],true));
									// get deleted file content for test location
									propagate_fileContent_test.push(getFileDetailByHash(uniqueRemoved[i],false));									
									
									$.ajax({
										type: "POST",
										url : "./php/mariadb_query.php",
										data : {
											query : query,
											bindV : JSON.stringify(bindV)
										},
										dataType : 'json'
									});									
								}                    															
							}                            
						}// end for
						
						
						// propagate delete to repo
						if (propagate) {
							// check user access
							var userAccess = $.parseJSON(
								$.ajax({
									url : "./php/getUserAccess.php",
									data : {
										site : site
									},
									async : false,
									dataType : 'json'
								}).responseText),
							// get user commit access
							commitAccess = (userAccess.master || userAccess.commit) ? 1 : 0,
							// get user publish access
							publishAccess = (userAccess.master || userAccess.publish) ? 1 : 0;							
							// hide live checkbox if user has no publish access
							if (!publishAccess) {
								$("#propagate_live").hide();
							}
							// only user with commit access can propagate delete to repo and test
							if (commitAccess) {
								var errorData = '';
								
								$.blockUI({ message: $('#propagateQuestion'), css: { width: '600px' } });
								// do nothing if answer Yes
								$('#propagate_yes').unbind().click(function() {
									var xhrs = [];
									
									// For each folder commit the delete to repo
									$.each(propagate_arrFolder, function (key, value) {
										// ajax for doing repo delete propagation
										var xhr = $.ajax({
											type: 'POST',
											url : "./php/svnDeleteCommit.php",
											data : {
												commitPath : value
											},
											async: false,
											beforeSend : function (){
												$.blockUI({ message: "<h3>Propagate deleted folder ...</h3>" });
											},
											success: function(data) {
												// if not successful get errordata
												if (data !='1') {
													if (!errorData) {
														errorData = data.join("<br>");
													}								
													$.blockUI({ message: "<h3>ERROR: </h3>" +
																		 "<h5>" + errorData + "</h5>" +
																		 "<h3>There is problem committing deleted folder to the subversion repository.</h3>" +
																		 "<h3>Propagation may not completed, Please contact WZDE admin.</h3>",css: { width: '600px' }  });
													// click outside message box to dismiss 
													$('.blockOverlay').unbind().click($.unblockUI);
													return false;													
												}  
											},
											dataType : 'json'
										});
										// push each ajax call into xhrs 
										xhrs.push(xhr);
									}); // end .each
									
									// For each file commit the delete to repo
									$.each(propagate_arrFile, function (key, value) {
										// ajax for doing repo delete propagation
										var xhr = $.ajax({
											type: 'POST',
											url : "./php/svnDeleteCommit.php",
											data : {
												commitPath : value
											},
											async: false,
											beforeSend : function (){
												$.blockUI({ message: "<h3>Propagate deleted file ...</h3>" });
											},
											success: function(data) {
												// if not successful get errordata
												if (data !='1') {
													if (!errorData) {
														errorData = data.join("<br>");
													}								
													$.blockUI({ message: "<h3>ERROR: </h3>" +
																		 "<h5>" + errorData + "</h5>" +
																		 "<h3>There is problem committing deleted file to the subversion repository.</h3>" +
																		 "<h3>Propagation may not completed, Please contact WZDE admin.</h3>",css: { width: '600px' }  });
													// click outside message box to dismiss 
													$('.blockOverlay').unbind().click($.unblockUI);
													return false;													
												}
											},
											dataType : 'json'
										});
										// push each ajax call into xhrs 
										xhrs.push(xhr);
									}); // end .each									
									
									// When all svn delete propagation are successful									
									$.when.apply($, xhrs).done(function() {
										
										// propagation to test
										// delete folder
										if (propagate_folderContent_test !== undefined && propagate_folderContent_test.length !== 0) {
											// use a flatten array
											deleteDir([].concat.apply([], propagate_folderContent_test));
										}											
										// delete file
										if (propagate_fileContent_test !== undefined && propagate_fileContent_test.length !== 0) {
											// flatten array 
											propagate_fileContent_test = [].concat.apply([], propagate_fileContent_test);										
											$.each(propagate_fileContent_test, function (key, value) {
												// put each object into a single element array
												deleteFile([value]);												
											});												
										}										

										// propagation to live (if selected)
										if (document.getElementById("propagateLiveCheckbox").checked) {
										
											// delete folder
											if (propagate_folderContent !== undefined && propagate_folderContent.length !== 0) {
												// use a flatten array
												deleteDir([].concat.apply([], propagate_folderContent));
											}											
											// delete file
											if (propagate_fileContent !== undefined && propagate_fileContent.length !== 0) {
												// flatten array 
												propagate_fileContent = [].concat.apply([], propagate_fileContent);										
												$.each(propagate_fileContent, function (key, value) {
													// put each object into a single element array
													deleteFile([value]);												
												});												
											}											
										}
										
										$.unblockUI();
									});
								});
								// do nothing if answer No
								$('#propagate_no').unbind().click(function() { 
									$.unblockUI();         
									return false; 
								});
							}							
						}
					}//end if                     
				}
			},// end Handlers
			commandsOptions: {
                quicklook : {
                    width : 640,
                    height : 480
                },				
				edit: {
					editors : [
						{
							// CodeMirror
							// mimes is not set for support everything kind of text file
							info : { name: 'Edit file' },
							dialogWidth: 920,
							mimes: ['text/html','text/javascript','text/css','text/x-php','text/plain','application/x-config','text/x-vb','text/x-cs','text/x-ascx','text/x-aspx','text/x-asp'], //types to edit
							load : function(textarea) {
								// ATWEB-8509 by WLUO - Do SVN UPDATE before edit file in-place
								var hash = this.file.hash,
									zone = this.fm.file(hash.substring(0, hash.indexOf('_')) + "_XA").name,
									checkoutPath = zone.toLowerCase() + "\\" + decode64(hash.substring(hash.indexOf('_')+1)).replace(/\0[\s\S]*$/g,'');
									// CWT-8853 - issue #1
									// Check the root level to see if it is already a workingcopy (committed)
									// Only do SVN update if the root is a workingCopy
									if (isWorkingCopy(checkoutPath) === 1) {
									// call SVN UPDATE before the file being edited
										var postSvnUpdateJson = $.parseJSON(
											$.ajax({
												type: 'POST',
												url : "../publish/scripts/svnCommands.php",
												data : {
													cPath : checkoutPath,
													command : 'update',
												},
												async: false,
												beforeSend : function (){
													$.blockUI({ message: "<h3>SVN Update...</h3>" });
												},
												success: function(data) {
													if (data !='1') {
														if (!errorData) {
															errorData = data.join("<br>");
														}
														$.blockUI({ message: "<h3>ERROR: </h3>" +
																			 "<h5>" + errorData + "</h5>" +
																			 "<3>There is problem running SVN UPDATE.</h3>" +
																			 "<h3>Please fix and try again.</h3>",css: { width: '600px' }  });
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
									}
								// set up codemirror
								var cmUrl = '//cdnjs.cloudflare.com/ajax/libs/codemirror/5.26.0/',
									dfrd = $.Deferred(),
									self = this,
									init = function() {
										var ta   = $(textarea),
											base = ta.parent(),
											editorBase;
										
										// set base height
										base.height(base.height());
										
										// CodeMirror configure
										editor = CodeMirror.fromTextArea(textarea, {
											lineNumbers: true,
											lineWrapping: true,
											indentUnit: 4,
											theme: "xq-dark"
										});
										
										// return editor instance
										dfrd.resolve(editor);
										
										// Auto mode set
										var info, m, mode, spec;
										if (! info) {
											info = CodeMirror.findModeByMIME(self.file.mime);
										}
										if (! info && (m = self.file.name.match(/.+\.([^.]+)$/))) {
											info = CodeMirror.findModeByExtension(m[1]);
										}
										if (info) {
											CodeMirror.modeURL = cmUrl + 'mode/%N/%N.js';
											mode = info.mode;
											spec = info.mime;
											editor.setOption('mode', spec);
											CodeMirror.autoLoadMode(editor, mode);
											// show MIME:mode in title bar
											base.prev().children('.elfinder-dialog-title').append(' (' + spec + ' : ' + mode + ')');
										}
										
										// editor base node
										editorBase = $(editor.getWrapperElement());
										ta.data('cm', true);
										
										// fit height to base
										editorBase.height('100%');
										
										// TextArea button and Setting button
										$('<div class="ui-dialog-buttonset"/>').css('float', 'left')
										.append(
											$('<button>TextArea</button>')
											.button()
											.on('click', function(){
												if (ta.data('cm')) {
													ta.removeData('cm');
													editorBase.hide();
													ta.val(editor.getValue()).show().focus();
													$(this).text('CodeMirror');
												} else {
													ta.data('cm', true);
													editorBase.show();
													editor.setValue(ta.hide().val());
													editor.refresh();
													editor.focus();
													$(this).text('TextArea');
												}
											})
										)
										.prependTo(base.next());
									};
								// load script then init
								if (typeof CodeMirror === 'undefined') {
									this.fm.loadScript([
										cmUrl + 'codemirror.min.js',
										cmUrl + 'addon/mode/loadmode.js',
										cmUrl + 'mode/meta.js',
										//js programming language support
										cmUrl + 'mode/javascript/javascript.min.js',
										//vbs programming language support
										cmUrl + 'mode/vbscript/vbscript.min.js'										
									], init);
									this.fm.loadCss(cmUrl + 'codemirror.min.css');
									this.fm.loadCss(cmUrl + 'theme/xq-dark.min.css');
								} else {
									init();
								}
								return dfrd;
							},
							close : function(textarea, instance) {
								instance && instance.toTextArea();
							},
							save : function(textarea, instance) {
								instance && $(textarea).data('cm') && (textarea.value = instance.getValue());
							},
							focus : function(textarea, instance) {
								instance && $(textarea).data('cm') && instance.focus();
							},
							resize : function(textarea, instance, e, data) {
								instance && instance.refresh();
							}
						}						
					]
				}
			},			
			getFileCallback : function (files, fm) {
				return false;
			},
			uiOptions : {
				// toolbar configuration
				toolbar : [
					['back', 'forward'],
					//['reload'],
					//['home', 'up'],
					['mkdir', 'mkfile', 'upload'],
					['open', 'download', 'getfile'],
					['info'],
					//['quicklook', 'launch','queue'],
					['quicklook', 'launch'],
					['copy', 'cut', 'paste'],
					['rm'],
					['duplicate', 'rename', 'edit', 'resize'],
					['extract', 'archive'],
					['search'],
					['view'],
					['help']
				],

				// directories tree options
				tree : {
					// expand current root on init
					openRootOnLoad : true,
					// auto load current dir parents
					syncTree : true
				},

				// navbar options
				navbar : {
					minWidth : 150,
					maxWidth : 500
				},

				// current working directory options
				cwd : {
					// display parent directory in listing as ".."
					oldSchool : false
				}
			},
			commands : [
				'open', 'reload', 'home', 'up', 'back', 'forward', 'getfile', 'quicklook',
				'download', 'rm', 'duplicate', 'rename', 'mkdir', 'mkfile', 'upload', 'copy',
				'cut', 'paste', 'edit', 'extract', 'archive', 'search', 'info', 'view', 'help',
				//'resize', 'sort', 'launch','queue'
				'resize', 'sort', 'launch','publish','git','gitpull','lint'
			],
			contextmenu : {
				// navbarfolder menu (rigt click any item on left panel)
				navbar : ['open', '|', 'copy', 'cut', 'paste', 'duplicate', '|', 'rm', '|', 'info', 'launch','publish','git','gitpull'],
				// current directory menu (right click empty space on right panel)
				cwd : ['reload', 'back', '|', 'upload', 'mkdir', 'mkfile', 'paste', '|', 'info'],
				// current directory file menu (right click any item on right panel)
				files : [
					'getfile', '|', 'open', 'quicklook', '|', 'download', '|', 'copy', 'cut', 'paste', 'duplicate', '|',
					//'rm', '|', 'edit', 'rename', 'resize', '|', 'archive', 'extract', '|', 'info','launch','queue'
					'rm', '|', 'edit', 'rename', 'resize', '|', 'archive', 'extract', '|', 'info','launch','lint'
				]
			}
		}).elfinder('instance');
		
		
	var uploadFiles = [];
	
	elf.bind('upload', function(e) {
		if (e.data && e.data.added && e.data.added.length) {
			var ntfNode = elf.getUI('notify');
			uploadFiles = uploadFiles.concat(e.data.added);
			setTimeout(function() {
				var hasDialog = ntfNode.children('.elfinder-notify-upload').length? true : false;
				var cnt;
				if (! hasDialog && (cnt = uploadFiles.length)) {
					uploadFiles = [];
					updateSite = false ;
					//alert(cnt + ' item are added.');
				}
			}, 100);
		}
	});		
		
});

// search hash map 
// in : 0=item to be searched 1=hash map to be searched on
// out: false = found, true = found
function array_search(needle,add_mapG) {
	var toReturn = false; 
	// if found removed hash in added, remove it from the add_mapG and return true
	if (needle in add_mapG) {
		delete add_mapG[needle];
		toReturn = true;
	}
	return toReturn; 
}


// ATWEB-8410 by WLUO 8/15/18 
// function to get all file detail by directory
// in : dir = file directory value in mysql table, ispublish = boolean true or false
// out: files detail in json (including sub-directory and sub-files)
function getDeletedFolderContent(dir,ispublish) {
	var fuzzyDir = dir + "/%",
		query_publish = "select path,filename,type,'del'as ops from webutility.wzde_files where path = :dir or path like :fuzzyDir ORDER BY FIELD(type, 'directory')",
		query_test = "select path,filename,type,'del_test'as ops from webutility.wzde_files where path = :dir or path like :fuzzyDir ORDER BY FIELD(type, 'directory')",
		query = (ispublish) ? query_publish : query_test,
		ops = (ispublish) ? 'del' : 'del_test';
		
	var bindV = {"dir" : dir, "fuzzyDir" : fuzzyDir},
		queryDataJson = $.parseJSON(
            $.ajax({
                url : "./php/mariadb_query.php",
                data : {
                    query : query,
                    bindV : JSON.stringify(bindV),
                    multi : true
                },
                async : false,
                dataType : 'json'
            }).responseText),
		// add and push the parent folder into the queue
		tempObj = {"path": dir.substring(0, dir.lastIndexOf('/')),
                   "filename": dir.substring(dir.lastIndexOf('/')+1,dir.length),
                   "type": 'directory',
                   "ops": ops};
    queryDataJson.push( tempObj );
	
	return 	queryDataJson;
}

// ATWEB-8410 by WLUO 8/15/18 
// function to get single file detail by hash
// in : hash=file hash value in mysql table, ispublish = boolean true or false
// out: file detail in json  
function getFileDetailByHash(hash, ispublish) {
	
	var query_publish = "select path,filename,type,'del'as ops from webutility.wzde_files where hash = :hash",
		query_test = "select path,filename,type,'del_test'as ops from webutility.wzde_files where hash = :hash",
		query = (ispublish) ? query_publish : query_test;
		
	var bindV = {"hash" : hash},
		// get single file path
		queryDataJson = $.parseJSON(
		$.ajax({
			url : "./php/mariadb_query.php",
			data : {
				query : query,
				bindV : JSON.stringify(bindV),
				multi : true
			},
			async : false,
			dataType : 'json'
		}).responseText);
		
	return 	queryDataJson;
}

// ATWEB-8410 by WLUO 8/15/18 
// function to add deleted directory to publish queue
// in : files detail in json
// out: nothing
function deleteDir (queryDataJson) {
	// variable
	var port = location.port,
		url = "";	
	// determine dev or live url
	// if port has value then we are not in live	
	if (port) {
		url = "https://staffappslx.meditech.com/wluo/php_Q/add_to_queue_dev.php";
	}
	// if port is null then we are at live	
	else {
		url = "https://staffappslx.meditech.com/wluo/php_Q/add_to_queue.php";
	}
    // slice array into 50 chunk and queue up files
    var k,l,temparray,chunk = 50;
    for (k=0,l=queryDataJson.length; k<l; k+=chunk) {
        temparray = queryDataJson.slice(k,k+chunk);
		// ajax POST to add the file to queue to delete
        $.ajax({
            type: 'POST',
            crossDomain: true,
            url : url,
            data : {
                filelist : JSON.stringify(temparray)
            }
        })
        .done(function () {
            //alert('added');
        })
        .fail(function (jqxhr, textStatus, error) {
            // do thing when fail
            var err = textStatus + ', ' + error;
            console.log("Request Failed: " + err);
        });    
	} // end FOR
}

// ATWEB-8410 by WLUO 8/15/18 
// function to add deleted file to publish queue
// in : files detail in json
// out: nothing
function deleteFile(queryDataJson) {
	// variable
	var port = location.port,
		url = "";	
	// determine dev or live url
	// if port has value then we are not in live
	if (port) {
		url = "https://staffappslx.meditech.com/wluo/php_Q/add_to_queue_dev.php";
	}
	// if port is null then we are at live
	else {
		url = "https://staffappslx.meditech.com/wluo/php_Q/add_to_queue.php";
	}	
	// ajax POST to add the file to queue to delete
    $.ajax({
        type: 'POST',
        crossDomain: true,
        url : url,
        data : {
            filelist : JSON.stringify(queryDataJson)
        }
    })
    .done(function () {
        //alert('added');
    })
    .fail(function (jqxhr, textStatus, error) {
        // do thing when fail
        var err = textStatus + ', ' + error;
        console.log("Request Failed: " + err);
    });                
}   

// called after ready() and we want to set the timer to 1 second so the elfinder nodes can be rendered
window.addEventListener('load', function() {
    // scroll to the current directory by using the #fragment (replace #elf_ with #nav-)
    setTimeout("$('div.elfinder-navbar').scrollTo(window.location.hash.replace('#elf_', '#nav-'),1000);", 1000);
}, false);

</script>
</head>
<body>

<!-- Header Element -->
<div class="title_area">
    <table class="title_area"><tr>
    <td class="title"><p class="title">WZDE Manage</p></td>
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

<!-- Element where elFinder will be created (REQUIRED) -->
<div id="elfinder"></div>

<!-- BlockUI git create popup -->
<div id="gitQuestion" style="display:none; cursor: default"> 
        <h2>This will create Git Repo for this site</h2>
        <p>All current content will be committed and pushed to BitBucket</p>
        <p>Do you want to continue ?</p> 
        <hr class="blockMsg-hr">
        <input type="button" id="git_yes" value="OK" /> 
        <input type="button" id="git_no" value="Cancel" /> 
</div> 

<!-- BlockUI git pull popup -->
<div id="gitPullQuestion" style="display:none; cursor: default"> 
        <h2>This will pull all changes from remote origin/master branch</h2>
        <p>It may overwrite  some or all content for this site</p>
        <p>Do you want to continue ?</p>
        <hr class="blockMsg-hr"> 
        <input type="button" id="gitP_yes" value="OK" /> 
        <input type="button" id="gitP_no" value="Cancel" /> 
</div> 

<!-- BlockUI HTML Lint popup -->
<div id="lintQuestion" style="display:none; cursor: default"> 
</div> 

<!-- jquery modal confirmation -->
<div id="lintFolderDialog" title="Checking folder..." style="display:none; cursor: default">
  <p>
    <span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>
    This will check all html file within the folder.
  </p>
  <p>
    It may take long time to complete. 
  </p>
</div>

<!-- BlockUI svn delete popup -->
<div id="propagateQuestion" style="display:none; cursor: default"> 
        <h2>File/folder has been deleted from WZDE </h2>
        <p>Do you also want to propagate your deletion ?</p>
		<div id="propagate_live">
			<input type="checkbox" id="propagateLiveCheckbox" name="plive" value="live">
			<label for="propagateLiveCheckbox">Including Live ?</label>
		</div>
        <hr class="blockMsg-hr">
        <input type="button" id="propagate_yes" value="Yes" /> 
        <input type="button" id="propagate_no" value="No" /> 
</div> 

<!-- Footer Element -->
<div class="footer">
<p class="copyright">Version 1.0<br></p>
</div>
</body>
</html>
