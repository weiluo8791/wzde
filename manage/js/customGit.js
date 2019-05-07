"use strict"
elFinder.prototype.commands.git = function() {
    this.exec = function(hashes) {     
        var fm    = this.fm, 
        dfrd  = $.Deferred().fail(function(error) { error && fm.error(error); }),
        files   = this.files(hashes),
        cnt   = files.length,
        file, url, arr, w, host, path;             
        
        if (!cnt) {
            return dfrd.reject();
        }        
        if (cnt == 1 && (file = files[0]) && file.mime == 'directory') {           
            path = fm.escape(fm.path(file.hash, true));
            arr = path.split("\\");
            
            $.blockUI({ message: $('#gitQuestion'), css: { width: '500px' } }); 
            $('#git_yes').unbind().click(function() { 
                // update the block message 
                $.blockUI({ message: "<h3>Creating Git Repo for "+ arr[0].toLowerCase() + "/" + arr[1] +"</h3>" });                
                $.ajax({
                    type: 'POST',
                    url : "php/createGitRepo.php",
                    data : {
                        resource : arr[0].toLowerCase(),
                        site : arr[1],
                        type : 'create'
                    }
                })
                .done(function (data) {
                    if (data == 0) {
                        $.blockUI({ message: "<h3><a href=https://bitbucket.org/mtdevs/cwt-wzde-" + arr[0].toLowerCase() + "-" + arr[1] + " target=_blank>Git Repo Created</a></h3>" }); 
                        setTimeout(function(){ $.unblockUI();}, 5000);                        
                    }
                    else if (data == 999) {
                        $.blockUI({ message: "<h3><a href=https://bitbucket.org/mtdevs/cwt-wzde-" + arr[0].toLowerCase() + "-" + arr[1] + " target=_blank>Git Repo already exists</a></h3>" }); 
                        setTimeout(function(){ $.unblockUI();}, 5000);                        
                    }                    
                    else {
                        $.blockUI({ message: "<h3>" + "ERROR : " + data}); 
                        setTimeout(function(){ $.unblockUI();}, 5000); 
                    }             
                })
                .fail(function (jqxhr, textStatus, error) {
                    // do something when fail
                    var err = textStatus + ', ' + error;
                    console.log("Request Failed: " + err);
                    $.unblockUI();
                });            
            });
            $('#git_no').unbind().click(function() { 
                $.unblockUI(); 
                return false; 
            });              
        }

    }
    
    // only enable git when only 1 directory and have wzde_git access 
    this.getstate = function(sel) {
        var fm   = this.fm,
        sel = this.files(sel),
        cnt = sel.length;
        //return 0 to enable, -1 to disable icon access
        return cnt == 1 ? (sel[0].mime == 'directory') && canGit(sel) ? 0 : -1 : -1;
    }
    
}

// Added access condition for showing "Create Git Repo"
// in : selected item
// out: true for enable git, false for disable
function canGit(sel){
    var canGit = false,
		isGit = false,
		//getResource function coming from customTools.js
        resource = getResource(sel[0].phash);
    // Only query if we are at the root level
    // If it is not at the root level resource will return false
    if (resource) {
        $.ajax({
            type: 'POST',
            url : "php/createGitRepo.php",
            data : {
                resource : resource,
                site : sel[0].name,
                type : 'query'
            },
            async : false,
            dataType : 'json'
        }).done(function (data) {
            canGit = data["canGit"];
			isGit = data["isGit"];
        }).fail(function (jqxhr, textStatus, error) {
            // do something when fail
            var err = textStatus + ', ' + error;
            console.log("Request Failed: " + err);
        });
    }
    return (canGit && !isGit);
}