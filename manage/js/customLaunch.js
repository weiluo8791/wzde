"use strict"
elFinder.prototype.commands.launch = function() {
    this.exec = function(hashes) {
        //do whatever        
        var fm    = this.fm, 
        dfrd  = $.Deferred().fail(function(error) { error && fm.error(error); }),
        files   = this.files(hashes),
        cnt   = files.length,
        file, url, arr, w;
        
        if (!cnt) {
            return dfrd.reject();
        }        
        if (cnt == 1 && (file = files[0]) && file.mime == 'directory') {           
            url = window.location.href;
            arr = url.split("/");
            url = arr[0] + "//" + arr[2] + "/zone/" + fm.escape(fm.path(file.hash, true)) + "/";
            url = url.replace(/\\/g, '/');
            
            // set window size for image if set
            //w = 'width='+parseInt(2*$(window).width()/3)+',height='+parseInt(2*$(window).height()/3);            
            //var wnd = window.open(url, 'new_window', w + ',top=50,left=50,scrollbars=yes,resizable=yes');
			
            var wnd = window.open(url, '_blank');
            if (!wnd) {
                return dfrd.reject('errPopup');
            }
        }

    }
    
    //only enable lanuch when only 1 item select and that item is a directory 
    this.getstate = function(sel) {
        var fm   = this.fm,
        sel = this.files(sel),
        cnt = sel.length;
        //return 0 to enable, -1 to disable icon access
        return cnt == 1 ? sel[0].mime == 'directory' ? 0 : -1 : -1;

    }
    
}