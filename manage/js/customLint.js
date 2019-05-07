"use strict"
var fileList = [];

elFinder.prototype.commands.lint = function() {
    this.exec = function(hashes) {
        //do whatever        
        var fm    = this.fm, 
        dfrd  = $.Deferred().fail(function(error) { error && fm.error(error); }),
        files   = this.files(hashes),
        cnt   = files.length,
        file, url, arr;
		
        if (!cnt) {
            return dfrd.reject();
        }
		// if lint on file
        if (cnt == 1 && (file = files[0]) && file.mime == 'text/html') {           
            url = window.location.href;
			getLint(file,'file');
        }
		// if lint on folder
		else if (cnt == 1 && (file = files[0]) && file.mime == 'directory') { 
			fileList = [];
			var fileArr = recursiveGetAllFiles(file.hash);
			
			var resultDownloadArr = {},
				fixedFileArr = [],
				fileFolder = file.name;
			
			if (fileArr.length > 20){
				// alert("This will check all html file within the folder. It may take long time to complete.");
				// $( function() {
					// $( "#lintFolderDialog" ).dialog({
							// modal: true,
								// buttons: {
								// Ok: function() {
									// $( this ).dialog( "close" );
							// }
						// }
					// });
				// });			
			}
			
			$.each( fileArr, function( key, value ) {
				var fileHash = value.hash,
					fileResultObject = [];
				
				if (value.mime == 'text/html') {

					$.ajax({
						type: 'GET',
						url : "php/connector.minimal.php",
						data : {
							cmd : 'file',
							target : fileHash
						},					
						async: false
					})						
					.done(function (data) {
						var opts = {
							doctype: 'auto',				
							indent: 'yes',
							quiet: 'yes',
							markup: 'yes',
							"show-info": false					
						};					
						var result = HTMLHint.verify(data);

						// push fixed file into result array
						fixedFileArr.push({data:tidy_html5(data,opts),file:value.name,hash:value.hash});
						
						$.each( result, function( key, value ) {					
							// setup resultDownload							
							fileResultObject[key] = value;							
							fileResultObject[key].id = value.rule.id;
							fileResultObject[key].description = value.rule.description;
							fileResultObject[key].link = value.rule.link;
							fileResultObject[key].file = decode64(fileHash.split("_")[1]);
							delete fileResultObject[key].rule;
						});
						
						if (fileResultObject !== undefined && fileResultObject.length > 0) {
							resultDownloadArr[fileHash] = fileResultObject;
						}						
					})
					.fail(function (jqxhr, textStatus, error) {
						// do something when fail
						var err = textStatus + ', ' + error;
						console.log("Request Failed: " + err);
					}); 									
				}
			});
			
			var finalDownloadArr =[];
			$.each( resultDownloadArr, function( key, value ) {		
				finalDownloadArr = finalDownloadArr.concat(value);								
			});				
			
			if(!isEmpty(resultDownloadArr)) {			
				$.blockUI.defaults.message  = "<h2>HTML Lint result for " + fileFolder + "</h2>";								        
				$.blockUI.defaults.message += "<p>Download revised files or Download lint results in CSV ?</p>" ;
				$.blockUI.defaults.message += "<hr class='blockMsg-hr'>";
				if (fixedFileArr.length > 0){
					$.blockUI.defaults.message += "<input type='button' id='lintF_yes' value='Download Revised' /> ";
				}
				$.blockUI.defaults.message += "<input type='button' id='lintD_yes' value='Download Results' /> ";
				$.blockUI.defaults.message += "<input type='button' id='lintD_no' value='Close' />";
				// style blockUI center
				$.blockUI({css: {	width: 700 + 'px',
									top: '50%',
									left: '50%',
									margin: (-400 / 2) + 'px 0 0 '+ (-700/2) + 'px'
				}});
				// download lint result and unbind blockUI
				$('#lintD_yes').unbind().click(function() {						
					if (navigator.appName != 'Microsoft Internet Explorer') {				
						JSONToCSVConvertor(finalDownloadArr,fileFolder,true);
						
					}						
					else {
						var popup = window.open('','csv','');							
						popup.document.body.innerHTML = '<pre>' + finalDownloadArr + '</pre>';
					}
					$.unblockUI(); 						
					return false;											
				});
				// just unbind blockUI
				$('#lintD_no').unbind().click(function() { 
					$.unblockUI(); 
					return false; 
				});
				// download fixed code
				$('#lintF_yes').unbind().click(function() {
					var zip = new JSZip();
					if (fixedFileArr.length > 0){
						$.each( fixedFileArr, function( key, value ) {
							var filePath = decode64(value.hash.split("_")[1]);
							zip.file(filePath, value.data);
						})
						zip.generateAsync({type:"blob"})
						.then(function(content) {
							saveAs(content, fileFolder + "-revised.zip");
						});
					}
					$.unblockUI(); 
					return false; 
				});						
			}
			// no error just unbind blockUI
			else {					
				$.blockUI.defaults.message  = "<h2>HTML Lint result for " + fileFolder + "</h2>";
				$.blockUI.defaults.message += "<p>No error found.</p>";
				$.blockUI.defaults.message += "<input type='button' id='lintD_no' value='OK' />";
				$.blockUI({css: { width: '500px' }});  
				$('#lintD_no').unbind().click(function() { 
					$.unblockUI(); 
					return false; 
				}); 					
			}
		}
    }
    
    //only enable lanuch when only 1 item select and that item is a directory 
    this.getstate = function(sel) {
        var fm   = this.fm,
        sel = this.files(sel),
        cnt = sel.length;
        //return 0 to enable, -1 to disable icon access
        return cnt == 1 ? (sel[0].mime == 'text/html' || sel[0].mime == 'directory' ) && (!sel[0].locked) ? 0 : -1 : -1;
    }
}


