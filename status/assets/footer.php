<?php
global $WZDE;
$WZDE['TIME_END'] = microtime(true);
$WZDE['TIME'] = $WZDE['TIME_END'] - $WZDE['TIME_START'];
?>

    <!-- Local -->
    <script type="text/javascript" src="assets/libs/jquery-1.12.4.min.js"></script>
    <script type="text/javascript" src="assets/libs/DataTables-1.10.15/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="assets/libs/DataTables-1.10.15/ColReorder-1.3.3/js/dataTables.colReorder.min.js"></script>
    <script type="text/javascript" src="assets/libs/DataTables-1.10.15/FixedHeader-3.1.2/js/dataTables.fixedHeader.min.js"></script>
    <script type="text/javascript" src="assets/libs/DataTables-1.10.15/Responsive-2.1.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.meditech.com/assets/foundation/5.5/js/foundation.min.js"></script>
    <script type="text/javascript" src="assets/libs/xml2json.js"></script>
    <script type="text/javascript" charset="utf8" src="https://use.fontawesome.com/c1367ca9e5.js"></script>

	
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="//www.googletagmanager.com/gtag/js?id=UA-22228657-9"></script>

<!-- gtag JS -->
<script src="assets/js/ga.js"></script>
	
    <script>
        //job status jsonp callback 
        function jobStatusCallback(json){
            if ($.isEmptyObject(json)) {                            
                $('#job_status_down').show();
                $('#job_status_up').hide();
                console.log("NO DATA!");
            }
            else {
                $('#job_status_down').hide();
                $('#job_status_up').show();
                $.each(json, function(key, value){
                    $('#job_table > tbody:last-child').append('<tr>' + 
                    '<td>' + value["user"] + '</td>' +
                    '<td>' + value["pid"] + '</td>' +
                    '<td>' + value["cpu"] + '</td>' +
                    '<td>' + value["start"] + '</td>' +
                    '<td>' + value["command"] + '</td>' +
                    '</tr>'
                    );                    
                })                
            }            
        }
        
		function timeConverter(UNIX_timestamp){
			var a = new Date(UNIX_timestamp),
				//var months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
				// re-align month because it start from 0 to 11
				months = [1,2,3,4,5,6,7,8,9,10,11,12];
				year = a.getFullYear(),
				month = months[a.getMonth()],
				date = a.getDate(),
				hour = a.getHours(),
				min = a.getMinutes(),
				sec = a.getSeconds(),
				time = year + '-' + month + '-' + date + ' ' + hour + ':' + min + ':' + sec ;
			return time;
		}		
		
		function msToHms(d) {
			d = Math.floor(Number(d) / 1000);
			var h = Math.floor(d / 3600);
			var m = Math.floor(d % 3600 / 60);
			var s = Math.floor(d % 3600 % 60);

			var hDisplay = h > 0 ? h + (h == 1 ? " hour, " : " hours, ") : "";
			var mDisplay = m > 0 ? m + (m == 1 ? " minute, " : " minutes, ") : "";
			var sDisplay = s > 0 ? s + (s == 1 ? " second" : " seconds") : "";
			return hDisplay + mDisplay + sDisplay; 
		}		
		
        $(document).ready( function () {
			
			//if not in iframe/dialog show title bar
			if (!window.frameElement) {
				$('#titleBar').show();
			}

            $.ajaxSetup({
                cache : false
            });
            
			var wzdeJenkinsJob = ["WZDE_atsignage_v2","WZDE_cdn_v2","WZDE_cts_v2","WZDE_customer_v2","WZDE_home_v2","WZDE_logi_v2","WZDE_staff_v2"],
				jenkinsURL = "http://wpltools.meditech.com:8080/job/",
				staffLookupURL = "https://staff.meditech.com/staff/staff.php?ename=";
					
			
            var repo_status = $.getJSON( "./repo_status.php",
                    function(json){
                        if ($.isEmptyObject(json)) {                            
                            $('#repo_status_down').show();
                            $('#repo_status_up').hide();
                            console.log("NO DATA!");
                        }
                        else {
                            $('#repo_status_down').hide();
                            $('#repo_status_up').show();
                            $.each(json["list"]["entry"], function(key, value){
                                var last= new Date(value["commit"]["date"]);                                
                                $('#repo_table > tbody:last-child').append('<tr>' + 
                                '<td>' + value["commit"]["@attributes"]["revision"] + '</td>' +
                                '<td>' + value["commit"]["author"] + '</td>' +
                                '<td>' + last.getFullYear() + "-" + (last.getMonth()+1) + "-" + last.getDate() + '</td>' +
                                '<td>' + value["name"] + '</td>' +
                                '</tr>'
                                );                                
                            })                            
                        }
                    });
            //CORS call to get WZDE work job status using jsonp
            var job_status = $.ajax({
                                url: "https://staffappslx.meditech.com/wluo/php_Q/worker_status_jsonp.php",
                                dataType: "jsonp"
                            });
			var commit_status = $.getJSON( "assets/scripts/getJenkinsJobs.php",
					function(json){
						if ($.isEmptyObject(json)) {                            
							console.log("NO DATA!");
						}
						else {
							var allJobs = json["jobs"],
								allWzdeJobs = allJobs.filter(function( job ) {
									return job["lastBuild"] != null && wzdeJenkinsJob.indexOf(job["name"]) > 0
								});						
								/* nonSuccessfulJobs = allWzdeJobs.filter(function( job ) {
									return job["lastBuild"] != null && job["lastBuild"]["result"] != "SUCCESS";
								}),
								successfulJobs = allWzdeJobs.filter(function( job ) {
									return job["lastBuild"] != null && job["lastBuild"]["result"] == "SUCCESS";
								}); */
								
							//console.log('allWzdeJobs', allWzdeJobs);							
							
							var lastJobTime = 0, lastUser = "", lastJob = "", lastJobStatus = "", jenkinsJobStatusBody = "" ;							
							$.each(allWzdeJobs, function( index, value ) {
								// find the max timestamp (for last job)
								if (value.lastBuild["timestamp"] > lastJobTime) {
									lastJobTime = value.lastBuild["timestamp"];										
									lastJobStatus = (value.lastBuild["result"] != null ? value.lastBuild["result"] : "In Progress...");
									lastJobName = value.name;
									lastJobNumber = value.lastBuild["number"];
									// only SUCCESS build has author
									if (lastJobStatus == "SUCCESS") {
										lastUser = value.lastBuild["changeSet"]["items"][0]["author"]["fullName"];										
									}
									else {
										lastUser = "";
									}
								}
								// set job table data
								jenkinsJobStatusBody += "<tr>";
								jenkinsJobStatusBody += "<td><a href=" + jenkinsURL + value.name + "/" + value.lastBuild["number"] + "/console>" + value.name + " - " + value.lastBuild["number"] +"</a></td>";
								jenkinsJobStatusBody += "<td>" + timeConverter(value.lastBuild["timestamp"]) + "</td>";
								jenkinsJobStatusBody += "<td>" + (value.lastBuild["result"] != null ? value.lastBuild["result"] : "In Progress...") + "</td>";
								jenkinsJobStatusBody += "<td>" + msToHms(value.lastBuild["duration"]) + "</td>";
								// only SUCCESS build has author and commit message
								if (value.lastBuild["result"] == "SUCCESS") {
									var ename = value.lastBuild["changeSet"]["items"][0]["author"]["fullName"];
									jenkinsJobStatusBody += "<td><a href=" + staffLookupURL + ename + " target=_blank rel='noopener noreferrer'>" + ename + "</a></td>";
									jenkinsJobStatusBody += "<td>" + value.lastBuild["changeSet"]["items"][0]["msg"] + "</td>";
								}
								else {
									jenkinsJobStatusBody += "<td></td>";
									jenkinsJobStatusBody += "<td></td>";
								}
								jenkinsJobStatusBody += "</tr>";
								
							});							
							// set last jobs info
							$('#lastBuildJob').html("<a href=" + jenkinsURL + lastJobName + "/" + lastJobNumber + "/console>" + lastJobName + " - " + lastJobNumber +"</a>");
							$('#lastBuildTime').html(timeConverter(lastJobTime));
							$('#lastBuildUser').html("<a href=" + staffLookupURL + lastUser + " target=_blank rel='noopener noreferrer'>" + lastUser + "</a>");
							$('#lastBuildStatus').html(lastJobStatus);							
							// set jobs table
							$('#jenkinsJobStatusBody').html(jenkinsJobStatusBody);
						}
					});					
					
            $('#wzdePublishStatus').DataTable({       
                "scrollY":        false, //ATWEB-7951 kdoricent
                "scrollX":        true, //ATWEB-7951  kdoricent
                "processing": true,
                "searching": false,
                "pagingType": "simple_numbers",
                "order": [[0, "desc"]],
                "autoWidth": false,
                "dt-responsive": true,
                "lengthChange": false,
                "info": true,
                //defer creating row until needed
                "deferRender": true,
                "orderClasses": false
            });
			
			$('#jenkinsJobStatus').DataTable({       
				"scrollY":        false, //ATWEB-7951 kdoricent
				"scrollX":        true, //ATWEB-7951  kdoricent
				"processing": true,
				"searching": false,
				"pagingType": "simple_numbers",
				"bPaginate": false,
				"order": [[0, "desc"]],
				"autoWidth": false,
				"dt-responsive": true,
				"lengthChange": false,
				"info": false,
				//defer creating row until needed
				"deferRender": true,
				"orderClasses": false
			});			
			
        });        
    </script>
    
    <div class="small-12 columns">
    <div class="footer">
        <p class="copyright">Version <?php echo VERSION ?><br></p>
        <p class="copyright">Page rendered in <strong><?php echo $WZDE['TIME']?></strong> seconds</p>
    </div>
</div>
</body></html>
