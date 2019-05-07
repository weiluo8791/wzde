// When the DOM is ready, get the party started!
$(document).ready(function () {
	$.ajaxSetup({
		cache: false
	});

	var list = $('#list'),
	html = buildAccessForm() + buildAccessResult();
	list.html(html);

	// Bring focus to the first input field.
	$('input:first').focus();
	// Highlight any text in search fields.
	$('#search input[type=search]').on('click', function () {
		$(this).select();
	});
    
    // Search user based on selection in site search results.
    $('#results').on('click','.searchUsers',function(e) {
        e.preventDefault();
        var user = $(this),
            id = user.attr('data-id'),
            name = user.attr('data-name');
        $('#user').val(name);
        searchUsers(id,name);
    });     

    // Search user based on selection in site search results.
    $('#results').on('click','.searchSites',function(e) {
        e.preventDefault();
        var site = $(this),
            id = site.attr('data-id'),
            name = site.attr('data-name');
        $('#site').val(name);
        searchSites(id,name);
    });     
    
    
	// Typeahead lookup for user search.
	$('#user').autocomplete({
		source: function (request, response) {
			$.ajax({
				url: "scripts/read.php",
				type: "POST",
				data: {
					data: [{
							term: '%' + request.term + '%'
						}
					],
					script: 'usersLookup.sql'
				},
				dataType: "json",
				success: function (data) {
					response(data);
				}
			});
		},
		minLength: 2,
		select: selectUser
	});

	// Typeahead lookup for user search.
	$('#site').autocomplete({
		source: function (request, response) {
			$.ajax({
				url: "scripts/read.php",
				type: "POST",
				data: {
					data: [{
							term: '%' + request.term + '%'
						}
					],
					script: 'sitesLookup.sql'
				},
				dataType: "json",
				success: function (data) {
					response(data);
				}
			});
		},
		minLength: 2,
		select: selectSite
	});
    
    // Single Edit.
	$('#results').on('click', '.edit', singleEdit);
	$(document).on('click', '#editAccess', editAccess);
    
    // Cancel dialog.
	$(document).on('click', '#cancelDialog', cancelDialog);

	// Check/uncheck all checkboxes.
	$('#results').on('click', '.selectAll', toggleCheckboxes);
    
	// Mass Add.
	$('#results').on('click', '.massAdd', massAdd);
	$(document).on('click', '#addUsersForSite', addUsersForSite);
	$(document).on('click', '#addSitesForUser', addSitesForUser);
	$(document).on('click', '.remove', removeUserSite);
    
	// Mass Edit.
	$('#results').on('click', '.massEdit', massEdit);
	$(document).on('click', '#editUsersForSite', editUsersForSite);
	$(document).on('click', '#editSitesForUser', editSitesForUser);
    
	// Delete.
	$('#results').on('click', '.massDelete', massDelete);
	$(document).on('click', '#deleteUsersFromSite', deleteUsersFromSite);
	$(document).on('click', '#deleteSitesFromUser', deleteSitesFromUser);

	// bind dialog checkbox click event on wzde_publish so publish will always include commit
	$('#dialog').on('click', 'input[type=checkbox]', function(e){
		if ( $('#wzde_publish').is(':checked') ){
		   $('#wzde_commit').prop('checked', true);
		}
	});	
});

// Run when selecting a user to edit.
function selectUser(e, ui) {
	var id = ui.item.id,
	name = ui.item.value;
	if (id) {
		searchUsers(id, name);
	}
}

// Search for users based on oid.
function searchUsers(id, name) {
	$('#user').attr('data-id', id).attr('data-name', name);
	$('#results').html('<p class="message">Searching...</p>');

	$.ajax({
		url: "scripts/read.php",
		type: "POST",
		data: {
			data: [{
					oid: id
				}
			],
			script: 'userAccessSites.sql'
		},
		dataType: "json",
		success: function (data) {
			userResults(data)
		}
	}).fail(userResultsFailed);

}

// Run when searching for a user failed.
function userResultsFailed() {
	$('#results').html('<p class="message">There was an issue retrieving information for the user.</p>');
}

