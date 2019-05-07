<?php
global $WZDE;
?>

<!-- main html file for WZDE (php version) -->
<body>

<div id="titleBar" class="title_area" style="display:none;">
    <table class="title_area"><tr>
    <td class="title"><p class="title">WZDE STATUS</p></td>
    <td class="subtitle"><p class="subtitle">
        Web Zone Development Environment (WZDE)<br />
        Advanced Technology Web
    </p></td>
    </tr></table>
</div>

<div class="small-12 columns" id="divBody" >

    <div class="small-12 columns flex-container">
        <div class="small-12 large-6 columns status-panel">
            <h2 class="status-title">WZDE Repository Status</h2>

            <div id="repo_status_up" class="callout success" style="display:none;">
              <p>Repository is Up.<span class="fa fa-check-circle-o fa-lg fa-fw"></span></p>
            </div>
            
            <div id="repo_status_down" class="callout alert" style="display:none;">
              <p>Repository is down.<span class="fa fa-times-circle-o fa-lg fa-fw"></span></p>
            </div>            

            <table id="repo_table" class="display">                
                <thead>
                    <tr style="display:table-row">
                        <th>Revision</th>
                        <th>User</th>
                        <th>Last</th>
                        <th>Zone</th>
                    </tr>
                </thead> 
                <tbody style="display: table-row-group;" align="center"></tbody>                
            </table>            
        </div>

        <div class="small-12 large-6 columns status-panel">
            <h2 class="status-title">WZDE Worker Job</h2>

            <div id="job_status_up" class="callout success" style="display:none;">
              <p>Jobs are running.<span class="fa fa-check-circle-o fa-lg fa-fw"></span></p>
            </div>
            
            <div id="job_status_down" class="callout alert" style="display:none;">
              <p>Jobs stopped.<span class="fa fa-times-circle-o fa-lg fa-fw"></span></p>
            </div>            

            <table id="job_table" class="display">
                <thead>
                    <tr style="display:table-row">
                        <th>User</th>
                        <th>PID</th>
                        <th>CPU</th>
                        <th>Start</th>
                        <th>Job</th>
                    </tr>
                </thead>            
                <tbody style="display: table-row-group;" align="center"></tbody>
            </table>            
        </div>
    </div>
	
	<!-- Commit Status -->
	<div class="small-12 columns">
		<div class="panel2">
		<h2 class="status-title">Commit Status</h2>

		<table cellpadding="0" cellspacing="2" border="0" class="display bottom-row-padding" id="jenkinsLastStatus">
			<tr><td><b>Last Job:</b></td><td id="lastBuildJob"></td></tr>
			<tr><td><b>Last Build Time:</b></td><td id="lastBuildTime"></td></tr>
			<tr><td><b>Last Build User:</b></td><td id="lastBuildUser"></td></tr>
			<tr><td><b>Last Build Status:</b></td><td id="lastBuildStatus"></td></tr>
		</table>

		<table cellpadding="0" cellspacing="0" border="0" class="display" id="jenkinsJobStatus">
			<thead>
				<tr>
					<th>Jobs</th>
					<th>Time</th>
					<th>Status</th>
					<th>Duration</th>					
					<th>User</th>
					<th>Message</th>					
				</tr>
			</thead>
			<tbody id="jenkinsJobStatusBody"></tbody>
		</table>  
		</div>
	</div>	

	<!-- Publish Status -->
	<div class="small-12 columns">
		<div class="panel2">
		<h2 class="status-title">Publish Status</h2>

		<table cellpadding="0" cellspacing="2" border="0" class="display bottom-row-padding" id="wzdeStatus">
			<tr><td><b>Queue Size:</b></td><td><?php echo $WZDE['Q_SIZE']?></td></tr>
			<tr><td><b>Last Queued:</b></td><td><?php echo date("Y-m-d H:i:s", substr($WZDE['LAST_Q'], 0, 10))?></td></tr>
			<tr><td><b>Last Processed:</b></td><td><?php echo date("Y-m-d H:i:s", substr($WZDE['LAST_P'], 0, 10))?></td></tr>
		</table>

		<table cellpadding="0" cellspacing="0" border="0" class="display" id="wzdePublishStatus">
			<thead>
				<tr>
					<th>Time</th>
					<th>Server</th>
					<th>Action</th>
					<th>Status</th>
					<th>File</th>
				</tr>
			</thead>
			<tbody>
				<?php echo $WZDE["LAST_PUBLISHED"]?>
			</tbody>
		</table>  
		</div>
	</div>
    
</div>