function isEmpty(obj) {
    for(var key in obj) {
        if(obj.hasOwnProperty(key))
            return false;
    }
    return true;
}

function getLint(file,type) {
	
	$.ajax({
		type: 'GET',
		url : "php/connector.minimal.php",
		data : {
			cmd : 'file',
			target : file.hash
		}
	})			
	.done(function (data) {
		var opts = {
			doctype: 'auto',				
			indent: 'yes',
			quiet: 'yes',
			markup: 'yes',
			"show-info": 'no',
			"tidy-mark": 'no'
		};
		// get lint result from htmlHint
		var result = HTMLHint.verify(data),
			resultDownload = [],
			output = "";
		// get fixed version from tidy
		var fixedFile = tidy_html5(data,opts);																			
		
		// process lint result lint
		$.each( result, function( key, value ) {
			// limit output on the pop-up box
			if (key < 8) {
				output += "<p>line " + value.line + ", col " + value.col + ", error - " + safe_tags_replace(value.rule.description) + " (" + value.rule.id + ")</p>";
			}					
			if (key == 8) {
				output += "<p>...</p>";
			}					
			// setup resultDownload
			resultDownload[key] = value;
			resultDownload[key].id = value.rule.id;
			resultDownload[key].description = value.rule.description;
			resultDownload[key].link = value.rule.link;
			delete resultDownload[key].rule;
		});		
		// if there is error setup blockUI output pop-up box
		if (output) {					
			$.blockUI.defaults.message  = "<h2>HTML Lint result for " + file.name + "</h2>";
			$.blockUI.defaults.message += output;									        
			$.blockUI.defaults.message += "<p>Download revised file or Download lint results in CSV ?</p>" ;
			$.blockUI.defaults.message += "<hr class='blockMsg-hr'>";
			$.blockUI.defaults.message += "<input type='button' id='lintF_yes' value='Download Revised' /> ";
			$.blockUI.defaults.message += "<input type='button' id='lintD_yes' value='Download Results' /> ";
			$.blockUI.defaults.message += "<input type='button' id='lintD_no' value='Close' />";
			// style blockUI center
			$.blockUI({css: {	width: 700 + 'px',
								top: '50%',
								left: '50%',
								margin: (-400 / 2) + 'px 0 0 '+ (-700/2) + 'px'
			}});
			// donwload lint result and unbind blockUI
			$('#lintD_yes').unbind().click(function() {						
				if (navigator.appName != 'Microsoft Internet Explorer') {
					console.log(resultDownload);
					JSONToCSVConvertor(resultDownload,file.name,true);
				}						
				else {
					var popup = window.open('','csv','');							
					popup.document.body.innerHTML = '<pre>' + resultDownload + '</pre>';
				}
				$.unblockUI(); 						
				return false;											
			});
			// just unbind blockUI
			$('#lintD_no').unbind().click(function() { 
				$.unblockUI(); 
				return false; 
			});
			// download fixed code
			$('#lintF_yes').unbind().click(function() {
				// append -fixed before the filename extension
				var filename = file.name.replace(/(\.[\w\d_-]+)$/i, '-revised$1');
				download(filename, fixedFile);										
				$.unblockUI(); 
				return false; 
			});						
		}
		// no error just unbind blockUI
		else {					
			$.blockUI.defaults.message  = "<h2>HTML Lint result for " + file.name + "</h2>";
			$.blockUI.defaults.message += "<p>No error found.</p>";
			$.blockUI.defaults.message += "<input type='button' id='lintD_no' value='OK' />";
			$.blockUI({css: { width: '500px' }});  
			$('#lintD_no').unbind().click(function() { 
				$.unblockUI(); 
				return false; 
			}); 					
		}
		
	})
	.fail(function (jqxhr, textStatus, error) {
		// do something when fail
		var err = textStatus + ', ' + error;
		console.log("Request Failed: " + err);
	}); 
	
}