// Run when searching for a user succeeded.
function userResults(sites) {
	var user = $('#user'),
	userID = user.attr('data-id'),
	userName = user.attr('data-name'),
	results = $('#results'),
	html = '';
	// Clear search by site field.
	$('#site').val('');
	html += '<table id="searchResults" class="access">';
	html += ' <thead>';
	html += '  <tr>';
	html += '   <th colspan="3" class="heading left">';
	html += '    <button data-type="user" data-id="' + userID + '" data-name="' + userName + '" class="massAdd"><span class="fa fa-plus fa-lg"></span> Bulk Add</button> ';
	html += '    <button data-type="user" data-id="' + userID + '" data-name="' + userName + '" class="massEdit"><span class="fa fa-pencil fa-lg"></span> Bulk Edit</button> ';
	html += '    <button data-type="user" data-id="' + userID + '" data-name="' + userName + '" class="massDelete"><span class="fa fa-trash-o fa-lg"></span> Delete</button> ';
	html += '   </th>';
	html += '   <th colspan="8" class="heading right">User: ' + userName + '</th>';
	html += '  </tr>';
	html += '  <tr>';
	html += '   <th class="subheading"><input type="checkbox" class="selectAll" /></th>';
	html += '   <th class="subheading">Edit</th>';
	html += '   <th class="subheading name">Site</th>';
	html += '   <th class="subheading">WZDE Write</th>';
	html += '   <th class="subheading">WZDE Publish</th>';
	html += '   <th class="subheading">WZDE Commit</th>';
	html += '   <th class="subheading">WZDE Read</th>';
	html += '   <th class="subheading">WZDE Checkout</th>';
	html += '   <th class="subheading">WZDE Git</th>';
	html += '  </tr>';
	html += ' </thead>';
	html += ' <tbody>';
	// Build html for search results table.
	for (var i = 0; i < sites.length; i++) {
		var site = sites[i],
		id = site.id,
		name = site.name,
		wzdeWrite = site.wzde_write,
		wzdePublish = site.wzde_publish,
		wzdeCommit = site.wzde_commit,
		wzdeRead = site.wzde_read,
		wzdeCheckout = site.wzde_checkout,
		wzdeGit = site.wzde_git;
		html += '<tr id="' + id + '">';
		html += ' <td><input type="checkbox" data-id="' + id + '" class="selection" /></td>';
		html += ' <td><button data-type="user" data-siteid="' + id + '" data-sitename="' + name + '" data-userid="' + userID + '" data-username="' + userName + '" class="edit"><span class="fa fa-pencil fa-lg"></span></button></td>';
		//html += ' <td class="name"><a href="../webeditor/open.asp?folder=' + name + '">' + name + '</a></td>';
        html += ' <td class="name"><a href="" data-id="' + id + '" data-name="' + name + '" class="searchSites">' + name + '</a></td>';
		html += ' <td>';
		if (wzdeWrite == 1) {
			html += '<span class="fa fa-check-circle fa-lg"></span>';
		}
		html += ' </td>';
		html += ' <td>';
		if (wzdePublish == 1) {
			html += '<span class="fa fa-check-circle fa-lg"></span>';
		}
		html += ' </td>';
		html += ' <td>';
		if (wzdeCommit == 1) {
			html += '<span class="fa fa-check-circle fa-lg"></span>';
		}
		html += ' </td>';
		html += ' <td>';
		if (wzdeRead == 1) {
			html += '<span class="fa fa-check-circle fa-lg"></span>';
		}
		html += ' </td>';
		html += ' <td>';
		if (wzdeCheckout == 1) {
			html += '<span class="fa fa-check-circle fa-lg"></span>';
		}
		html += ' </td>';
		html += ' <td>';
		if (wzdeGit == 1) {
			html += '<span class="fa fa-check-circle fa-lg"></span>';
		}
		html += ' </td>';
		html += '</tr>';
	}
	html += '</tbody></table>';
	// Display search results table.
	results.html(html);
}

// Run when selecting a site to edit.
function selectSite(e, ui) {
	var id = ui.item.id,
	name = ui.item.value;
	if (id) {
		searchSites(id, name);
	}
}

// Search for sites based on site id.
function searchSites(id, name) {
	$('#site').attr('data-id', id).attr('data-name', name);
	$('#results').html('<p class="message">Searching...</p>');
	$.ajax({
		url: "scripts/read.php",
		type: "POST",
		data: {
			data: [{
					id: id
				}
			],
			script: 'siteAccessUsers.sql'
		},
		dataType: "json",
		success: function (data) {
			siteResults(data)
		}
	}).fail(siteResultsFailed);
}

// Run when searching for a site failed.
function siteResultsFailed() {
	$('#results').html('<p class="message">There was an issue retrieving information for the site.</p>');
}

// Run when searching for a site succeeded.
function siteResults(users) {
	var site = $('#site'),
	siteID = site.attr('data-id'),
	siteName = site.attr('data-name'),
	results = $('#results'),
	html = '';
	// Clear search by user field.
	$('#user').val('');
	html += '<table id="searchResults" class="access">';
	html += ' <thead>';
	html += '  <tr>';
	html += '   <th colspan="3" class="heading left">';
	html += '    <button data-type="site" data-id="' + siteID + '" data-name="' + siteName + '" class="massAdd"><span class="fa fa-plus fa-lg"></span> Bulk Add</button> ';
	html += '    <button data-type="site" data-id="' + siteID + '" data-name="' + siteName + '" class="massEdit"><span class="fa fa-pencil fa-lg"></span> Bulk Edit</button> ';
	html += '    <button data-type="site" data-id="' + siteID + '" data-name="' + siteName + '" class="massDelete"><span class="fa fa-trash-o fa-lg"></span> Delete</button> ';
	html += '   </th>';
	html += '   <th colspan="8" class="heading right">Site: ' + siteName + '</th>';
	html += '  </tr>';
	html += '  <tr>';
	html += '   <th class="subheading"><input type="checkbox" class="selectAll" /></th>';
	html += '   <th class="subheading">Edit</th>';
	html += '   <th class="subheading name">Name</th>';
	html += '   <th class="subheading">WZDE Write</th>';
	html += '   <th class="subheading">WZDE Publish</th>';
	html += '   <th class="subheading">WZDE Commit</th>';
	html += '   <th class="subheading">WZDE Read</th>';
	html += '   <th class="subheading">WZDE Checkout</th>';
	html += '   <th class="subheading">WZDE Git</th>';
	html += '  </tr>';
	html += ' </thead>';
	html += ' <tbody>';
	// Build html for search results table.
	for (var i = 0; i < users.length; i++) {
		var user = users[i],
		id = user.oid,
		name = user.name,
		wzdeWrite = user.wzde_write,
		wzdePublish = user.wzde_publish,
		wzdeCommit = user.wzde_commit,
		wzdeRead = user.wzde_read,
		wzdeCheckout = user.wzde_checkout,
		wzdeGit = user.wzde_git;
		html += '<tr id="' + cleanOID(id) + '">';
		html += ' <td><input type="checkbox" data-id="' + id + '" class="selection" /></td>';
		html += ' <td><button data-type="site" data-siteid="' + siteID + '" data-sitename="' + siteName + '" data-userid="' + id + '" data-username="' + name + '" class="edit"><span class="fa fa-pencil fa-lg"></span></button></td>';
		html += ' <td class="name"><a href="" data-id="' + id + '" data-name="' + name + '" class="searchUsers">' + name + '</a></td>';
		html += ' <td>';
		if (wzdeWrite == 1) {
			html += '<span class="fa fa-check-circle fa-lg"></span>';
		}
		html += ' </td>';
		html += ' <td>';
		if (wzdePublish == 1) {
			html += '<span class="fa fa-check-circle fa-lg"></span>';
		}
		html += ' </td>';
		html += ' <td>';
		if (wzdeCommit == 1) {
			html += '<span class="fa fa-check-circle fa-lg"></span>';
		}
		html += ' </td>';
		html += ' <td>';
		if (wzdeRead == 1) {
			html += '<span class="fa fa-check-circle fa-lg"></span>';
		}
		html += ' </td>';
		html += ' <td>';
		if (wzdeCheckout == 1) {
			html += '<span class="fa fa-check-circle fa-lg"></span>';
		}
		html += ' </td>';
		html += ' <td>';
		if (wzdeGit == 1) {
			html += '<span class="fa fa-check-circle fa-lg"></span>';
		}
		html += ' </td>';
		html += '</tr>';
	}
	html += '</tbody></table>';
	// Display search results table.
	results.html(html);
}

