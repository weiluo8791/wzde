//By Wei Qi Luo 5/2013
//This program show the status board information in the WU banner

$(document).ready(function() {
//needed for IE because IE (all versions) treat Ajax call just as other web request.
$.ajaxSetup({ cache: false });

//query status
statusByGroup();
});

function statusByGroup() {
//serialize the array
var groups=['3489','1089','1090','2948'].join(',');
var id="";

var jqxhr = $.getJSON( "js/getStatus_JSON.php",{ groups: groups})
		.done(function( json_data ) {
			$.each(json_data, function(key, value){                
                switch (value["NodeID"])
				{
					case '3489' :
						id="Staff_Status";
						break;
					case '1089' :
						id="Home_Status";
						break;                          
					case '1090' :
						id="Customer_Status";
						break;
					case '2948' :
						id="WZDE_Status";
						break;
					default :
						id="xxxx";
						break;
				}
				//console.log("p#"+id);
				switch(value["Status"].trim())
				{
					case "0" :
						$("#"+id).addClass('SystemStatusGrey').removeClass('SystemStatusDefault');
						break;
					case "1" :
						$("#"+id).addClass('SystemStatusGreen').removeClass('SystemStatusDefault');
						break;
					case "2" :
						$("#"+id).addClass('SystemStatusRed').removeClass('SystemStatusDefault');
						break;
					case "3" :
						$("#"+id).addClass('SystemStatusYellow').removeClass('SystemStatusDefault');
						break;
					case "14" :
						$("#"+id).addClass('SystemStatusRed').removeClass('SystemStatusDefault');
						break;
					default : 
						$("#"+id).addClass('SystemStatusBlack').removeClass('SystemStatusDefault');
						break;
				}
			});
		})
		.always(function(){
			//query every 300 seconds (5min)
			setTimeout(statusByGroup, 300000);
		})
		.fail(function( jqxhr, textStatus, error ) {
			var err = textStatus + ', ' + error;
			console.log( "Request Failed: " + err);
		});
};	