function recursiveGetAllFiles(hash){	
  $.ajax({
	type: 'GET',
	url : "php/connector.minimal.php",
	data : {
		cmd : 'open',
		target : hash
	},
    success: function(data,status)
    {      
		$.each( data.files, function( key, value ) {
			if (value.mime !== 'directory'){
				fileList.push({"hash":value.hash,"mime":value.mime,"name":value.name});
			}			
			else {
				recursiveGetAllFiles(value.hash);
			}
		})
	},
    async:   false
  });	
  return fileList;
}

// download 
function download(filename, text) {
  var element = document.createElement('a');
  element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
  element.setAttribute('download', filename);
  element.style.display = 'none';
  document.body.appendChild(element);
  element.click();
  document.body.removeChild(element);
}

// escape html tag
var tagsToReplace = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;'
};

function replaceTag(tag) {
    return tagsToReplace[tag] || tag;
}

function safe_tags_replace(str) {
    return str.replace(/[&<>]/g, replaceTag);
}

function JSONToCSVConvertor(JSONData, ReportTitle, ShowLabel) {     

	//If JSONData is not an object then JSON.parse will parse the JSON string in an Object
	var arrData = typeof JSONData != 'object' ? JSON.parse(JSONData) : JSONData;
	var CSV = '';    
	//This condition will generate the Label/Header
	if (ShowLabel) {
		var row = "";

		//This loop will extract the label from 1st index of on array
		for (var index in arrData[0]) {
			//Now convert each value to string and comma-seprated
			row += index + ',';
		}
		row = row.slice(0, -1);
		//append Label row with line break
		CSV += row + '\r\n';
	}

	//1st loop is to extract each row
	for (var i = 0; i < arrData.length; i++) {
		var row = "";
		//2nd loop will extract each column and convert it in string comma-seprated
		for (var index in arrData[i]) {
			row += '"' + arrData[i][index] + '",';
		}
		row.slice(0, row.length - 1);
		//add a line break after each row
		CSV += row + '\r\n';
	}

	if (CSV == '') {        
		alert("Invalid data");
		return;
	}   

	//this trick will generate a temp "a" tag
	var link = document.createElement("a");    
	link.id="lnkDwnldLnk";

	//this part will append the anchor tag and remove it after automatic click
	document.body.appendChild(link);

	var csv = CSV,  
		blob = new Blob([csv], { type: 'text/csv' }),
		csvUrl = window.URL.createObjectURL(blob),
		filename = 'lintResult_' + ReportTitle + '.csv';
		
	$("#lnkDwnldLnk")
		.attr({
			'download': filename,
			'href': csvUrl
		}); 

	$('#lnkDwnldLnk')[0].click();    	
	document.body.removeChild(link);
}