// Run when the Edit button is selected for a single site or user.
function singleEdit() {
	var button = $(this),
	type = button.attr('data-type'),
	siteID = button.attr('data-siteid'),
	siteName = button.attr('data-sitename'),
	userOID = button.attr('data-userid'),
	userName = button.attr('data-username');
	if (siteID && userOID) {
        $.ajax({
            url: "scripts/read.php",
            type: "POST",
            data: {
                data: [{
                        id: siteID,
                        oid: userOID
                    }
                ],
                script: 'userAccess.sql'
            },
            dataType: "json",
            success: function (data) {
                openSingleEditDialog(type, siteID, siteName, userOID, userName, data[0]);
            }
        });     
        
	}
}

// Open single edit dialog.
function openSingleEditDialog(type, siteID, siteName, userOID, userName, access) {
	$('#dialog').dialog({
		width: '30%',
		modal: true,
		show: 'fade',
		hide: 'fade',
		resizable: false,
		position: {
			my: "center",
			at: "top",
			of: $('body')
		},
		open: function () {
			var dialog = $(this),
			accessOptions = buildAccessOptions(access),
			html = '';
			html += '<p>You are about to edit access for ' + userName + ' within the ' + siteName + ' site.</p>';
			html += accessOptions;
			html += '<p>Are you sure you wish to continue?</p>';
			html += '<div id="dialogMessage"></div>';
			html += '<p id="dialogButtons"><button data-type="' + type + '" data-siteid="' + siteID + '" data-sitename="' + siteName + '" data-useroid="' + userOID + '" data-username="' + userName + '" id="editAccess" class="confirm">Yes, Edit</button> <button id="cancelDialog" class="cancel">No, Cancel</button></p>';
			dialog.dialog('option', 'title', 'Edit Access');
			dialog.html(html);
		},
		close: function () {
			$(this).html('').dialog('destroy');
		}
	});
}

// Run when the select all checkbox is selected.
function toggleCheckboxes() {
	var selectAll = $(this),
	checked = selectAll.prop('checked'),
	table = $('#searchResults'),
	selections = table.find($('.selection'));
	if (checked) {
		selections.each(function () {
			$(this).prop('checked', true);
		});
	} else {
		selections.each(function () {
			$(this).prop('checked', false);
		});
	}
}

// Build html for access options.
function buildAccessOptions(selections) {
	var options = [['wzde_write', 'WZDE Write'], ['wzde_publish', 'WZDE Publish'], ['wzde_commit', 'WZDE Commit'], ['wzde_read', 'WZDE Read'], ['wzde_checkout', 'WZDE Checkout'], ['wzde_git', 'WZDE Git']],
	html = '<p>';
	for (var i = 0; i < options.length; i++) {
		var option = options[i],
		id = option[0],
		name = option[1],
		checked = '';
		if (selections && selections[id] == 1) {
			checked = 'checked';
		}
		//deafult option wzde_read,wzde_write,wzde_commit
		if (!selections && (id == 'wzde_read' || id == 'wzde_write' || id == 'wzde_commit')) {
			checked = 'checked';
		}
		html += '<label for="' + id + '"><input type="checkbox" data-id="' + id + '" id="' + id + '" class="accessSelection" ' + checked + ' /> ' + name + '</label><br />';
	}
	html += '</p>';
	return html;
}

