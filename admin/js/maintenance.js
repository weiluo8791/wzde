$(document).ready(function () {
	$.ajaxSetup({
		cache : false
	});
	var list = $('#list');
	var html = buildFunctionList();
	list.html(html);

	//popup event
	$("#m_sync").on("click", function () {
		opendialog('File Integrity', 'scripts/maintenance.php?action=sync');
	});

	$("#m_deploy").on("click", function () {
		opendialog('Deploy to Test', 'scripts/maintenance.php?action=deploy');
	});

	$("#m_hash").on("click", function () {
		opendialog('Generate Hash', 'scripts/maintenance.php?action=hash');
	});

	//individual function event
	$("#staff_hash").on("click", {
		resource : "staff"
	}, updateHash);
	//individual function event
	$("#customer_hash").on("click", {
		resource : "customer"
	}, updateHash);
	//individual function event
	$("#home_hash").on("click", {
		resource : "home"
	}, updateHash);
	//individual function event
	$("#staffapps_hash").on("click", {
		resource : "staffapps"
	}, updateHash);
	//individual function event
	$("#atsignage_hash").on("click", {
		resource : "atsignage"
	}, updateHash);

});

// Build html for Deploy select
function buildSelectForm(searchSite, siteList) {
	if (!searchSite) {
		searchSite = [];
	}
	if (!siteList) {
		siteList = [];
	}
	var html = ' <div class="row" style="margin-top: 15px;">';
	html += '  <div class="small-12 medium-4 large-4 columns">';
	html += '   <p><label for="site"><span class="fa fa-home"></span> Site</label><br /><select id="selectSite" multiple="multiple">' + buildSelectField(searchSite, siteList) + '</select>';
	html += '                                                                          <input type="checkbox" id="allSite" ><label for="site"> Select All</label></p>';
	html += '  </div>';
	html += ' </div>';

	html += ' <div class="row">';
	html += '  <div class="small-12 columns">';
	html += '   <p><input type="submit" name="submitSelect" id="submitSelect" value="Submit" /></p>';
	html += '  </div>';
	html += ' </div>';
	return html;
}

// Build html for multiselect fields.
function buildSelectField(selections, list) {
	var html = '';
	for (var i = 0; i < list.length; i++) {
		var entry = list[i],
		id = entry.id,
		name = entry.name,
		zone = entry.zone,
		selected = '';
		if (selections.indexOf(id) >= 0) {
			selected = 'selected';
		}
		html += '<option value="' + id + ';' + zone + '" ' + selected + '>' + name + '</option>';
	}
	return html;
}

