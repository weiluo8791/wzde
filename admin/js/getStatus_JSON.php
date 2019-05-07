<?php
//get the POST value
$groups=$_GET['groups'];
//unserialize back to array
$groups = explode(',', $groups);

//count "?" for the IN condition
$qMarks = str_repeat('?,', count($groups) - 1) . '?';

try {
   $mssql = new PDO( "sqlsrv:server=MTSQLCLUSTER.meditech.com;Database=SolarWindsOrion","solarwindsreader","a&=4,v2)#9|)b2N"); 
   $mssql->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

   $maria = new PDO('mysql:host=atdmariadblive;dbname=SolarWindsOrion', "web","meditech");
}
catch( PDOException $e ) {
   die( "Error connecting to SQL Servers"); 
}

$mssqlQuery = 	"
				Select 
					[Nodes].NodeID,[Nodes].Status
				from 
					[SolarWindsOrion].[dbo].[Nodes] 
				WITH 
					(NOLOCK)
				WHERE 
					[Nodes].NodeID IN ($qMarks)
				";		
            

echo json_encode(fetch($mssql,$mssqlQuery,$groups));

function fetch($handler,$query,$args){
	$stmt = $handler->prepare($query);
	//Execute withe bind value in array
	$stmt->execute($args);

	$ret = array();
	foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $obj){
		$ret[$obj['NodeID']] = $obj;
	} 
	return $ret;
}

?>