// Run when confirming editing access for single site or user.
function editAccess() {
	var button = $(this),
	type = button.attr('data-type'),
	siteID = button.attr('data-siteid'),
	siteName = button.attr('data-sitename'),
	userOID = button.attr('data-useroid'),
	userName = button.attr('data-username'),
	accessCheckboxes = $('#dialog').find($('.accessSelection')),
	userAccess = {};
	// Loop through access checkboxes and build access values.
	accessCheckboxes.each(function () {
		var checkbox = $(this),
		checked = checkbox.prop('checked'),
		option = checkbox.attr('data-id'),
		access = 0;
		if (checked) {
			access = 1;
		}
		userAccess[option] = access;
	});
	if (type && siteID && userOID) {
		$('#dialogMessage').html('<p>Editing access...</p>');
		$('#dialogButtons').hide();
        // Ajax to update access
		$.ajax({
			url: "scripts/update.php",
			type: "POST",
			data: {
				data: [{
						id: siteID,
						oid: userOID,
                        wzdeWrite: userAccess["wzde_write"],
                        wzdePublish: userAccess["wzde_publish"],
                        wzdeCommit: userAccess["wzde_commit"],
                        wzdeRead: userAccess["wzde_read"],
                        wzdeCheckout: userAccess["wzde_checkout"],
                        wzdeGit: userAccess["wzde_git"]                                                
					}
				],
				script: 'updateAccess.sql'
			},
			dataType: "json",
            // Callback to update access table after update
			success: function (data) {
				accessEdited(data,type,siteID,userOID,siteName,userName);
			}
		});
	}
}

// Run when editing access for single site or user is finished.
function accessEdited(info,type,siteID,userOID,siteName,userName) {
	if (info["status"].indexOf('ERROR') >= 0) {
		$('#dialogMessage').html('<p>There was an issue editing access.<br />Please try again.</p>');
		$('#dialogButtons').show();
	} else {
		cancelDialog();
		// Reload search results to visually see change in access.
		if (type == 'site') {
			searchSites(siteID, siteName);
		} else {
			searchUsers(userOID, userName);
		}
	}
}

// Run when the Delete button is selected.
function massDelete() {
	var button = $(this),
	type = button.attr('data-type'),
	id = button.attr('data-id'),
	name = button.attr('data-name'),
	table = $('#searchResults'),
	checkboxes = table.find($('.selection')),
	cnt = 0;
	// Check if there are selections.
	checkboxes.each(function () {
		var checkbox = $(this),
		checked = checkbox.prop('checked');
		if (checked) {
			cnt++;
		}
	});
	// User selected at least one.
	if (cnt > 0) {
		if (type == 'site') {
			openMassDeleteSiteDialog(id, name);
		} else {
			openMassDeleteUserDialog(id, name);
		}
	}
}

// Open delete site dialog.
function openMassDeleteSiteDialog(id, name) {
	$('#dialog').dialog({
		width: '30%',
		modal: true,
		show: 'fade',
		hide: 'fade',
		resizable: false,
		position: {
			my: "center",
			at: "top",
			of: $('body')
		},
		open: function () {
			var dialog = $(this),
			html = '';
			html += '<p>You are about to delete the selected users from the <strong>' + name + '</strong> site.</p>';
			html += '<p>Are you sure you wish to continue?</p>';
			html += '<div id="dialogMessage"></div>';
			html += '<p id="dialogButtons"><button data-id="' + id + '" id="deleteUsersFromSite" class="confirm">Yes, Delete</button> <button id="cancelDialog" class="cancel">No, Cancel</button></p>';
			dialog.dialog('option', 'title', 'Mass Delete User Access for Site');
			dialog.html(html);
		},
		close: function () {
			$(this).html('').dialog('destroy');
		}
	});
}

// Run when confirming deletion of users from site.
function deleteUsersFromSite() {
	var siteID = $(this).attr('data-id'),
	checkboxes = $('#searchResults').find($('.selection')),
	users = [];
	checkboxes.each(function () {
		var checkbox = $(this),
		checked = checkbox.prop('checked'),
		user_oid = checkbox.attr('data-id');
		if (checked) {
			users.push(user_oid);
		}
	});

	if (users.length > 0) {
		$('#dialogMessage').html('<p>Deleting users from site...</p>');
		$('#dialogButtons').hide();
        
		$.ajax({
			url: "scripts/update.php",
			type: "POST",
			data: {
				data: [{
						siteID: siteID,
                        users: users.join('|')
					}
				],
				script: 'deleteUsersFromSite.sql'
			},
			dataType: "json",
			// Callback to update access table after update
			success: function (data) {
                usersDeletedFromSite(data,users);
			}
		});
	}
}

// Run when deleting users from site is finished.
function usersDeletedFromSite(data,users) {
	if (data["status"].indexOf('ERROR') >= 0) {
		$('#dialogMessage').html('<p>There was an issue deleting the users.<br />Please try again.</p>');
		$('#dialogButtons').show();
	} else {
		cancelDialog();
		if (users.length > 0) {
			for (var i = 0; i < users.length; i++) {
				var id = cleanOID(users[i]);
				// Remove table rows.
				$('#' + id).remove();
			}
		}
	}
}