// Get list of site
function getSitesList() {
	var query = "SELECT distinct idsites id, site name,zone FROM webutility.wzde_files order by site asc",
	bindV = {};
	queryDataJson = $.parseJSON(
			$.ajax({
				url : "mariadb_query.php",
				cache : false,
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

//load select2 for deploy
function loadMultiSelect() {
	// Multiselect resources.
	$('#selectSite').select2({
		templateSelection : function (selection) {
			var $selection = $('<span id="' + selection.id + '" class="site">' + selection.text + '</span>');
			return $selection;
		}
	});
	//select all
	$("#allSite").click(function () {
		if ($("#allSite").is(':checked')) {
			$("#selectSite > option").prop("selected", "selected");
			$("#selectSite").trigger("change");
		} else {
			$("#selectSite > option").removeAttr("selected");
			$("#selectSite").trigger("change");
		}
	});
	$('#submitSelect').on('click', submitSelect);
}

//callback function for submit deploy
function submitSelect(e) {
	e.preventDefault();
	var payload = {},
	site = [];

	$('.site').each(function () {
		var entry = $(this),
		parts = entry.attr('id').split(';');
		id = parts[0];
		name = entry.text();
		zone = parts[1];
		if (id) {
			site.push({
				id : id,
				name : name,
				zone : zone
			});
		}
	});
	payload.site = site;
	//console.log(payload.site);

	if (selectAction == 'deploy') {
		deployWholeSite(payload.site);
	} else if (selectAction == 'sync') {
		fixSyncWholeSite(payload.site);
	}
}

function opendialog(action, page) {

	var $dialog = $('#JQUI_dialog_container')
		.html('<iframe style="border: 0px;position: absolute; " src="' + page + '" width="97%" height="97%"></iframe>')
		.dialog({
			title : action,
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

function buildFunctionList() {

	var html = '<div class="row">';

	html += '<div class="small-12 medium-8 large-8 columns">';
	html += '<p class="body_nav">';
	//html += '<a href="maintenance.php?function=sync"><span class="fa fa-refresh fa-lg"> Syncing</span></a>';
	html += '<button id="m_sync"><span class="fa fa-refresh fa-lg"> Syncing</span></button>';
	html += '&nbsp;<span class="description">Check/Fix Syncing Issue.</span>';
	html += '</p>';
	html += '</div>';
	html += '</div>';

	html += '<div class="row">';
	html += '<div class="small-12 medium-8 large-8 columns">';
	html += '<p class="body_nav">';
	html += '<button id="m_deploy"><span class="fa fa-rocket fa-lg"> Deploy</span></button>';
	html += '&nbsp;<span class="description">Unconditional Deploy to Test.</span>';
	html += '</p>';
	html += '</div>';
	html += '</div>';

	html += '<div class="row">';
	html += '<div class="small-12 medium-8 large-8 columns">';
	html += '<p class="body_nav">';
	html += '<button id="m_hash"><span class="fa fa-key fa-lg"> Hash</span></button>';
	html += '&nbsp;<span class="description">Generate and Update Hash for ALL files.</span>';
	html += '</p>';
	html += '</div>';
	html += '</div>';

	return html;
}

//update file hash value in mariadb by zone
//in : event = [resource]
//out: nothing
function updateHash(event) {
	var total = 0,
	message = $("#hash_message");
	message.removeClass().addClass("message process").html('<p>Gathering file list...</p>');
	//ajax call to get all file and its hash value by zone
	$.ajax({
		url : "wzdeAdmin.php",
		type : "GET",
		data : {
			resource : event.data.resource
		},
		dataType : "json",
		success : function () {
			message.removeClass().addClass("message process").html('<p>Updating file...</p>');
		},
	})
	.done(function (data) {
		var fileList = [];
		$.each(data, function (key, value) {
			var path = value["path"],
			filename = value["filename"],
			checksum = value["checksum"];
			//fileList.push({path:path,filename:filename,checksum:checksum});
			fileList = [{
					path : path,
					filename : filename,
					checksum : checksum
				}
			];
			total++;
			//ajax call to update each file with hash value
			$.ajax({
				url : "update.php",
				type : "POST",
				data : {
					data : fileList,
					script : 'updateFileHash.sql'
				},
				dataType : "json",
				success : function () {
					message.removeClass().addClass("message process").append(path + '/' + filename + '</br >');
				},
				error : function () {
					message.removeClass().addClass("message process").append('<p>There was an error with ' + path + '/' + filename + '</p>');
				}
			})
			.fail(function (jqxhr, textStatus, error) {
				var err = textStatus + ', ' + error;
				console.log("Request Failed: " + err);
			});
		});
		$("#hash_message p:first").replaceWith('<p>Updated ' + total + ' files</p>');
	})
	.fail(function (jqxhr, textStatus, error) {
		var err = textStatus + ', ' + error;
		console.log("Request Failed: " + err);
	});
}

function deployWholeSite(sites) {
	var query = "select path,filename,type,'add'as ops from webutility.wzde_files where path like :dir",
	message = $("#deploy_message");

	//blockUI popup
	$.blockUI({
		message : $('#deployWholeQuestion'),
		css : {
			width : '500px'
		}
	});

	//if answer Yes
	$('#dw_yes').unbind().click(function () {
		//for each site selected
		$.each(sites, function (key, value) {
			var checkoutPath = value.zone + '/' + value.name,
			dir = value.zone + '/' + value.name + '%',
			bindV = {
				"dir" : dir
			};

			//commit the whole site
			$.ajax({
				type : 'POST',
				url : "svnCommands.php",
				cache : false,
				data : {
					cPath : value.zone + '/' + value.name,
					committed : isCommitted(checkoutPath),
					command : 'wholeCommit',
				},
				beforeSend : function () {
					$.blockUI({
						message : "<h3>Commit in progress...</h3>"
					});
				},
				dataType : 'json'
			});

			//get list of file need to be deployed
			var queryDataJson = $.parseJSON(
					$.ajax({
						url : "mariadb_query.php",
						cache : false,
						data : {
							query : query,
							bindV : JSON.stringify(bindV),
							multi : true
						},
						beforeSend : function () {
							$.blockUI({
								message : "<h3>Gathering files...</h3>"
							});
						},
						async : false,
						dataType : 'json'
					}).responseText);

			//add and push the parent folder into the queue
			var tempObj = {
				"path" : dir.substring(0, dir.lastIndexOf('/')),
				"filename" : dir.substring(dir.lastIndexOf('/') + 1, dir.length - 1),
				"type" : 'directory',
				"ops" : 'add'
			};
			queryDataJson.push(tempObj);

			//console.log(queryDataJson);

			//slice array into 50 chunk and queue up files to be deployed
			var k,
			l,
			temparray,
			chunk = 50;
			for (k = 0, l = queryDataJson.length; k < l; k += chunk) {
				temparray = queryDataJson.slice(k, k + chunk);
				$.ajax({
					type : 'POST',
					crossDomain : true,
					url : "https://staffappslx.meditech.com/wluo/php_Q/add_to_deploy.php",
					cache : false,
					data : {
						filelist : JSON.stringify(temparray),
					},
					beforeSend : function () {
						$.blockUI({
							message : "<h3>Deploy in progress...</h3>"
						});
					},
				})
				.done(function () {
					$.unblockUI();
				})
				.fail(function (jqxhr, textStatus, error) {
					var err = textStatus + ', ' + error;
					$.unblockUI();
					console.log("Request Failed: " + err);
				});
			} //end for

		}); //end each

		message.removeClass().addClass("message process").html('<p>Site Deployed</p>');
	});

	$('#dw_no').unbind().click(function () {
		$.unblockUI();
		return false;
	});
}

function fixSyncWholeSite(sites) {
	var bad = 0,
	message = $("#sync_message");
	message.removeClass().addClass("message process").html('<p>Gathering file list...</p>');

	$.blockUI({
		message : $('#fixSyncWholeQuestion'),
		css : {
			width : '500px'
		}
	});

	$('#sw_yes').unbind().click(function () {

		$.each(sites, function (key, value) {
			var checkoutPath = value.zone + '/' + value.name;
			//ajax call to get all file and its hash value by zone
			$.ajax({
				url : "wzdeAdmin.php",
				type : "GET",
				data : {
					resource : value.zone,
					site : value.name
				},
				dataType : "json",
                async : false,
				success : function () {
					//message.removeClass().addClass("message process").html('<p>Checking file...</p>');
				},
			})
			.done(function (data) {
				$.each(data, function (key, value) {
					var path = value["path"],
					filename = value["filename"],
					checksum = value["checksum"],
                    fileList = [{
                            path : path,
                            filename : filename,
                            checksum : checksum
                        }
                    ];
                    $.ajax({
                        url: "read.php",
                        type: "POST",
                        data: {data: fileList,script:'readSiteFile.sql'},
                        dataType: "json",
                        async : false
                    })
                    .done(function(data) {
                        if (data[0].total==1) {
                            message.removeClass().addClass("message process").append('<span class="fa fa-check">'+path + '/' + filename + '</span></br>');
                        }
                        else if (data[0].total==0) {
                            message.removeClass().addClass("message process").append('<span style="color:red" class="fa fa-close">'+path + '/' + filename + '</span></br>');
                            bad++;
                        }
                    })                    
                    .fail(function(jqxhr,textStatus,error) {
                        var err = textStatus + ', ' + error;
                        console.log("Request Failed: " + err);
                    });                        
                });
                $("#sync_message p:first").replaceWith('<p>Total '+bad+' bad links</p>');
            })
            .fail(function (jqxhr, textStatus, error) {
                var err = textStatus + ', ' + error;
                console.log("Request Failed: " + err);
            });            
		});       
		$.unblockUI();
	});

	$('#sw_no').unbind().click(function () {
		$.unblockUI();
		return false;
	});
}

//Check if the root site is committed
function isCommitted(path) {
	//only take the resource and root
	path = path.split("/").slice(0, 2).join("/");
	var queryDataJson = $.parseJSON(
			$.ajax({
				type : 'POST',
				url : "svnCommands.php",
				data : {
					cPath : path,
					command : 'info',
				},
				async : false,
				dataType : 'json'
			}).responseText);

	return queryDataJson;
}
