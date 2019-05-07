"use strict"

elFinder.prototype.commands.queue = function() {
    this.exec = function(hashes) {       
        var fm    = this.fm, 
        dfrd  = $.Deferred().fail(function(error) { error && fm.error(error); }),
        files   = this.files(hashes),
        cnt   = files.length,
        file;
       
        if (!cnt) {
            return dfrd.reject();
        }
        
        //if any file
        if (cnt > 0) {
            var i,j;
            for (i=0,j=cnt;i<j;i++) {
                file = files[i];
                
                //add directory, need to find all the file in mariaDB
                if (file.mime == 'directory') {                    
                    var query = "select path,filename,type,'add'as ops from webutility.wzde_files where path like :dir";
                    var dir = fm.escape(fm.path(file.hash, true)) + '%',
                        dir = dir.replace(/\\/g,"/");
                    var bindV = {"dir" : dir};
                    var queryDataJson = $.parseJSON(
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
                            url : "http://lamp/wluo/php_Q/add_to_queue.php",
                            data : {
                                filelist : JSON.stringify(temparray),
                                //filelist : queryDataJson,
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
                
                //add single file
                else {
                    var query = "select path,filename,type,'add'as ops from webutility.wzde_files where hash = :hash";
                    var hash = file.hash;
                    var bindV = {"hash" : hash};
                    //get single file path
                    var queryDataJson = $.parseJSON(
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
                    //queue single file      
                    $.ajax({
                            type: 'POST',
                            crossDomain: true,
                            url : "http://lamp/wluo/php_Q/add_to_queue.php",
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
            }
        }
    }
    this.getstate = function() {
        //return 0 to enable, -1 to disable icon access
        return 0;
    }
}