// Open mass delete user dialog.
function openMassDeleteUserDialog(id, name) {
	$('#dialog').dialog({
		width: '30%',
		modal: true,
		show: 'fade',
		hide: 'fade',
		resizable: false,
		position: {
			my: "center",
			at: "top",
			of: $('body')
		},
		open: function () {
			var dialog = $(this),
			html = '';
			html += '<p>You are about to delete the selected sites from <strong>' + name + '</strong>.</p>';
			html += '<p>Are you sure you wish to continue?</p>';
			html += '<div id="dialogMessage"></div>';
			html += '<p id="dialogButtons"><button data-id="' + id + '" id="deleteSitesFromUser" class="confirm">Yes, Delete</button> <button id="cancelDialog" class="cancel">No, Cancel</button></p>';
			dialog.dialog('option', 'title', 'Mass Delete Site Access for User');
			dialog.html(html);
		},
		close: function () {
			$(this).html('').dialog('destroy');
		}
	});
}

// Run when confirming deletion of sites from user.
function deleteSitesFromUser() {
	var userOID = $(this).attr('data-id'),
	checkboxes = $('#searchResults').find($('.selection')),
	sites = [];
	checkboxes.each(function () {
		var checkbox = $(this),
		checked = checkbox.prop('checked'),
		siteID = checkbox.attr('data-id');
		if (checked) {
			sites.push(siteID);
		}
	});
	if (sites.length > 0) {
		$('#dialogMessage').html('<p>Deleting sites from user...</p>');
		$('#dialogButtons').hide();
        
		$.ajax({
			url: "scripts/update.php",
			type: "POST",
			data: {
				data: [{
						userOID: userOID,
                        sites: sites.join('|')
					}
				],
				script: 'deleteSitesFromUser.sql'
			},
			dataType: "json",
			// Callback to update access table after update
			success: function (data) {
                usersDeletedFromSite(data,sites);
			}
		});        
	}
}

// Run when deleting sites from user is finished.
function sitesDeletedFromUser(data,sites) {
	if (data["status"].indexOf('ERROR') >= 0) {
		$('#dialogMessage').html('<p>There was an issue deleting the sites.<br />Please try again.</p>');
		$('#dialogButtons').show();
	} else {
		cancelDialog();
		if (sites.length > 0) {
			for (var i = 0; i < sites.length; i++) {
				var id = sites[i];
				// Remove table rows.
				$('#' + id).remove();
			}
		}
	}
}


// Run when the Mass Edit button is selected.
function massEdit() {
	var button = $(this),
	type = button.attr('data-type'),
	id = button.attr('data-id'),
	name = button.attr('data-name'),
	table = $('#searchResults'),
	checkboxes = table.find($('.selection')),
	cnt = 0;
	// Check if there are selections.
	checkboxes.each(function () {
		var checkbox = $(this),
		checked = checkbox.prop('checked');
		if (checked) {
			cnt++;
		}
	});
	// User selected at least one.
	if (cnt > 0) {
		if (type == 'site') {
			openMassEditSiteDialog(id, name);
		} else {
			openMassEditUserDialog(id, name);
		}
	}
}

// Open mass edit site dialog.
function openMassEditSiteDialog(id, name) {
	$('#dialog').dialog({
		width: '30%',
		modal: true,
		show: 'fade',
		hide: 'fade',
		resizable: false,
		position: {
			my: "center",
			at: "top",
			of: $('body')
		},
		open: function () {
			var dialog = $(this),
			accessOptions = buildAccessOptions(),
			html = '';
			html += '<p>You are about to apply the same access for the selected users for the <strong>' + name + '</strong> site.</p>';
			html += accessOptions;
			html += '<p>Are you sure you wish to continue?</p>';
			html += '<div id="dialogMessage"></div>';
			html += '<p id="dialogButtons"><button data-id="' + id + '" data-name="' + name + '" id="editUsersForSite" class="confirm">Yes, Edit</button> <button id="cancelDialog" class="cancel">No, Cancel</button></p>';
			dialog.dialog('option', 'title', 'Mass Edit User Access for Site');
			dialog.html(html);
		},
		close: function () {
			$(this).html('').dialog('destroy');
		}
	});
}

// Run when confirming edits of users for site.
function editUsersForSite() {
	var button = $(this),
	siteID = button.attr('data-id'),
	siteName = button.attr('data-name'),
	accessCheckboxes = $('#dialog').find($('.accessSelection')),
	userAccess = {},
	userCheckboxes = $('#searchResults').find($('.selection')),
	users = [];
	// Loop through access checkboxes and build access values.
	accessCheckboxes.each(function () {
		var checkbox = $(this),
		checked = checkbox.prop('checked'),
		option = checkbox.attr('data-id'),
		access = 0;
		if (checked) {
			access = 1;
		}
		userAccess[option] = access;
	});
	// Loop through user checkboxes and define access values to each user.
	userCheckboxes.each(function () {
		var checkbox = $(this),
		checked = checkbox.prop('checked'),
		id = checkbox.attr('data-id');
		if (checked) {
			users.push({
				id: id,
				access: userAccess
			});
		}
	});
	if (users.length > 0) {
		var num = 10,
		iteration = 1,
		remainder = users.length % num,
		totalIterations = 0,
		group = '';
		// Reset total processed.
		totalProcessed = 0;
		// Add 1 iteration if remainder is greater than 0.
		if (remainder > 0) {
			totalIterations = Math.floor(users.length / num) + 1;
		} else {
			totalIterations = Math.floor(users.length / num);
		}
		$('#dialogMessage').html('<p>Editing users for site...</p>');
		$('#dialogButtons').hide();

		for (var i = 0; i < users.length; i += num) {
			group = users.slice(i, i + num);            
            // Ajax to edit all user for site
            $.ajax({
                url: "scripts/update.php",
                type: "POST",
                data: {
                    data: [{
                            siteID: siteID,
                            users: JSON.stringify(group)
                        }
                    ],
                    script: 'editUsersForSite.sql'
                },
                dataType: "json",
                // Callback to update access table after update
                success: function (data) {
                    usersEditedForSite(data,siteID,siteName,iteration,totalIterations,users.length,group.length);
                }
            });                        
			iteration++;
		}
	}
}

