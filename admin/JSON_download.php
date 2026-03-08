<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 1;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

$batch			= 20;
$filename		= 'houses_'.date('Y.m.d');
$dir			= '../json/out';

$sql = "SELECT $HuizenID, $HuizenListing FROM $TableHuizen WHERE ($HuizenDetails = '1' OR $HuizenDetails = '2') AND $HuizenOffline = '0' ORDER BY $HuizenEind ASC";
$result	= mysqli_query($db, $sql);	
$total = mysqli_num_rows($result);

$counter = $fileCounter = $allCounter = 1;

$result	= mysqli_query($db, $sql);	
if($row = mysqli_fetch_array($result)) {
	$ids = array();
	do {
		$ids[] = ($row[$HuizenListing] > 0 ? $row[$HuizenListing] : $row[$HuizenID]);		
		$counter++;
		$allCounter++;
		if($counter > $batch || $allCounter > $total) {
			$myfile = fopen($dir.'/'.$filename.'_'.$fileCounter.".json", "w");
			fwrite($myfile, json_encode($ids));
			fclose($myfile);
			
			$fileCounter++;
			$counter = 1;
			$ids = array();
		}
	} while($row = mysqli_fetch_array($result));
}

?>