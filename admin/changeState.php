<?php
include_once(__DIR__.'/../include/config.php');
include_once('../include/HTML_TopBottom.php');
$db = connect_db();

$minUserLevel = 3;
$cfgProgDir = '../auth/';
include($cfgProgDir. "secure.php");

if(!isset($_REQUEST['state']) OR !isset($_REQUEST['id'])) {
	$deel_1 = "Onvoldoende gegevens bekend";
} else {
	$details = getFundaData($_REQUEST['id']);
	
	if($_REQUEST['state'] == 'details') {
		mark4Details($_REQUEST['id']);
		$deel_1 = "<i>". $details['adres'] ."</i> is in de wachtrij gezet voor vernieuwen van de details";
	} else {	
		if($_REQUEST['state'] == 'verkocht')	$sql = "UPDATE $TableHuizen SET $HuizenVerkocht = '1' WHERE $HuizenID = ".$_REQUEST['id'];
		if($_REQUEST['state'] == 'offline')		$sql = "UPDATE $TableHuizen SET $HuizenOffline = '1' WHERE $HuizenID = ".$_REQUEST['id'];
		if($_REQUEST['state'] == 'ignore')		$sql = "INSERT INTO $TableIgnore ($IgnoreID) VALUES (". $_REQUEST['id'] .")";
		if($_REQUEST['state'] == 'available')		$sql = "UPDATE $TableHuizen SET HuizenEind = '". time() ."' WHERE $HuizenID = ".$_REQUEST['id'];
				
		if(mysqli_query($db, $sql)) {
			$deel_1 = "Status van <i>". $details['adres'] ."</i> aangepast naar ". $_REQUEST['state'];
			if($_REQUEST['state'] == 'verkocht')	mark4Details($_REQUEST['id']);
			if($_REQUEST['state'] == 'offline')	remove4Details($_REQUEST['id']);
		}	else {
			$deel_1 = "Status van <i>". $details['adres'] ."</i> kon niet worden aangepast naar ". $_REQUEST['state'];
		}		
	}
}

echo $HTMLHeader;
echo "<tr>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo showBlock($deel_1);
echo "</td>\n";
echo "<td width='50%' valign='top' align='center'>\n";
echo "&nbsp;";
echo "</td>\n";
echo "</tr>\n";
echo $HTMLFooter;