// Run when editing users for site is finished.
function usersEditedForSite(info,siteID,siteName,iteration,totalIterations,totalUsers,group) {
	var message = $('#dialogMessage');
	if (info["status"].indexOf('ERROR') >= 0) {
		message.html('<p>There was an issue editing users.<br />Please try again.</p>');
		$('#dialogButtons').show();
	} else {
		var id = siteID,
		name = siteName,
		group = group,
		totalProcessed = updateTotalProcessed(group),
		iteration = iteration,
		totalIterations = totalIterations,
		totalUsers = totalUsers;
		message.html('<p>Total Users Processed: ' + totalProcessed + ' of ' + totalUsers + '</p>');
		// If all have been processed, close the dialog and refresh the table.
		if (totalProcessed == totalUsers) {
			// Close dialog.
			cancelDialog();
			// Reload search results to visually see change in access.
			searchSites(id, name);
		}
	}
}

// Open mass edit user dialog.
function openMassEditUserDialog(id, name) {
	$('#dialog').dialog({
		width: '30%',
		modal: true,
		show: 'fade',
		hide: 'fade',
		resizable: false,
		position: {
			my: "center",
			at: "top",
			of: $('body')
		},
		open: function () {
			var dialog = $(this),
			accessOptions = buildAccessOptions(),
			html = '';
			html += '<p>You are about to apply the same access to the selected sites for <strong>' + name + '</strong>.</p>';
			html += accessOptions;
			html += '<p>Are you sure you wish to continue?</p>';
			html += '<div id="dialogMessage"></div>';
			html += '<p id="dialogButtons"><button data-id="' + id + '" data-name="' + name + '" id="editSitesForUser" class="confirm">Yes, Edit</button> <button id="cancelDialog" class="cancel">No, Cancel</button></p>';
			dialog.dialog('option', 'title', 'Mass Edit Site Access for User');
			dialog.html(html);
		},
		close: function () {
			$(this).html('').dialog('destroy');
		}
	});
}

// Run when confirming edits of sites for user.
function editSitesForUser() {
	var button = $(this),
	userOID = button.attr('data-id'),
	userName = button.attr('data-name'),
	accessCheckboxes = $('#dialog').find($('.accessSelection')),
	userAccess = {},
	siteCheckboxes = $('#searchResults').find($('.selection')),
	sites = [];
	// Loop through access checkboxes and build access values.
	accessCheckboxes.each(function () {
		var checkbox = $(this),
		checked = checkbox.prop('checked'),
		option = checkbox.attr('data-id'),
		access = 0;
		if (checked) {
			access = 1;
		}
		userAccess[option] = access;
	});
	// Loop through site checkboxes and define access values to each site.
	siteCheckboxes.each(function () {
		var checkbox = $(this),
		checked = checkbox.prop('checked'),
		id = checkbox.attr('data-id');
		if (checked) {
			sites.push({
				id: id,
				access: userAccess
			});
		}
	});
	if (sites.length > 0) {
		var num = 10,
		iteration = 1,
		remainder = sites.length % num,
		totalIterations = 0,
		group = '';
		// Reset total processed.
		totalProcessed = 0;
		// Add 1 iteration if remainder is greater than 0.
		if (remainder > 0) {
			totalIterations = Math.floor(sites.length / num) + 1;
		} else {
			totalIterations = Math.floor(sites.length / num);
		}
		$('#dialogMessage').html('<p>Editing sites for user...</p>');
		$('#dialogButtons').hide();
		for (var i = 0; i < sites.length; i += num) {
			group = sites.slice(i, i + num);
            // Ajax to edit all site for user
            $.ajax({
                url: "scripts/update.php",
                type: "POST",
                data: {
                    data: [{
                            user_oid: userOID,
                            sites: JSON.stringify(group)
                        }
                    ],
                    script: 'editSitesForUser.sql'
                },
                dataType: "json",
                // Callback to update access table after update
                success: function (data) {
                    sitesEditedForUser(data,userOID,userName,iteration,totalIterations,sites.length,group.length);
                }
            });             
            
			iteration++;
		}
	}
}

// Run when editing sites for user is finished.
function sitesEditedForUser(info,userOID,userName,iteration,totalIterations,totalSites,group) {
	var message = $('#dialogMessage');
	if (info["status"].indexOf('ERROR') >= 0) {
		message.html('<p>There was an issue editing sites.<br />Please try again.</p>');
		$('#dialogButtons').show();
	} else {
		var id = userOID,
		name = userName,
		group = group,
		totalProcessed = updateTotalProcessed(group),
		iteration = iteration,
		totalIterations = totalIterations,
		totalSites = totalSites;
		message.html('<p>Total Sites Processed: ' + totalProcessed + ' of ' + totalSites + '</p>');
		// If all have been processed, close the dialog and refresh the table.
		if (totalProcessed == totalSites) {
			// Close dialog.
			cancelDialog();
			// Reload search results to visually see change in access.
			searchUsers(id, name);
		}
	}
}



