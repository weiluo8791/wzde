"use strict"
elFinder.prototype.commands.publish = function() {
    this.exec = function(hashes) {
        //do whatever        
        var fm    = this.fm, 
        dfrd  = $.Deferred().fail(function(error) { error && fm.error(error); }),
        files   = this.files(hashes),
        cnt   = files.length,
        file, url, arr, host, path;
        
        if (!cnt) {
            return dfrd.reject();
        }        
        if (cnt == 1 && (file = files[0]) && file.mime == 'directory') {           
            host = window.location.origin;
            path = fm.escape(fm.path(file.hash, true));
            arr = path.split("\\");
            url = host + "/publish/index.php?resource=" + arr[0].toLowerCase() + "&folder=" + arr[1];
            url = url.replace(/\\/g, '/');            
            var wnd = window.open(url, '_blank');
            if (!wnd) {
                return dfrd.reject('errPopup');
            }
        }

    }
    
    //only enable publish when only 1 item select and that item is a directory 
    this.getstate = function(sel) {
        var fm   = this.fm,
        sel = this.files(sel),
        cnt = sel.length;
        //return 0 to enable, -1 to disable icon access
        return cnt == 1 ? sel[0].mime == 'directory' ? 0 : -1 : -1;

    }
    
}