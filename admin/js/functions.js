// Build html for application form.
function buildSearchForm(searchSite,logType,siteList,typeList) {
    if (!searchSite) { searchSite = []; }
    if (!logType) { logType = []; }
    if (!siteList) { siteList = []; }
    if (!typeList) { typeList = []; }
    var html  = '<form id="submitSearchForm">';
        html += ' <div class="row">';
        html += '  <div class="small-12 medium-4 large-4 columns">'; 
        html += '   <p><label for="site"><span class="fa fa-home"></span> Site</label><br /><select id="searchSite" multiple="multiple">'+buildSelectField(searchSite,siteList)+'</select>';
        html += '                                                                          <input type="checkbox" id="allSite" ><label for="site"> Select All</label></p>';   
        html += '   <p><label for="type"><span class="fa fa-folder-o "></span> Type</label><br /><select id="logType" multiple="multiple">'+buildSelectField(logType,typeList)+'</select>';		 
        html += '                                                                          <input type="checkbox" id="allType" ><label for="type"> Select All</label></p>';   	 
        html += '  </div>';  
        html += ' </div>';

        html += ' <div class="row">';
        html += '  <div class="small-12 columns">';
        html += '   <div id="message"></div>';
        html += '   <p><input type="submit" name="submitSearch" id="submitSearch" value="Submit" /></p>';
        html += '  </div>';   
        html += ' </div>';
        html += '</form>';
    return html;
}

function buildAccessForm(){

    var html  = '<form id="search" class="accessForm">';
        html += ' <table class="access">';
        html += '  <thead>';
        html += '    <tr>';
        html += '     <th class="heading">Edit By User</th>';
        html += '     <th class="heading">Edit By Site</th>';
        html += '    </tr>';
        html += '  </thead>';
        html += '  <tbody>';
        html += '   <tr>';
        html += '    <td><input type="search" name="user" id="user" placeholder="Please enter at least two characters" /></td>';        
        html += '    <td><input type="search" name="site" id="site" placeholder="Please enter at least two characters" /></td>';
        html += '   </tr>';
        html += '  </tbody>';
        html += ' </table>';
        html += '</form>';
    return html;
}


function buildAccessResult() {
    var html  = '<div id="results"></div>';
        html += '<div id="dialog" title=""></div>';
    return html;
}

// Build html for multiselect fields.
function buildSelectField(selections,list) {
    var html = '';
    for (var i=0; i<list.length; i++) {
        var entry = list[i],
        id = entry.id,
        name = entry.name,
        selected = '';
        if (selections.indexOf(id) >= 0) {
            selected = 'selected';
        }
        html += '<option value="'+id+'" '+selected+'>'+name+'</option>';
    }
    return html;
}