// Run when the Mass Add button is selected.
function massAdd() {
	var button = $(this),
	type = button.attr('data-type'),
	id = button.attr('data-id'),
	name = button.attr('data-name');
	if (type == 'site') {
		openMassAddSiteDialog(id, name);
	} else {
		openMassAddUserDialog(id, name);
	}
}

// Run when selecting the remove button in the mass add dialog.
function removeUserSite() {
	$(this).parent().remove();
}

// Open mass add site dialog.
function openMassAddSiteDialog(id, name) {
	$('#dialog').dialog({
		width: '50%',
		modal: true,
		show: 'fade',
		hide: 'fade',
		resizable: false,
		position: {
			my: "center",
			at: "top",
			of: $('body')
		},
		open: function () {
			var dialog = $(this),
			accessOptions = buildAccessOptions(),
			html = '';
			html += '<table><tr>';
			html += '<td width="60%" valign="top">';
			html += ' <p>Select the users you would like to add to the <strong>' + name + '</strong> site.</p>';
			html += ' <div class="accessForm"><p><input type="search" name="addUser" id="addUser" placeholder="Please enter at least two characters." /></p></div>';
			html += ' <div id="userList"></div>';
			html += '</td>';
			html += '<td width="40%" valign="top">';
			html += '<p>Select the access you would like to apply to the users for the <strong>' + name + '</strong> site.</p>';
			html += accessOptions;
			html += '</td>';
			html += '</tr></table>';
			html += '<p>Are you sure you wish to continue?</p>';
			html += '<div id="dialogMessage"></div>';
			html += '<p id="dialogButtons"><button data-id="' + id + '" data-name="' + name + '" id="addUsersForSite" class="confirm">Yes, Add</button> <button id="cancelDialog" class="cancel">No, Cancel</button></p>';
			dialog.dialog('option', 'title', 'Mass Add Users For Site');
			dialog.html(html);
			var addUserField = $('#dialog #addUser');
			// Bring focus to the first input field.
			addUserField.focus();
			// Highlight any text in search field.
			addUserField.on('click', function () {
				$(this).select();
			});             
            // Typeahead lookup for user search.
            addUserField.autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: "scripts/read.php",
                        type: "POST",
                        data: {
                            data: [{
                                    term: '%' + request.term + '%'
                                }
                            ],
                            script: 'usersLookup.sql'
                        },
                        dataType: "json",
                        success: function (data) {
                            response(data);
                        }
                    });
                },
                minLength: 2,
                select: addUser
            });                                    
		},
		close: function () {
			$(this).html('').dialog('destroy');
		}
	});
}

// Run when selecting a user to add.
function addUser(e, ui) {
	var id = ui.item.id,
	name = ui.item.value,
	users = [],
	list = $('#dialog #userList'),
	html = '<p data-id="' + id + '" class="user"><button class="remove"><span class="fa fa-minus"></span> Remove</button> ' + name + '</p>';
	// Loop through existing users and build users array.
	$('#searchResults .selection').each(function () {
		var entry = $(this),
		id = entry.attr('data-id');
		if (id) {
			users.push(id);
		}
	});
	// Loop through users to be added and build users array.
	$('#dialog #userList .user').each(function () {
		var entry = $(this),
		id = entry.attr('data-id');
		if (id) {
			users.push(id);
		}
	});
	// Add user if they don't already exist.
	if (users.indexOf(id) == -1) {
		list.append(html);
	}
	// Remove name from field and give it focus.
	$(this).val('').focus();
	return false;
}

// Run when confirming adding users to site.
function addUsersForSite() {
	var button = $(this),
	siteID = button.attr('data-id'),
	siteName = button.attr('data-name'),
	accessCheckboxes = $('#dialog').find($('.accessSelection')),
	userAccess = {},
	usersToBeAdded = $('#dialog #userList .user'),
	users = [];
	// Loop through access checkboxes and build access values.
	accessCheckboxes.each(function () {
		var checkbox = $(this),
		checked = checkbox.prop('checked'),
		option = checkbox.attr('data-id'),
		access = 0;
		if (checked) {
			access = 1;
		}
		userAccess[option] = access;
	});
	// Loop through users to be added and define access values to each user.
	usersToBeAdded.each(function () {
		var user = $(this),
		id = user.attr('data-id');
		if (id) {
			users.push({
				id: id,
				access: userAccess
			});
		}
	});
	if (users.length > 0) {
		$('#dialogMessage').html('<p>Adding users to site...</p>');
		$('#dialogButtons').hide();
        // Ajax to edit all site for user
        $.ajax({
            url: "scripts/update.php",
            type: "POST",
            data: {
                data: [{
                        site_id: siteID,
                        users: JSON.stringify(users)
                    }
                ],
                script: 'addUsersForSite.sql'
            },
            dataType: "json",
            // Callback to update access table after update
            success: function (data) {
                usersAddedForSite(data,siteID,siteName);
            }
        });         
	}
}

// Run when adding users for site is finished.
function usersAddedForSite(info,siteID,siteName) {
	if (info["status"].indexOf('ERROR') >= 0) {
		$('#dialogMessage').html('<p>There was an issue adding users.<br />Please try again.</p>');
		$('#dialogButtons').show();
	} else {
		cancelDialog();
		var id = siteID,
		name = siteName;
		// Reload search results to visually see change in access.
		searchSites(id, name);
	}
}

