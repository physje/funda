<?php
include_once(__DIR__.'/include/config.php');
include_once(__DIR__.'/include/HTML_TopBottom.php');

$db = connect_db();

$run = false;
$rowWidth = 215;
if(isset($_REQUEST['id'])) {
	$vorigID = $_REQUEST['id'];
}

if(isset($_REQUEST['offline'])) {
    $sql = "UPDATE $TableHuizen SET $HuizenOffline = '1' WHERE $HuizenID = $vorigID";
    mysqli_query($db, $sql);
}

if(isset($_REQUEST['i'])) {
    $i = $_REQUEST['i'];
} else {
    $i = 0;
}

$halfJaar = mktime(0,0,0,(date("n")-8), date("j"), date("Y"));

$sql = "SELECT * FROM $TableHuizen WHERE $HuizenVerkocht = '1' AND $HuizenOffline = '0' AND $HuizenEind < ". $halfJaar ." ORDER BY $HuizenEind ASC LIMIT $i,2";
$result = mysqli_query($db, $sql);	

if($row = mysqli_fetch_array($result)) {
	$fundaID = $row[$HuizenID];
	
	echo $HTMLHeader;
  echo "<body onload=\"window.open('http://funda.nl/$fundaID', 'funda');\">\n";
  echo "<table border=0>\n";
  echo "<tr>\n";
      
  # Vorige huis
  if($i > 0) {
  	$data				= getFundaData($vorigID);
  	  	
  	echo "	<td width='$rowWidth'>\n";
  	echo "	<h1>Vorige</h1><br>\n";
  	echo formatStreetAndNumber($vorigID) ."<br>\n";
  	echo "	Laatst gezien :". date('d-m-Y', $data['eind']) ."<br>\n";
  	
  	if($data['offline'] == 1) {
  		echo "<a href='?id=$vorigID&i=$i&online' class='$class'>Online</a>\n";
  	} else {
  		echo "<a href='?id=$vorigID&i=". ($i-1) ."&offline' class='$class'>Offline</a>\n";
  	}
  	
  	echo "	</td>\n";  	
  } else {
  	echo "	<td width='$rowWidth'>&nbsp;</td>\n";
  }
  
  do {
  	$fundaID = $row[$HuizenID];
  	# Huidige huis
  	echo "	<td>&nbsp;</td>\n";  	
  	echo "	<td width='$rowWidth'>\n";
  	if($run) {
  		echo "<h1>Volgende</h1><br>\n";
  	} else {
  		echo "<h1>$fundaID</h1><br>\n";
  	}
  	echo urldecode($row[$HuizenAdres]) ."<br>\n";
	  echo "	Laatst gezien :". date('d-m-Y', $row[$HuizenEind]) ."<br>\n";
	  echo "	<a href='?id=$fundaID&i=$i&offline'>Offline</a> | <a href='?id=$fundaID&i=". ($i+1). "&online'>Online</a>\n";
	  echo "	</td>\n";
	  $run = true;
  } while($row = mysqli_fetch_array($result));
      
  echo "</tr>\n";
  echo "</table>\n";    
  echo "</body>\n";
    
  echo $HTMLFooter;
}