// Open mass add user dialog.
function openMassAddUserDialog(id, name) {
	$('#dialog').dialog({
		width: '50%',
		modal: true,
		show: 'fade',
		hide: 'fade',
		resizable: false,
		position: {
			my: "center",
			at: "top",
			of: $('body')
		},
		open: function () {
			var dialog = $(this),
			accessOptions = buildAccessOptions(),
			html = '';
			html += '<table><tr>';
			html += '<td width="60%" valign="top">';
			html += ' <p>Select the sites you would like to add to <strong>' + name + '</strong>.</p>';
			html += ' <div class="accessForm"><p><input type="search" name="addSite" id="addSite" placeholder="Please enter at least two characters." /></p></div>';
			html += ' <div id="siteList"></div>';
			html += '</td>';
			html += '<td width="40%" valign="top">';
			html += '<p>Select the access you would like to apply to <strong>' + name + '</strong> for the listed sites.</p>';
			html += accessOptions;
			html += '</td>';
			html += '</tr></table>';
			html += '<p>Are you sure you wish to continue?</p>';
			html += '<div id="dialogMessage"></div>';
			html += '<p id="dialogButtons"><button data-id="' + id + '" data-name="' + name + '" id="addSitesForUser" class="confirm">Yes, Add</button> <button id="cancelDialog" class="cancel">No, Cancel</button></p>';
			dialog.dialog('option', 'title', 'Mass Add Sites For User');
			dialog.html(html);
			var addSiteField = $('#dialog #addSite');
			// Bring focus to the first input field.
			addSiteField.focus();
			// Highlight any text in search field.
			addSiteField.on('click', function () {
				$(this).select();
			});
            // Typeahead lookup for user search.
            addSiteField.autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: "scripts/read.php",
                        type: "POST",
                        data: {
                            data: [{
                                    term: '%' + request.term + '%'
                                }
                            ],
                            script: 'sitesLookup.sql'
                        },
                        dataType: "json",
                        success: function (data) {
                            response(data);
                        }
                    });
                },
                minLength: 2,
                select: addSite
            });            
		},
		close: function () {
			$(this).html('').dialog('destroy');
		}
	});
}

// Run when selecting a site to add.
function addSite(e, ui) {
	var id = ui.item.id,
	name = ui.item.value,
	sites = [],
	list = $('#dialog #siteList'),
	html = '<p data-id="' + id + '" class="site"><button class="remove"><span class="fa fa-minus"></span> Remove</button> ' + name + '</p>';
	// Loop through existing sites and build sites array.
	$('#searchResults .selection').each(function () {
		var entry = $(this),
		id = entry.attr('data-id');
		if (id) {
			sites.push(id);
		}
	});
	// Loop through sites to be added and build sites array.
	$('#dialog #siteList .site').each(function () {
		var entry = $(this),
		id = entry.attr('data-id');
		if (id) {
			sites.push(id);
		}
	});
	// Add site if they don't already exist.
	if (sites.indexOf(id) == -1) {
		list.append(html);
	}
	// Remove name from field and give it focus.
	$(this).val('').focus();
	return false;
}

// Run when confirming adding sites to user.
function addSitesForUser() {
	var button = $(this),
	userOID = button.attr('data-id'),
	userName = button.attr('data-name'),
	accessCheckboxes = $('#dialog').find($('.accessSelection')),
	userAccess = {},
	sitesToBeAdded = $('#dialog #siteList .site'),
	sites = [];
	// Loop through access checkboxes and build access values.
	accessCheckboxes.each(function () {
		var checkbox = $(this),
		checked = checkbox.prop('checked'),
		option = checkbox.attr('data-id'),
		access = 0;
		if (checked) {
			access = 1;
		}
		userAccess[option] = access;
	});
	// Loop through sites to be added and define access values to each site.
	sitesToBeAdded.each(function () {
		var site = $(this),
		id = site.attr('data-id');
		if (id) {
			sites.push({
				id: id,
				access: userAccess
			});
		}
	});
	if (sites.length > 0) {
		$('#dialogMessage').html('<p>Adding sites to user...</p>');
		$('#dialogButtons').hide();
        // Ajax add Sites to User
        $.ajax({
            url: "scripts/update.php",
            type: "POST",
            data: {
                data: [{
                        user_oid: userOID,
                        sites: JSON.stringify(sites)
                    }
                ],
                script: 'addSitesForUser.sql'
            },
            dataType: "json",
            // Callback to update access table after update
            success: function (data) {
                sitesAddedForUser(data,userOID,userName);
            }
        }); 
	}
}

// Run when adding sites to user is finished.
function sitesAddedForUser(info,userOID,userName) {
	if (info["status"].indexOf('ERROR') >= 0) {
		$('#dialogMessage').html('<p>There was an issue adding sites.<br />Please try again.</p>');
		$('#dialogButtons').show();
	} else {
		cancelDialog();
		var id = userOID,
		name = userName;
		// Reload search results to visually see change in access.
		searchUsers(id, name);
	}
}
// Update total processed.
function updateTotalProcessed(num) {
	return totalProcessed = totalProcessed + num;
}

// Clean up staff oid.
function cleanOID(oid) {
	return oid.replace(/[\\.,-\/#!$%\^&\*;:{}=\-_`~()' ]/g, "");
}

// Close the dialog.
function cancelDialog() {
	var dialog = $('#dialog');
	dialog.html('');
	dialog.dialog('close');
	dialog.dialog('destroy');
}
